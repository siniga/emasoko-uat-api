<?php

namespace App\Http\Controllers\Api\MobileApp;

use App\Http\Controllers\Api\ExternalApp\PaymentMandateController as ExternalPaymentMandateController;
use App\Http\Requests\Api\ExternalApp\CreatePaymentMandateRequest;
use App\Http\Resources\InstallmentResource;
use App\Http\Resources\MandateResource;
use App\Models\DebitOrder;
use App\Models\Installment;
use Carbon\Carbon;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;

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

    public function getMandate(Request $request): JsonResponse
    {
        $identifier = $this->mandateIdentifier($request);

        if (!$identifier) {
            return $this->errorResponse('Mandate reference or uuid is required.', null, 422);
        }

        $debitOrder = $this->findMandate($identifier);

        if (!$debitOrder) {
            return $this->errorResponse('Mandate not found.', null, 404);
        }

        return $this->successResponse('Mandate fetched successfully.', [
            'mandate' => new MandateResource($debitOrder),
        ]);
    }

    public function create(CreatePaymentMandateRequest $request): JsonResponse
    {
        $account = $request->paymentAccount();

        $totalAmount = $this->amount($request->input('total_amount'));
        $installmentAmount = $this->amount($request->input('installment_amount'));
        $minInstallmentAmount = $this->amount($request->input('min_installment_amount'));
        $maxInstallmentAmount = $this->amount($request->input('max_installment_amount'));

        $partner = $request->partner();
        $partnerId = $partner->id;
        $accountId = $account->id;

        if (!$this->checkIfPartnerHasPaymentChannel($partnerId, $accountId)) {
            return $this->errorResponse(
                'Partner has not linked to any payment channel, contact eMakato support team.',
                null,
                403,
                [
                    'partner_id' => $partnerId,
                    'account_id' => $accountId,
                ]
            );
        }

        $customerAccount = $this->paymentMandatePartyService->upsertCustomerAccount([
            'account_type_id' => (int) $account->account_type_id,
            'account_id' => $accountId,
            'phone' => $request->phone,
            'secondary_phone' => $request->secondary_phone,
            'account_name' => $request->account_name,
            'account_number' => $request->account_number,
            'email' => $request->email,
            'id_number' => $request->NIN,
            'address' => $request->input('address', 'Dar es Salaam'),
        ], Auth::id(), (string) Str::uuid());

        $installmentNumber = $this->deductionService->calculateNumberOfInstallments(
            $request->debit_type,
            $totalAmount,
            $installmentAmount,
            $maxInstallmentAmount
        );

        $debitOrder = $this->paymentMandateWorkflowService->createDraft([
            'partner_id' => $partnerId,
            'customer_account_uuid' => $customerAccount->uuid,
            'loan_amount' => $totalAmount,
            'debit_type' => $request->debit_type,
            'installment_amount' => $installmentAmount,
            'min_installment_amount' => $minInstallmentAmount,
            'max_installment_amount' => $maxInstallmentAmount,
            'periodicity' => $request->periodicity,
            'number_of_installment' => $installmentNumber,
            'start_date' => Carbon::parse($request->deduction_start_date)->format('Y-m-d'),
        ], Auth::id());

        if (!$debitOrder) {
            return $this->errorResponse('Failed to create mandate, review your request and submit again.', null, 400);
        }

        $response = $this->paymentMandateWorkflowService->submit($debitOrder->uuid);
        $debitOrder->refresh();

        if ($response['success'] === true) {
            return $this->successResponse(
                $response['success_msg'] ?: 'Mandate created successfully.',
                $this->actionMandatePayload($debitOrder, [
                    'status' => 'success',
                    'lifecycle_status' => $response['lifecycle_status'],
                    'redirect_url' => $response['redirect_url'] ?? null,
                ]),
                (int) $response['response_code']
            );
        }

        return $this->errorResponse($response['success_msg'] ?? 'Unable to create mandate.', null, 500, $this->actionMandatePayload($debitOrder, [
            'status' => 'failed',
            'lifecycle_status' => $debitOrder->lifecycle_status,
            'redirect_url' => $response['redirect_url'] ?? null,
        ]));
    }

    public function verifyOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reference' => 'required_without:uuid',
            'uuid' => 'required_without:reference',
            'otpPin' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed.', $validator->errors(), 422);
        }

        $debitOrder = $this->findMandate($this->mandateIdentifier($request));

        if (!$debitOrder) {
            return $this->errorResponse('Mandate not found.', null, 404);
        }

        $result = $this->paymentMandateWorkflowService->verifyOtp($debitOrder->uuid, $request->otpPin, Auth::id());
        $status = $result['success'] ? 'success' : 'failed';
        $debitOrder->refresh();

        if (!$result['success']) {
            return $this->errorResponse($result['success_msg'] ?: 'OTP verification failed.', null, (int) $result['response_code'], $this->actionMandatePayload($debitOrder, [
                'status' => $status,
                'lifecycle_status' => $result['lifecycle_status'],
            ]));
        }

        return $this->successResponse($result['success_msg'] ?: 'OTP verified successfully.', $this->actionMandatePayload($debitOrder, [
            'status' => $status,
            'lifecycle_status' => $result['lifecycle_status'],
        ]), (int) $result['response_code']);
    }

    public function createOtp(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reference' => 'required_without:uuid',
            'uuid' => 'required_without:reference',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed.', $validator->errors(), 422);
        }

        $debitOrder = $this->findMandate($this->mandateIdentifier($request));

        if (!$debitOrder) {
            return $this->errorResponse('Mandate not found.', null, 404);
        }

        $result = $this->paymentMandateWorkflowService->resendOtp($debitOrder->uuid, Auth::id());
        $status = $result['success'] ? 'success' : 'failed';
        $debitOrder->refresh();

        if (!$result['success']) {
            return $this->errorResponse($result['success_msg'] ?: 'OTP request failed.', null, (int) $result['response_code'], $this->actionMandatePayload($debitOrder, [
                'status' => $status,
                'lifecycle_status' => $result['lifecycle_status'],
            ]));
        }

        return $this->successResponse($result['success_msg'] ?: 'OTP created successfully.', $this->actionMandatePayload($debitOrder, [
            'status' => $status,
            'lifecycle_status' => $result['lifecycle_status'],
        ]), (int) $result['response_code']);
    }

    public function fetchStatus(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reference' => 'required_without:uuid',
            'uuid' => 'required_without:reference',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed.', $validator->errors(), 422);
        }

        $debitOrder = $this->findMandate($this->mandateIdentifier($request));

        if (!$debitOrder) {
            return $this->errorResponse('Mandate not found.', null, 404);
        }

        $result = $this->paymentMandateWorkflowService->fetchStatus($debitOrder->uuid);
        $status = $result['success'] ? 'success' : 'failed';
        $debitOrder->refresh();

        $remarks = $debitOrder->remarks_message ?: $result['success_msg'];

        if (!$result['success']) {
            return $this->errorResponse($remarks ?: 'Mandate status fetch failed.', null, (int) $result['response_code'], $this->actionMandatePayload($debitOrder, [
                'status' => $status,
                'lifecycle_status' => $debitOrder->resolved_lifecycle_status,
            ]));
        }

        return $this->successResponse($remarks ?: 'Mandate status fetched successfully.', $this->actionMandatePayload($debitOrder, [
            'status' => $status,
            'lifecycle_status' => $debitOrder->resolved_lifecycle_status,
        ]), (int) $result['response_code']);
    }

    public function cancellation(Request $request): JsonResponse
    {
        $validator = Validator::make($request->all(), [
            'reference' => 'required_without:uuid',
            'uuid' => 'required_without:reference',
            'reasons' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed.', $validator->errors(), 422);
        }

        $debitOrder = $this->findMandate($this->mandateIdentifier($request));

        if (!$debitOrder) {
            return $this->errorResponse('Mandate not found.', null, 404);
        }

        $result = $this->paymentMandateWorkflowService->cancel($debitOrder, Auth::id(), $request->reasons);
        $status = $result['success'] ? 'success' : 'failed';
        $debitOrder->refresh();

        $payload = $this->actionMandatePayload($debitOrder, [
            'status' => $status,
            'lifecycle_status' => $result['lifecycle_status'],
            'provider_response_code' => $result['provider_response_code'],
        ]);

        if (!$result['success']) {
            return $this->errorResponse($result['success_msg'] ?: 'Mandate cancellation failed.', null, (int) $result['response_code'], $payload);
        }

        return $this->successResponse($result['success_msg'] ?: 'Mandate cancelled successfully.', $payload, (int) $result['response_code']);
    }

    public function deductionHistories(Request $request, string $uuid): JsonResponse
    {
        $debitOrder = $this->findMandate($uuid);

        if (!$debitOrder) {
            return $this->errorResponse('Mandate not found.', null, 404);
        }

        $histories = $debitOrder->installments()
            ->with('debitOrder')
            ->orderBy('payment_date')
            ->get();

        return $this->successResponse('Deduction histories fetched successfully.', [
            'mandate' => new MandateResource($debitOrder),
            'histories' => InstallmentResource::collection($histories),
        ]);
    }

    public function archiveMandate(Request $request): JsonResponse
    {
        $identifier = $this->mandateIdentifier($request);

        if (!$identifier) {
            return $this->errorResponse('Mandate reference or uuid is required.', null, 422);
        }

        $debitOrder = $this->findMandate($identifier);
        if (!$debitOrder) {
            return $this->errorResponse('Mandate not found.', null, 404);
        }

        $archiveRequest = $this->paymentMandateWorkflowService->archive($debitOrder, Auth::id());
        $debitOrder->refresh();

        return $this->successResponse('Archiving payment mandate request sent.', $this->actionMandatePayload($debitOrder, [
            'request_id' => $archiveRequest->id,
            'status' => $archiveRequest->status,
        ]));
    }

    public function closeMandate(Request $request): JsonResponse
    {
        $identifier = $this->mandateIdentifier($request);

        if (!$identifier) {
            return $this->errorResponse('Mandate reference or uuid is required.', null, 422);
        }

        $debitOrder = $this->findMandate($identifier);
        if (!$debitOrder) {
            return $this->errorResponse('Mandate not found.', null, 404);
        }

        $deleteRequest = $this->paymentMandateWorkflowService->requestClosing($debitOrder, Auth::id());
        $debitOrder->refresh();

        return $this->successResponse('Closing payment mandate request sent.', $this->actionMandatePayload($debitOrder, [
            'request_id' => $deleteRequest->id,
            'status' => $deleteRequest->status,
        ]));
    }

    protected function findMandateByReference(string $reference): ?DebitOrder
    {
        return $this->findMandate($reference);
    }

    protected function findMandate(?string $reference): ?DebitOrder
    {
        if (!$reference) {
            return null;
        }

        return $this->paymentMandateQueryService
            ->baseQuery([], Auth::user())
            ->where(function ($query) use ($reference) {
                $query->where('debit_orders.reference', $reference)
                    ->orWhere('debit_orders.uuid', $reference);
            })
            ->first();
    }

    protected function mandateIdentifier(Request $request): ?string
    {
        return $request->input('reference')
            ?: $request->input('uuid')
            ?: $request->route('reference');
    }

    protected function actionMandatePayload(DebitOrder $debitOrder, array $extra = []): array
    {
        return array_merge([
            'mandate_reference' => $debitOrder->reference,
            'mandate_uuid' => $debitOrder->uuid,
            'mandate' => new MandateResource($debitOrder),
        ], $extra);
    }

    protected function amount($value): float
    {
        return (float) str_replace(',', '', (string) $value);
    }

    protected function summaryMandateFilters(Request $request, int $year, int $month): array
    {
        $filters = $request->except(['status', 'lifecycle_status']);

        if (empty($filters['start_at']) && empty($filters['end_at'])) {
            $startAt = Carbon::create($year, $request->filled('month') ? $month : 1, 1)->startOfDay();
            $endAt = $request->filled('month')
                ? $startAt->copy()->endOfMonth()
                : Carbon::create($year, 12, 31)->endOfDay();

            $filters['start_at'] = $startAt->toDateString();
            $filters['end_at'] = $endAt->toDateString();
        }

        return $filters;
    }

    protected function mandateSummaryMap(array $filters): array
    {
        $baseQuery = $this->paymentMandateQueryService->baseQuery($filters, Auth::user());
        $summaryMap = [
            DebitOrder::SUMMARY_PENDING => ['count' => 0, 'amount' => 0.0],
            DebitOrder::SUMMARY_APPROVED => ['count' => 0, 'amount' => 0.0],
            DebitOrder::SUMMARY_FAILED => ['count' => 0, 'amount' => 0.0],
        ];

        foreach (DebitOrder::lifecycleSummaryMap() as $summary => $statuses) {
            $summaryQuery = (clone $baseQuery)->whereIn('debit_orders.lifecycle_status', $statuses);

            $summaryMap[$summary] = [
                'count' => (clone $summaryQuery)->count(),
                'amount' => (float) $summaryQuery->sum('debit_orders.total_amount'),
            ];
        }

        return $summaryMap;
    }

    protected function summaryPayload(Request $request): array
    {
        $channel = $request->input('payment_channel', $request->input('channel'));
        $currentYear = (int) $request->input('year', now()->year);
        $currentMonth = (int) $request->input('month', now()->month);

        $summaryMap = $this->mandateSummaryMap($this->summaryMandateFilters($request, $currentYear, $currentMonth));

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
