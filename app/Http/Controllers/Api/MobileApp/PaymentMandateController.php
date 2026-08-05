<?php

namespace App\Http\Controllers\Api\MobileApp;

use App\Http\Controllers\Api\ExternalApp\PaymentMandateController as ExternalPaymentMandateController;
use App\Http\Resources\MandateResource;
use App\Models\DebitOrder;
use App\Models\Installment;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Auth;

#[Group('Payment Mandates', description: 'Mobile APIs for creating, viewing, and managing payment mandates.', weight: 2)]
class PaymentMandateController extends ExternalPaymentMandateController
{
    public function all(Request $request): JsonResponse
    {
        $filters = $request->all();
        $debitOrders = $this->paymentMandateQueryService
            ->listQuery($filters, Auth::user())
            ->orderBy('debit_orders.created_at', 'DESC')
            ->get();

        return $this->successResponse('Mandates fetched successfully.', [
            'mandates' => MandateResource::collection($debitOrders),
            'filters' => $this->filterPayload($filters),
        ]);
    }

    public function summaries(Request $request): JsonResponse
    {
        return $this->successResponse('Mobile summaries fetched successfully.', $this->summaryPayload($request));
    }

    public function spHomepage(Request $request): JsonResponse
    {
        $recentMandates = $this->paymentMandateQueryService
            ->listQuery($request->all(), Auth::user())
            ->orderBy('debit_orders.created_at', 'DESC')
            ->limit(5)
            ->get();

        return $this->successResponse('Homepage fetched successfully.', [
            ...$this->summaryPayload($request),
            'recent_mandates' => MandateResource::collection($recentMandates),
        ]);
    }

    public function debitOrderSummaries(Request $request): JsonResponse
    {
        $year = (int) $request->input('year', now()->year);
        $channel = $request->input('payment_channel', $request->input('channel'));

        $rows = $this->paymentMandateQueryService
            ->baseQuery($request->all(), Auth::user())
            ->selectRaw('MONTH(debit_orders.created_at) as month')
            ->selectRaw('COUNT(debit_orders.id) as total_count')
            ->selectRaw('COALESCE(SUM(debit_orders.total_amount), 0) as total_amount')
            ->selectRaw("SUM(CASE WHEN debit_orders.lifecycle_status IN (?, ?, ?, ?) THEN 1 ELSE 0 END) as pending_count", [
                DebitOrder::LIFECYCLE_DRAFT,
                DebitOrder::LIFECYCLE_SUBMITTED,
                DebitOrder::LIFECYCLE_AWAITING_OTP,
                DebitOrder::LIFECYCLE_VERIFIED,
            ])
            ->selectRaw("SUM(CASE WHEN debit_orders.lifecycle_status IN (?, ?, ?, ?, ?) THEN 1 ELSE 0 END) as approved_count", [
                DebitOrder::LIFECYCLE_ACTIVE,
                DebitOrder::LIFECYCLE_CANCELLATION_REQUESTED,
                DebitOrder::LIFECYCLE_CANCELLED,
                DebitOrder::LIFECYCLE_CLOSED,
                DebitOrder::LIFECYCLE_ARCHIVED,
            ])
            ->selectRaw("SUM(CASE WHEN debit_orders.lifecycle_status = ? THEN 1 ELSE 0 END) as active_count", [DebitOrder::LIFECYCLE_ACTIVE])
            ->selectRaw("SUM(CASE WHEN debit_orders.lifecycle_status = ? THEN 1 ELSE 0 END) as failed_count", [DebitOrder::LIFECYCLE_FAILED])
            ->whereYear('debit_orders.created_at', $year)
            ->when($channel, function ($query) use ($channel) {
                $query->where('debit_orders.channel', $channel);
            })
            ->groupBy(DB::raw('MONTH(debit_orders.created_at)'))
            ->orderBy(DB::raw('MONTH(debit_orders.created_at)'))
            ->get()
            ->keyBy('month');

        $summaries = collect(range(1, 12))->map(function ($month) use ($rows) {
            $row = $rows->get($month);

            return [
                'month' => $month,
                'month_name' => now()->month($month)->format('F'),
                'total_count' => (int) ($row->total_count ?? 0),
                'pending_count' => (int) ($row->pending_count ?? 0),
                'approved_count' => (int) ($row->approved_count ?? 0),
                'active_count' => (int) ($row->active_count ?? 0),
                'failed_count' => (int) ($row->failed_count ?? 0),
                'total_amount' => (float) ($row->total_amount ?? 0),
            ];
        })->values();

        return $this->successResponse('Debit order summaries fetched successfully.', [
            'year' => $year,
            'summaries' => $summaries,
            'filters' => $this->filterPayload($request->all()),
        ]);
    }

    public function deductionHistories(Request $request, string $uuid): JsonResponse
    {
        $debitOrder = $this->paymentMandateQueryService
            ->baseQuery([], Auth::user())
            ->where(function ($query) use ($uuid) {
                $query->where('debit_orders.uuid', $uuid)
                    ->orWhere('debit_orders.reference', $uuid);
            })
            ->first();

        if (!$debitOrder) {
            return $this->errorResponse('Mandate not found.', null, 404);
        }

        $histories = $debitOrder->installments()
            ->with('debitOrder')
            ->orderBy('payment_date')
            ->get();

        return $this->successResponse('Deduction histories fetched successfully.', [
            'mandate' => new MandateResource($debitOrder),
            'histories' => \App\Http\Resources\InstallmentResource::collection($histories),
        ]);
    }

    public function archiveMandate(Request $request): JsonResponse
    {
        $reference = $request->input('reference', $request->input('uuid'));
        if (!$reference) {
            return $this->errorResponse('Mandate reference or uuid is required.', null, 422);
        }

        $debitOrder = $this->findMandate($reference);
        if (!$debitOrder) {
            return $this->errorResponse('Mandate not found.', null, 404);
        }

        $archiveRequest = $this->paymentMandateWorkflowService->archive($debitOrder, Auth::id());

        return $this->successResponse('Archiving payment mandate request sent.', [
            'request_id' => $archiveRequest->id,
            'status' => $archiveRequest->status,
            'mandate_reference' => $debitOrder->reference,
        ]);
    }

    public function closeMandate(Request $request): JsonResponse
    {
        $reference = $request->input('reference', $request->input('uuid'));
        if (!$reference) {
            return $this->errorResponse('Mandate reference or uuid is required.', null, 422);
        }

        $debitOrder = $this->findMandate($reference);
        if (!$debitOrder) {
            return $this->errorResponse('Mandate not found.', null, 404);
        }

        $deleteRequest = $this->paymentMandateWorkflowService->requestClosing($debitOrder, Auth::id());

        return $this->successResponse('Closing payment mandate request sent.', [
            'request_id' => $deleteRequest->id,
            'status' => $deleteRequest->status,
            'mandate_reference' => $debitOrder->reference,
        ]);
    }

    protected function findMandate(string $reference): ?DebitOrder
    {
        return $this->paymentMandateQueryService
            ->baseQuery([], Auth::user())
            ->where(function ($query) use ($reference) {
                $query->where('debit_orders.reference', $reference)
                    ->orWhere('debit_orders.uuid', $reference);
            })
            ->first();
    }

    protected function summaryPayload(Request $request): array
    {
        $channel = $request->input('payment_channel', $request->input('channel'));
        $currentYear = (int) $request->input('year', now()->year);
        $currentMonth = (int) $request->input('month', now()->month);

        $mandates = DebitOrder::mandateApprovalStats($channel, $currentYear);
        $summaryMap = [
            DebitOrder::SUMMARY_PENDING => ['count' => 0, 'amount' => 0.0],
            DebitOrder::SUMMARY_APPROVED => ['count' => 0, 'amount' => 0.0],
            DebitOrder::SUMMARY_FAILED => ['count' => 0, 'amount' => 0.0],
        ];

        foreach ($mandates as $mandate) {
            $summary = match ((int) $mandate->approved) {
                1 => DebitOrder::SUMMARY_APPROVED,
                2 => DebitOrder::SUMMARY_FAILED,
                default => DebitOrder::SUMMARY_PENDING,
            };

            $summaryMap[$summary] = [
                'count' => (int) $mandate->number_of_mandate,
                'amount' => (float) $mandate->total_amount,
            ];
        }

        $thisMonth = Installment::thisMonthStats($currentMonth, $currentYear, $channel);
        $today = Installment::todayStats(now()->toDateString(), $channel);

        return [
            'mandates' => [
                'pending' => $summaryMap[DebitOrder::SUMMARY_PENDING],
                'approved' => $summaryMap[DebitOrder::SUMMARY_APPROVED],
                'failed' => $summaryMap[DebitOrder::SUMMARY_FAILED],
                'total' => [
                    'count' => array_sum(array_column($summaryMap, 'count')),
                    'amount' => array_sum(array_column($summaryMap, 'amount')),
                ],
            ],
            'collections' => [
                'month' => [
                    'year' => $currentYear,
                    'month' => $currentMonth,
                    'expected_count' => (int) ($thisMonth->total_collections ?? 0),
                    'paid_count' => (int) ($thisMonth->successful_collections ?? 0),
                    'expected_amount' => (float) ($thisMonth->total_amount ?? 0),
                    'paid_amount' => (float) ($thisMonth->paid_amount ?? 0),
                ],
                'today' => [
                    'date' => now()->toDateString(),
                    'expected_count' => (int) ($today->total_collections ?? 0),
                    'paid_count' => (int) ($today->successful_collections ?? 0),
                    'expected_amount' => (float) ($today->total_amount ?? 0),
                    'paid_amount' => (float) ($today->paid_amount ?? 0),
                ],
            ],
            'filters' => $this->filterPayload($request->all()),
        ];
    }

    protected function filterPayload(array $filters = []): array
    {
        unset($filters['status'], $filters['lifecycle_status']);

        $stats = $this->paymentMandateQueryService->stats($filters, Auth::user());

        return [
            'lifecycle_statuses' => collect(DebitOrder::lifecycleStatusFilterOptions())
                ->map(function (array $option) use ($stats) {
                    $value = $option['value'];

                    return [
                        'value' => $value,
                        'label' => $option['label'],
                        'count' => (int) ($stats[$value] ?? 0),
                    ];
                })
                ->all(),
            'collection_statuses' => [
                ['value' => 'OPEN', 'label' => 'Open'],
                ['value' => 'CLOSED', 'label' => 'Fully Paid'],
                ['value' => 'PARTIAL_PAID', 'label' => 'Partial Paid'],
                ['value' => 'FAILED', 'label' => 'Failed'],
                ['value' => 'FUNDSCHECK', 'label' => 'Fundscheck'],
            ],
        ];
    }
}
