<?php

namespace App\Http\Controllers\Api\ExternalApp;

use App\Http\Controllers\Api\ExternalApp\Concerns\RespondsWithApi;
use App\Http\Controllers\Controller;
use App\Http\Requests\Api\ExternalApp\CreatePaymentMandateRequest;
use App\Http\Resources\MandateResource;
use Illuminate\Http\Request;
use App\Models\DebitOrder;
use App\Services\DeductionService;
use App\Services\PaymentMandatePartyService;
use App\Services\PaymentMandateQueryService;
use App\Services\PaymentMandateWorkflowService;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\Account;
use App\Models\Installment;
use App\Models\PartnerAccount;
use App\Services\MandateActionService;
use Illuminate\Http\JsonResponse;

use Dedoc\Scramble\Attributes\Group;
use Dedoc\Scramble\Attributes\Endpoint;
use Dedoc\Scramble\Attributes\QueryParameter;
use Dedoc\Scramble\Attributes\PathParameter;
use Dedoc\Scramble\Attributes\BodyParameter;
use Dedoc\Scramble\Attributes\Response;

#[Group('Payment Mandates', description: 'APIs for creating, verifying, querying, and cancelling payment mandates.', weight: 2)]
class PaymentMandateController extends Controller
{
    use RespondsWithApi;

    public function __construct(
        protected MandateActionService $mandateActionService,
        protected DeductionService $deductionService,
        protected PaymentMandateQueryService $paymentMandateQueryService,
        protected PaymentMandateWorkflowService $paymentMandateWorkflowService,
        protected PaymentMandatePartyService $paymentMandatePartyService
    ) {}

    // #[Endpoint(
    //     operationId: 'paymentMandateSummaries',
    //     title: 'Get mandate summaries',
    //     description: 'Returns mandate approval stats, mandate values, month collection, and today collection.'
    // )]
    // #[QueryParameter('channel', description: 'Optional payment channel filter.', type: 'string', example: 'NMB')]
    // #[Response(200, 'Mandate summary fetched successfully.', type: 'array{
    // success: bool,
    // message: string,
    // data: array{
    //     number_of_pending_mandate: int,
    //     number_of_approved_mandate: int,
    //     number_of_failed_mandate: int,
    //     total_number_of_mandate: int,
    //     pending_mandate_value: float,
    //     approved_mandate_value: float,
    //     failed_mandate_value: float,
    //     total_mandate_value: float,
    //     month: string,
    //     this_month_expected: float,
    //     this_month_paid: float,
    //     today_expected: float,
    //     today_paid: float
    // }}')]
    // public function summaries(Request $request): JsonResponse
    // {
    //     $approvedStatuses = [0, 1, 2]; // status

    //     //current year and month
    //     $current_year = date('Y');
    //     $current_month = date('m');
    //     $channel = $request->channel;

    //     //query
    //     $debit_orders = DebitOrder::mandateApprovalStats($channel);

    //     // Map over all approved statuses to ensure complete results
    //     $mandates = collect($approvedStatuses)->map(function ($status) use ($debit_orders) {
    //         $order = $debit_orders->firstWhere('approved', $status);
    //         return [
    //             'approved' => $status,
    //             'number_of_mandate' => $order ? $order->number_of_mandate : 0,
    //             'total_amount' => $order ? $order->total_amount : 0,
    //         ];
    //     });

    //     //total number of mandates
    //     $total_number_of_mandate = $mandates[0]['number_of_mandate'] + $mandates[1]['number_of_mandate'] + $mandates[2]['number_of_mandate'];

    //     //summation of mandate values
    //     $total_mandate_value = $mandates[0]['total_amount'] + $mandates[1]['total_amount'] + $mandates[2]['total_amount'];

    //     // This Month’s Collection
    //     $this_month = Installment::thisMonthStats($current_month, $current_year, $channel);

    //     // Today’s Collection
    //     $today = Installment::todayStats(now()->toDateString(), $channel);

    //     //stats
    //     $stats  = [
    //         //number of mandates
    //         'number_of_pending_mandate' =>  $mandates[0]['number_of_mandate'],
    //         'number_of_approved_mandate' =>  $mandates[1]['number_of_mandate'],
    //         'number_of_failed_mandate' =>  $mandates[2]['number_of_mandate'],
    //         'total_number_of_mandate' => $total_number_of_mandate,

    //         //mandates values
    //         'pending_mandate_value' => $mandates[0]['total_amount'],
    //         'approved_mandate_value' => $mandates[1]['total_amount'],
    //         'failed_mandate_value' => $mandates[2]['total_amount'],
    //         'total_mandate_value' => $total_mandate_value,

    //         //this month and today collection
    //         'month' => date('F'),
    //         'this_month_expected' => $this_month->total_amount,
    //         'this_month_paid' => $this_month->paid_amount,
    //         'today_expected' => $today->total_amount,
    //         'today_paid' => $today->paid_amount
    //     ];

    //     //response
    //     return $this->successResponse('Mandate summary fetched successfully.', $stats);
    // }

    #[Endpoint(
        operationId: 'listPaymentMandates',
        title: 'All mandates',
        description: 'Returns all mandates.'
    )]
    #[QueryParameter('start_at', description: 'Optional start date filter for mandate creation date.', type: 'string', format: 'date', example: '2026-06-01')]
    #[QueryParameter('end_at', description: 'Optional end date filter for mandate creation date.', type: 'string', format: 'date', example: '2026-06-30')]
    #[QueryParameter('payment_channel', description: 'Optional payment channel slug filter, for example NMB or CRDB.', type: 'string', example: 'NMB')]
    #[QueryParameter('corporate_reference', description: 'Optional corporate reference filter. Use this when your user is linked to more than one partner.', type: 'string', example: 'CO0001')]
    #[QueryParameter('lifecycle_status', description: 'Optional mandate lifecycle status filter.', type: 'string', example: 'ACTIVE')]
    #[QueryParameter('keyword', description: 'Optional search term for mandate reference, account name, or account number.', type: 'string', example: 'DO0000123')]
    #[Response(200, 'Mandates fetched successfully.', type: 'array{
    success: bool,
    message: string,
    data: array{
        mandates: array<array{
            payment_channel: string|null,
            reference: string,
            periodicity: string|null,
            debit_type: string|null,
            installment_amount: float|null,
            min_installment_amount: float|null,
            max_installment_amount: float|null,
            total_amount: float|null,
            paid_amount: float|null,
            outstanding_amount: float|null,
            number_of_installment: int|null,
            start_date: string|null,
            end_date: string|null,
            lifecycle_status: string|null,
            contract_status: string|null,
            remarks: string|null
        }>
    }}')]
    public function all(Request $request): JsonResponse
    {
        $debit_orders = $this->paymentMandateQueryService
            ->listQuery($request->all(), Auth::user())
            ->orderBy('debit_orders.created_at', 'DESC')
            ->get();

        //response
        return $this->successResponse('Mandates fetched successfully.', [
            'mandates' => MandateResource::collection($debit_orders),
        ]);
    }

    #[Endpoint(
        operationId: 'getPaymentMandate',
        title: 'Get mandate',
        description: 'Returns mandate by reference.'
    )]
    #[PathParameter('reference', description: 'Mandate reference number.', type: 'string', required: true, example: 'DO0000123')]
    #[Response(200, 'Mandate fetched successfully.', type: 'array{
    success: bool,
    message: string,
    data: array{
        mandate: array{
            payment_channel: string|null,
            reference: string,
            periodicity: string|null,
            debit_type: string|null,
            installment_amount: float|null,
            min_installment_amount: float|null,
            max_installment_amount: float|null,
            total_amount: float|null,
            paid_amount: float|null,
            outstanding_amount: float|null,
            number_of_installment: int|null,
            start_date: string|null,
            end_date: string|null,
            lifecycle_status: string|null,
            contract_status: string|null,
            remarks: string|null
        }
    }}')]
    #[Response(404, 'Mandate not found.')]
    public function getMandate(Request $request): JsonResponse
    {
        $debit_order = $this->findMandateByReference($request->reference);

        if (!$debit_order) {
            return $this->errorResponse('Mandate not found.', null, 404);
        }

        return $this->successResponse('Mandate fetched successfully.', [
            'mandate' => new MandateResource($debit_order),
        ]);
    }

    #[Endpoint(
        operationId: 'listMandatePaymentChannels',
        title: 'Active payment channels',
        description: 'Lists of active payment channels. Use slug as payment_channel when creating a mandate.'
    )]
    #[Response(200, 'Payment channels fetched successfully.', type: 'array{
    success: bool,
    message: string,
    data: array{
        payment_channels: array<array{
            id: int,
            name: string,
            slug: string,
        }>
    }}')]
    public function getPaymentChannels(): JsonResponse
    {
        $accounts = Account::whereIn('account_type_id', [1, 2])
            ->where('active', 1)
            ->get();

        $payment_channels = $accounts->map(function ($account) {
            return [
                'id' => $account->id,
                'name' => $account->name,
                'slug' => $account->slug,
            ];
        });

        return $this->successResponse('Payment channels fetched successfully.', [
            'payment_channels' => $payment_channels,
        ]);
    }

    #[Endpoint(
        operationId: 'createPaymentMandate',
        title: 'Create mandate',
        description: 'Creates a new payment mandate from the selected payment_channel.'
    )]
    #[BodyParameter('payment_channel', description: 'Payment provider slug returned by GET /api/mandate/channels.', type: 'string', required: true, example: 'CRDB')]
    #[BodyParameter('corporate_reference', description: 'Partner corporate reference. Required only when your user is linked to more than one partner.', type: 'string', example: 'CO0001')]
    #[BodyParameter('account_name', description: 'Required when the payment_channel is a BANK channel.', type: 'string', example: 'Tony Missokia')]
    #[BodyParameter('account_number', description: 'Required when the payment_channel is a BANK channel.', type: 'string', example: '015123456789')]
    #[BodyParameter('phone', description: 'Customer phone number. This should be number connected to bank account if payment channel is BANK.', type: 'string', required: true, example: '255712345678')]
    #[BodyParameter('NIN', description: 'Optional customer identification number.', type: 'string', example: '19900101123456789012')]
    #[BodyParameter('address', description: 'Optional customer address.', type: 'string', example: 'Dar es Salaam')]
    #[BodyParameter('total_amount', description: 'Total mandate amount. Minimum 1,000.', type: 'number', required: true, example: 500000)]
    #[BodyParameter('debit_type', description: 'Mandate debit type. {FIXED, DYNAMIC}', type: 'string', required: true, example: 'FIXED')]
    #[BodyParameter('installment_amount', description: 'Required when debit_type is FIXED.', type: 'number', example: 50000)]
    #[BodyParameter('min_installment_amount', description: 'Required when debit_type is DYNAMIC.', type: 'number', example: 10000)]
    #[BodyParameter('max_installment_amount', description: 'Required when debit_type is DYNAMIC.', type: 'number', example: 100000)]
    #[BodyParameter('periodicity', description: 'Deduction frequency.', type: 'string', required: true, example: 'MONTHLY')]
    #[BodyParameter('deduction_start_date', description: 'Start date. Must be today or future date.', type: 'string', format: 'date', required: true, example: '2026-06-10')]
    #[Response(200, 'Mandate created successfully.', type: 'array{
    success: bool,
    message: string,
    data: array{
        status: string,
        lifecycle_status: string,
        mandate_reference: string,
        redirect_url: string|null
    }}')]
    #[Response(422, 'Validation failed.')]
    #[Response(403, 'Partner has not linked to payment channel.')]
    public function create(CreatePaymentMandateRequest $request): JsonResponse
    {
        $account = $request->paymentAccount();

        $total_amount = floatval(str_replace(',', '', $request->input('total_amount')));
        $installment_amount = floatval(str_replace(',', '', $request->input('installment_amount')));
        $min_installment_amount = floatval(str_replace(',', '', $request->input('min_installment_amount')));
        $max_installment_amount = floatval(str_replace(',', '', $request->input('max_installment_amount')));

        $account_type_id = (int) $account->account_type_id;
        $account_id = $account->id;

        $partner = $request->partner();

        $partner_id = $partner->id;

        if ($this->checkIfPartnerHasPaymentChannel($partner_id, $account_id) == false) {
            return $this->errorResponse(
                'Partner has not linked to any payment channel, contact eMakato support team.',
                null,
                403,
                [
                    'partner_id' => $partner_id,
                    'account_id' => $account_id,
                ]
            );
        }

        $customerAccount = $this->paymentMandatePartyService->upsertCustomerAccount([
            'account_type_id' => $account_type_id,
            'account_id' => $account_id,
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
            $total_amount,
            $installment_amount,
            $max_installment_amount
        );

        $debitOrder = $this->paymentMandateWorkflowService->createDraft([
            'partner_id' => $partner_id,
            'customer_account_uuid' => $customerAccount->uuid,
            'loan_amount' => $total_amount,
            'debit_type' => $request->debit_type,
            'installment_amount' => $installment_amount,
            'min_installment_amount' => $min_installment_amount,
            'max_installment_amount' => $max_installment_amount,
            'periodicity' => $request->periodicity,
            'number_of_installment' => $installmentNumber,
            'start_date' => Carbon::parse($request->deduction_start_date)->format('Y-m-d'),
        ], Auth::id());

        if (!$debitOrder) {
            return $this->errorResponse('Failed to create mandate, review your request and submit again.', null, 400);
        }

        $response = $this->paymentMandateWorkflowService->submit($debitOrder->uuid);

        if ($response['success'] === true) {
            return $this->successResponse(
                $response['success_msg'] ?: 'Mandate created successfully.',
                [
                    'status' => 'success',
                    'lifecycle_status' => $response['lifecycle_status'],
                    'mandate_reference' => $debitOrder->reference,
                ],
                (int) $response['response_code']
            );
        }

        return $this->errorResponse($response['success_msg'] ?? 'Unable to create mandate.', null, 500);
    }

    #[Endpoint(
        operationId: 'verifyMandateOtp',
        title: 'Verify mandate OTP',
        description: 'Verify OTP for a payment mandate using mandate reference.'
    )]
    #[BodyParameter('reference', description: 'Mandate reference number.', type: 'string', required: true, example: 'DO0000123')]
    #[BodyParameter('otpPin', description: 'OTP received by customer.', type: 'string', required: true, example: '123456')]
    #[Response(200, 'OTP verified successfully.')]
    #[Response(422, 'Validation failed.')]
    #[Response(404, 'Mandate not found.')]
    public function verifyOtp(Request $request)
    {
        //validation
        $validator = Validator::make($request->all(), [
            'reference' => 'required',
            'otpPin' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed.', $validator->errors(), 422);
        }

        $debit_order = $this->findMandateByReference($request->reference);

        if (!$debit_order) {
            return $this->errorResponse('Mandate not found.', null, 404);
        }

        $result = $this->paymentMandateWorkflowService->verifyOtp($debit_order->uuid, $request->otpPin, Auth::id());
        $success = $result['success'];
        $status = $success ? 'success' : 'failed';
        $response_code = $result['response_code'];
        $remarks = $result['success_msg'];

        //response
        if (!$success) {
            return $this->errorResponse($remarks ?: 'OTP verification failed.', null, (int) $response_code, [
                'status' => $status,
                'mandate_reference' => $debit_order->reference,
            ]);
        }

        return $this->successResponse($remarks ?: 'OTP verified successfully.', [
            'status' => $status,
            'mandate_reference' => $debit_order->reference,
        ], (int) $response_code);
    }

    #[Endpoint(
        operationId: 'createMandateOtp',
        title: 'Create mandate OTP',
        description: 'Requests or resends OTP for a mandate using mandate reference.'
    )]
    #[BodyParameter('reference', description: 'Mandate reference number.', type: 'string', required: true, example: 'DO0000123')]
    #[Response(200, 'OTP created successfully.', type: 'array{
    success: bool,
    message: string,
    data: array{
        status: string,
        mandate_reference: string
    }}')]
    #[Response(422, 'Validation failed.')]
    #[Response(404, 'Mandate not found.')]
    public function createOtp(Request $request): JsonResponse
    {
        //validation
        $validator = Validator::make($request->all(), [
            'reference' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed.', $validator->errors(), 422);
        }

        $debit_order = $this->findMandateByReference($request->reference);

        if (!$debit_order) {
            return $this->errorResponse('Mandate not found.', null, 404);
        }

        $result = $this->paymentMandateWorkflowService->resendOtp($debit_order->uuid, Auth::id());
        $success = $result['success'];
        $status = $success ? 'success' : 'failed';
        $response_code = $result['response_code'];
        $remarks = $result['success_msg'];

        //response
        if (!$success) {
            return $this->errorResponse($remarks ?: 'OTP request failed.', null, (int) $response_code, [
                'status' => $status,
                'mandate_reference' => $debit_order->reference,
            ]);
        }

        return $this->successResponse($remarks ?: 'OTP created successfully.', [
            'status' => $status,
            'mandate_reference' => $debit_order->reference,
        ], (int) $response_code);
    }

    #[Endpoint(
        operationId: 'fetchMandateStatus',
        title: 'Fetch mandate status',
        description: 'Fetches the latest mandate status from the payment channel.'
    )]
    #[BodyParameter('reference', description: 'Mandate reference number.', type: 'string', required: true, example: 'DO0000123')]
    #[Response(200, 'Mandate status fetched successfully.', type: 'array{
    success: bool,
    message: string,
    data: array{
        status: string,
        lifecycle_status: string,
        mandate_reference: string
    }}')]
    #[Response(422, 'Validation failed.')]
    #[Response(404, 'Mandate not found.')]
    public function fetchStatus(Request $request): JsonResponse
    {
        //validation
        $validator = Validator::make($request->all(), [
            'reference' => 'required'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed.', $validator->errors(), 422);
        }

        $debit_order = $this->findMandateByReference($request->reference);

        if (!$debit_order) {
            return $this->errorResponse('Mandate not found.', null, 404);
        }

        $result = $this->paymentMandateWorkflowService->fetchStatus($debit_order->uuid);
        $success = $result['success'];
        $status = $success ? 'success' : 'failed';
        $response_code = $result['response_code'];
        $remarks = $result['success_msg'];

        // Always use shared status mapping for both NMB and non-NMB channels.
        $debit_order->refresh();
        $mandate_status_badge = mandate_status_badge($debit_order);
        $mandate_status_text = $mandate_status_badge ? trim(strip_tags($mandate_status_badge)) : 'Unknown Status';
        $remarks = $debit_order->remarks;

        //response
        if (!$success) {
            return $this->errorResponse($remarks ?: 'Mandate status fetch failed.', null, (int) $response_code, [
                'status' => $status,
                'lifecycle_status' => $mandate_status_text,
                'mandate_reference' => $debit_order->reference,
            ]);
        }

        return $this->successResponse($remarks ?: 'Mandate status fetched successfully.', [
            'status' => $status,
            'mandate_status' => $mandate_status_text,
            'mandate_reference' => $debit_order->reference,
        ], (int) $response_code);
    }

    #[Endpoint(
        operationId: 'cancelPaymentMandate',
        title: 'Cancel mandate',
        description: 'Initiates cancellation for a mandate using mandate reference and cancellation reason.'
    )]
    #[BodyParameter('reference', description: 'Mandate reference number.', type: 'string', required: true, example: 'DO0000123')]
    #[BodyParameter('reasons', description: 'Reason for cancelling the mandate.', type: 'string', required: true, example: 'Customer requested cancellation')]
    #[Response(201, 'Mandate cancelled successfully.', type: 'array{
    success: bool,
    message: string,
    data: array{
        status: string,
        lifecycle_status: string,
        provider_response_code: int|null,
        mandate_reference: string
    }}')]
    #[Response(422, 'Validation failed.')]
    #[Response(404, 'Mandate not found.')]
    public function cancellation(Request $request): JsonResponse
    {
        //validation
        $validator = Validator::make($request->all(), [
            'reference' => 'required',
            'reasons' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed.', $validator->errors(), 422);
        }

        $debit_order = $this->findMandateByReference($request->reference);

        if (!$debit_order) {
            return $this->errorResponse('Mandate not found.', null, 404);
        }

        $result = $this->paymentMandateWorkflowService->cancel($debit_order, Auth::id(), $request->reasons);
        $status = $result['success'] ? 'success' : 'failed';

        if (!$result['success']) {
            return $this->errorResponse($result['success_msg'] ?: 'Mandate cancellation failed.', null, (int) $result['response_code'], [
                'status' => $status,
                'lifecycle_status' => $result['lifecycle_status'],
                'provider_response_code' => $result['provider_response_code'],
                'mandate_reference' => $debit_order->reference,
            ]);
        }

        return $this->successResponse($result['success_msg'] ?: 'Mandate cancelled successfully.', [
            'status' => $status,
            'lifecycle_status' => $result['lifecycle_status'],
            'provider_response_code' => $result['provider_response_code'],
            'mandate_reference' => $debit_order->reference,
        ], (int) $result['response_code']);
    }

    //Check if partner has payment channel
    protected function checkIfPartnerHasPaymentChannel($partner_id, $account_id)
    {
        $partner_account = PartnerAccount::where(["partner_id" => $partner_id, "account_id" => $account_id])->first();

        if ($partner_account)
            return true;
        else
            return false;
    }

    protected function findMandateByReference(string $reference): ?DebitOrder
    {
        return $this->paymentMandateQueryService
            ->baseQuery([], Auth::user())
            ->where('reference', $reference)
            ->first();
    }
}
