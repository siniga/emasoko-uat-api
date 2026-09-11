<?php

namespace App\Console\Commands;

use App\Services\CollectionIntegrityService;
use Carbon\Carbon;
use Illuminate\Console\Command;

class AuditCollectionIntegrity extends Command
{
    protected $signature = 'collections:audit-integrity
        {--month= : Month to audit in YYYY-MM format}
        {--from= : Inclusive start date}
        {--to= : Inclusive end date}
        {--fail-on-issues : Return a non-zero exit code when issues are detected}';

    protected $description = 'Audit collection settlements, allocations, references, and accounting identities';

    public function handle(CollectionIntegrityService $service): int
    {
        [$start, $end] = $this->period();
        $this->info("Auditing collections from {$start->toDateString()} to {$end->toDateString()}...");

        $result = $service->audit($start, $end);

        $this->table(
            ['Check', 'Value'],
            [
                ['Audit run', $result['run_id']],
                ['Issues detected', number_format($result['detected_count'])],
                ['Issue value', number_format($result['detected_amount'], 2)],
                ['Expected', number_format($result['accounting']['expected_amount'], 2)],
                ['Schedule paid', number_format($result['accounting']['schedule_paid'], 2)],
                ['Schedule outstanding', number_format($result['accounting']['schedule_outstanding'], 2)],
                ['Accounting difference', number_format($result['accounting']['identity_difference'], 2)],
                ['Cash received', number_format($result['accounting']['cash_received'], 2)],
            ]
        );

        foreach ($result['by_type'] as $type => $count) {
            $this->line("{$type}: {$count}");
        }

        if (abs($result['accounting']['identity_difference']) > 0.01) {
            $this->error('Expected = schedule paid + outstanding did not balance.');
            return self::FAILURE;
        }

        if ($this->option('fail-on-issues') && $result['detected_count'] > 0) {
            return self::FAILURE;
        }

        $this->info('Collection integrity audit completed.');
        return self::SUCCESS;
    }

    private function period(): array
    {
        if ($month = $this->option('month')) {
            $start = Carbon::createFromFormat('Y-m', $month)->startOfMonth();
            return [$start, $start->copy()->endOfMonth()];
        }

        $start = $this->option('from')
            ? Carbon::parse($this->option('from'))->startOfDay()
            : now()->startOfMonth();
        $end = $this->option('to')
            ? Carbon::parse($this->option('to'))->endOfDay()
            : now()->endOfMonth();

        return [$start, $end];
    }
}
