<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\DebitOrder;
use App\Models\Installment;
use App\Models\Partner;
use Carbon\Carbon;
use Illuminate\Database\Eloquent\Builder;
use Illuminate\Http\Request;

class BIController extends Controller
{
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $title = 'BI Dashboard';
        $channel = $request->channel;
        $today = Carbon::today();
        $currentYear = (int) $today->format('Y');
        $currentMonth = (int) $today->format('m');

        $mandates = $this->buildMandateSummary($channel);
        $thisMonth = Installment::thisMonthStats($currentMonth, $currentYear, $channel);
        $todayStats = Installment::todayStats($today->toDateString(), $channel);

        $openOperationalInstallments = $this->operationalInstallments($channel, true);
        $allOperationalInstallments = $this->operationalInstallments($channel, false);

        $overdue = (clone $openOperationalInstallments)
            ->whereDate('installments.payment_date', '<', $today->toDateString())
            ->whereIn('installments.status', ['OPEN', 'FAILED', 'FUNDSCHECK'])
            ->selectRaw('COUNT(*) as overdue_count')
            ->selectRaw('COALESCE(SUM(installments.outstanding_amount), 0) as overdue_amount')
            ->first();

        $partialPaid = (clone $allOperationalInstallments)
            ->where('installments.status', 'PARTIAL_PAID')
            ->selectRaw('COUNT(*) as partial_paid_count')
            ->selectRaw('COALESCE(SUM(installments.current_balance), 0) as partial_paid_amount')
            ->first();

        $failedToday = (clone $openOperationalInstallments)
            ->whereDate('installments.payment_date', $today->toDateString())
            ->whereIn('installments.status', ['FAILED', 'FUNDSCHECK'])
            ->selectRaw('COUNT(*) as failed_today_count')
            ->selectRaw('COALESCE(SUM(installments.installment_amount), 0) as failed_today_amount')
            ->first();

        $upcoming = (clone $openOperationalInstallments)
            ->whereBetween('installments.payment_date', [
                $today->copy()->addDay()->toDateString(),
                $today->copy()->addDays(7)->toDateString(),
            ])
            ->where('installments.status', 'OPEN')
            ->selectRaw('COUNT(*) as upcoming_count')
            ->selectRaw('COALESCE(SUM(installments.installment_amount), 0) as upcoming_amount')
            ->first();

        $recentSuccessful = (clone $allOperationalInstallments)
            ->with([
                'debitOrder.partner:id,uuid,business_name',
                'debitOrder.customerAccount:id,account_name,account_number',
            ])
            ->whereIn('installments.status', ['CLOSED', 'PARTIAL_PAID'])
            ->whereNotNull('installments.effective_payment_date')
            ->orderByDesc('installments.effective_payment_date')
            ->limit(6)
            ->get();

        $recentFailed = (clone $allOperationalInstallments)
            ->with([
                'debitOrder.partner:id,uuid,business_name',
                'debitOrder.customerAccount:id,account_name,account_number',
            ])
            ->whereIn('installments.status', ['FAILED', 'FUNDSCHECK'])
            ->orderByDesc('installments.updated_at')
            ->limit(6)
            ->get();

        $partnerAttention = $this->buildPartnerAttentionData($channel, $currentMonth, $currentYear);

        $stats = [
            'month' => $today->format('F'),
            'total_number_of_mandate' => $mandates['total_count'],
            'number_of_approved_mandate' => $mandates['approved_count'],
            'number_of_pending_mandate' => $mandates['pending_count'],
            'number_of_failed_mandate' => $mandates['failed_count'],
            'total_mandate_value' => $mandates['total_value'],
            'approved_mandate_value' => $mandates['approved_value'],
            'pending_mandate_value' => $mandates['pending_value'],
            'failed_mandate_value' => $mandates['failed_value'],
            'active_mandate_count' => $mandates['approved_count'],
            'this_month_expected' => (float) $thisMonth->total_amount,
            'this_month_paid' => (float) $thisMonth->paid_amount,
            'this_month_rate' => $this->percentage($thisMonth->paid_amount, $thisMonth->total_amount),
            'today_expected' => (float) $todayStats->total_amount,
            'today_paid' => (float) $todayStats->paid_amount,
            'today_rate' => $this->percentage($todayStats->paid_amount, $todayStats->total_amount),
            'overdue_unpaid_count' => (int) $overdue->overdue_count,
            'overdue_unpaid_amount' => (float) $overdue->overdue_amount,
            'partial_paid_count' => (int) $partialPaid->partial_paid_count,
            'partial_paid_amount' => (float) $partialPaid->partial_paid_amount,
            'failed_today_count' => (int) $failedToday->failed_today_count,
            'failed_today_amount' => (float) $failedToday->failed_today_amount,
            'upcoming_next_7_days_count' => (int) $upcoming->upcoming_count,
            'upcoming_next_7_days_amount' => (float) $upcoming->upcoming_amount,
        ];

        $postData = [
            'year' => $currentYear,
            'month' => $currentMonth,
            'channel' => $channel,
        ];

        $paymentChannels = Account::where('active', 1)->get();
        $formAction = route('bi.dashboard');

        return view('bi.dashboard', compact(
            'title',
            'stats',
            'recentSuccessful',
            'recentFailed',
            'partnerAttention',
            'paymentChannels',
            'formAction',
            'postData'
        ));
    }

    private function operationalInstallments(?string $channel, bool $openContractsOnly): Builder
    {
        return Installment::filterCondition()
            ->whereHas('debitOrder', function ($query) use ($channel, $openContractsOnly) {
                $query->where('debit_orders.approved', 1)
                    ->where('debit_orders.status', 240)
                    ->where('debit_orders.deleted', 0)
                    ->when($openContractsOnly, function ($q) {
                        $q->where('debit_orders.contract_status', 'OPEN');
                    })
                    ->when($channel, function ($q) use ($channel) {
                        $q->where('debit_orders.channel', $channel);
                    })
                    ->whereHas('partner', function ($partnerQuery) {
                        $partnerQuery->where('partners.approved', 1);
                    });
            });
    }

    private function buildMandateSummary(?string $channel): array
    {
        $approvedStatuses = [0, 1, 2];
        $debitOrders = DebitOrder::mandateApprovalStats($channel);

        $mandates = collect($approvedStatuses)->mapWithKeys(function ($status) use ($debitOrders) {
            $order = $debitOrders->firstWhere('approved', $status);

            return [
                $status => [
                    'count' => $order ? (int) $order->number_of_mandate : 0,
                    'value' => $order ? (float) $order->total_amount : 0,
                ],
            ];
        });

        return [
            'pending_count' => $mandates[0]['count'],
            'approved_count' => $mandates[1]['count'],
            'failed_count' => $mandates[2]['count'],
            'total_count' => $mandates[0]['count'] + $mandates[1]['count'] + $mandates[2]['count'],
            'pending_value' => $mandates[0]['value'],
            'approved_value' => $mandates[1]['value'],
            'failed_value' => $mandates[2]['value'],
            'total_value' => $mandates[0]['value'] + $mandates[1]['value'] + $mandates[2]['value'],
        ];
    }

    private function buildPartnerAttentionData(?string $channel, int $month, int $year): array
    {
        $partners = Partner::filterCondition()
            ->where('approved', 1)
            ->get();

        $rows = [];

        foreach ($partners as $partner) {
            $monthly = Installment::monthlyCollectionStats($partner->id, $month, $year, 240, 0, $channel);

            if ((float) $monthly->total_amount <= 0) {
                continue;
            }

            $rate = $this->percentage($monthly->paid_amount, $monthly->total_amount);

            $rows[] = [
                'uuid' => $partner->uuid,
                'partner_name' => $partner->business_name,
                'expected_amount' => (float) $monthly->total_amount,
                'collected_amount' => (float) $monthly->paid_amount,
                'outstanding_amount' => (float) $monthly->outstanding_amount,
                'number_of_collection' => (int) $monthly->number_of_collection,
                'rate' => $rate,
            ];
        }

        return collect($rows)
            ->sortByDesc('outstanding_amount')
            ->sortBy('rate')
            ->take(8)
            ->values()
            ->toArray();
    }

    private function percentage($numerator, $denominator): float
    {
        if ((float) $denominator <= 0) {
            return 0;
        }

        return round(((float) $numerator / (float) $denominator) * 100, 2);
    }
}
