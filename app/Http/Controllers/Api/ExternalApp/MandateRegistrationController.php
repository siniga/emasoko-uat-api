<?php

namespace App\Http\Controllers\Api\ExternalApp;

use App\Classes\Messaging;
use App\Http\Controllers\Controller;
use App\Jobs\CRDBMandateRegistrationJob;
use App\Jobs\MandateRegistrationJob;
use App\Models\Account;
use App\Models\Customer;
use App\Models\CustomerAccount;
use App\Models\CustomerPartner;
use App\Models\DebitOrder;
use App\Models\OtpRequest;
use App\Models\PartnerAccount;
use App\Models\PartnerUser;
use App\Services\BankApiService;
use App\Services\BankPayloadService;
use App\Services\DeductionService;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;

class MandateRegistrationController extends Controller
{
    public function __construct(protected DeductionService $deductionService, protected BankPayloadService $payloadService, protected Messaging $messaging, protected BankApiService $bankApi) {}

    //create payment mandate
    public function create(Request $request)
    {
        //validation
        $validator = Validator::make($request->all(), [
            'channel' => 'required', //BANK or MNO
            'payment_channel' => 'required', //NMB, CRDB, VODACOM, TIGO
            'account_name' => 'required_if:debit_type,==,BANK',
            'account_number' => 'required_if:debit_type,==,BANK',
            'phone' => 'required',
            'NIN' => 'required',
            'address' => 'required',
            'total_amount' => 'required',
            'debit_type' => 'required', //FIXED or DYNAMIC
            'min_installment_amount' => 'required_if:debit_type,==,DYNAMIC',
            'min_installment_amount' => 'required_if:debit_type,==,DYNAMIC',
            'installment_amount' => 'required_if:debit_type,==,FIXED',
            'periodicity' => 'required', //MONTHLY, WEEKLY, DAILY, YEARLY
            'deduction_start_date' => 'required'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'response_code' => 403,
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
        $customer = Customer::firstOrNew(['phone' => $unique_phone,]);
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

        //1.8: Request data for debit order
        $total_amount = floatval(str_replace(',', '', $request->input('total_amount')));
        $installment_amount = floatval(str_replace(',', '', $request->input('installment_amount')));
        $min_installment_amount = floatval(str_replace(',', '', $request->input('min_installment_amount')));
        $max_installment_amount = floatval(str_replace(',', '', $request->input('max_installment_amount')));

        //1.8.1: generate otp
        $otp_code = rand(10000, 99999);

        //1.8.2: Get Partner Id
        $partner_id = $this->getPartnerId(Auth::user()->id);

        //1.8.3: Check if user has linked to any service provider
        if ($partner_id == null) {
            return response()->json([
                'success' => false,
                'response_code' => 403,
                'error_msg' => "User has not linked to any service provider, Contact eMakato Support Team."
            ]);
        }

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
        $debit_order->customer_account_id = $customer_account->id;
        $debit_order->total_amount = $total_amount;
        $debit_order->outstanding_amount = $total_amount;
        $debit_order->reference = Carbon::now()->format('dmY') . rand(1000, 1999);
        $debit_order->debit_type = $request->debit_type;
        $debit_order->installment_amount = $installment_amount;
        $debit_order->min_installment_amount = $min_installment_amount;
        $debit_order->max_installment_amount = $max_installment_amount;
        $debit_order->periodicity = $request->periodicity;
        $debit_order->installment_number = $this->calculateNumberOfInstallments($request->debit_type, $total_amount, $installment_amount, $max_installment_amount); //calculate number of installment
        $debit_order->start_date = $request->deduction_start_date;
        $debit_order->next_debit_date = $request->deduction_start_date;
        $debit_order->deduction_end_date = "totalLoanCovered";
        $debit_order->otp_code = $otp_code;
        $debit_order->approved = 0;
        $debit_order->created_by = auth()->id();
        $debit_order->save();

        if ($debit_order) {
            //create deduction installments
            $response = $this->deductionService->createInstallment($debit_order);

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

                //Create Request to NMB
                dispatch(new MandateRegistrationJob($payload));
            } elseif ($request->payment_channel == "CRDB") {
                //requestId
                $requestId = Str::uuid()->toString();

                //create payload
                $payload = $this->payloadService->CRDBMandatePayload($debit_order, $requestId);

                //create data
                $data['payload'] = $payload;
                $data['requestId'] = $requestId;
                $data['mandateReferenceNumber'] = $debit_order->reference;

                //Create Request to CRDB
                dispatch(new CRDBMandateRegistrationJob($data));
            }

            //return success message
            return response()->json([
                'success' => true,
                'response_code' => 200,
                'data' => [
                    'mandateReference' => $debit_order->reference,
                ]
            ]);
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
                'response_code' => 403,
                'error_msg' => $validator->errors()
            ], 422);
        }

        //debit order
        $debit_order = DebitOrder::where("reference", $request->reference)->first();

        //create otp request
        $otp_request = new OtpRequest();
        $otp_request->reference = $debit_order->reference;
        $otp_request->account_number = $debit_order->customerAccount->account_number;
        $otp_request->action = 'verify';
        $otp_request->created_by = Auth::user()->id;
        $otp_request->save();

        //create payload
        $payload = $this->payloadService->verifyOtpPayload($otp_request, $request->otpPin);
        Log::debug("== Payload ==");
        Log::debug($payload);

        //passing data
        $accountNumber = $debit_order->customerAccount->account_number;
        $otpReference = $debit_order->reference;

        //api service
        $http_response_code = $this->bankApi->verifyOtpRequest($payload, $accountNumber, $otpReference);

        if ($http_response_code == 202) {
            return response()->json(
                [
                    'success' => true,
                    'response_code' => 200,
                    'success_msg' => "OTP verified",
                ],
                200
            );
        } else if ($http_response_code == 500) {
            return response()->json(
                [
                    'success' => false,
                    'response_code' => 400,
                    'error_msg' => "Failed to verify OTP, Try again or resend OTP."
                ],
                200
            );
        } else {
            return response()->json(
                [
                    'success' => false,
                    'response_code' => 400,
                    'error_msg' => "Failed to verify OTP, Try again or contact support."
                ],
                200
            );
        }
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
                'response_code' => 403,
                'error_msg' => $validator->errors()
            ], 422);
        }

        //debit order
        $debit_order = DebitOrder::where("reference", $request->reference)->first();

        //create otp request
        $otp_request = new OtpRequest();
        $otp_request->reference = $debit_order->reference;
        $otp_request->account_name = $debit_order->customerAccount->account_name;
        $otp_request->account_number = $debit_order->customerAccount->account_number;
        $otp_request->action = 'create';
        $otp_request->created_by = Auth::user()->id;
        $otp_request->save();

        //create payload
        $payload = $this->payloadService->createOtpPayload($otp_request);

        //create otp
        $http_response_code = $this->bankApi->createOtpRequest($payload);

        if ($http_response_code == 200) {
            return response()->json(
                [
                    'success' => true,
                    'response_code' => 200,
                    'success_msg' => "New OTP sent",
                ],
                200
            );
        } else if ($http_response_code == 500) {
            return response()->json(
                [
                    'success' => false,
                    'response_code' => 400,
                    'error_msg' => "Failed to send OTP, Try resend OTP."
                ],
                200
            );
        } else {
            return response()->json(
                [
                    'success' => false,
                    'response_code' => 400,
                    'error_msg' => "Failed to send OTP, Try resend OTP.",
                ]
            );
        }
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

    //Get Partner Id
    protected function getPartnerId($user_id)
    {
        $partner = PartnerUser::where(['user_id' => $user_id])->first();

        if ($partner) {
            return $partner->partner->id;
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

    //calculate number of installment
    protected function calculateNumberOfInstallments($debit_type, $total_amount, $iamount, $max_amount)
    {
        $numberInstallment = 0;

        if ($debit_type == "FIXED") {
            $numberInstallment = floatval($total_amount) / floatval($iamount);
            return ceil($numberInstallment); //$numberInstallment;
        } else if ($debit_type == "DYNAMIC") {
            $numberInstallment = floatval($total_amount) / floatval($max_amount);
            return ceil($numberInstallment); //$numberInstallment;
        }
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
