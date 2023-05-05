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
use App\Http\Requests\CustomerAccountRequest;
use App\Http\Requests\CustomerDeductionRequest;
use App\Jobs\SendSMSJob;
use App\Models\CustomerPartner;
use App\Models\SmsLog;
use Illuminate\Support\Facades\Session;
use PDF;

class PaymentMandateController extends Controller
{
    private $messaging;
    public function __construct()
    {
        $this->middleware('auth');

        //messaging
        $this->messaging = new Messaging();
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

        if (isset($_POST['filter'])) {
            $start_at = $request->input('start_at');
            $end_at = $request->input('end_at');
            $account_type_id = $request->input('account_type_id');
            $account_id = $request->input('account_id');
            $status = $request->input('status');
            $keyword = $request->input('keyword');

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
        } else {
            //if consent not null
            if ($consent != null) {
                if ($consent == 'pending') {
                    $consent = 0;
                } else if ($consent == 'approved') {
                    $consent = 1;
                } else if ($consent == 'rejected') {
                    $consent = 99;
                }
                $debit_orders = $debit_orders->where('debit_orders.approved', $consent);
            }

            //passing variables
            $start_at = null;
            $end_at = null;
            $account_type_id = null;
            $account_id = null;
            $status = $consent;
            $keyword = null;

            //debit orders
            $debit_orders = $debit_orders->get();
        }

        //populate data
        $account_types = AccountType::all();
        $accounts = Account::all();

        //render view
        return view('payment_mandate.lists', compact('title', 'account_types', 'accounts', 'debit_orders', 'start_at', 'end_at', 'account_type_id', 'account_id', 'status', 'keyword'));
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
        $pdf = PDF::loadView('payment_mandate.export_pdf', compact('debit_orders'))->setPaper('a4', 'landscape');

        // download PDF file with download method
        return $pdf->download(date('YmdHis') . '-Paymentmandate.pdf');
    }


    //payment details form
    function create_account($ca_id = null, $mandate_id = null)
    {
        $title = "Payment Mandate";

        //customer account
        $customer_account = [];
        if ($ca_id != null)
            $customer_account = CustomerAccount::where(['id' => $ca_id])->first();

        //payment mandate
        $debit_order = [];
        if ($mandate_id != null)
            $debit_order = DebitOrder::where(['id' => $mandate_id])->first();

        //service provider
        $partner = PartnerUser::where(['user_id' => Auth::user()->id])->first();

        //populate data
        $account_types = AccountType::all();
        $accounts = Account::all();

        //render view
        return view('payment_mandate.create.account_form', compact('title', 'customer_account', 'debit_order', 'account_types', 'accounts', 'partner'));
    }

    //store payment details
    function store_account(CustomerAccountRequest $request)
    {
        // validated request
        $validated = $request->validated();

        //customer account ID and debit order ID
        $ca_id = $request->input('customer_account_id');
        $debit_order_id = $request->input('debit_order_id');
        $partner_id = $request->input('partner_id');

        //unique phone
        if ($validated['account_type_id'] == 1)
            $unique_phone = casting_phone($validated['phone']);
        else if ($request->input('account_type_id') == 2)
            $unique_phone =  casting_phone($validated['account_number_phone']);

        //TODO: validate phone number to MNO for matching details


        //create or update customer
        $customer = Customer::firstOrNew([
            'phone' => $unique_phone,
        ]);
        $customer->fullname = strtoupper($validated['account_name']);
        $customer->email = $request->input('email');
        $customer->id_number = $request->input('nida_number');
        $customer->address = $request->input('address');
        $customer->created_by = Auth::user()->id;
        $customer->save();

        //Link customer with service provider
        $customer_partner = CustomerPartner::firstOrNew(
            [
                'customer_id' => $customer->id,
                'partner_id' => $partner_id
            ]
        );
        $customer_partner->created_by = Auth::user()->id;
        $customer_partner->save();

        //create or update customer account
        $customer_account = CustomerAccount::firstOrNew([
            'account_type_id' => $validated['account_type_id'],
            'account_id' => $validated['account_id'],
            'phone' => $unique_phone
        ]);
        $customer_account->account_name = $validated['account_name'];
        $customer_account->account_number = $validated['account_number'];
        $customer_account->customer_id = $customer->id;
        $customer_account->created_by = Auth::user()->id;
        $customer_account->save();

        //redirect with message
        return Redirect::route('payment-mandate.create-deduction', [$customer_account->id, $debit_order_id])->with('success', "Customer deduction channel saved!");
    }


    //deduction information
    function create_deduction($ca_id, $mandate_id = null)
    {
        $title = "Payment Mandate";

        //customer account
        $customer_account = CustomerAccount::findOrFail($ca_id);

        //debit order
        $debit_order = [];
        if ($mandate_id != null)
            $debit_order = DebitOrder::where(['id' => $mandate_id])->firstOrFail();

        //render view
        return view('payment_mandate.create.deduction_form', compact('title', 'customer_account', 'debit_order'));
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
                            return $fail('Maximum deduction should not be less than minimum deduction amount');
                        }
                    }
                ],
            ],
            [
                'loan_amount.required' => 'Loan amount required',
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

        //customer account id
        $ca_id = $request->input('customer_account_id');
        $debit_order_id = $request->input('debit_order_id');

        //customer account
        $customer_account = CustomerAccount::findOrFail($ca_id);

        //partner user
        $partner_user = PartnerUser::where(['user_id' => Auth::user()->id])->first();

        //create or update debit order
        if ($debit_order_id != null) {
            $debit_order = DebitOrder::where(['id' => $debit_order_id]);

            if ($debit_order->count() > 0) {
                $debit_order = $debit_order->first();
            } else {
                $debit_order = DebitOrder::firstOrNew([
                    'customer_account_id' => $customer_account->id,
                    'partner_id' => $partner_user->partner_id,
                    'approved' => 0
                ]);
            }
        } else {
            $debit_order = DebitOrder::firstOrNew([
                'customer_account_id' => $customer_account->id,
                'partner_id' => $partner_user->partner_id,
                'approved' => 0
            ]);
        }

        $debit_order->uuid = Str::uuid();
        $debit_order->total_amount = $loan_amount;
        $debit_order->debit_type = $request->input('debit_type');
        $debit_order->installment_amount = $installment_amount;
        $debit_order->max_installment_amount = $min_installment_amount;
        $debit_order->min_installment_amount = $max_installment_amount;
        $debit_order->periodicity = $request->input('periodicity');
        $debit_order->installment_number = $request->input('number_of_installment');
        $debit_order->start_date = $request->input('start_date');
        $debit_order->deduction_end_date = $request->input('deduction_end');

        //calculate end date
        if ($request->input('deduction_end') === 'totalLoanCovered') {
            //calculate end date from start date
            $debit_order->end_date = date('Y-m-d', strtotime($request->input('start_date') . '+2 year'));
        } else if ($request->input('deduction_end') == 'specificDate') {
            //fixed end date
            $debit_order->end_date = $$request->input('end_date');
        }

        //evidence_of_aggrement attached
        if ($request->hasFile('evidence_of_aggrement')) {
            $path = $request->file('evidence_of_aggrement');
            $docsName = $path->getClientOriginalName();

            $path->move('assets/uploads/docs', $docsName);
            $debit_order->evidence_of_aggrement = $docsName;
        }
        $debit_order->save();

        //redirect with message
        return Redirect::route('payment-mandate.preview', [$customer_account->id, $debit_order->id])->with('success', "Customer deduction details saved!");
    }

    //customer preview
    function preview($ca_id, $mandate_id)
    {
        $title = "Payment Mandate";

        //customer account
        $customer_account = CustomerAccount::findOrFail($ca_id);

        //debit order
        $debit_order = DebitOrder::findOrFail($mandate_id);

        //render view
        return view('payment_mandate.create.preview', compact('title', 'customer_account', 'debit_order'));
    }

    //cancel registration
    function cancel($mandate_id)
    {
        $title = 'Cancel payment mandate';

        //debit order
        $debit_order = DebitOrder::findOrFail($mandate_id);

        //delete debit order
        $debit_order->delete();

        //redirect
        return Redirect::route('payment-mandate.lists')->with('danger', 'Payment mandate application cancelled!');
    }

    //confirm application
    function confirm(Request $request)
    {
        //post data
        $mandate_id = $request->input('mandate_id');

        //debit order
        $mandate = DebitOrder::findOrFail($mandate_id);

        //update status of debit mandate
        $mandate->status = 'submitted';
        $mandate->save();

        //customer details
        $customer_name = $mandate->customerAccount->account_name;

        //sending sms to customer
        $messageId = $this->messaging->generateMessageId();
        $message = "Habari {$customer_name}, eMakato imeweka makato yako, utatumiwa ujumbe wa kuhakiki taarifa zako.";

        $sms_log = SmsLog::firstOrNew([
            'message_id' => $messageId,
            'phone' => $mandate->customerAccount->phone,
            'sender' => "LAINA"
        ]);
        $sms_log->message = $message;
        $sms_log->sms_count = 1;
        $sms_log->created_by = Auth::user()->id;
        $sms_log->save();

        //send sms in background
        $data = [
            "message_id" => $messageId,
            "schedule" => null
        ];
        dispatch(new SendSMSJob($data));

        //response
        $success_message = "Debit mandate confirmed";

        //response
        return response()->json(['success' => false, 'success_msg' => $success_message], 200);
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
        $success_message = "Debit mandate saved";

        //response
        return response()->json(['success' => false, 'success_msg' => $success_message], 200);
    }


    //customer details
    function show($id)
    {
        $title = "Debit Order Information";

        //debit order
        $debit_order = DebitOrder::findOrFail($id);

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
        $debit_order = DebitOrder::findOrFail($id);

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
        $debit_order = DebitOrder::findOrFail($id);

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
