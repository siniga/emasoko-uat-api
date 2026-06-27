<?php

namespace App\Console\Commands;

use App\Models\CollectionBatch;
use App\Models\Installment;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class DailyCollectionReport extends Command
{
    protected $signature = 'daily:collection
                            {--month= : Reporting month in YYYY-MM format (defaults to the current month)}';

    protected $description = 'Display a month-to-date collection status report';

    public function handle(): int
    {
        $month = $this->reportingMonth();

        if (!$month) {
            return self::FAILURE;
        }

        $batches = CollectionBatch::query()
            ->select(['id', 'collectionReference', 'installment_ids', 'created_at'])
            ->with(['debits:id,collection_batch_id,installment_id'])
            ->where('pushed', 1)
            ->where('deleted', 0)
            ->where('created_at', '>=', $month->copy()->startOfMonth())
            ->where('created_at', '<', $month->copy()->addMonth()->startOfMonth())
            ->orderBy('collectionReference')
            ->get();

        $installmentIds = $this->installmentIdsByBatch($batches);
        $statuses = Installment::query()
            ->whereIn('id', $installmentIds->flatten()->unique()->values())
            ->pluck('status', 'id');

        $summary = $this->summarize($installmentIds, $statuses);
        $missingCount = $summary['missing'];

        $this->components->info(
            sprintf(
                'Collection report for %s (%d pushed %s)',
                $month->format('F Y'),
                $batches->count(),
                $batches->count() === 1 ? 'batch' : 'batches'
            )
        );

        $this->table(
            ['Total', 'Paid', 'Insufficient funds', 'Failed', 'Other'],
            [[
                $summary['total'],
                $summary['paid'],
                $summary['insufficient_funds'],
                $summary['failed'],
                $summary['other'],
            ]]
        );

        if ($missingCount > 0) {
            $this->components->warn(
                "{$missingCount} referenced installment(s) no longer exist and were excluded."
            );
        }

        Log::info('Daily collection report generated.', [
            'reporting_month' => $month->format('Y-m'),
            'batch_count' => $batches->count(),
            'summary' => $summary,
        ]);

        return self::SUCCESS;
    }

    private function reportingMonth(): ?Carbon
    {
        $value = $this->option('month');

        if (!$value) {
            return now()->startOfMonth();
        }

        try {
            $month = Carbon::createFromFormat('!Y-m', (string) $value);
        } catch (Throwable) {
            $month = null;
        }

        if (!$month || $month->format('Y-m') !== $value) {
            $this->components->error('The --month option must use the YYYY-MM format.');

            return null;
        }

        return $month;
    }

    private function installmentIdsByBatch(Collection $batches): Collection
    {
        return $batches->mapWithKeys(function (CollectionBatch $batch) {
            $lineIds = $batch->debits
                ->pluck('installment_id')
                ->filter()
                ->map(fn ($id) => (int) $id)
                ->values();

            if ($lineIds->isNotEmpty()) {
                return [$batch->id => $lineIds];
            }

            $legacyIds = collect(explode(',', (string) $batch->installment_ids))
                ->map(fn ($id) => trim($id))
                ->filter(fn ($id) => ctype_digit($id))
                ->map(fn ($id) => (int) $id)
                ->values();

            return [$batch->id => $legacyIds];
        });
    }

    private function summarize(Collection $installmentIds, Collection $statuses): array
    {
        $summary = [
            'total' => 0,
            'paid' => 0,
            'insufficient_funds' => 0,
            'failed' => 0,
            'other' => 0,
            'missing' => 0,
        ];

        foreach ($installmentIds->flatten() as $installmentId) {
            $status = $statuses->get($installmentId);

            if ($status === null) {
                $summary['missing']++;
                continue;
            }

            $summary['total']++;

            match ($status) {
                'CLOSED' => $summary['paid']++,
                'FUNDSCHECK' => $summary['insufficient_funds']++,
                'FAILED' => $summary['failed']++,
                default => $summary['other']++,
            };
        }

        return $summary;
    }
}
