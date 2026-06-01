<?php

namespace App\Http\Controllers\Api\ExternalApp;

use App\Http\Controllers\Api\ExternalApp\Concerns\RespondsWithApi;
use App\Http\Controllers\Controller;
use App\Http\Resources\MandateResource;
use Illuminate\Http\Request;
use App\Models\DebitOrder;
use App\Services\DeductionService;
use App\Services\PaymentMandatePartyService;
use App\Services\PaymentMandateQueryService;
use App\Services\PaymentMandateWorkflowService;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Carbon\Carbon;
use App\Models\Account;
use App\Models\Installment;
use App\Models\Partner;
use App\Models\PartnerAccount;
use App\Services\MandateActionService;

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

    //summaries
    public function summaries(Request $request)
    {
        $approvedStatuses = [0, 1, 2]; // status

        //current year and month
        $current_year = date('Y');
        $current_month = date('m');
        $channel = $request->channel;

        //query
        $debit_orders = DebitOrder::mandateApprovalStats($channel);

        // Map over all approved statuses to ensure complete results
        $mandates = collect($approvedStatuses)->map(function ($status) use ($debit_orders) {
            $order = $debit_orders->firstWhere('approved', $status);
            return [
                'approved' => $status,
                'number_of_mandate' => $order ? $order->number_of_mandate : 0,
                'total_amount' => $order ? $order->total_amount : 0,
            ];
        });

        //total number of mandates
        $total_number_of_mandate = $mandates[0]['number_of_mandate'] + $mandates[1]['number_of_mandate'] + $mandates[2]['number_of_mandate'];

        //summation of mandate values
        $total_mandate_value = $mandates[0]['total_amount'] + $mandates[1]['total_amount'] + $mandates[2]['total_amount'];

        // This Month’s Collection
        $this_month = Installment::thisMonthStats($current_month, $current_year, $channel);

        // Today’s Collection
        $today = Installment::todayStats(now()->toDateString(), $channel);

        //stats
        $stats  = [
            //number of mandates
            'number_of_pending_mandate' =>  $mandates[0]['number_of_mandate'],
            'number_of_approved_mandate' =>  $mandates[1]['number_of_mandate'],
            'number_of_failed_mandate' =>  $mandates[2]['number_of_mandate'],
            'total_number_of_mandate' => $total_number_of_mandate,

            //mandates values
            'pending_mandate_value' => $mandates[0]['total_amount'],
            'approved_mandate_value' => $mandates[1]['total_amount'],
            'failed_mandate_value' => $mandates[2]['total_amount'],
            'total_mandate_value' => $total_mandate_value,

            //this month and today collection
            'month' => date('F'),
            'this_month_expected' => $this_month->total_amount,
            'this_month_paid' => $this_month->paid_amount,
            'today_expected' => $today->total_amount,
            'today_paid' => $today->paid_amount
        ];

        //response
        return $this->successResponse('Mandate summary fetched successfully.', $stats);
    }

    //Filter Mandates
    public function all(Request $request)
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

    //get mandate by reference
    public function getMandate(Request $request)
    {
        $debit_order = $this->findMandateByReference($request->reference);

        if (!$debit_order) {
            return $this->errorResponse('Mandate not found.', null, 404);
        }

        return $this->successResponse('Mandate fetched successfully.', [
            'mandate' => new MandateResource($debit_order),
        ]);
    }

    //create payment mandate
    public function create(Request $request)
    {
        //validation
        $validator = Validator::make($request->all(), [
            'channel' => 'required', //BANK or MNO
            'payment_channel' => 'required', //NMB, CRDB, VODACOM, TIGO
            'corporate_reference' => 'required',
            'account_name' => 'required_if:channel,BANK',
            'account_number' => 'required_if:channel,BANK',
            'phone' => 'required',
            'NIN' => 'required',
            'address' => 'required',
            'total_amount' => [
                'required',
                function ($attribute, $value, $fail) {
                    $amount = floatval(str_replace(',', '', (string) $value));
                    if ($amount < 1000) {
                        return $fail('Total amount should be greater than or equal to 1,000');
                    }
                }
            ],
            'debit_type' => 'required', //FIXED or DYNAMIC
            'installment_amount' => [
                'required_if:debit_type,FIXED',
                function ($attribute, $value, $fail) {
                    $amount = floatval(str_replace(',', '', (string) $value));
                    if ($amount < 1000) {
                        return $fail('Installment amount should be greater than or equal to 1,000');
                    }
                }
            ],
            'min_installment_amount' => 'required_if:debit_type,DYNAMIC',
            'max_installment_amount' => [
                'required_if:debit_type,DYNAMIC',
                function ($attribute, $value, $fail) use ($request) {
                    $min_installment_amount = floatval(str_replace(',', '', (string) $request->input('min_installment_amount')));
                    $max_installment_amount = floatval(str_replace(',', '', (string) $value));
                    if ($min_installment_amount > $max_installment_amount) {
                        return $fail('Maximum installment amount should not be less than minimum deduction amount');
                    }
                }
            ],
            'periodicity' => 'required', //MONTHLY, WEEKLY, DAILY, YEARLY
            'deduction_start_date' => 'required|date|after_or_equal:today'
        ]);

        $validator->after(function ($validator) use ($request) {
            $accountTypeId = ($request->channel === 'BANK') ? 1 : 2;
            $account = Account::where([
                'account_type_id' => $accountTypeId,
                'slug' => $request->payment_channel,
            ])->first();

            if (!$account) {
                $validator->errors()->add('payment_channel', 'Selected payment channel was not found.');
                return;
            }

            if (!$account->supportsPeriodicity($request->periodicity)) {
                $validator->errors()->add('periodicity', 'Selected periodicity is not supported for this payment channel.');
            }
        });

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed.', $validator->errors(), 422);
        }

        $total_amount = floatval(str_replace(',', '', $request->input('total_amount')));
        $installment_amount = floatval(str_replace(',', '', $request->input('installment_amount')));
        $min_installment_amount = floatval(str_replace(',', '', $request->input('min_installment_amount')));
        $max_installment_amount = floatval(str_replace(',', '', $request->input('max_installment_amount')));

        $account_type_id = ($request->channel == "BANK") ? 1 : 2;
        $account_id = $this->getAccountId($account_type_id, $request->payment_channel);

        $partner  = Partner::where('reference', $request->corporate_reference)->first();

        if (!$partner) {
            return $this->errorResponse('Partner not found.', null, 404);
        }

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
            'address' => $request->address,
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
                    'redirect_url' => $response['redirect_url'] ?? null,
                ],
                (int) $response['response_code']
            );
        }

        return $this->errorResponse($response['success_msg'] ?? 'Unable to create mandate.', null, 500);
    }

    //OTP Verification
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

    //create OTP
    public function createOtp(Request $request)
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

    //query mandate status
    public function fetchStatus(Request $request)
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
                'mandate_status' => $mandate_status_text,
                'mandate_reference' => $debit_order->reference,
            ]);
        }

        return $this->successResponse($remarks ?: 'Mandate status fetched successfully.', [
            'status' => $status,
            'mandate_status' => $mandate_status_text,
            'mandate_reference' => $debit_order->reference,
        ], (int) $response_code);
    }

    //mandate cancellation
    public function cancellation(Request $request)
    {
        //validation
        $validator = Validator::make($request->all(), [
            'reference' => 'required',
            'reasons' => 'required',
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed.', $validator->errors(), 422);
        }

        // debit order
        $debit_order = DebitOrder::where(['reference' => $request->reference])->first();

        if (!$debit_order) {
            return $this->errorResponse('Mandate not found.', null, 404);
        }

        //save cancellation information
        $debit_order->cancellation_reason = $request->reasons;
        $debit_order->cancellationStartedBy = Auth::user()->id;
        $debit_order->cancellationStartedAt = date('Y-m-d H:i:s');
        $debit_order->cancellationApprovedBy = Auth::user()->id;
        $debit_order->cancellationApprovedAt = date('Y-m-d H:i:s');
        $debit_order->save();

        //return response
        $success = true;
        $status = 'success';
        $response_code = 201;
        $remarks = "Mandate cancellation request sent successfully. Wait for few minutes for the cancellation to take effect.";

        // request mandate cancellation in background
        $this->mandateActionService->cancelMandate($debit_order);

        //response
        return $this->successResponse($remarks, [
            'status' => $status,
            'mandate_reference' => $debit_order->reference,
        ], $response_code);
    }

    //Get Customer Account Id
    protected function getAccountId($account_type_id, $payment_channel)
    {
        $account = Account::where(['account_type_id' => $account_type_id, 'slug' => $payment_channel])->first();

        if ($account) {
            return $account->id;
        } else {
            return null;
        }
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
