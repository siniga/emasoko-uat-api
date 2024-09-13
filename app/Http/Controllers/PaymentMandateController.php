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
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;
use App\Classes\Messaging;
use App\Events\CancelMandate;
use App\Events\CreateOtp;
use App\Events\MandateStatus;
use App\Events\VerifyOtp;
use App\Http\Requests\CustomerAccountRequest;
use App\Http\Requests\CustomerDeductionRequest;
use App\Jobs\MandateRegistrationJob;
use App\Jobs\SendSMSJob;
use App\Models\CustomerPartner;
use App\Models\OtpRequest;
use App\Models\Partner;
use App\Models\SmsLog;
use App\Services\BankApiService;
use App\Services\BankApiServices;
use App\Services\BankPayloadService;
use App\Services\DeductionService;
use App\Services\MnoBankServices;
use Carbon\Carbon;
use Illuminate\Support\Facades\Session;
use PDF;

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
    public function lists(Request $request, $consent = null)
    {
        $title = 'Payment Mandate';
        //has_allowed('roles', 'lists');

        //debit
        $debit_orders = DebitOrder::where_cond();
        if ($consent) {
            $debit_orders->where(['approved' => $consent]);
        }
        if (isset($_POST['filter'])) {
            //all request
            $requests = $request->all();

            $debit_orders = $debit_orders
                ->when($requests, function ($query) use ($requests) {
                    $query->withFilters($requests);
                });

            //debit orders
            $debit_orders = $debit_orders->get();
        } else {
            //if consent not null
            // if ($consent != null) {
            //     if ($consent == 'pending') {
            //         $consent = 0;
            //     } else if ($consent == 'approved') {
            //         $consent = 1;
            //     } else if ($consent == 'rejected') {
            //         $consent = 99;
            //     }
            //     $debit_orders = $debit_orders->where('debit_orders.approved', $consent);
            // }
            //debit orders
            $debit_orders = $debit_orders->paginate(50);
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
        return view('payment_mandate.lists', compact('title', 'account_types', 'accounts', 'debit_orders', 'post_data'));
    }

    //export xls
    function export_xls(Request $request)
    {
        return Excel::download(new PaymentMandateExport($request->start_at, $request->end_at, $request->account_type_id, $request->account_id, $request->status, $request->keyword), date('YmdHis') . '-PaymentMandate.xlsx');
    }

    //export xls
    function export_pdf(Request $request)
    {
        $start_at = $request->input('start_at');
        $end_at = $request->input('end_at');
        $account_type_id = $request->input('account_type_id');
        $account_id = $request->input('account_id');
        $status = $request->input('status');
        $keyword = $request->input('keyword');

        //debit
        $debit_orders = DebitOrder::where_cond();

        //join
        $debit_orders = $debit_orders->join('customer_accounts', 'customer_accounts.id', '=', 'debit_orders.customer_account_id');

        //start data and end date
        if ($start_at != null && $end_at != null) {
            $start_at = date('Y-m-d', strtotime($start_at));
            $end_at = date('Y-m-d', strtotime($end_at));

            $debit_orders = $debit_orders->whereBetween('debit_orders.created_at', [$start_at, $end_at]);
        }

        //payment channel
        if ($account_type_id != null) {
            $debit_orders = $debit_orders->where('customer_accounts.account_type_id', $account_type_id);
        }

        //bank account
        if ($account_id != null) {
            $debit_orders = $debit_orders->where('customer_accounts.account_id', $account_id);
        }

        //status
        if ($status != null)
            $debit_orders = $debit_orders->where('debit_orders.approved', $status);

        //keyword
        if ($keyword != null) {
            $debit_orders = $debit_orders->where(function ($query) use ($keyword) {
                $query->orWhere('customer_accounts.account_name', 'LIKE', "%$keyword%")
                    ->orWhere('customer_accounts.account_number', 'LIKE', "%$keyword%");
            });
        }

        //debit orders
        $debit_orders = $debit_orders->get();

        //pdf
        $pdf = PDF::loadView('payment_mandate.export_pdf', compact('ddestination_account_idebit_orders'))->setPaper('a4', 'landscape');

        // download PDF file with download method
        return $pdf->download(date('YmdHis') . '-Paymentmandate.pdf');
    }

    //payment details form
    function create_account($ca_id = null, $do_id = null)
    {
        $title = "Payment Mandate";

        //customer account
        $customer_account = [];
        if ($ca_id != null)
            $customer_account = CustomerAccount::where(['uuid' => $ca_id])->first();

        //payment mandate
        $debit_order = [];
        if ($do_id != null)
            $debit_order = DebitOrder::where(['uuid' => $do_id])->first();

        //populate data
        $account_types = AccountType::all();
        $accounts = Account::all();

        //render view
        return view('payment_mandate.create.account_form', compact('title', 'customer_account', 'debit_order', 'account_types', 'accounts'));
    }

    //store payment details
    function store_account(CustomerAccountRequest $request)
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


        //create or update customer
        $customer = Customer::firstOrNew(['phone' => $unique_phone,]);
        $customer->fullname = strtoupper($validated['account_name']);
        $customer->email = $request->input('email');
        $customer->id_number = $request->input('nida_number');
        $customer->address = $request->input('address');
        $customer->created_by = Auth::user()->id;
        $customer->save();

        //create or update customer account
        $customer_account = CustomerAccount::firstOrNew([
            'account_type_id' => $validated['account_type_id'],
            'account_id' => $validated['account_id'],
            'phone' => $unique_phone
        ]);
        $customer_account->uuid = $customer_account_uuid;
        $customer_account->secondary_phone = $validated['secondary_phone'];
        $customer_account->account_name = $validated['account_name'];
        $customer_account->account_number = $validated['account_number'];
        $customer_account->bank_code = $validated['bank_code'];
        $customer_account->customer_id = $customer->id;
        $customer_account->created_by = Auth::user()->id;
        $customer_account->save();


        //redirect with message
        return Redirect::route('payment-mandate.create-deduction', [$customer_account_uuid, $debit_order_uuid])->with('success', "Customer deduction channel saved");
    }

    function create_deduction($ca_id, $do_id = null)
    {
        $title = "Payment Mandate";

        //customer account
        $customer_account = CustomerAccount::where(['uuid' => $ca_id])->first();

        //debit order
        $debit_order = [];
        if ($do_id != null)
            $debit_order = DebitOrder::where(['uuid' => $do_id])->first();

        //populate data
        $provider = PartnerUser::where(['user_id' => Auth::user()->id])->first();
        $providers = Partner::all();

        //render view
        return view('payment_mandate.create.deduction_form', compact('title', 'customer_account', 'debit_order', 'providers', 'provider'));
    }

    //store deduction information
    function store_deduction(Request $request)
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
                //'evidence_of_aggrement' => 'required',
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
                //'evidence_of_aggrement.required' => 'Evidence of aggrement is required',
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
        $ca_id = $request->input('customer_account_id');
        $ca_uuid = $request->input('customer_account_uuid');

        //customer account
        $customer_account = CustomerAccount::findOrFail($ca_id);

        //debit order uuid
        if ($request->debit_order_uuid != null)
            $debit_uuid = $request->debit_order_uuid;
        else
            $debit_uuid = Str::uuid();

        //create or update debit order
        if ($debit_uuid != null) {
            $debit_order = DebitOrder::where(['uuid' => $debit_uuid]);

            if ($debit_order->count() > 0) {
                $debit_order = $debit_order->first();
            } else {
                $debit_order = DebitOrder::firstOrNew([
                    'customer_account_id' => $customer_account->id,
                    'partner_id' => $partner_id,
                    'approved' => "PENDING"
                ]);
            }
        } else {
            $debit_order = DebitOrder::firstOrNew([
                'customer_account_id' => $customer_account->id,
                'partner_id' => $partner_id,
                'approved' => "PENDING"
            ]);
        }

        $debit_order->uuid = $debit_uuid;
        $debit_order->total_amount = $loan_amount;
        $debit_order->outstanding_amount = $loan_amount;
        if ($debit_order->created_by == null) {
            $debit_order->created_by = auth()->id();
        }
        $debit_order->reference = Carbon::now()->format('dmY') . rand(1000, 1999);
        $debit_order->debit_type = $request->input('debit_type');
        $debit_order->installment_amount = $installment_amount;
        $debit_order->min_installment_amount = $min_installment_amount;
        $debit_order->max_installment_amount = $max_installment_amount;
        $debit_order->periodicity = $request->input('periodicity');
        $debit_order->installment_number = $request->input('number_of_installment');
        $debit_order->start_date = $request->input('start_date');
        $debit_order->next_debit_date = $request->input('start_date');
        $debit_order->deduction_end_date = $request->input('deduction_end');

        // calculate end date
        if ($request->input('deduction_end') === 'totalLoanCovered') {
            // TODO: Create end date by check number of installment
            $debit_order->end_date = date('Y-m-d', strtotime($request->input('start_date') . '+2 year'));
        } else if ($request->input('deduction_end') == 'specificDate') {
            // fixed end date
            $debit_order->end_date = $$request->input('end_date');
        }

        $debit_order->otp_code = $otp_code;
        $debit_order->approved = "PENDING";
        $debit_order->save();

        if ($debit_order) {
            Log::debug("== Debit order creation ==");
            Log::debug($debit_order);

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

            //construct message
            $message = "Kuridhia Vigezo na Masharti toa namba ya siri {$otp_code} kwenda " . strtoupper($debit_order->partner->business_name);

            //send SMS
            $data = ["phone" => $customer_account->phone, "message" => $message];
            dispatch(new SendSMSJob($data));
        }

        // redirect with message
        return Redirect::route('payment-mandate.create-evidence', [$debit_uuid])->with('success', "Customer deduction information saved, OTP PIN sent to customer for Terms and Condition consent.");
    }

    //evidence of agrement
    function create_evidence($debit_uuid)
    {
        $title = "Payment Mandate";

        // debit order
        $debit_order = DebitOrder::where(['uuid' => $debit_uuid])->firstOrFail();

        //render view
        return view('payment_mandate.create.create_evidence', compact('title', 'debit_order'));
    }

    //verify evidence of agrement
    function verify_evidence(Request $request)
    {
        //Validate your data
        $this->validate(
            $request,
            [
                'otp_code' => 'required',
            ]
        );

        //post data
        $otp_code = $request->otp_code;
        $debit_uuid = $request->debit_order_uuid;

        //debit order
        $debit_order = DebitOrder::where(['uuid' => $debit_uuid, 'otp_code' => $otp_code])->first();

        if (!$debit_order) {
            return Redirect::route('payment-mandate.create-evidence', $debit_uuid)->with('danger', 'Invalid OTP, please try again');
        }

        //Create Evidence of Agreement
        view()->share('debit_order', $debit_order);
        $pdf = PDF::loadView('payment_mandate.create.digital_evidence');

        //create filename
        $filename = $debit_order->uuid . '_evidence.pdf';

        $path = 'assets/uploads/docs/pdf/';
        $pdf->save($path  . $filename);

        //save evidence in database
        $debit_order->evidence_of_aggrement = $filename;
        $debit_order->status = 100;

        $debit_order->save();

         // redirect with message
         return Redirect::route('payment-mandate.preview', [$debit_uuid])->with('success', "Customer Evidence of aggrement verified");
    }

    //customer preview
    function preview($debit_uuid)
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
    function cancel($debit_uuid)
    {
        $title = 'Cancel payment mandate';

        // debit order
        $debit_order = DebitOrder::where(['uuid' => $debit_uuid])->first();

        //delete debit order
        $debit_order->delete();

        //redirect
        return Redirect::route('payment-mandate.lists')->with('danger', 'Payment mandate application cancelled');
    }

    //confirm application
    function confirm(Request $request)
    {
        //post data
        $debit_uuid = $request->input('mandate_id');

        //debit order
        $debit_order = DebitOrder::findOrFail($debit_uuid);

        //update status of debit mandate
        $debit_order->status = 200;
        $debit_order->save();

        //create payload
        $payload = $this->payloadService->mandateRegistrationPayload($debit_order);

        //Create Request to NMB
        dispatch(new MandateRegistrationJob($payload));

        //response
        return response()->json(['success' => false, 'success_msg' => "Debit mandate processed"], 200);
    }

    //saving application
    function saving(Request $request)
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
    function resend_otp(Request $request)
    {
        //post data
        $debit_order = DebitOrder::where("uuid", $request->uuid)->first();

        //create otp request
        $otp_request = new OtpRequest();
        $otp_request->reference = $debit_order->reference;
        $otp_request->account_number = $debit_order->customerAccount->account_number;
        $otp_request->action = 'create';
        $otp_request->created_by = Auth::user()->id;
        $otp_request->save();

        //create payload
        $payload = $this->payloadService->createOtpPayload($otp_request);

        //create otp
        $http_response_code = $this->bankApi->createOtpRequest($payload);

        //response
        return response()->json(['success' => true, 'success_msg' => "OTP request initiated"], 200);
    }

    // verify_otp
    function verify_otp(Request $request)
    {
        // post data
        $debit_order = DebitOrder::where("uuid", $request->uuid)->first();

        //create otp request
        $otp_request = new OtpRequest();
        $otp_request->reference = $debit_order->otp_reference;
        $otp_request->account_number = $debit_order->customerAccount->account_number;
        $otp_request->action = 'verify';
        $otp_request->created_by = Auth::user()->id;
        $otp_request->save();

        //create payload
        $payload = $this->payloadService->verifyOtpPayload($otp_request, $request->otpPin);

        //api service
        $http_response_code = $this->bankApi->verifyOtpRequest($payload, $debit_order->customerAccount->account_number, $debit_order->reference);

        //response
        return response()->json(['success' => true, 'success_msg' => "OTP verification initiated", "http_response_code" => $http_response_code], 200);
    }

    //mandate status
    function fetch_status(Request $request)
    {
        // post data
        $debit_order = DebitOrder::where("uuid", $request->uuid)->first();

        //create payload
        $payload = [
            "user" => "EMakato",
            "correlationId" => Str::uuid()->toString(),
            "targetService" => "MandateStatus",
            "status" =>  "Check",
            "data" => [
                "mandateReferenceNumber" => $debit_order->reference
            ]
        ];

        Log::debug('== MandateStatus Request ==');
        Log::debug($payload);

        //dispatch event
        event(new MandateStatus($payload));

        //response
        return response()->json(['error' => false, "status" => 200, "message" => 'Mandate status initiated.'], 200);
    }

    //mandate cancellation
    function cancel_mandate(Request $request)
    {
        // post data
        $debit_order = DebitOrder::where("uuid", $request->uuid)->first();

        //create payload
        $payload = $this->payloadService->mandateCancellationPayload($debit_order);

        //api service
        $http_response_code = $this->bankApi->mandateCancellationRequest($payload);

        return response()->json(['success' => true, 'success_msg' => "Mandate cancellation initiated", "http_response_code" => $http_response_code], 200);
    }

    //customer details
    function show($id)
    {
        $title = "Debit Order Information";

        //debit order
        $debit_order = DebitOrder::where(['uuid' => $id])->first();

        //render view
        return view('payment_mandate.show', compact('title', 'debit_order'));
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
