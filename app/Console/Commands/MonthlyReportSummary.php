<?php

namespace App\Console\Commands;

use App\Jobs\SendEmailJob;
use App\Models\DebitOrder;
use App\Models\Installment;
use App\Models\PaymentSettlement;
use App\Models\Partner;
use Carbon\Carbon;
use Illuminate\Console\Command;
use Illuminate\Support\Facades\Log;
use Throwable;

class MonthlyReportSummary extends Command
{
    private const MANDATE_SUMMARIES = [
        DebitOrder::SUMMARY_PENDING,
        DebitOrder::SUMMARY_APPROVED,
        DebitOrder::SUMMARY_FAILED,
    ];

    protected $signature = 'send:monthly-report-summary
                            {--month= : Report month in YYYY-MM format (defaults to the current month)}';

    protected $description = 'Queue monthly report summaries for configured partner approvers';

    public function handle(): int
    {
        $reportMonth = $this->reportMonth();

        if (!$reportMonth) {
            return self::FAILURE;
        }

        $periodStart = $reportMonth->copy()->startOfMonth();
        $periodEnd = $periodStart->copy()->addMonth();
        $partners = $this->reportPartners();

        if ($partners->isEmpty()) {
            $this->components->warn('No eligible monthly report partners were found.');

            Log::warning('Monthly report summaries were not queued because no partners were found.', [
                'report_month' => $reportMonth->format('Y-m'),
                'partner_ids' => config('services.monthly_report.partner_ids', []),
            ]);

            return self::SUCCESS;
        }

        $formattedMonth = $reportMonth->format('F Y');
        $subject = "Monthly Summary Report: {$formattedMonth}";
        $fromAddress = config('mail.from.address');
        $queued = 0;
        $skippedPartners = [];

        foreach ($partners as $partner) {
            $recipient = $partner->partnerStaff
                ->first(fn ($staff) => filled($staff->email));

            if (!$recipient) {
                $skippedPartners[] = $partner->id;
                continue;
            }

            $mandates = $this->mandateSummary($partner->id, $periodStart, $periodEnd);
            $collections = $this->collectionSummary($partner->id, $periodStart, $periodEnd);

            SendEmailJob::dispatch([
                'to_email' => $recipient->email,
                'from_email' => $fromAddress,
                'name' => $recipient->name,
                'subject' => $subject,
                'body' => $this->buildReportBody($formattedMonth, $mandates, $collections),
            ]);

            $queued++;

            Log::info('Monthly report summary queued for partner.', [
                'report_month' => $reportMonth->format('Y-m'),
                'partner_id' => $partner->id,
                'recipient_id' => $recipient->id,
                'mandates' => $mandates,
                'collections' => $collections,
            ]);
        }

        if ($skippedPartners) {
            $this->components->warn(
                'Skipped partner(s) without an email recipient: ' . implode(', ', $skippedPartners)
            );
        }

        $this->components->info(
            sprintf(
                'Queued %d monthly %s for %s.',
                $queued,
                $queued === 1 ? 'summary' : 'summaries',
                $reportMonth->format('Y-m')
            )
        );

        return self::SUCCESS;
    }

    private function reportMonth(): ?Carbon
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

    private function reportPartners()
    {
        $partnerIds = collect(config('services.monthly_report.partner_ids', []))
            ->map(fn ($id) => (int) $id)
            ->filter()
            ->unique()
            ->values();

        return Partner::query()
            ->select(['id'])
            ->with(['partnerStaff:id,partner_id,name,email'])
            ->where('status', 202)
            ->whereIn('id', $partnerIds)
            ->get();
    }

    private function mandateSummary(int $partnerId, Carbon $periodStart, Carbon $periodEnd): array
    {
        $lifecycleRows = DebitOrder::query()
            ->selectRaw('lifecycle_status, COUNT(*) AS mandate_count, COALESCE(SUM(total_amount), 0) AS total_amount')
            ->where('partner_id', $partnerId)
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

    private function collectionSummary(int $partnerId, Carbon $periodStart, Carbon $periodEnd): array
    {
        $eligibleInstallments = fn () => Installment::query()
            ->whereHas('debitOrder', function ($query) use ($partnerId) {
                $query->where('partner_id', $partnerId)
                    ->whereIn(
                        'lifecycle_status',
                        DebitOrder::lifecycleStatusesForSummary(DebitOrder::SUMMARY_APPROVED)
                    );
            });

        $expected = $eligibleInstallments()
            ->where('payment_date', '>=', $periodStart)
            ->where('payment_date', '<', $periodEnd)
            ->sum('installment_amount');

        $actual = PaymentSettlement::query()
            ->reportable()
            ->whereHas('debitOrder', fn ($query) => $query->where('partner_id', $partnerId))
            ->where('effective_payment_date', '>=', $periodStart)
            ->where('effective_payment_date', '<', $periodEnd)
            ->sum('paid_amount');

        return [
            'expected' => (float) $expected,
            'actual' => (float) $actual,
        ];
    }

    private function buildReportBody(string $formattedMonth, array $mandates, array $collections): string
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
            '<h4>Monthly Summary Report: %s</h4><table>%s</table>',
            $formattedMonth,
            $tableRows
        );
    }
}
