<?php

namespace App\Services;

use App\Models\AuditLog;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Event;
use Illuminate\Support\Facades\Schema;
use Illuminate\Support\Str;
use Throwable;

class AuditTrailService
{
    private const EVENTS = [
        'created',
        'updated',
        'deleted',
        'restored',
        'forceDeleted',
    ];

    private const SENSITIVE_KEYS = [
        'access_token',
        'api_key',
        'client_secret',
        'confirm_password',
        'current_password',
        'new_password',
        'otp',
        'otp_code',
        'password',
        'password_confirm',
        'pin',
        'refresh_token',
        'remember_token',
        'secret',
        'signature',
        'token',
    ];

    private bool $auditTableExists = false;
    private bool $checkedAuditTable = false;

    public function register(): void
    {
        foreach (self::EVENTS as $event) {
            Event::listen("eloquent.{$event}: *", function (string $eventName, array $payload) use ($event) {
                $model = $payload[0] ?? null;

                if ($model instanceof Model) {
                    $this->record($event, $model);
                }
            });
        }
    }

    public function record(string $event, Model $model): void
    {
        if (!$this->shouldAudit($model, $event)) {
            return;
        }

        try {
            AuditLog::withoutEvents(function () use ($event, $model) {
                AuditLog::create($this->payload($event, $model));
            });
        } catch (Throwable $exception) {
            report($exception);
        }
    }

    private function shouldAudit(Model $model, string $event): bool
    {
        if ($model instanceof AuditLog || !$this->auditTableExists()) {
            return false;
        }

        if ($event === 'updated') {
            $changed = array_diff(array_keys($model->getChanges()), ['updated_at']);

            return !empty($changed);
        }

        return true;
    }

    private function payload(string $event, Model $model): array
    {
        $request = request();
        $user = Auth::user();

        return [
            'uuid' => (string) Str::uuid(),
            'event' => $event,
            'auditable_type' => get_class($model),
            'auditable_id' => $model->getKey(),
            'user_id' => $user?->id,
            'user_name' => $user?->name,
            'user_email' => $user?->email,
            'guard' => Auth::getDefaultDriver(),
            'request_method' => $request?->method(),
            'route_name' => $request?->route()?->getName(),
            'url' => $request?->fullUrl(),
            'ip_address' => $request?->ip(),
            'user_agent' => $request?->userAgent(),
            'old_values' => $this->oldValues($event, $model),
            'new_values' => $this->newValues($event, $model),
            'metadata' => [
                'connection' => $model->getConnectionName(),
                'table' => $model->getTable(),
                'primary_key' => $model->getKeyName(),
                'console' => app()->runningInConsole(),
            ],
            'created_at' => now(),
        ];
    }

    private function oldValues(string $event, Model $model): ?array
    {
        if ($event === 'created') {
            return null;
        }

        if ($event === 'updated') {
            $original = [];

            foreach ($model->getChanges() as $key => $value) {
                if ($key === 'updated_at') {
                    continue;
                }

                $original[$key] = $model->getOriginal($key);
            }

            return $this->mask($original);
        }

        return $this->mask($model->getOriginal());
    }

    private function newValues(string $event, Model $model): ?array
    {
        if (in_array($event, ['deleted', 'forceDeleted'], true)) {
            return null;
        }

        if ($event === 'updated') {
            return $this->mask(collect($model->getChanges())
                ->except('updated_at')
                ->all());
        }

        return $this->mask($model->getAttributes());
    }

    private function mask(array $values): array
    {
        foreach ($values as $key => $value) {
            if ($this->isSensitiveKey((string) $key)) {
                $values[$key] = '[masked]';
                continue;
            }

            if (is_array($value)) {
                $values[$key] = $this->mask($value);
            }
        }

        return $values;
    }

    private function isSensitiveKey(string $key): bool
    {
        $key = strtolower($key);

        foreach (self::SENSITIVE_KEYS as $sensitiveKey) {
            if ($key === $sensitiveKey || str_contains($key, $sensitiveKey)) {
                return true;
            }
        }

        return false;
    }

    private function auditTableExists(): bool
    {
        if (!$this->checkedAuditTable) {
            try {
                $this->auditTableExists = Schema::hasTable('audit_logs');
            } catch (Throwable) {
                $this->auditTableExists = false;
            }

            $this->checkedAuditTable = true;
        }

        return $this->auditTableExists;
    }
}
