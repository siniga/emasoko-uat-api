<?php

namespace App\Services;

use App\Models\SystemHealthCheck;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Cache;
use Illuminate\Support\Facades\Log;

class BankConnectivityHealthService
{
    public const CACHE_KEY = 'system_health.bank_connectivity.latest';

    public function run(?array $services = null): Collection
    {
        $selectedServices = collect($services)
            ->filter()
            ->map(fn ($service) => strtoupper((string) $service))
            ->values();

        $results = collect(config('services.bank_health.targets', []))
            ->filter(function ($target) use ($selectedServices) {
                return $selectedServices->isEmpty()
                    || $selectedServices->contains(strtoupper((string) ($target['service'] ?? '')));
            })
            ->map(fn ($target, $key) => $this->probe((string) $key, $target))
            ->values();

        $retentionDays = max(1, (int) config('services.bank_health.retention_days', 30));

        SystemHealthCheck::query()
            ->where('checked_at', '<', now()->subDays($retentionDays))
            ->delete();

        Cache::forget(self::CACHE_KEY);

        return $results;
    }

    public function latest(): Collection
    {
        return Cache::remember(self::CACHE_KEY, now()->addMinutes(10), function () {
            $latestIds = SystemHealthCheck::query()
                ->selectRaw('MAX(id)')
                ->groupBy('endpoint_key');

            return SystemHealthCheck::query()
                ->whereIn('id', $latestIds)
                ->orderBy('service')
                ->orderBy('endpoint_label')
                ->get();
        });
    }

    public function history(int $limit = 100): Collection
    {
        return SystemHealthCheck::query()
            ->latest('checked_at')
            ->limit($limit)
            ->get();
    }

    public function overallStatus(Collection $checks): string
    {
        if ($checks->isEmpty() || $checks->every(fn ($check) => $check->status === SystemHealthCheck::STATUS_UNKNOWN)) {
            return SystemHealthCheck::STATUS_UNKNOWN;
        }

        if ($checks->contains(fn ($check) => $check->status === SystemHealthCheck::STATUS_DOWN)) {
            return SystemHealthCheck::STATUS_DOWN;
        }

        if ($checks->contains(fn ($check) => $check->status === SystemHealthCheck::STATUS_UNKNOWN)) {
            return 'degraded';
        }

        return SystemHealthCheck::STATUS_HEALTHY;
    }

    protected function probe(string $key, array $target): SystemHealthCheck
    {
        $service = strtoupper((string) ($target['service'] ?? 'BANK'));
        $label = (string) ($target['label'] ?? $key);
        $url = trim((string) ($target['url'] ?? ''));
        $checkedAt = now();

        if ($url === '') {
            return $this->record([
                'service' => $service,
                'endpoint_key' => $key,
                'endpoint_label' => $label,
                'status' => SystemHealthCheck::STATUS_UNKNOWN,
                'message' => 'Endpoint is not configured.',
                'checked_at' => $checkedAt,
            ]);
        }

        $parts = parse_url($url);
        $host = $parts['host'] ?? null;
        $scheme = strtolower((string) ($parts['scheme'] ?? 'https'));
        $port = (int) ($parts['port'] ?? ($scheme === 'http' ? 80 : 443));

        if (!$host || !in_array($scheme, ['http', 'https'], true)) {
            return $this->record([
                'service' => $service,
                'endpoint_key' => $key,
                'endpoint_label' => $label,
                'status' => SystemHealthCheck::STATUS_UNKNOWN,
                'port' => $port ?: null,
                'error_category' => 'configuration',
                'message' => 'Endpoint configuration is invalid.',
                'checked_at' => $checkedAt,
            ]);
        }

        $timeout = max(1, (int) config('services.bank_health.connect_timeout', 5));
        $transport = $scheme === 'https' ? 'tls' : 'tcp';
        $remote = "{$transport}://{$host}:{$port}";
        $context = stream_context_create([
            'ssl' => [
                'verify_peer' => true,
                'verify_peer_name' => true,
                'peer_name' => $host,
                'SNI_enabled' => true,
            ],
        ]);

        $errorNumber = 0;
        $errorMessage = '';
        $startedAt = hrtime(true);
        $socket = @stream_socket_client(
            $remote,
            $errorNumber,
            $errorMessage,
            $timeout,
            STREAM_CLIENT_CONNECT,
            $context
        );
        $responseTime = (int) round((hrtime(true) - $startedAt) / 1_000_000);

        if (is_resource($socket)) {
            fclose($socket);

            return $this->record([
                'service' => $service,
                'endpoint_key' => $key,
                'endpoint_label' => $label,
                'status' => SystemHealthCheck::STATUS_HEALTHY,
                'response_time_ms' => $responseTime,
                'port' => $port,
                'message' => strtoupper($transport) . ' connection established.',
                'checked_at' => $checkedAt,
            ]);
        }

        $category = $this->errorCategory($errorNumber, $errorMessage);

        Log::warning('Bank connectivity health check failed.', [
            'service' => $service,
            'endpoint_key' => $key,
            'error_category' => $category,
            'error_number' => $errorNumber,
            'response_time_ms' => $responseTime,
        ]);

        return $this->record([
            'service' => $service,
            'endpoint_key' => $key,
            'endpoint_label' => $label,
            'status' => SystemHealthCheck::STATUS_DOWN,
            'response_time_ms' => $responseTime,
            'port' => $port,
            'error_category' => $category,
            'message' => $this->safeFailureMessage($category),
            'checked_at' => $checkedAt,
        ]);
    }

    protected function record(array $attributes): SystemHealthCheck
    {
        return SystemHealthCheck::create(array_merge([
            'check_type' => 'tcp_tls',
            'response_time_ms' => null,
            'port' => null,
            'error_category' => null,
            'message' => null,
        ], $attributes));
    }

    protected function errorCategory(int $number, string $message): string
    {
        $message = strtolower($message);

        return match (true) {
            str_contains($message, 'timed out') => 'timeout',
            str_contains($message, 'getaddrinfo'),
            str_contains($message, 'name or service'),
            str_contains($message, 'nodename nor servname') => 'dns',
            str_contains($message, 'certificate'),
            str_contains($message, 'ssl'),
            str_contains($message, 'tls') => 'tls',
            str_contains($message, 'refused') => 'refused',
            str_contains($message, 'no route'),
            str_contains($message, 'network is unreachable') => 'network',
            $number !== 0 => 'connection',
            default => 'unknown',
        };
    }

    protected function safeFailureMessage(string $category): string
    {
        return match ($category) {
            'timeout' => 'Connection timed out. The VPN tunnel or bank gateway may be unavailable.',
            'dns' => 'The bank gateway hostname could not be resolved.',
            'tls' => 'The gateway was reached but the secure connection failed.',
            'refused' => 'The gateway refused the connection.',
            'network' => 'No network route to the bank gateway was found.',
            default => 'The bank gateway could not be reached.',
        };
    }
}
