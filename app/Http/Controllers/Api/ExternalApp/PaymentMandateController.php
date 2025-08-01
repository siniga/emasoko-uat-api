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
use App\Models\Partner;
use App\Models\PartnerAccount;
use Barryvdh\DomPDF\Facade\Pdf;

class PaymentMandateController extends Controller
{
    public function __construct(protected DeductionService $deductionService, protected BankPayloadService $payloadService, protected BankApiService $bankApi) {}

    //Filter Mandates
    protected function all(Request $request)
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
    protected function getMandate(Request $request)
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
    protected function create(Request $request)
    {
        //post data
        $total_amount = floatval(str_replace(',', '', $request->input('total_amount')));
        $installment_amount = floatval(str_replace(',', '', $request->input('installment_amount')));
        $min_installment_amount = floatval(str_replace(',', '', $request->input('min_installment_amount')));
        $max_installment_amount = floatval(str_replace(',', '', $request->input('max_installment_amount')));

        //validation
        $validator = Validator::make($request->all(), [
            'channel' => 'required', //BANK or MNO
            'payment_channel' => 'required', //NMB, CRDB, VODACOM, TIGO
            'corporate_reference' => 'required',
            'account_name' => 'required_if:debit_type,==,BANK',
            'account_number' => 'required_if:debit_type,==,BANK',
            'phone' => 'required',
            'NIN' => 'required',
            'address' => 'required',
            'total_amount' => [
                'required',
                function ($attribute, $value, $fail) use ($total_amount) {
                    if ($total_amount < 1000) {
                        return $fail('Total amount should be greater than or equal to 1,000');
                    }
                }
            ],
            'debit_type' => 'required', //FIXED or DYNAMIC
            'installment_amount' => [
                'required_if:debit_type,==,FIXED',
                function ($attribute, $value, $fail) use ($installment_amount) {
                    if ($installment_amount < 1000) {
                        return $fail('Installment amount should be greater than or equal to 1,000');
                    }
                }
            ],
            'min_installment_amount' => 'required_if:debit_type,==,DYNAMIC',
            'max_installment_amount' => [
                'required_if:debit_type,==,DYNAMIC',
                function ($attribute, $value, $fail) use ($min_installment_amount, $max_installment_amount) {
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
        $debit_order = new DebitOrder();
        $debit_order->uuid = Str::uuid();
        $debit_order->partner_id = $partner_id;
        $debit_order->reference = Carbon::now()->format('dmY') . $this->deductionService->getMandateReference();
        $debit_order->customer_account_id = $customer_account->id;
        $debit_order->channel = $customer_account->account->slug;
        $debit_order->total_amount = $total_amount;
        $debit_order->outstanding_amount = $total_amount;
        $debit_order->debit_type = $request->debit_type;
        $debit_order->installment_amount = $installment_amount;
        $debit_order->min_installment_amount = $min_installment_amount;
        $debit_order->max_installment_amount = $max_installment_amount;
        $debit_order->periodicity = $request->periodicity;
        $debit_order->installment_number = $this->deductionService->calculateNumberOfInstallments($request->debit_type, $total_amount, $installment_amount, $max_installment_amount);
        $debit_order->start_date = Carbon::parse($request->deduction_start_date)->format('Y-m-d');
        $debit_order->next_debit_date = Carbon::parse($request->deduction_start_date)->format('Y-m-d');
        $debit_order->deduction_end_date = "totalLoanCovered";
        $debit_order->otp_code = $otp_code;
        $debit_order->approved = 0;
        $debit_order->created_by = Auth::user()->id;
        $debit_order->save();

        if ($debit_order) {
            // add debit end date
            $debit_order->end_date = $this->deductionService->calculateEndDate($debit_order);
            $debit_order->save();

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
            $filename = $this->createEvidence($debit_order);
            $debit_order->aggrement_evidence = $filename;
            $debit_order->status = 100;
            $debit_order->save();

            //2: Mandate registration => NMB or CRDB
            //2.1: check if account is NMB/CRDB/EcoBank/NBC
            if ($request->payment_channel == "NMB") {
                //create payload
                $payload = $this->payloadService->mandateRegistrationPayload($debit_order);

                //arr response
                $arr_response = $this->bankApi->mandateRegistrationRequest($payload);

                if ($arr_response) {
                    $response_code = $arr_response[0];
                    if ($response_code == 201) {
                        //return success message
                        return response()->json([
                            'success' => true,
                            'response_code' => 200,
                            'success_msg' => "Mandate created successfully.",
                            'data' => [
                                'status' => 'success',
                                'mandateReference' => $debit_order->reference,
                            ]
                        ]);
                    } else {
                        // error response
                        return response()->json([
                            'success' => false,
                            'response_code' => 500,
                            'error_msg' => $arr_response[1]
                        ]);
                    }
                } else {
                    // error response
                    return response()->json([
                        'success' => false,
                        'response_code' => 500,
                        'error_msg' => "API system error, contact eMakato support team."
                    ]);
                }
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
    protected function verifyOtp(Request $request)
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
    protected function createOtp(Request $request)
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
    protected function fetchStatus(Request $request)
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

        //declare channel and remarks
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
                if ($response_code == 240 & $debit_order->approved == 1) {
                    $this->deductionService->createInstallment($debit_order);

                    //update response code
                    $response_code = 202;
                } else {
                    $success = false;
                    $response_code = 500;
                    $status = 'failed';
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

    //mandate cancellation
    protected function cancellation(Request $request)
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

        //declare channel and remarks
        $success = true;
        $status = 'success';
        $response_code = 200;
        $remarks = "";

        //check if account is NMB/CRDB/EcoBank/NBC
        if ($debit_order->channel == "NMB") {
            //create payload
            $payload = $this->payloadService->mandateCancellationPayload($debit_order);

            //api response
            $arr_response = $this->bankApi->mandateCancellationRequest($payload);

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
