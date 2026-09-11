<?php

namespace App\Http\Controllers\Api\ExternalApp;

use App\Http\Controllers\Api\ExternalApp\Concerns\RespondsWithApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\InstallmentResource;
use App\Models\DebitOrder;
use App\Models\Installment;
use App\Models\PaymentSettlement;
use Dedoc\Scramble\Attributes\BodyParameter;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Response;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Validator;

#[Group('Payment Collections', description: 'APIs for viewing and filtering payment collections.', weight: 3)]
class PaymentCollectionController extends Controller
{
    use RespondsWithApi;

    public function __construct() {}

    #[Endpoint(
        operationId: 'listPaymentCollections',
        title: 'All collections',
        description: 'Returns expected and paid collections for the selected month and year.'
    )]
    #[BodyParameter('year', description: 'Optional collection year. Defaults to current year.', type: 'integer', example: 2026)]
    #[BodyParameter('month', description: 'Optional collection month number. Defaults to current month.', type: 'integer', example: 6)]
    #[BodyParameter('status', description: 'Optional collection status filter. Example values: OPEN, CLOSED, FUNDSCHECK, FAILED.', type: 'string', example: 'OPEN')]
    #[Response(200, 'Collections fetched successfully.', type: 'array{
    success: bool,
    message: string,
    data: array{
        collections: array<array{
            mandate: array{
                reference: string|null,
                debit_type: string|null,
                start_date: string|null,
                end_date: string|null,
                contract_status: string|null,
                approved: int|null
            },
            installment_order: int|null,
            installment_amount: float|null,
            min_installment_amount: float|null,
            max_installment_amount: float|null,
            current_balance: float|null,
            outstanding_amount: float|null,
            payment_date: string|null,
            last_paid_amount: float|null,
            reference: string|null,
            status: string|null,
            remarks: string|null
        }>
    }}')]
    public function all(Request $request): JsonResponse
    {
        //current date
        $current_year = date('Y');
        $current_month = date('m');

        if ($request->has('year'))
            $current_year = $request->year;

        if ($request->has('month'))
            $current_month = $request->month;

        //collections
        $collections = Installment::filterCondition();

        //collection by date
        $collections = $collections
            ->with(['debitOrder'])
            ->whereMonth('installments.payment_date', $current_month)
            ->whereYear('installments.payment_date', $current_year);

        //apply status
        if ($request->filled('status')) {
            $collections = $collections->whereIn(
                'installments.status',
                $this->normalizeCollectionStatuses($request->status)
            );
        }

        //order
        $collections = $collections->orderBy('installments.payment_date', 'ASC')->get();

        return $this->successResponse('Collections fetched successfully.', [
            'collections' => InstallmentResource::collection($collections),
        ]);
    }

    #[Endpoint(
        operationId: 'filterPaymentCollections',
        title: 'Filter collections',
        description: 'Returns expected collections for a specific mandate reference, optionally filtered by collection status.'
    )]
    #[BodyParameter('reference', description: 'Mandate reference number.', type: 'string', required: true, example: 'DO0000123')]
    #[BodyParameter('status', description: 'Optional collection status filter. Example values: OPEN, CLOSED, FUNDSCHECK, FAILED.', type: 'string', example: 'OPEN')]
    #[Response(200, 'Collections fetched successfully.', type: 'array{
    success: bool,
    message: string,
    data: array{
        collections: array<array{
            mandate: array{
                reference: string|null,
                debit_type: string|null,
                start_date: string|null,
                end_date: string|null,
                contract_status: string|null,
                approved: int|null
            },
            installment_order: int|null,
            installment_amount: float|null,
            min_installment_amount: float|null,
            max_installment_amount: float|null,
            current_balance: float|null,
            outstanding_amount: float|null,
            payment_date: string|null,
            last_paid_amount: float|null,
            reference: string|null,
            status: string|null,
            remarks: string|null
        }>
    }}')]
    #[Response(422, 'Validation failed.')]
    public function filtering(Request $request): JsonResponse
    {
        //validate request
        $validator = Validator::make($request->all(), [
            'reference' => 'required|string',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed.', $validator->errors(), 422);
        }

        //collections
        $collections = Installment::filterCondition();

        // mandate reference
        if ($request->reference) {
            $collections = $collections->whereHas('debitOrder', function ($query) use ($request) {
                $query->where('debit_orders.reference', $request->reference);
            });
        }

        //apply status
        if ($request->filled('status')) {
            $collections = $collections->whereIn(
                'installments.status',
                $this->normalizeCollectionStatuses($request->status)
            );
        }

        //order
        $collections = $collections->orderBy('installments.payment_date', 'ASC')->get();

        return $this->successResponse('Collections fetched successfully.', [
            'collections' => InstallmentResource::collection($collections),
        ]);
    }

    #[Endpoint(
        operationId: 'actualPaymentCollections',
        title: 'Actual collections',
        description: 'Returns actual aggregated collections by partner for the selected month and year.'
    )]
    #[BodyParameter('year', description: 'Optional collection year for payment_date and effective_payment_date filters. Defaults to current year.', type: 'integer', example: 2026)]
    #[BodyParameter('month', description: 'Optional collection month number for payment_date and effective_payment_date filters. Defaults to current month.', type: 'integer', example: 6)]
    #[Response(200, 'Actual collections fetched successfully.', type: 'array{
    success: bool,
    message: string,
    data: array{
        collections: array<array{
            corporate_reference: string,
            partner_name: string,
            number_of_collection: int,
            total_amount: float,
            outstanding_amount: float,
            paid_amount: float,
            percent: float
        }>
    }}')]
    public function actualCollections(Request $request): JsonResponse
    {
        $year = (int) $request->input('year', date('Y'));
        $month = (int) $request->input('month', date('m'));
        $authorizedInstallments = Installment::filterCondition()->select('installments.id');

        $settledInstallments = PaymentSettlement::filterCondition()
            ->join('installments', 'installments.id', '=', 'payment_settlements.installment_id')
            ->whereIn('payment_settlements.installment_id', $authorizedInstallments)
            ->whereYear('payment_settlements.effective_payment_date', $year)
            ->whereMonth('payment_settlements.effective_payment_date', $month)
            ->reportable('payment_settlements')
            ->select('payment_settlements.debit_order_id', 'payment_settlements.installment_id')
            ->selectRaw('MAX(installments.installment_amount) as expected_amount')
            ->selectRaw('SUM(payment_settlements.paid_amount) as paid_amount')
            ->selectRaw('COUNT(payment_settlements.id) as payment_event_count')
            ->groupBy('payment_settlements.debit_order_id', 'payment_settlements.installment_id')
            ->havingRaw('SUM(payment_settlements.paid_amount) > 0');

        $collections = DB::query()
            ->fromSub($settledInstallments, 'settled_installments')
            ->join('debit_orders', 'debit_orders.id', '=', 'settled_installments.debit_order_id')
            ->join('partners', 'partners.id', '=', 'debit_orders.partner_id')
            ->select('partners.uuid', 'partners.reference', 'partners.business_name as partner_name')
            ->selectRaw('COUNT(*) as number_of_collection')
            ->selectRaw('SUM(settled_installments.payment_event_count) as payment_event_count')
            ->selectRaw('COALESCE(SUM(settled_installments.expected_amount), 0) as total_amount')
            ->selectRaw('COALESCE(SUM(settled_installments.paid_amount), 0) as paid_amount')
            ->groupBy('partners.id', 'partners.uuid', 'partners.reference', 'partners.business_name')
            ->orderByDesc('paid_amount')
            ->get()
            ->map(function ($row) {
                $expected = (float) $row->total_amount;
                $paid = (float) $row->paid_amount;

                return [
                    'corporate_reference' => $row->reference,
                    'partner_name' => $row->partner_name,
                    'number_of_collection' => (int) $row->number_of_collection,
                    'payment_event_count' => (int) $row->payment_event_count,
                    'total_amount' => $expected,
                    'outstanding_amount' => max($expected - $paid, 0),
                    'paid_amount' => $paid,
                    'percent' => $expected > 0 ? round(($paid / $expected) * 100, 2) : 0,
                ];
            })
            ->values()
            ->all();

        return $this->successResponse('Actual collections fetched successfully.', [
            'collections' => $collections,
            'filters' => ['year' => $year, 'month' => $month],
        ]);
    }

    protected function normalizeCollectionStatuses(string $status): array
    {
        $normalized = strtoupper(trim($status));
        $normalized = str_replace(['-', ' '], '_', $normalized);

        return match ($normalized) {
            'PAID', 'FULLY_PAID' => ['CLOSED'],
            'PARTIAL', 'PARTIALPAID' => ['PARTIAL_PAID'],
            'COLLECTED', 'SUCCESSFUL' => ['CLOSED', 'PARTIAL_PAID'],
            'PENDING' => ['OPEN'],
            'FUND_CHECK' => ['FUNDSCHECK'],
            default => [$normalized],
        };
    }
}
