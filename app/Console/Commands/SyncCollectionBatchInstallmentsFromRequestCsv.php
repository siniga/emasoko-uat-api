<?php

namespace App\Console\Commands;

use App\Models\CollectionBatch;
use App\Models\Installment;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\DB;

class SyncCollectionBatchInstallmentsFromRequestCsv extends Command
{
    private const DEFAULT_REMARK = 'Successfully Created Collection Request';

    protected $signature = 'collections:sync-installments-from-request-csv
        {--date=2026-07-01 : Batch created_at date in YYYY-MM-DD or DD-MM-YYYY format}
        {--lifecycle=recreated : Collection batch lifecycle_status to match}
        {--remarks= : Collection batch remarks to match}
        {--dry-run : Show what would be updated without saving changes}';

    protected $description = 'Backfill collection batch installment_ids and number_of_collections from SERIAL No. values in request_csv';

    public function handle(): int
    {
        $date = $this->parseDate((string) $this->option('date'));

        if (!$date) {
            $this->error('Invalid --date. Use YYYY-MM-DD or DD-MM-YYYY.');

            return self::FAILURE;
        }

        $lifecycle = (string) $this->option('lifecycle');
        $remarks = (string) ($this->option('remarks') ?: self::DEFAULT_REMARK);
        $dryRun = (bool) $this->option('dry-run');

        $query = CollectionBatch::query()
            ->whereDate('created_at', $date->toDateString())
            ->where('lifecycle_status', $lifecycle)
            ->where('remarks', $remarks)
            ->orderBy('created_at')
            ->orderBy('collectionReference');

        $total = (clone $query)->count();

        if ($total === 0) {
            $this->warn('No collection batches matched the provided filters.');
            $this->line("Date: {$date->toDateString()}, lifecycle_status: {$lifecycle}, remarks: {$remarks}");

            return self::SUCCESS;
        }

        $this->info(($dryRun ? 'Dry run: ' : '') . "Processing {$total} collection batch(es).");

        $stats = [
            'matched_batches' => $total,
            'updated_batches' => 0,
            'empty_csv' => 0,
            'empty_serials' => 0,
            'missing_installments' => 0,
        ];

        $query->chunk(100, function ($batches) use (&$stats, $dryRun) {
            foreach ($batches as $batch) {
                $serials = $this->serialsFromRequestCsv((string) $batch->request_csv);

                if (trim((string) $batch->request_csv) === '') {
                    $stats['empty_csv']++;
                    $this->warn($this->batchLabel($batch) . ': request_csv is empty.');
                    continue;
                }

                if ($serials->isEmpty()) {
                    $stats['empty_serials']++;
                    $this->warn($this->batchLabel($batch) . ': no SERIAL No. values found in request_csv.');
                    continue;
                }

                $missing = $this->missingInstallmentIds($serials->all());
                if (!empty($missing)) {
                    $stats['missing_installments'] += count($missing);
                    $this->warn($this->batchLabel($batch) . ': missing installment id(s): ' . implode(',', $missing));
                }

                $updates = [
                    'installment_ids' => $serials->implode(','),
                    'number_of_collections' => $serials->count(),
                    'updated_at' => now(),
                ];

                if (!$dryRun) {
                    DB::table('collection_batches')
                        ->where('id', $batch->id)
                        ->update($updates);
                }

                $stats['updated_batches']++;
                $this->line($this->batchLabel($batch) . ': ' . $serials->count() . ' collection(s) ' . ($dryRun ? 'would be synced.' : 'synced.'));
            }
        });

        $this->table(
            ['Metric', 'Count'],
            collect($stats)->map(fn ($count, $metric) => [$metric, $count])->values()->all()
        );

        $this->info($dryRun ? 'Dry run complete.' : 'Sync complete.');

        return self::SUCCESS;
    }

    protected function serialsFromRequestCsv(string $csv)
    {
        return collect(preg_split('/\r\n|\r|\n/', $csv))
            ->map(fn ($line) => trim((string) $line))
            ->filter()
            ->map(fn ($line) => str_getcsv($line, ';'))
            ->map(fn ($columns) => trim((string) ($columns[0] ?? '')))
            ->reject(fn ($serial) => $serial === '' || strcasecmp($serial, 'SERIAL No.') === 0)
            ->filter(fn ($serial) => ctype_digit($serial))
            ->unique()
            ->values();
    }

    protected function missingInstallmentIds(array $ids): array
    {
        $existing = Installment::query()
            ->whereIn('id', $ids)
            ->pluck('id')
            ->map(fn ($id) => (string) $id)
            ->all();

        return array_values(array_diff($ids, $existing));
    }

    protected function parseDate(string $value): ?Carbon
    {
        $value = trim($value);

        foreach (['Y-m-d', 'd-m-Y'] as $format) {
            try {
                $date = Carbon::createFromFormat($format, $value);

                if ($date && $date->format($format) === $value) {
                    return $date->startOfDay();
                }
            } catch (\Throwable $e) {
                // Try the next accepted format.
            }
        }

        return null;
    }

    protected function batchLabel(CollectionBatch $batch): string
    {
        return 'Batch ' . ($batch->collectionReference ?: $batch->id);
    }
}
