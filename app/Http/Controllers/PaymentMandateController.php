<?php

namespace App\Http\Controllers;

use App\Exports\PaymentMandateExport;
use App\Models\Account;
use App\Models\AccountType;
use App\Models\Customer;
use App\Models\CustomerAccount;
use App\Models\DebitOrder;
use App\Models\PartnerUser;
use App\Rules\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use App\Classes\Messaging;
use App\Http\Requests\CustomerAccountRequest;
use App\Http\Requests\CustomerDeductionRequest;
use App\Jobs\CRDBMandateRegistrationJob;
use App\Jobs\MandateRegistrationJob;
use App\Jobs\SendSMSJob;
use App\Models\CustomerPartner;
use App\Models\Installment;
use App\Models\OtpRequest;
use App\Models\Partner;
use App\Models\User;
use App\Services\BankApiService;
use App\Services\BankPayloadService;
use App\Services\DeductionService;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class PaymentMandateController extends Controller
{

    public function __construct(protected DeductionService $deductionService, protected BankPayloadService $payloadService, protected Messaging $messaging, protected BankApiService $bankApi)
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function lists(Request $request, $status = null)
    {
        $title = 'Payment Mandate';
        //has_allowed('roles', 'lists');

        //debit orders
        $number_of_debit_orders = 0;
        $debit_orders = DebitOrder::filterCondition();

        if ($status) {
            switch ($status) {
                case 'pending':
                    $approved = 0;
                    break;
                case 'approved':
                    $approved = 1;
                    break;
                case 'failed':
                    $approved = 2;
                    break;
                default:
                    $approved = 0;
            }

            //where condition
            $debit_orders->where(['approved' => $approved]);
        }

        if (isset($_POST['filter'])) {
            //all request
            $requests = $request->all();

            $debit_orders = $debit_orders->when($requests, function ($query) use ($requests) {
                $query->withFilters($requests);
            });

            //debit orders
            $number_of_debit_orders = $debit_orders->count();
            $debit_orders = $debit_orders->orderBy('debit_orders.created_at', 'DESC')->get();     
        } else {
            //debit orders
            $number_of_debit_orders = $debit_orders->count();
            $debit_orders = $debit_orders->orderBy('debit_orders.created_at', 'DESC')->paginate(50);
        }

        $post_data = [
            'start_at' => $request->start_at ?? null,
            'end_at' => $request->end_at ?? null,
            'account_type_id' => $request->account_type_id ?? null,
            'account_id' => $request->account_id ?? null,
            'status' => $request->status ?? null,
            'keyword' => $request->keyword ?? null
        ];

        //populate data
        $account_types = AccountType::all();
        $accounts = Account::all();

        //render view
        return view('payment_mandate.lists', compact('title', 'account_types', 'accounts', 'debit_orders', 'number_of_debit_orders' ,'post_data'));
    }

    //export xls
    public function export_xls(Request $request)
    {
        return Excel::download(new PaymentMandateExport($request->start_at, $request->end_at, $request->account_type_id, $request->account_id, $request->status, $request->keyword), date('YmdHis') . '-PaymentMandate.xlsx');
    }

    //export xls
    public function export_pdf(Request $request)
    {
        $requests = $request->all();

        //debit orders
        $debit_orders = DebitOrder::filterCondition();

        $debit_orders = $debit_orders->when($requests, function ($query) use ($requests) {
            $query->withFilters($requests);
        });

        //debit orders
        $debit_orders = $debit_orders->orderBy('debit_orders.created_at', 'DESC')->get();

        //pdf
        $pdf = PDF::loadView('payment_mandate.pdf.report', compact('debit_orders'))->setPaper('a4', 'portrait');

        // download PDF file with download method
        return $pdf->download(date('YmdHis') . '_Payment_Mandate.pdf');
    }

    //payment details form
    public function create_account($customer_id = null, $debit_order_id = null)
    {
        $title = "Payment Mandate";

        //customer account
        $customer_account = [];
        if ($customer_id != null)
            $customer_account = CustomerAccount::where(['uuid' => $customer_id])->first();

        //payment mandate
        $debit_order = [];
        if ($debit_order_id != null)
            $debit_order = DebitOrder::where(['uuid' => $debit_order_id])->first();

        //populate data
        $account_types = AccountType::all();
        $accounts = Account::all();

        //render view
        return view('payment_mandate.create.account_form', compact('title', 'customer_account', 'debit_order', 'account_types', 'accounts'));
    }

    //store payment details
    public function store_account(CustomerAccountRequest $request)
    {
        // validated request
        $validated = $request->validated();

        //customer account ID and debit order ID
        $debit_order_uuid = $request->debit_order_uuid;

        //customer account uuid
        if ($request->customer_account_uuid != null)
            $customer_account_uuid = $request->customer_account_uuid;
        else
            $customer_account_uuid = Str::uuid();

        //unique phone
        if ($validated['account_type_id'] == 1)
            $unique_phone = casting_phone($validated['phone']);
        else if ($request->input('account_type_id') == 2)
            $unique_phone =  casting_phone($validated['account_number_phone']);

        //TODO: validate phone number to MNO for matching details

        //get Bank code if account_type_id = 1
        $bank_code = null;
        if ($validated['account_type_id'] == 1)
            $bank_code = substr($validated['account_number'], 0, 3);

        //create or update customer
        $customer = Customer::firstOrNew(['phone' => $unique_phone]);
        $customer->fullname = strtoupper($validated['account_name']);
        $customer->email = $request->input('email');
        $customer->id_number = $request->input('nida_number');
        $customer->address = $request->input('address');
        $customer->created_by = Auth::user()->id;
        $customer->save();

        //create or update customer account
        $customer_account = CustomerAccount::firstOrNew(['uuid' => $customer_account_uuid]);
        $customer_account->account_type_id = $validated['account_type_id'];
        $customer_account->account_id = $validated['account_id'];
        $customer_account->phone = $unique_phone;
        $customer_account->secondary_phone = $validated['secondary_phone'];
        $customer_account->account_name = $validated['account_name'];
        $customer_account->account_number = $validated['account_number'];
        $customer_account->bank_code = $bank_code;
        $customer_account->customer_id = $customer->id;
        $customer_account->created_by = Auth::user()->id;
        $customer_account->save();


        //redirect with message
        return Redirect::route('payment-mandate.create-deduction', [$customer_account_uuid, $debit_order_uuid])->with('success', "Customer deduction channel saved");
    }

    public function create_deduction($customer_account_uuid, $debit_order_uuid = null)
    {
        $title = "Payment Mandate";

        //users
        $user = User::findOrFail(Auth::user()->id);

        $provider = null;
        if ($user->partners->where('authorize', '=', 1)->count() == 1)
            $provider = $user->partners[0];

        //customer account
        $customer_account = CustomerAccount::where(['uuid' => $customer_account_uuid])->first();

        //debit order
        $debit_order = [];
        if ($debit_order_uuid != null)
            $debit_order = DebitOrder::where(['uuid' => $debit_order_uuid])->first();

        //providers
        $providers = Partner::filterCondition();
        $providers = $providers->get();

        //render view
        return view('payment_mandate.create.deduction_form', compact('title', 'customer_account', 'debit_order', 'providers', 'provider'));
    }

    //store deduction information
    public function store_deduction(Request $request)
    {
        //mandate post data
        $loan_amount = floatval(str_replace(',', '', $request->input('loan_amount')));
        $installment_amount = floatval(str_replace(',', '', $request->input('installment_amount')));
        $min_installment_amount = floatval(str_replace(',', '', $request->input('min_installment_amount')));
        $max_installment_amount = floatval(str_replace(',', '', $request->input('max_installment_amount')));

        //Validate your data
        $this->validate(
            $request,
            [
                'provider_id' => 'required_if:partner_id,==,null',
                'loan_amount' => 'required',
                'debit_type' => 'required',
                'periodicity' => 'required',
                'number_of_installment' => 'required',
                'start_date' => 'required',
                'deduction_end' => 'required',
                'installment_amount' => 'required_if:debit_type,==,FIXED',
                'end_date' => 'required_if:deduction_end,==,specificDate',
                'min_installment_amount' => 'required_if:debit_type,==,DYNAMIC',
                'max_installment_amount' => [
                    'required_if:debit_type,==,DYNAMIC',
                    function ($attribute, $value, $fail) use ($min_installment_amount, $max_installment_amount) {
                        if ($min_installment_amount > $max_installment_amount) {
                            return $fail('Maximum installment amount should not be less than minimum deduction amount');
                        }
                    }
                ],
            ],
            [
                'provider_id.required_if' => 'Service provider required',
                'loan_amount.required' => 'Total amount required',
                'debit_type.required' => 'Debit type required',
                'installment_amount.required_if' => 'Installment amount required',
                'min_installment_amount.required_if' => 'Minimum installment amount required',
                'max_installment_amountrequired_if' => 'Maximum installment amount required',
                'periodicity.required' => 'Periodicity required',
                'number_of_installment.required' => 'Number of installment required',
                'start_date.required' => 'Start date required',
                'deduction_end.required' => 'Deduction end required',
                'end_date.required_if' => 'End date required',
            ]
        );

        //generate otp
        $otp_code = rand(10000, 99999);

        //check for provider id
        if ($request->partner_id != null)
            $partner_id = $request->partner_id;
        else
            $partner_id = $request->provider_id;

        //customer account id
        $customer_account_uuid = $request->input('customer_account_uuid');

        //customer account
        $customer_account = CustomerAccount::where("uuid", "=", $customer_account_uuid)->first();

        //debit order uuid
        if ($request->debit_order_uuid != null)
            $debit_order_uuid = $request->debit_order_uuid;
        else
            $debit_order_uuid = Str::uuid();

        //create or update debit order
        if ($debit_order_uuid != null) {
            $debit_order = DebitOrder::where(['uuid' => $debit_order_uuid]);

            if ($debit_order->count() > 0) {
                $debit_order = $debit_order->first();
            } else {
                $debit_order = DebitOrder::firstOrNew([
                    'customer_account_id' => $customer_account->id,
                    'partner_id' => $partner_id,
                    'approved' => 0
                ]);
            }
        } else {
            $debit_order = DebitOrder::firstOrNew([
                'customer_account_id' => $customer_account->id,
                'partner_id' => $partner_id,
                'approved' => 0
            ]);
        }

        $debit_order->uuid = $debit_order_uuid;
        $debit_order->total_amount = $loan_amount;
        $debit_order->outstanding_amount = $loan_amount;
        if ($debit_order->created_by == null) {
            $debit_order->created_by = auth()->id();
        }
        $debit_order->reference = Carbon::now()->format('dmY') . $this->deductionService->getMandateReference();
        $debit_order->debit_type = $request->input('debit_type');
        $debit_order->installment_amount = $installment_amount;
        $debit_order->min_installment_amount = $min_installment_amount;
        $debit_order->max_installment_amount = $max_installment_amount;
        $debit_order->periodicity = $request->input('periodicity');
        $debit_order->installment_number = $request->input('number_of_installment');
        $debit_order->start_date = $request->input('start_date');
        $debit_order->next_debit_date = $request->input('start_date');
        $debit_order->deduction_end_date = $request->input('deduction_end');
        $debit_order->otp_code = $otp_code;
        $debit_order->approved = 0;
        $debit_order->channel = $customer_account->account->slug;
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
            $customer_partner->created_by = Auth::user()->id;
            $customer_partner->save();

            //create evidence of aggrement
            $filename = $this->createEvidence($debit_order);
            $debit_order->aggrement_evidence = $filename;
            $debit_order->status = 100;
            $debit_order->save();
        }

        // redirect with message
        return Redirect::route('payment-mandate.preview', [$debit_order_uuid])->with('success', "Customer deduction information saved, OTP PIN sent to customer for Terms and Condition consent.");
    }

    //customer preview
    public function preview($debit_uuid)
    {
        $title = "Payment Mandate";

        // debit order
        $debit_order = DebitOrder::where(['uuid' => $debit_uuid])->firstOrFail();

        // customer account
        $customer_account = $debit_order->customerAccount ?? null;

        // render view
        return view('payment_mandate.create.preview', compact('title', 'customer_account', 'debit_order'));
    }

    // cancel registration
    public function cancel($debit_order_uuid)
    {
        $title = 'Cancel payment mandate';

        // debit order
        $debit_order = DebitOrder::where(['uuid' => $debit_order_uuid])->first();

        //delete debit order
        $del_installment = Installment::where(['debit_order_id' => $debit_order->id])->delete();
        $debit_order->delete();

        //redirect
        return Redirect::route('payment-mandate.lists')->with('danger', 'Payment mandate application cancelled');
    }

    //confirm application
    public function confirm(Request $request)
    {
        $show_modal = false; //show OTP Modal

        //post data
        $debit_order_uuid = $request->input('uuid');

        //debit order
        $debit_order = DebitOrder::where("uuid", "=", $debit_order_uuid)->first();

        //update status of debit mandate
        $debit_order->status = 200;
        $debit_order->save();

        //check if account is NMB/CRDB/EcoBank/NBC
        if ($debit_order->channel == "NMB") {
            //create payload
            $payload = $this->payloadService->mandateRegistrationPayload($debit_order);

            //show OTP Modal
            $show_modal = true;

            //Create Request to NMB
            dispatch(new MandateRegistrationJob($payload));
        } else if ($debit_order->channel == "CRDB") {
            //requestId
            $requestId = Str::uuid()->toString();

            //create payload
            $payload = $this->payloadService->CRDBMandatePayload($debit_order, $requestId);

            //create data
            $data['payload'] = $payload;
            $data['requestId'] = $requestId;
            $data['mandateReferenceNumber'] = $debit_order->reference;

            //show OTP Modal
            $show_modal = false;

            //Create Request to CRDB
            dispatch(new CRDBMandateRegistrationJob($data));
        }

        //response
        return response()->json(['success' => true, 'show_modal' => $show_modal, 'success_msg' => "Debit mandate processed"], 200);
    }

    //saving application
    public function saving(Request $request)
    {
        //post data
        $mandate_id = $request->input('mandate_id');

        //debit order
        $mandate = DebitOrder::findOrFail($mandate_id);

        //update status of debit mandate
        $mandate->status = 'saved';
        $mandate->save();

        //response
        return response()->json(['success' => false, 'success_msg' => "Debit mandate saved"], 200);
    }

    //resend otp
    public function resend_otp(Request $request)
    {
        //post data
        $debit_order = DebitOrder::where("uuid", $request->uuid)->first();

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

        if ($http_response_code == 202) {
            return response()->json(['success' => true, 'success_msg' => "OTP succesfully generated and sent", "http_response_code" => $http_response_code], 200);
        } else if ($http_response_code == 404) {
            return response()->json(['success' => true, 'success_msg' => "Mandate not found, Edit mandate and process again.", "http_response_code" => $http_response_code], 200);
        } else if ($http_response_code == 410) {
            return response()->json(['success' => true, 'success_msg' => "Duplicate request found, wait for first request to process.", "http_response_code" => $http_response_code], 200);
        } else if ($http_response_code == 510) {
            return response()->json(['success' => true, 'success_msg' => "Users mobile number is missing, edit mandate and process again", "http_response_code" => $http_response_code], 200);
        } else if ($http_response_code == 520) {
            return response()->json(['success' => true, 'success_msg' => "SMS failed to send, contact eMakato support team.", "http_response_code" => $http_response_code], 200);
        } else {
            return response()->json(['success' => true, 'success_msg' => "System error, contact eMakato support team.", "http_response_code" => $http_response_code], 200);
        }
    }

    // verify_otp
    public function verify_otp(Request $request)
    {
        // post data
        $debit_order = DebitOrder::where("uuid", $request->uuid)->first();

        //create otp request
        $otp_request = new OtpRequest();
        $otp_request->reference = $debit_order->reference;
        $otp_request->account_number = $debit_order->customerAccount->account_number;
        $otp_request->action = 'verify';
        $otp_request->created_by = Auth::user()->id;
        $otp_request->save();

        //create payload
        $payload = $this->payloadService->verifyOtpPayload($otp_request, $request->otpPin);

        //passing data
        $accountNumber = $debit_order->customerAccount->account_number;
        $otpReference = $debit_order->reference;

        //api service
        $http_response_code = $this->bankApi->verifyOtpRequest($payload, $accountNumber, $otpReference);

        if ($http_response_code == 202) {
            return response()->json(['success' => true, 'success_msg' => "OTP verified", "http_response_code" => $http_response_code], 200);
        } else if ($http_response_code == 404) {
            return response()->json(['success' => true, 'success_msg' => "Mandate not found, Edit mandate and process again.", "http_response_code" => $http_response_code], 200);
        } else if ($http_response_code == 430) {
            return response()->json(['success' => true, 'success_msg' => "OTP Invalid, Enter valid otp sent to a customer phone.", "http_response_code" => $http_response_code], 200);
        } else if ($http_response_code == 431) {
            return response()->json(['success' => true, 'success_msg' => "OTP Expired, create new OTP by click resend otp link.", "http_response_code" => $http_response_code], 200);
        } else {
            return response()->json(['success' => true, 'success_msg' => "System error, contact eMakato support team.", "http_response_code" => $http_response_code], 200);
        }
    }

    //query mandate status
    public function fetch_status(Request $request)
    {
        // debit order
        $debit_order = DebitOrder::where("uuid", $request->uuid)->first();

        //create payload
        $payload = $this->payloadService->mandateStatusPayload($debit_order);

        //api service
        $arr_response = $this->bankApi->mandateStatusRequest($payload);

        if ($arr_response) {
            $http_response_code = $arr_response[0];
            $remarks = $arr_response[1];

            if ($http_response_code == 240) {
                return response()->json(["success" => true, 'success_msg' => "Mandate activated.", "http_response_code" => $http_response_code], 200);
            } else if ($http_response_code == 249 || $http_response_code == 430) {
                return response()->json(["success" => true, 'success_msg' => $remarks, "http_response_code" => $http_response_code], 200);
            } else if ($http_response_code == 404) {
                return response()->json(["success" => true, 'success_msg' => "Mandate not found, Edit mandate and process again.", "http_response_code" => $http_response_code], 200);
            } else {
                return response()->json(['success' => true, 'success_msg' => "System error, contact eMakato support team.", "http_response_code" => $http_response_code], 200);
            }
        } else {
            return response()->json(['success' => true, 'success_msg' => "System error, contact eMakato support team.", "http_response_code" => 500], 200);
        }
    }

    //mandate cancellation
    function cancel_mandate(Request $request)
    {
        // post data
        $debit_order = DebitOrder::where("uuid", $request->uuid)->first();

        //save cancellation information
        $debit_order->cancellation_reason = $request->remarks;
        $debit_order->cancellationStartedBy = Auth::user()->id;
        $debit_order->cancellationStartedAt = date('Y-m-d H:i:s');
        $debit_order->cancellationApprovedBy = Auth::user()->id;
        $debit_order->cancellationApprovedAt = date('Y-m-d H:i:s');
        $debit_order->save();

        //create payload
        $payload = $this->payloadService->mandateCancellationPayload($debit_order);

        //api response
        $arr_response = $this->bankApi->mandateCancellationRequest($payload);

        if ($arr_response) {
            $http_response_code = $arr_response[0];
            $remarks = $arr_response[1];

            if ($http_response_code == 405) {
                return response()->json(
                    [
                        "success" => true,
                        'success_msg' => $remarks,
                        "http_response_code" => $http_response_code,
                        "remarks" => $remarks
                    ],
                    200
                );
            } else if ($http_response_code == 500) {
                return response()->json(
                    [
                        "success" => false,
                        'success_msg' => $remarks,
                        "http_response_code" => $http_response_code,
                        "remarks" => $remarks
                    ],
                    200
                );
            } else {
                return response()->json(
                    [
                        "success" => false,
                        'success_msg' => "Failed to update mandate status, Try again...",
                        "http_response_code" => 500,
                        "remarks" => $remarks
                    ],
                    200
                );
            }
        } else {
            return response()->json(
                [
                    "success" => false,
                    'success_msg' => "API does not response, Kindly try again or contact support.",
                    "http_response_code" => 500,
                    "remarks" => "API does not response, Kindly try again or contact support."
                ],
                200
            );
        }
    }

    //customer details
    function show($id)
    {
        $title = "Debit Order Information";

        //debit order
        $debit_order = DebitOrder::where(['uuid' => $id])->first();

        // customer account
        $customer_account = $debit_order->customerAccount ?? null;

        //render view
        return view('payment_mandate.show', compact('title', 'debit_order', 'customer_account'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $title = "Payment Mandate";

        //debit order
        $debit_order = DebitOrder::where(['uuid' => $id])->first();

        //populate data
        $account_types = AccountType::all();
        $accounts = Account::all();

        //render view
        return view('payment_mandate.edit', compact('title', 'debit_order', 'account_types', 'accounts'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(Request $request, $id)
    {
        $loan_amount = floatval(str_replace(',', '', $request->input('loan_amount')));
        $installment_amount = floatval(str_replace(',', '', $request->input('installment_amount')));
        $min_installment_amount = floatval(str_replace(',', '', $request->input('min_installment_amount')));
        $max_installment_amount = floatval(str_replace(',', '', $request->input('max_installment_amount')));

        //validate
        $this->validate(
            $request,
            [
                'account_type_id' => 'required',
                'account_id' => 'required',
                'account_name' => 'required_if:account_type_id,1',
                'account_number' => 'required_if:account_type_id,1',
                'phone' => new PhoneNumber(),
                'account_number_phone' => new PhoneNumber(),
                'installment_amount' => ['required_if:debit_type,==,FIXED'],
                'min_installment_amount' => ['required_if:debit_type,==,DYNAMIC'],
                'max_installment_amount' => [
                    'required_if:debit_type,==,DYNAMIC',
                    function ($attribute, $value, $fail) use ($min_installment_amount, $max_installment_amount) {
                        if ($min_installment_amount > $max_installment_amount) {
                            return $fail('Maximum deduction should not be less than minimum deduction amount');
                        }
                    }
                ]
            ],
            [
                'account_type_id.required' => 'Payment channel required',
                'account_id.required' => 'Bank/MNO name required',
                'account_name.required_if' => 'Name of account holder required',
                'account_number.required_if' => 'Account number required',
                'phone.required_if' => 'Phone connected to bank required',
                'account_number_phone.required_if' => 'MNO Phone required',
            ]
        );

        //debit orders
        $debit_order = DebitOrder::findOrFail($id);
        $debit_order->total_amount = floatval(str_replace(",", "", $request->input('loan_amount')));
        $debit_order->debit_type = $request->input('debit_type');

        //check for installment amount
        if ($request->input('debit_type') == 'FIXED') {
            $debit_order->installment_amount = floatval(str_replace(",", "", $request->input('installment_amount')));
        } else if ($request->input('debit_type') == 'DYNAMIC') {
            $debit_order->max_installment_amount = floatval(str_replace(",", "", $request->input('max_installment_amount')));
            $debit_order->min_installment_amount = floatval(str_replace(",", "", $request->input('min_installment_amount')));
        }

        $debit_order->periodicity = $request->input('periodicity');
        $debit_order->installment_number = $request->input('number_of_installment');
        $debit_order->start_date = $request->input('start_date');
        $debit_order->deduction_end_date = $request->input('deduction_end');

        if ($debit_order->created_by == null) {
            $debit_order->created_by = auth()->id();
        }
        //calculate end date
        if ($request->input('deduction_end') === 'totalLoanCovered') {
            //calculate end date from start date
            $debit_order->end_date = date('Y-m-d', strtotime($request->input('start_date') . '+2 year'));
        } else if ($request->input('deduction_end') == 'specificDate') {
            //fixed end date
            $debit_order->end_date = $request->input('end_date');
        }

        //evidence_of_aggrement attached
        if ($request->hasFile('evidence_of_aggrement')) {
            $path = $request->file('evidence_of_aggrement');
            $docsName = $path->getClientOriginalName();

            $path->move('assets/uploads/docs', $docsName);
            $debit_order->evidence_of_aggrement = $docsName;
        }
        $debit_order->save();


        //save or update account details
        // $customer_account = CustomerAccount::firstOrNew([
        //     'uuid' => $uuid
        // ]);
        // $customer_account->account_type_id = $request->input('account_type_id');
        // $customer_account->account_id = $request->input('account_id');
        // $customer_account->account_number = $request->input('account_number');
        // $customer_account->account_name = strtoupper($request->input('account_name'));
        // $customer_account->phone = casting_phone($request->input('phone'));
        // $customer_account->email = $request->input('email');
        // $customer_account->id_number = $request->input('nida_number');
        // $customer_account->address = $request->input('address');
        // $customer_account->created_by = Auth::user()->id;
        // $customer_account->save();


        //redirect
        return Redirect::route('payment-mandate.lists')->with('success', 'Payment mandate updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        $title = 'Delete payment mandate';

        //debit order
        $debit_order = DebitOrder::where(['uuid' => $id])->first();

        //delete debit order
        $debit_order->delete();

        //redirect
        return Redirect::route('payment-mandate.lists')->with('danger', 'Payment mandate deleted!');
    }

    public function createEvidence(DebitOrder $debit_order)
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

    //send remainder for payment mandate
    public function sendRemainder($id)
    {
        $title = "Send Remainder";

        $debit_order = DebitOrder::findOrFail($id);

        //populate data
        $account_types = AccountType::all();
        $accounts = Account::all();

        //render view
        return view('payment_mandate.send_remainder', compact('title', 'debit_order'));
    }
}
