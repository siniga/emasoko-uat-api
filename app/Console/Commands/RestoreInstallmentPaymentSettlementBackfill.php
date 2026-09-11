<?php

namespace App\Console\Commands;

use App\Models\PaymentSettlement;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Str;

class RestoreInstallmentPaymentSettlementBackfill extends Command
{
    protected $signature = 'payments:restore-installment-payment-backfill
        {run_id : Backfill run UUID printed by the backfill command}
        {--apply : Delete replacement events and restore archived snapshots}';

    protected $description = 'Restore payment settlement snapshots archived by an installment payment backfill run';

    public function handle(): int
    {
        $runId = trim((string) $this->argument('run_id'));

        if (!Str::isUuid($runId)) {
            $this->components->error('The run_id must be a valid UUID.');

            return self::FAILURE;
        }

        $archiveQuery = DB::table('payment_settlement_backfill_archives')
            ->where('run_id', $runId)
            ->whereNull('restored_at');
        $replacementQuery = PaymentSettlement::query()->where('backfill_run_id', $runId);

        $archiveCount = (clone $archiveQuery)->count();
        $archiveAmount = (clone $archiveQuery)->get(['settlement_payload'])->sum(function ($row) {
            $payload = json_decode($row->settlement_payload, true, 512, JSON_THROW_ON_ERROR);

            return (float) ($payload['paid_amount'] ?? 0);
        });
        $replacementCount = (clone $replacementQuery)->count();
        $replacementAmount = (float) (clone $replacementQuery)->sum('paid_amount');

        if ($archiveCount === 0 && $replacementCount === 0) {
            $wasRestored = DB::table('payment_settlement_backfill_archives')
                ->where('run_id', $runId)
                ->whereNotNull('restored_at')
                ->exists();

            $this->components->{$wasRestored ? 'info' : 'error'}(
                $wasRestored ? 'This backfill run has already been restored.' : 'No backfill data was found for this run ID.'
            );

            return $wasRestored ? self::SUCCESS : self::FAILURE;
        }

        $this->table(
            ['Action', 'Rows', 'Amount'],
            [
                ['Replacement events to delete', number_format($replacementCount), number_format($replacementAmount, 2)],
                ['Archived snapshots to restore', number_format($archiveCount), number_format($archiveAmount, 2)],
            ]
        );

        if (!$this->option('apply')) {
            $this->components->warn('Dry run only. Pass --apply after reviewing the totals.');

            return self::SUCCESS;
        }

        DB::transaction(function () use ($runId) {
            $archives = DB::table('payment_settlement_backfill_archives')
                ->where('run_id', $runId)
                ->whereNull('restored_at')
                ->lockForUpdate()
                ->get();

            PaymentSettlement::query()
                ->where('backfill_run_id', $runId)
                ->lockForUpdate()
                ->get();

            PaymentSettlement::query()->where('backfill_run_id', $runId)->delete();

            foreach ($archives as $archive) {
                $payload = json_decode($archive->settlement_payload, true, 512, JSON_THROW_ON_ERROR);

                if (PaymentSettlement::query()->whereKey($payload['id'])->exists()) {
                    throw new \RuntimeException(
                        "Cannot restore payment settlement {$payload['id']}: that ID is already in use."
                    );
                }

                DB::table('payment_settlements')->insert($payload);
            }

            DB::table('payment_settlement_backfill_archives')
                ->where('run_id', $runId)
                ->whereNull('restored_at')
                ->update(['restored_at' => now()]);
        });

        $this->components->info('The payment settlement backfill run was restored successfully.');

        return self::SUCCESS;
    }
}
