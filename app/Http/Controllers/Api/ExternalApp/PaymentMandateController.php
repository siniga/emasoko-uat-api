<?php

namespace App\Http\Controllers\Api\ExternalApp;

use App\Http\Controllers\Controller;
use App\Http\Resources\MandateResource;
use App\Models\Customer;
use App\Models\CustomerAccount;
use Illuminate\Http\Request;
use App\Models\CustomerPartner;
use App\Models\DebitOrder;
use App\Models\OtpRequest;
use App\Services\BankApiService;
use App\Services\BankPayloadService;
use App\Services\DeductionService;
use Carbon\Carbon;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Log;
use App\Jobs\MandateRegistrationJob;
use App\Jobs\CRDBMandateRegistrationJob;
use App\Models\Account;
use App\Models\Installment;
use App\Models\Partner;
use App\Models\PartnerAccount;
use App\Services\MandateActionService;
use Barryvdh\DomPDF\Facade\Pdf;

class PaymentMandateController extends Controller
{
    public function __construct(protected MandateActionService $mandateActionService, protected DeductionService $deductionService, protected BankPayloadService $payloadService, protected BankApiService $bankApi) {}

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
        return response()->json([
            'success' => true,
            'response_code' => 200,
            'data' => $stats,
        ]);
    }

    //Filter Mandates
    public function all(Request $request)
    {
        //all request
        $requests = $request->all();

        //debit orders
        $debit_orders = DebitOrder::filterCondition();

        $debit_orders = $debit_orders->when($requests, function ($query) use ($requests) {
            $query->withFilters($requests);
        });

        //debit orders
        $debit_orders = $debit_orders->orderBy('debit_orders.created_at', 'DESC')->get();

        //response
        return response()->json([
            'success' => true,
            'response_code' => 200,
            'mandates' => MandateResource::collection($debit_orders)
        ]);
    }

    //get mandate by reference
    public function getMandate(Request $request)
    {
        // debit order
        $debit_order = DebitOrder::where(['reference' => $request->reference])->first();

        if (!$debit_order) {
            return response()->json([
                'success' => false,
                'response_code' => 404,
                'message' => "Mandate not found",
            ]);
        }

        //response
        return response()->json([
            'success' => true,
            'response_code' => 200,
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

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'response_code' => 422,
                'error_msg' => $validator->errors()
            ], 422);
        }

        //post data
        $total_amount = floatval(str_replace(',', '', $request->input('total_amount')));
        $installment_amount = floatval(str_replace(',', '', $request->input('installment_amount')));
        $min_installment_amount = floatval(str_replace(',', '', $request->input('min_installment_amount')));
        $max_installment_amount = floatval(str_replace(',', '', $request->input('max_installment_amount')));

        //Create or Update mandate
        //1.1: Detect account_type id
        $account_type_id = ($request->channel == "BANK") ? 1 : 2;

        //1.2: Detect account_id
        $account_id = $this->getAccountId($account_type_id, $request->payment_channel);

        //1.3: Format uuid and unique phone
        $customer_account_uuid = Str::uuid();
        $unique_phone =  casting_phone($request->phone);

        //1.4: TODO: Validate phone number to MNO for matching details

        //1.5: get Bank code if account_type_id = 1
        $bank_code = null;
        if ($request->channel == "BANK")
            $bank_code = substr($request->account_number, 0, 3);

        //1.6: Create or Update Customer
        $customer = Customer::firstOrNew(['phone' => $unique_phone]);
        $customer->fullname = strtoupper($request->account_name);
        $customer->email = $request->email;
        $customer->id_number = $request->NIN;
        $customer->address = $request->address;
        $customer->created_by = Auth::user()->id;
        $customer->updated_by = Auth::user()->id;
        $customer->save();

        //1.7: Create or Update Customer Account
        $customer_account = CustomerAccount::firstOrNew([
            'account_type_id' => $account_type_id,
            'account_id' => $account_id,
            'phone' => $unique_phone
        ]);
        $customer_account->uuid = $customer_account_uuid;
        $customer_account->secondary_phone = $request->secondary_phone;
        $customer_account->account_name = $request->account_name;
        $customer_account->account_number = $request->account_number;
        $customer_account->bank_code = $bank_code;
        $customer_account->customer_id = $customer->id;
        $customer_account->created_by = Auth::user()->id;
        $customer_account->updated_by = Auth::user()->id;
        $customer_account->save();

        //1.8.1: generate otp
        $otp_code = rand(10000, 99999);

        //1.8.2: Get Partner by corporate reference
        $partner  = Partner::where('reference', $request->corporate_reference)->first();

        if (!$partner) {
            return response()->json([
                'success' => false,
                'response_code' => 404,
                'error_msg' => "Partner not found"
            ]);
        }

        //partnerID
        $partner_id = $partner->id;

        //1.8.4: Check if if partner has a payment channel
        if ($this->checkIfPartnerHasPaymentChannel($partner_id, $account_id) == false) {
            return response()->json([
                'success' => false,
                'partner' => $partner_id,
                'account_id' => $account_id,
                'response_code' => 403,
                'error_msg' => "Partner has not linked to any payment channel, Contact eMakato Support Team."
            ]);
        }

        //1.9: Create Debit Order
        $debitOrder = new DebitOrder();
        $debitOrder->uuid = Str::uuid();
        $debitOrder->partner_id = $partner_id;
        $debitOrder->reference = Carbon::now()->format('dmY') . $this->deductionService->getMandateReference();
        $debitOrder->customer_account_id = $customer_account->id;
        $debitOrder->channel = $customer_account->account->slug;
        $debitOrder->total_amount = $total_amount;
        $debitOrder->outstanding_amount = $total_amount;
        $debitOrder->debit_type = $request->debit_type;
        $debitOrder->installment_amount = $installment_amount;
        $debitOrder->min_installment_amount = $min_installment_amount;
        $debitOrder->max_installment_amount = $max_installment_amount;
        $debitOrder->periodicity = $request->periodicity;
        $debitOrder->installment_number = $this->deductionService->calculateNumberOfInstallments($request->debit_type, $total_amount, $installment_amount, $max_installment_amount);
        $debitOrder->start_date = Carbon::parse($request->deduction_start_date)->format('Y-m-d');
        $debitOrder->next_debit_date = Carbon::parse($request->deduction_start_date)->format('Y-m-d');
        $debitOrder->deduction_end_date = "totalLoanCovered";
        $debitOrder->otp_code = $otp_code;
        $debitOrder->approved = 0;
        $debitOrder->created_by = Auth::user()->id;
        $debitOrder->save();

        if ($debitOrder) {
            // add debit end date
            $debitOrder->end_date = $this->deductionService->calculateEndDate($debitOrder);
            $debitOrder->save();

            // Link customer with service provider
            $customer_partner = CustomerPartner::firstOrNew(
                [
                    'customer_id' => $customer_account->customer->id,
                    'partner_id' => $partner_id
                ]
            );
            $customer_partner->created_by = Auth::user()->id; //get logged in user
            $customer_partner->save();

            //create evidence of aggrement
            $filename = $this->createEvidence($debitOrder);
            $debitOrder->aggrement_evidence = $filename;
            $debitOrder->status = 100;
            $debitOrder->save();

            //mandate registration
            $jsonResponse = $this->mandateActionService->registerMandate($debitOrder);
            $response = $jsonResponse->getData(true);

            //success = True or False
            $success = $response['success'];

            if ($success == true) {
                return response()->json([
                    'success' => true,
                    'response_code' => 201,
                    'success_msg' => "Mandate created successfully.",
                    'data' => [
                        'status' => 'success',
                        'mandateReference' => $debitOrder->reference,
                    ]
                ]);
            } else {
                // error response
                return response()->json([
                    'success' => false,
                    'response_code' => 500,
                    'error_msg' => $response['remarks']
                ]);
            }
        } else {
            return response()->json([
                'success' => false,
                'response_code' => 400,
                'error_msg' => "Failed to create mandate, review your request and submit again."
            ]);
        }
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
            return response()->json([
                'success' => false,
                'response_code' => 422,
                'error_msg' => $validator->errors()
            ], 422);
        }

        // debit order
        $debit_order = DebitOrder::where(['reference' => $request->reference])->first();

        if (!$debit_order) {
            return response()->json([
                'success' => false,
                'response_code' => 404,
                'error_msg' => "Mandate not found",
            ]);
        }

        //otp reference
        $otpReference = $debit_order->reference;

        //create otp request
        $otp_request = new OtpRequest();
        $otp_request->reference = $otpReference;
        $otp_request->account_number = $debit_order->customerAccount->account_number;
        $otp_request->action = 'verify';
        $otp_request->created_by = Auth::user()->id;
        $otp_request->save();

        //declare channel and remarks
        $success = true;
        $status = 'success';
        $response_code = 200;
        $remarks = "";

        //check if account is NMB/CRDB/EcoBank/NBC
        if ($debit_order->channel == "NMB") {
            //create payload
            $payload = $this->payloadService->verifyOtpPayload($otp_request, $request->otpPin);

            //api service
            $arr_response = $this->bankApi->verifyOtpRequest($payload, $otpReference);

            if ($arr_response) {
                $response_code = $arr_response[0];
                $remarks = $arr_response[1];

                if ($response_code == 202) {
                    //update response code
                    $response_code = 202;
                } else {
                    $success = false;
                    $status = 'failed';
                    $response_code = 500;
                }
            } else {
                $success = false;
                $status = 'failed';
                $remarks = "API system error, contact eMakato support team.";
                $response_code = 500;
            }
        }

        //response
        return response()->json(
            [
                'success' => $success,
                "response_code" => $response_code,
                "message" => $remarks,
                'data' => [
                    'status' => $status,
                    'mandateReference' => $debit_order->reference,
                ]
            ]
        );
    }

    //create OTP
    public function createOtp(Request $request)
    {
        //validation
        $validator = Validator::make($request->all(), [
            'reference' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'response_code' => 422,
                'error_msg' => $validator->errors()
            ], 422);
        }

        // debit order
        $debit_order = DebitOrder::where(['reference' => $request->reference])->first();

        if (!$debit_order) {
            return response()->json([
                'success' => false,
                'response_code' => 404,
                'error_msg' => "Mandate not found",
            ]);
        }

        //create otp request
        $otp_request = new OtpRequest();
        $otp_request->reference = $debit_order->reference;
        $otp_request->account_name = $debit_order->customerAccount->account_name;
        $otp_request->account_number = $debit_order->customerAccount->account_number;
        $otp_request->action = 'create';
        $otp_request->created_by = Auth::user()->id;
        $otp_request->save();

        //declare channel and remarks
        $success = true;
        $status = 'success';
        $response_code = 200;
        $remarks = "";

        //check if account is NMB/CRDB/EcoBank/NBC
        if ($debit_order->channel == "NMB") {
            //create payload
            $payload = $this->payloadService->createOtpPayload($otp_request);

            //api service
            $arr_response = $this->bankApi->createOtpRequest($payload);

            if ($arr_response) {
                $response_code = $arr_response[0];
                $remarks = $arr_response[1];

                if ($response_code == 201) {
                    //update response code
                    $response_code = 201;
                } else {
                    $success = false;
                    $status = 'failed';
                    $response_code = 500;
                }
            } else {
                $success = false;
                $status = 'failed';
                $remarks = "API system error, contact eMakato support team.";
                $response_code = 500;
            }
        }

        //response
        return response()->json(
            [
                'success' => $success,
                "response_code" => $response_code,
                "message" => $remarks,
                'data' => [
                    'status' => $status,
                    'mandateReference' => $debit_order->reference,
                ]
            ],
            200
        );
    }

    //query mandate status
    public function fetchStatus(Request $request)
    {
        //validation
        $validator = Validator::make($request->all(), [
            'reference' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'response_code' => 422,
                'error_msg' => $validator->errors()
            ], 422);
        }

        // debit order
        $debit_order = DebitOrder::where(['reference' => $request->reference])->first();

        if (!$debit_order) {
            return response()->json([
                'success' => false,
                'response_code' => 404,
                'error_msg' => "Mandate not found",
            ]);
        }

        //declare response
        $success = true;
        $status = 'success';
        $response_code = 200;
        $remarks = "";

        //check if account is NMB/CRDB/EcoBank/NBC
        if ($debit_order->channel == "NMB") {
            //create payload
            $payload = $this->payloadService->mandateStatusPayload($debit_order);

            //api service
            $arr_response = $this->bankApi->mandateStatusRequest($payload);

            if ($arr_response) {
                $response_code = $arr_response[0];
                $remarks = $arr_response[1];

                // Create debit order installments IF response code = 240
                if ($response_code == 240) {
                    $this->deductionService->createInstallment($debit_order);
                }
            } else {
                $success = false;
                $status = 'failed';
                $remarks = "API system error, contact eMakato support team.";
                $response_code = 500;
            }
        }

        // Always use shared status mapping for both NMB and non-NMB channels.
        $debit_order->refresh();
        $mandate_status_badge = mandate_status_badge($debit_order);
        $mandate_status_text = $mandate_status_badge ? trim(strip_tags($mandate_status_badge)) : 'Unknown Status';
        $remarks = $debit_order->remarks;

        //response
        return response()->json(
            [
                'success' => $success,
                "response_code" => $response_code,
                "message" => $remarks,
                'data' => [
                    'status' => $status,
                    'mandateStatus' => $mandate_status_text,
                    'mandateReference' => $debit_order->reference,
                ]
            ]
        );
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
            return response()->json([
                'success' => false,
                'response_code' => 422,
                'error_msg' => $validator->errors()
            ], 422);
        }

        // debit order
        $debit_order = DebitOrder::where(['reference' => $request->reference])->first();

        if (!$debit_order) {
            return response()->json([
                'success' => false,
                'response_code' => 404,
                'error_msg' => "Mandate not found",
            ]);
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
        return response()->json(
            [
                'success' => $success,
                "response_code" => $response_code,
                "message" => $remarks,
                'data' => [
                    'status' => $status,
                    'mandateReference' => $debit_order->reference,
                ]
            ],
            200
        );
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

    //Create Evidence of Agreement
    protected function createEvidence(DebitOrder $debit_order)
    {
        //Create Evidence of Agreement
        view()->share('debit_order', $debit_order);

        //PDF Creation
        $pdf = Pdf::loadView('payment_mandate.pdf.evidence');

        //create filename
        $filename = "{$debit_order->reference}.pdf";

        $path = 'assets/uploads/pdf/';
        $pdf->save($path  . $filename);

        return $filename;
    }
}
