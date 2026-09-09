<?php

namespace App\Console\Commands;

use App\Services\BankConnectivityHealthService;
use Illuminate\Console\Command;

class CheckBankConnectivity extends Command
{
    protected $signature = 'system-health:check-banks
        {--service=* : Limit checks to a bank service such as NMB or CRDB}';

    protected $description = 'Check TCP/TLS connectivity to configured bank gateways without sending transactions';

    public function handle(BankConnectivityHealthService $healthService): int
    {
        $checks = $healthService->run($this->option('service'));

        if ($checks->isEmpty()) {
            $this->warn('No matching bank health targets are configured.');

            return self::FAILURE;
        }

        $this->table(
            ['Service', 'Endpoint', 'Status', 'Latency', 'Message'],
            $checks->map(fn ($check) => [
                $check->service,
                $check->endpoint_label,
                strtoupper($check->status),
                $check->response_time_ms !== null ? "{$check->response_time_ms} ms" : '-',
                $check->message,
            ])->all()
        );

        return $checks->contains(fn ($check) => $check->status === 'down')
            ? self::FAILURE
            : self::SUCCESS;
    }
}
