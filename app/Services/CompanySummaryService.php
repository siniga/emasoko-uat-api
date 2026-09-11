<?php

namespace App\Services;

use App\Models\DebitOrder;
use App\Models\Installment;
use App\Models\Partner;
use Illuminate\Support\Facades\Auth;

class CompanySummaryService
{
    public function __construct(private DashboardReportingQueryService $reporting)
    {
    }

    public function forPartner(Partner $partner): array
    {
        $currentMonth = (int) now()->format('m');
        $currentYear = (int) now()->format('Y');
        $today = now()->toDateString();

        $mandateBase = DebitOrder::query()
            ->where('partner_id', $partner->id)
            ->where('deleted', 0);

        $installmentBase = Installment::query()
            ->whereHas('debitOrder', function ($query) use ($partner) {
                $query->where('partner_id', $partner->id)
                    ->where('approved', 1)
                    ->where('status', 240)
                    ->where('deleted', 0);
            });

        $filters = ['partner_id' => $partner->id];
        $monthlyExpected = $this->reporting->expectedCollections(Auth::user(), $filters)
            ->where('expected_year', $currentYear)->where('expected_month', $currentMonth);
        $monthlyPaid = $this->reporting->paymentCollections(Auth::user(), $filters)
            ->where('collected_year', $currentYear)->where('collected_month', $currentMonth);
        $todayExpected = $this->reporting->expectedCollections(Auth::user(), $filters)->whereDate('payment_date', $today);
        $todayPaid = $this->reporting->paymentCollections(Auth::user(), $filters)->whereDate('effective_payment_date', $today);

        $totalMandates = $this->mandateTotals(clone $mandateBase);
        $pendingMandates = $this->mandateTotals(
            (clone $mandateBase)->where('approved', 0)
        );
        $failedMandates = $this->mandateTotals(
            (clone $mandateBase)->where('approved', 99)
        );
        $activeMandates = $this->mandateTotals(
            (clone $mandateBase)
                ->where('approved', 1)
                ->where('status', 240)
                ->where('contract_status', 'OPEN')
        );

        $overdue = (clone $installmentBase)
            ->whereHas('debitOrder', function ($query) {
                $query->where('contract_status', 'OPEN');
            })
            ->whereDate('payment_date', '<', $today)
            ->whereIn('status', ['OPEN', 'FAILED', 'FUNDSCHECK'])
            ->selectRaw('COUNT(*) as overdue_count')
            ->selectRaw('COALESCE(SUM(outstanding_amount), 0) as overdue_amount')
            ->first();

        $summary = [
            'documents' => $partner->partnerDocuments->count(),
            'accounts' => $partner->partnerAccounts->count(),
            'verified_accounts' => $partner->partnerAccounts->where('is_verified', 1)->count(),
            'representatives' => $partner->partnerStaff->count(),
            'total_mandates' => (int) $totalMandates->total_count,
            'total_mandates_amount' => (float) $totalMandates->total_amount,
            'pending_mandates' => (int) $pendingMandates->total_count,
            'pending_mandates_amount' => (float) $pendingMandates->total_amount,
            'failed_mandates' => (int) $failedMandates->total_count,
            'failed_mandates_amount' => (float) $failedMandates->total_amount,
            'active_mandates' => (int) $activeMandates->total_count,
            'active_mandates_amount' => (float) $activeMandates->total_amount,
            'current_month_label' => now()->format('F'),
            'this_month_expected' => (float) (clone $monthlyExpected)->sum('installment_amount'),
            'this_month_collected' => (float) (clone $monthlyPaid)->sum('paid_amount'),
            'today_expected' => (float) (clone $todayExpected)->sum('installment_amount'),
            'today_collected' => (float) (clone $todayPaid)->sum('paid_amount'),
            'overdue_count' => (int) $overdue->overdue_count,
            'overdue_amount' => (float) $overdue->overdue_amount,
        ];

        $summary['collection_rate'] = $summary['this_month_expected'] > 0
            ? round(($summary['this_month_collected'] / $summary['this_month_expected']) * 100, 2)
            : 0;

        return $summary;
    }

    private function mandateTotals($query)
    {
        return $query
            ->selectRaw('COUNT(*) as total_count')
            ->selectRaw('COALESCE(SUM(total_amount), 0) as total_amount')
            ->first();
    }
}
