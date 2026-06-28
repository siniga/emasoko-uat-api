<?php

namespace App\Console\Commands;

use App\Jobs\SendEmailJob;
use App\Models\DebitOrder;
use App\Models\Installment;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\Log;
use Throwable;

class DailyReportSummary extends Command
{
    private const MANDATE_SUMMARIES = [
        DebitOrder::SUMMARY_PENDING,
        DebitOrder::SUMMARY_APPROVED,
        DebitOrder::SUMMARY_FAILED,
    ];
    private const PAID_INSTALLMENT_STATUSES = ['CLOSED', 'PARTIAL_PAID'];

    protected $signature = 'send:daily-report-summary
                            {--date= : Report date in YYYY-MM-DD format (defaults to today)}';

    protected $description = 'Queue the daily report summary for eMakato managers';

    public function handle(): int
    {
        $reportDate = $this->reportDate();

        if (!$reportDate) {
            return self::FAILURE;
        }

        $periodStart = $reportDate->copy()->startOfDay();
        $periodEnd = $periodStart->copy()->addDay();
        $mandates = $this->mandateSummary($periodStart, $periodEnd);
        $collections = $this->collectionSummary($periodStart, $periodEnd);
        $recipients = $this->recipients();

        if ($recipients->isEmpty()) {
            $this->components->warn('No daily report recipients were found.');

            Log::warning('Daily report summary was not queued because no recipients were found.', [
                'report_date' => $reportDate->toDateString(),
                'recipient_ids' => config('services.daily_report.recipient_ids', []),
            ]);

            return self::SUCCESS;
        }

        $formattedDate = $reportDate->format('F d, Y');
        $subject = "Daily Summary Report: {$formattedDate}";
        $body = $this->buildReportBody($formattedDate, $mandates, $collections);
        $fromAddress = config('mail.from.address');

        foreach ($recipients as $recipient) {
            SendEmailJob::dispatch([
                'to_email' => $recipient->email,
                'from_email' => $fromAddress,
                'name' => $recipient->name,
                'subject' => $subject,
                'body' => $body,
            ]);
        }

        $this->components->info(
            sprintf(
                'Queued the %s daily summary for %d %s.',
                $reportDate->toDateString(),
                $recipients->count(),
                $recipients->count() === 1 ? 'recipient' : 'recipients'
            )
        );

        Log::info('Daily report summary queued.', [
            'report_date' => $reportDate->toDateString(),
            'recipient_count' => $recipients->count(),
            'mandates' => $mandates,
            'collections' => $collections,
        ]);

        return self::SUCCESS;
    }

    private function reportDate(): ?Carbon
    {
        $value = $this->option('date');

        if (!$value) {
            return now()->startOfDay();
        }

        try {
            $date = Carbon::createFromFormat('!Y-m-d', (string) $value);
        } catch (Throwable) {
            $date = null;
        }

        if (!$date || $date->toDateString() !== $value) {
            $this->components->error('The --date option must use the YYYY-MM-DD format.');

            return null;
        }

        return $date;
    }

    private function mandateSummary(Carbon $periodStart, Carbon $periodEnd): array
    {
        $lifecycleRows = DebitOrder::query()
            ->selectRaw('lifecycle_status, COUNT(*) AS mandate_count, COALESCE(SUM(total_amount), 0) AS total_amount')
            ->whereIn('lifecycle_status', array_merge(...array_map(
                fn ($summary) => DebitOrder::lifecycleStatusesForSummary($summary),
                self::MANDATE_SUMMARIES
            )))
            ->where('created_at', '>=', $periodStart)
            ->where('created_at', '<', $periodEnd)
            ->groupBy('lifecycle_status')
            ->get();

        $summary = [];

        foreach (self::MANDATE_SUMMARIES as $summaryStatus) {
            $statuses = DebitOrder::lifecycleStatusesForSummary($summaryStatus);
            $rows = $lifecycleRows->whereIn('lifecycle_status', $statuses);
            $summary[$summaryStatus] = [
                'count' => (int) $rows->sum('mandate_count'),
                'amount' => (float) $rows->sum('total_amount'),
            ];
        }

        $summary['total'] = [
            'count' => array_sum(array_column($summary, 'count')),
            'amount' => array_sum(array_column($summary, 'amount')),
        ];

        return $summary;
    }

    private function collectionSummary(Carbon $periodStart, Carbon $periodEnd): array
    {
        $eligibleInstallments = fn () => Installment::query()
            ->whereHas('debitOrder', function ($query) {
                $query->whereIn(
                    'lifecycle_status',
                    DebitOrder::lifecycleStatusesForSummary(DebitOrder::SUMMARY_APPROVED)
                );
            });

        $expected = $eligibleInstallments()
            ->where('payment_date', '>=', $periodStart)
            ->where('payment_date', '<', $periodEnd)
            ->sum('installment_amount');

        $actual = $eligibleInstallments()
            ->whereIn('status', self::PAID_INSTALLMENT_STATUSES)
            ->where('effective_payment_date', '>=', $periodStart)
            ->where('effective_payment_date', '<', $periodEnd)
            ->sum('current_balance');

        return [
            'expected' => (float) $expected,
            'actual' => (float) $actual,
        ];
    }

    private function recipients(): Collection
    {
        $recipientIds = collect(config('services.daily_report.recipient_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        return User::query()
            ->select(['id', 'name', 'email'])
            ->whereIn('id', $recipientIds)
            ->whereNotNull('email')
            ->where('email', '!=', '')
            ->get();
    }

    private function buildReportBody(string $formattedDate, array $mandates, array $collections): string
    {
        $rows = [
            ['Registered Mandate', $mandates['total']['amount'], $mandates['total']['count']],
            ['Approved Mandate', $mandates[DebitOrder::SUMMARY_APPROVED]['amount'], $mandates[DebitOrder::SUMMARY_APPROVED]['count']],
            ['Pending Mandate', $mandates[DebitOrder::SUMMARY_PENDING]['amount'], $mandates[DebitOrder::SUMMARY_PENDING]['count']],
            ['Failed Mandate', $mandates[DebitOrder::SUMMARY_FAILED]['amount'], $mandates[DebitOrder::SUMMARY_FAILED]['count']],
        ];

        $tableRows = collect($rows)
            ->map(fn ($row) => sprintf(
                '<tr><td>%s:</td><td>%s (%s)</td></tr>',
                $row[0],
                number_format($row[1]),
                number_format($row[2])
            ))
            ->push(sprintf(
                '<tr><td>Expected Collection:</td><td>%s</td></tr>',
                number_format($collections['expected'])
            ))
            ->push(sprintf(
                '<tr><td>Actual Collection:</td><td>%s</td></tr>',
                number_format($collections['actual'])
            ))
            ->implode('');

        return sprintf(
            '<h4>Daily Summary Report: %s</h4><table>%s</table>',
            $formattedDate,
            $tableRows
        );
    }
}
