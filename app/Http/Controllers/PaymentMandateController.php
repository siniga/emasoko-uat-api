<?php

namespace App\Http\Controllers;

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
use App\Exports\DebitOrders;
use App\Http\Requests\CustomerAccountRequest;
use App\Jobs\CRDBMandateRegistrationJob;
use App\Jobs\MandateRegistrationJob;
use App\Jobs\SendSMSJob;
use App\Models\CustomerPartner;
use App\Models\DebitOrderRequest;
use App\Models\Installment;
use App\Models\OtpRequest;
use App\Models\Partner;
use App\Models\User;
use App\Services\BankApiService;
use App\Services\BankPayloadService;
use App\Services\DeductionService;
use App\Services\MandateActionService;
use Carbon\Carbon;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Validator;

class PaymentMandateController extends Controller
{

    public function __construct(protected MandateActionService $mandateActionService, protected DeductionService $deductionService, protected BankPayloadService $payloadService, protected Messaging $messaging, protected BankApiService $bankApi)
    {
        $this->middleware('auth');
    }

    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function lists(Request $request)
    {
        $title = 'Payment Mandate';
        //has_allowed('roles', 'lists');

        $status = null;
        if (isset($request->status)) {
            $status = $request->status;
        }

        //debit orders
        $number_of_debit_orders = 0;
        $debit_orders = DebitOrder::filterCondition()
            ->select(
                'id',
                'uuid',
                'reference',
                'partner_id',
                'customer_account_id',
                'debit_type',
                'periodicity',
                'start_date',
                'end_date',
                'contract_status',
                'status',
                'remarks',
                'approved',
                'cancelled',
                'closed',
                'created_at',
                'created_by'
            )
            ->whereHas('partner', function ($q) {
                $q->where('partners.approved', '=', 1);
            });

        if ($status) {
            switch ($status) {
                case 'pending':
                    $debit_orders = $debit_orders->where(['approved' => 0]);
                    break;
                case 'approved':
                    $debit_orders = $debit_orders->where(['approved' => 1]);
                    break;
                case 'failed':
                    $debit_orders = $debit_orders->where(['approved' => 2]);
                    break;
                case 'cancelled':
                    $debit_orders = $debit_orders->where(['cancelled' => 1]);
                    break;
                case 'closed':
                    $debit_orders = $debit_orders->where(['closed' => 1]);
                    break;
                default:
                    $debit_orders = $debit_orders->where(['approved' => 0]);
            }
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
            $debit_orders = $debit_orders->orderBy('debit_orders.created_at', 'DESC')->paginate(50)->withQueryString();
        }

        $post_data = [
            'start_at' => $request->start_at ?? null,
            'end_at' => $request->end_at ?? null,
            'account_type_id' => $request->account_type_id ?? null,
            'account_id' => $request->account_id ?? null,
            'partner_id' => $request->partner_id ?? null,
            'status' => $request->status ?? null,
            'keyword' => $request->keyword ?? null
        ];

        //populate data
        $account_types = AccountType::all();
        $accounts = Account::where('active', '=', 1)->get();
        $partners = Partner::filterCondition()->where('approved', '=', 1)->get();

        //render view
        return view('payment_mandate.lists', compact('title', 'account_types', 'accounts', 'partners', 'debit_orders', 'number_of_debit_orders', 'post_data'));
    }

    //export xls
    public function export_xls(Request $request)
    {
        //all request
        $requests = $request->all();

        //Query Debit Mandate
        $debit_orders = DebitOrder::filterCondition()
            ->select(
                'id',
                'reference',
                'partner_id',
                'customer_account_id',
                'debit_type',
                'total_amount',
                'paid_amount',
                'outstanding_amount',
                'current_balance',
                'installment_amount',
                'min_installment_amount',
                'max_installment_amount',
                'installment_number',
                'periodicity',
                'start_date',
                'end_date',
                'contract_status',
                'status',
                'remarks',
                'approved',
                'created_at',
                'created_by'
            )
            ->whereHas('partner', function ($q) {
                $q->where('partners.status', 202);
            });

        $debit_orders = $debit_orders->when($requests, function ($query) use ($requests) {
            $query->withFilters($requests);
        });

        //debit orders
        $debit_orders = $debit_orders->orderBy('debit_orders.created_at', 'DESC')->get();

        //array debit orders
        $arr_debit_orders = [];

        foreach ($debit_orders as $val) {
            //format status
            $consent_status = "PENDING";
            if (intval($val->approved) == 0)
                $consent_status = "PENDING";
            else if (intval($val->approved) == 1)
                $consent_status = "APPROVED";
            else if (intval($val->approved) == 2)
                $consent_status = "FAILED";
            else if (intval($val->approved) == 9)
                $consent_status = "CANCELLED";

            //arr debit orders
            $arr_debit_orders[] = [
                'Business_Name' => strtoupper($val->partner->business_name),
                'Mandate_Reference' => $val->reference,
                'Mandate_Status' => $consent_status,
                'Account_Name' => strtoupper($val->customerAccount->account_name ?? ''),
                'Account_Number' => $val->customerAccount->account_number ?? '',
                'Debit_Type' => $val->debit_type,
                'Periodicity' => $val->periodicity,
                'Number_Installment' => $val->installment_number,
                'Total_Amount' => number_format($val->total_amount),
                'Paid_Amount' => number_format($val->current_balance),
                'Outstanding_Amount' => number_format($val->outstanding_amount),
                'Deduction_Amount' => $val->installment_amount == 0 ? number_format($val->max_installment_amount) : number_format($val->installment_amount),
                'Deduction_Start_Date' => $val->start_date,
                'Deduction_End Date' => $val->end_date,
                'Contract_Status' => $val->contract_status,
                'Remarks' => $val->remarks,
                'Created_Date' => $val->created_at,
                'Created_By' => $val->userCreated->name ?? '',
            ];
        }

        //create filename
        $filename = "Debit_Mandates_" . date('YmdHis')  . ".xlsx";

        //return
        return Excel::download(new DebitOrders($arr_debit_orders), $filename);
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

        //user
        $user = User::findOrFail(Auth::user()->id);

        //check if logged in user can create mandate
        if ($user->hasRole('Company_Approver') || $user->hasRole('Company_User')) {
            $partner_user = PartnerUser::whereHas('partner', function ($query) {
                $query->where('approved', '=', 1);
            })->where('user_id', '=', $user->id);

            if ($partner_user->count() == 0)
                return Redirect::route('payment-mandate.lists')->with('danger', "Service provider is not activated, Kindly contact eMakato Team.");
        }

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
        $accounts = Account::where(['active' => 1])->get();

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

        $arr_debit_orders = [];
        if ($debit_order_uuid == null) {
            $arr_debit_orders = $this->deductionService->hasActiveDebitOrder($customer_account_uuid);
        }

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
        return view('payment_mandate.create.deduction_form', compact('title', 'customer_account', 'debit_order', 'providers', 'provider', 'arr_debit_orders'));
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
                'loan_amount' => [
                    'required',
                    function ($attribute, $value, $fail) use ($loan_amount) {
                        if ($loan_amount < 1000) {
                            return $fail('Total amount should be greater than or equal to 1,000');
                        }
                    }
                ],
                'debit_type' => 'required',
                'periodicity' => 'required',
                'number_of_installment' => 'required',
                'start_date' => 'required',
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
                'start_date.required' => 'Deduction start date required'
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
            $debit_order->created_by = Auth::user()->id;
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
        $debit_order->deduction_end_date = "totalLoanCovered";
        $debit_order->otp_code = $otp_code;
        $debit_order->approved = 0;
        $debit_order->channel = $customer_account->account->slug;
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
            $customer_partner->created_by = Auth::user()->id;
            $customer_partner->save();

            //create evidence of agreement
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

    //save application for later confirmation
    public function save(Request $request)
    {
        //post data
        $debit_order_uuid = $request->input('uuid');

        //debit order
        $debit_order = DebitOrder::where("uuid", $debit_order_uuid)->first();

        //update status of debit mandate
        $debit_order->status = 100;
        $debit_order->save();

        //response
        return response()->json(['success' => false, 'success_msg' => "Debit mandate saved"], 200);
    }

    //confirm application
    public function confirm(Request $request)
    {
        //post data
        $debit_order_uuid = $request->input('uuid');

        //debit order
        $debitOrder = DebitOrder::where("uuid", $debit_order_uuid)->first();

        //update status of debit mandate
        $debitOrder->status = 200;
        $debitOrder->save();

        //mandate registration
        $jsonResponse = $this->mandateActionService->registerMandate($debitOrder);
        $response = $jsonResponse->getData(true);

        //logging response
        Log::debug("== Mandate response data ==");
        Log::debug($response);

        //Define variables for response
        $success = $response['success'];
        $response_code = $response['response_code'];
        $remarks = $response['remarks'];
        $channel = $debitOrder->channel;
        $redirect_url  = $response['redirect_url'] ?? null;

        //response
        return response()->json([
            'success' => $success,
            'channel' => $channel,
            "response_code" => $response_code,
            "success_msg" => $remarks,
            'redirect_url' => $redirect_url
        ], 200);
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

        //declare channel and remarks
        $success = true;
        $response_code = 200;
        $remarks = "";
        $channel = $debit_order->channel;

        //check if account is NMB/CRDB/EcoBank/NBC
        if ($debit_order->channel == "NMB") {
            //create payload
            $payload = $this->payloadService->createOtpPayload($otp_request);

            //api service
            $arr_response = $this->bankApi->createOtpRequest($payload);

            if ($arr_response) {
                $success = true;
                $response_code = $arr_response[0];
                $remarks = $arr_response[1];
            } else {
                $success = false;
                $remarks = "API system error, contact eMakato support team.";
                $response_code = 500;
            }
        }

        //response
        return response()->json(['success' => $success, 'channel' => $channel, "response_code" => $response_code, "success_msg" => $remarks], 200);
    }

    // verify_otp
    public function verify_otp(Request $request)
    {
        // Step 1: Validate request data
        $request->validate([
            'uuid' => 'required|uuid|exists:debit_orders,uuid',
            'otpPin' => 'required|string|min:4|max:10',
        ]);

        // post data
        $debit_order = DebitOrder::where("uuid", $request->uuid)->first();

        //create otp request
        $otp_request = new OtpRequest();
        $otp_request->reference = $debit_order->reference;
        $otp_request->account_number = $debit_order->customerAccount->account_number;
        $otp_request->action = 'verify';
        $otp_request->created_by = Auth::user()->id;
        $otp_request->save();

        //declare channel and remarks
        $success = true;
        $response_code = 200;
        $remarks = "";
        $channel = $debit_order->channel;

        //check if account is NMB/CRDB/EcoBank/NBC
        if ($debit_order->channel == "NMB") {
            //create payload
            $payload = $this->payloadService->verifyOtpPayload($otp_request, $request->otpPin);

            //api service
            $arr_response = $this->bankApi->verifyOtpRequest($payload, $debit_order->reference);

            if ($arr_response) {
                $success = true;
                $response_code = $arr_response[0];
                $remarks = $arr_response[1];
            } else {
                $success = false;
                $remarks = "API system error, contact eMakato support team.";
                $response_code = 500;
            }
        }

        //response
        return response()->json(['success' => $success, 'channel' => $channel, "response_code" => $response_code, "success_msg" => $remarks], 200);
    }

    //query mandate status
    public function fetch_status(Request $request)
    {
        // debit order
        $debit_order = DebitOrder::where("uuid", $request->uuid)->first();

        //declare channel and remarks
        $success = true;
        $response_code = 200;
        $remarks = "";
        $channel = $debit_order->channel;

        //check if account is NMB/CRDB/EcoBank/NBC
        if ($debit_order->channel == "NMB") {
            //create payload
            $payload = $this->payloadService->mandateStatusPayload($debit_order);

            //api service
            $arr_response = $this->bankApi->mandateStatusRequest($payload);

            if ($arr_response) {
                $success = true;
                $response_code = $arr_response[0];
                $remarks = $arr_response[1];

                // Create debit order installments IF response code = 240
                if ($response_code == 240)
                    $this->deductionService->createInstallment($debit_order);
            } else {
                $success = false;
                $remarks = "API system error, contact eMakato support team.";
                $response_code = 500;
            }
        }

        //response
        return response()->json(['success' => $success, 'channel' => $channel, "response_code" => $response_code, "success_msg" => $remarks], 200);
    }

    //mandate cancellation
    public function cancel_mandate(Request $request)
    {
        // post data
        $debitOrder = DebitOrder::where("uuid", $request->uuid)->first();
        $channel = $debitOrder->channel;

        //save cancellation information
        $debitOrder->cancellation_reason = $request->remarks;
        $debitOrder->cancellationStartedBy = Auth::user()->id;
        $debitOrder->cancellationStartedAt = date('Y-m-d H:i:s');
        $debitOrder->cancellationApprovedBy = Auth::user()->id;
        $debitOrder->cancellationApprovedAt = date('Y-m-d H:i:s');
        $debitOrder->save();

        //return response
        $success = true;
        $response_code = 201;
        $remarks = "Mandate cancellation request sent successfully. Wait for few minutes for the cancellation to take effect.";

        // request mandate cancellation in background
        $this->mandateActionService->cancelMandate($debitOrder);

        //response
        return response()->json(['success' => $success, 'channel' => $channel, "response_code" => $response_code, "success_msg" => $remarks], 200);
    }

    //customer details
    public function show($id)
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
            $debit_order->created_by = Auth::user()->id;
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
    public function destroy($uuid)
    {
        $title = 'Delete payment mandate';

        // Wrap in transaction to ensure data consistency
        DB::transaction(function () use ($uuid) {
            $debitOrder = DebitOrder::where('uuid', $uuid)->firstOrFail();

            // Delete related installments
            $debitOrder->installments()->delete();

            // Delete related debit order requests
            $debitOrder->debitOrderRequests()->delete();

            //delete collection debit orders
            $debitOrder->collectionDebitOrders()->delete();

            // Delete the main debit order
            $debitOrder->delete();
        });

        //redirect
        return Redirect::route('payment-mandate.lists')->with('danger', 'Payment mandate deleted!');
    }

    //request cancellation payment mandate
    public function request_cancellation(Request $request)
    {
        $title = 'Request payment mandate cancellation';

        if ($request->isMethod('post')) {
            // Step 1: Validate the uploaded file
            $request->validate([
                'partner_id' => 'required|exists:partners,id',
                'attachment' => 'required|file|mimes:xls,xlsx|mimetypes:application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);

            // Step 1.1: Get the service provider ID from the request
            $serviceProviderId = $request->input('partner_id');

            // Step 1.2: Load the Excel file into a Collection
            $data = Excel::toArray([], $request->file('attachment'));
            $rows = $data[0];

            //headers
            $headers = array_map('trim', $rows[0]); // row 0 = headers

            // Step 1.3: Validate the content of the file
            $errors = [];
            foreach (array_slice($rows, 1) as $index => $row) {
                $rowAssoc = array_combine($headers, $row);

                //validate each row
                $validator = Validator::make($rowAssoc, [
                    'Mandate_Reference' => [
                        'required',
                        'numeric',
                        function ($attribute, $value, $fail) use ($serviceProviderId) {
                            $exists = DebitOrder::where('reference', $value)
                                ->where('partner_id', $serviceProviderId)
                                ->exists();

                            if (!$exists) {
                                $fail("Mandate Reference [$value] does not belong to the selected Service Provider.");
                            }
                        },
                    ],
                ]);

                if ($validator->fails()) {
                    $errors[$index + 1] = $validator->errors()->all(); // Row number starts at 1
                }
            }

            if (!empty($errors)) {
                return back()->with('file_errors', $errors);
            }

            // Step 2: Process the valid rows
            $mandateReferences = [];
            foreach (array_slice($rows, 1) as $row) {
                $rowAssoc = array_combine($headers, $row);
                $mandateReferences[] = $rowAssoc['Mandate_Reference'];
            }
            // Step 3: Create cancellation requests
            foreach ($mandateReferences as $reference) {
                // Find the debit order by reference and service provider
                $debitOrder = DebitOrder::where('reference', $reference)
                    ->where('partner_id', $serviceProviderId)
                    ->first();

                if ($debitOrder) {
                    // Create a cancellation request
                    $cancellationRequest = new DebitOrderRequest();
                    $cancellationRequest->debit_order_id = $debitOrder->id;
                    $cancellationRequest->request_type = 'CANCELLATION';
                    $cancellationRequest->requested_by = Auth::user()->id;
                    $cancellationRequest->status = 'PENDING';
                    $cancellationRequest->requested_at = Carbon::now();
                    $cancellationRequest->save();
                }
            }
            // Step 4: Redirect with success message
            return Redirect::route('payment-mandate.request-cancellation')->with('success', 'Cancellation requests have been successfully created.');
        }

        //providers
        $providers = Partner::filterCondition();
        $providers = $providers->get();

        //render view
        return view('payment_mandate.request_cancellation', compact('title', 'providers'));
    }

    //approve payment mandate cancellation
    public function approve_cancellation(Request $request)
    {
        $title = 'Approve payment mandate cancellation';

        if ($request->isMethod('post')) {
            if (!$request->has('request_id')) {
                return back()->with('danger', 'No payment mandate selected for cancellation.');
            } else {
                foreach ($request->post('request_id') as $dd_request_id) {
                    // Step 2: Find the cancellation request
                    $cancellationRequest = DebitOrderRequest::findOrFail($dd_request_id);

                    // Step 3: Update the cancellation request status
                    $cancellationRequest->status = 'APPROVED';
                    $cancellationRequest->approved_by = Auth::user()->id;
                    $cancellationRequest->approved_at = Carbon::now();
                    $cancellationRequest->save();

                    // Step 4: Update the debit order status
                    $debitOrder = $cancellationRequest->debitOrder;
                    if ($debitOrder) {
                        $debitOrder->cancelled = 1; // Mark as cancelled
                        $debitOrder->save();
                    }

                    // request mandate cancellation in background
                    $this->mandateActionService->cancelMandate($debitOrder);
                }

                // Step 5: add a success message for cancellation
                return Redirect::route('payment-mandate.approve-cancellation')->with('success', 'Payment mandate cancellation request approved successfully.');
            }
        }

        //cancellation requests
        $dd_requests = DebitOrderRequest::with('debitOrder')
            ->where('request_type', 'CANCELLATION')->where('status', 'PENDING')->get();

        //render view
        return view('payment_mandate.approve_cancellation', compact('title', 'dd_requests'));
    }

    //request deleting payment mandate
    public function request_deletion(Request $request)
    {
        $title = 'Request payment mandate deletion';

        if ($request->isMethod('post')) {
            // Step 1: Validate the uploaded file
            $request->validate([
                'partner_id' => 'required|exists:partners,id',
                'attachment' => 'required|file|mimes:xls,xlsx|mimetypes:application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);

            // Step 1.1: Get the service provider ID from the request
            $serviceProviderId = $request->input('partner_id');

            // Step 1.2: Load the Excel file into a Collection
            $data = Excel::toArray([], $request->file('attachment'));
            $rows = $data[0];

            //headers
            $headers = array_map('trim', $rows[0]); // row 0 = headers

            // Step 1.3: Validate the content of the file
            $errors = [];
            foreach (array_slice($rows, 1) as $index => $row) {
                $rowAssoc = array_combine($headers, $row);

                //validate each row
                $validator = Validator::make($rowAssoc, [
                    'Mandate_Reference' => [
                        'required',
                        'numeric',
                        function ($attribute, $value, $fail) use ($serviceProviderId) {
                            $debit_order = DebitOrder::where('reference', $value)
                                ->where('partner_id', $serviceProviderId);

                            if (!$debit_order->exists()) {
                                $fail("Mandate Reference [$value] does not belong to the selected Service Provider.");
                            } else {
                                // Check if the debit order is active or not
                                $status = $debit_order->first()->status;
                                if ($status === 240) {
                                    $fail("Mandate Reference [$value] is active. You can not delete an active mandate.");
                                }
                            }
                        },
                    ],
                ]);

                if ($validator->fails()) {
                    $errors[$index + 1] = $validator->errors()->all(); // Row number starts at 1
                }
            }

            if (!empty($errors)) {
                return back()->with('file_errors', $errors);
            }

            // Step 2: Process the valid rows
            $mandateReferences = [];
            foreach (array_slice($rows, 1) as $row) {
                $rowAssoc = array_combine($headers, $row);
                $mandateReferences[] = $rowAssoc['Mandate_Reference'];
            }
            // Step 3: Create cancellation requests
            foreach ($mandateReferences as $reference) {
                // Find the debit order by reference and service provider
                $debitOrder = DebitOrder::where('reference', $reference)
                    ->where('partner_id', $serviceProviderId)
                    ->first();

                if ($debitOrder) {
                    // Create a cancellation request
                    $cancellationRequest = new DebitOrderRequest();
                    $cancellationRequest->debit_order_id = $debitOrder->id;
                    $cancellationRequest->request_type = 'DELETION';
                    $cancellationRequest->requested_by = Auth::user()->id;
                    $cancellationRequest->status = 'PENDING';
                    $cancellationRequest->requested_at = Carbon::now();
                    $cancellationRequest->save();
                }
            }
            // Step 4: Redirect with success message
            return Redirect::route('payment-mandate.request-deletion')->with('success', 'Deletion requests have been successfully created.');
        }

        //providers
        $providers = Partner::filterCondition();
        $providers = $providers->get();

        //render view
        return view('payment_mandate.request_deletion', compact('title', 'providers'));
    }

    //request delete selected payment mandates
    public function delete_selected(Request $request)
    {
        $title = 'Request payment mandate deletion';
    }

    //approve payment mandate deletion
    public function approve_deletion(Request $request)
    {
        $title = 'Approve payment mandate cancellation';

        if ($request->isMethod('post')) {
            if (!$request->has('request_id')) {
                return back()->with('danger', 'No payment mandate selected for deletion.');
            } else {
                foreach ($request->post('request_id') as $dd_request_id) {
                    // Step 2: Find the cancellation request
                    $cancellationRequest = DebitOrderRequest::findOrFail($dd_request_id);

                    // Step 3: Update the cancellation request status
                    $cancellationRequest->status = 'APPROVED';
                    $cancellationRequest->approved_by = Auth::user()->id;
                    $cancellationRequest->approved_at = Carbon::now();
                    $cancellationRequest->save();

                    // Step 4: Update the debit order status
                    $debitOrder = $cancellationRequest->debitOrder;
                    if ($debitOrder) {
                        $debitOrder->deleted = 1; // Mark as deleted
                        $debitOrder->save();
                    }

                    // request mandate cancellation in background
                    $this->mandateActionService->cancelMandate($debitOrder);
                }

                // Step 5: add a success message for cancellation
                return Redirect::route('payment-mandate.approve-deletion')->with('success', 'Payment mandate deletion request approved successfully.');
            }
        }

        //deletion requests
        $dd_requests = DebitOrderRequest::with('debitOrder')
            ->where('request_type', 'DELETION')->where('status', 'PENDING')->get();

        //render view
        return view('payment_mandate.approve_deletion', compact('title', 'dd_requests'));
    }

    //request closing payment mandate
    public function request_closing(Request $request)
    {
        $title = 'Request payment mandate closing';

        if ($request->isMethod('post')) {
            // Step 1: Validate the uploaded file
            $request->validate([
                'partner_id' => 'required|exists:partners,id',
                'attachment' => 'required|file|mimes:xls,xlsx|mimetypes:application/vnd.ms-excel,application/vnd.openxmlformats-officedocument.spreadsheetml.sheet',
            ]);

            // Step 1.1: Get the service provider ID from the request
            $serviceProviderId = $request->input('partner_id');

            // Step 1.2: Load the Excel file into a Collection
            $data = Excel::toArray([], $request->file('attachment'));
            $rows = $data[0];

            //headers
            $headers = array_map('trim', $rows[0]); // row 0 = headers

            // Step 1.3: Validate the content of the file
            $errors = [];
            foreach (array_slice($rows, 1) as $index => $row) {
                $rowAssoc = array_combine($headers, $row);

                //validate each row
                $validator = Validator::make($rowAssoc, [
                    'Mandate_Reference' => [
                        'required',
                        'numeric',
                        function ($attribute, $value, $fail) use ($serviceProviderId) {
                            $exists = DebitOrder::where('reference', $value)
                                ->where('partner_id', $serviceProviderId)
                                ->exists();

                            if (!$exists) {
                                $fail("Mandate Reference [$value] does not belong to the selected Service Provider.");
                            }
                        },
                    ],
                ]);

                if ($validator->fails()) {
                    $errors[$index + 1] = $validator->errors()->all(); // Row number starts at 1
                }
            }

            if (!empty($errors)) {
                return back()->with('file_errors', $errors);
            }

            // Step 2: Process the valid rows
            $mandateReferences = [];
            foreach (array_slice($rows, 1) as $row) {
                $rowAssoc = array_combine($headers, $row);
                $mandateReferences[] = $rowAssoc['Mandate_Reference'];
            }
            // Step 3: Create cancellation requests
            foreach ($mandateReferences as $reference) {
                // Find the debit order by reference and service provider
                $debitOrder = DebitOrder::where('reference', $reference)
                    ->where('partner_id', $serviceProviderId)
                    ->first();

                if ($debitOrder) {
                    // Create a closing request
                    $closingRequest = new DebitOrderRequest();
                    $closingRequest->debit_order_id = $debitOrder->id;
                    $closingRequest->request_type = 'CLOSING';
                    $closingRequest->requested_by = Auth::user()->id;
                    $closingRequest->status = 'PENDING';
                    $closingRequest->requested_at = Carbon::now();
                    $closingRequest->save();
                }
            }
            // Step 4: Redirect with success message
            return Redirect::route('payment-mandate.request-closing')->with('success', 'Closing requests have been successfully created.');
        }

        //providers
        $providers = Partner::filterCondition();
        $providers = $providers->get();

        //render view
        return view('payment_mandate.request_closing', compact('title', 'providers'));
    }

    //approve payment mandate closing
    public function approve_closing(Request $request)
    {
        $title = 'Approve payment mandate closing';

        if ($request->isMethod('post')) {
            if (!$request->has('request_id')) {
                return back()->with('danger', 'No payment mandate selected for closing.');
            } else {
                foreach ($request->post('request_id') as $dd_request_id) {
                    // Step 2: Find the closing request
                    $closingRequest = DebitOrderRequest::findOrFail($dd_request_id);

                    // Step 3: Update the closing request status
                    $closingRequest->status = 'APPROVED';
                    $closingRequest->approved_by = Auth::user()->id;
                    $closingRequest->approved_at = Carbon::now();
                    $closingRequest->save();

                    // Step 4: Update the debit order status
                    $debitOrder = $closingRequest->debitOrder;
                    if ($debitOrder) {
                        $debitOrder->closed = 1;
                        $debitOrder->contract_status = "CLOSED";
                        $debitOrder->save();
                    }

                    // request mandate cancellation in background
                    $this->mandateActionService->cancelMandate($debitOrder);
                }

                // Step 5: add a success message for cancellation
                return Redirect::route('payment-mandate.approve-closing')->with('success', 'Payment mandate closing request approved successfully.');
            }
        }

        //closing requests
        $dd_requests = DebitOrderRequest::with('debitOrder')
            ->where('request_type', 'CLOSING')->where('status', 'PENDING')->get();

        //render view
        return view('payment_mandate.approve_closing', compact('title', 'dd_requests'));
    }

    //request archive payment mandate
    public function request_archive($uuid)
    {
        $title = 'Archive payment mandate';

        //debit order
        $debitOrder = DebitOrder::where(['uuid' => $uuid])->first();

        // Create a closing request
        $closingRequest = new DebitOrderRequest();
        $closingRequest->debit_order_id = $debitOrder->id;
        $closingRequest->request_type = 'ARCHIVE';
        $closingRequest->requested_by = Auth::user()->id;
        $closingRequest->status = 'PENDING';
        $closingRequest->requested_at = Carbon::now();
        $closingRequest->save();

        //response 
        return response()->json(["success" => true, 'success_msg' => "Archiving payment mandate request sent."], 200);
    }

    //approve payment mandate archive
    public function approve_archive(Request $request)
    {
        $title = 'Approve payment mandate archive';

        if ($request->isMethod('post')) {
            if (!$request->has('request_id')) {
                return back()->with('danger', 'No payment mandate selected for archive.');
            } else {
                foreach ($request->post('request_id') as $dd_request_id) {
                    // Step 2: Find the closing request
                    $closingRequest = DebitOrderRequest::findOrFail($dd_request_id);

                    // Step 3: Update the closing request status
                    $closingRequest->status = 'APPROVED';
                    $closingRequest->approved_by = Auth::user()->id;
                    $closingRequest->approved_at = Carbon::now();
                    $closingRequest->save();

                    // Step 4: Update the debit order status
                    $debitOrder = $closingRequest->debitOrder;
                    if ($debitOrder) {
                        $debitOrder->archived = 1; // Mark as archived
                        $debitOrder->deleted = 1; // Mark as deleted
                        $debitOrder->save();
                    }
                }

                // Step 5: add a success message for cancellation
                return Redirect::route('payment-mandate.approve-archive')->with('success', 'Payment mandate archive request approved successfully.');
            }
        }

        //archive requests
        $dd_requests = DebitOrderRequest::with('debitOrder')
            ->where('request_type', 'ARCHIVE')->where('status', 'PENDING')->get();

        //render view
        return view('payment_mandate.approve_archive', compact('title', 'dd_requests'));
    }

    public function unarchieve($id)
    {
        $title = 'Unarchive payment mandate';

        //debit order
        $debit_order = DebitOrder::where(['uuid' => $id])->first();
        $debit_order->deleted = 0;
        $debit_order->save();

        //redirect
        return Redirect::route('payment-mandate.lists')->with('danger', 'Payment mandate unarchived');
    }

    public function createEvidence(DebitOrder $debitOrder)
    {
        //Create Evidence of Agreement
        view()->share('debit_order', $debitOrder);

        //PDF Creation
        $pdf = Pdf::loadView('payment_mandate.pdf.evidence');

        //create filename
        $filename = "{$debitOrder->reference}.pdf";

        $path = 'assets/uploads/pdf/';
        $pdf->save($path  . $filename);

        return $filename;
    }

    public function update_installments()
    {
        $debit_orders = DebitOrder::where(['periodicity' => 'MONTHLY', 'debit_type' => 'DYNAMIC'])->get();

        foreach ($debit_orders as $val) {
            $dayOfMonth = date('d', strtotime($val->start_date));

            $installments = Installment::where(['debit_order_id' => $val->id])->get();

            foreach ($installments as $installment) {
                $year = date('Y', strtotime($installment->payment_date));
                $month = date('m', strtotime($installment->payment_date));

                $date = Carbon::createFromDate($year, $month, $dayOfMonth);
                $installment_date = $date->format('Y-m-d'); // Format as dd-mm-yyyy

                //update installment
                $up_installment = Installment::findOrFail($installment->id);
                $up_installment->payment_date = $installment_date;
                $up_installment->save();
            }
        }

        return response()->json(
            [
                "success" => true,
                'success_msg' => "Installments updated",
            ],
            200
        );
    }

    public function update_mandates()
    {
        $debit_orders = DebitOrder::whereIn('id', [5714, 5720])->get();

        foreach ($debit_orders as $debit_order) {
            // create installments
            $this->deductionService->createInstallment($debit_order);

            // //Find summation of installments
            // $installments = Installment::select(DB::raw('COUNT(installments.id) as number_of_installments'), DB::raw('COALESCE(SUM(installments.installment_amount), 0) as paid_amount'))->where(['debit_order_id' => $debit_order->id, 'status' => 'CLOSED'])->get();

            // $amount = $installments[0]->paid_amount;
            // $outstanding_amount = $debit_order->total_amount - $amount;
            // $current_balance = $amount;
            // $paid_amount = $amount;

            // $debit_order->outstanding_amount = $outstanding_amount;
            // $debit_order->current_balance = $current_balance;
            // $debit_order->paid_amount = $paid_amount;
            // $debit_order->paid_installment = $installments[0]->number_of_installments;

            // //6. Update contract status = outstanding amount <= 0
            // if ($outstanding_amount <= 0)
            //     $debit_order->contract_status = "CLOSED";
            // else
            //     $debit_order->contract_status = "OPEN";

            // $debit_order->save();
        }

        return response()->json(['success' => true, 'message' => 'DD updated']);
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
