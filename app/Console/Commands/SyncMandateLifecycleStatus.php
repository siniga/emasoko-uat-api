<?php

namespace App\Console\Commands;

use App\Models\DebitOrder;
use Illuminate\Console\Command;

class SyncMandateLifecycleStatus extends Command
{
    /**
     * The name and signature of the console command.
     *
     * @var string
     */
    protected $signature = 'mandates:sync-lifecycle-status {--force : Recalculate even when lifecycle_status already exists}';

    /**
     * The console command description.
     *
     * @var string
     */
    protected $description = 'Backfill lifecycle_status on debit orders using legacy status, flags, and contract state.';

    public function handle(): int
    {
        $force = (bool) $this->option('force');
        $query = DebitOrder::query()->orderBy('id');

        if (!$force) {
            $query->where(function ($q) {
                $q->whereNull('lifecycle_status')
                    ->orWhere('lifecycle_status', '');
            });
        }

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->info('No debit orders require lifecycle synchronization.');

            return self::SUCCESS;
        }

        $this->info("Synchronizing lifecycle_status for {$total} debit orders...");

        $updated = 0;
        $query->chunkById(200, function ($debitOrders) use (&$updated) {
            foreach ($debitOrders as $debitOrder) {
                $resolved = $debitOrder->resolved_lifecycle_status;

                if ($debitOrder->lifecycle_status !== $resolved) {
                    $debitOrder->lifecycle_status = $resolved;
                    $debitOrder->save();
                    $updated++;
                }
            }
        });

        $this->info("Lifecycle synchronization completed. Updated {$updated} debit orders.");

        return self::SUCCESS;
    }
}
