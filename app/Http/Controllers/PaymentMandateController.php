<?php

namespace App\Http\Controllers;

use App\Exports\PaymentMandateExport;
use App\Models\Account;
use App\Models\AccountType;
use App\Models\Customer;
use App\Models\CustomerAccount;
use App\Models\DebitOrder;
use App\Models\Gender;
use App\Models\LoanPurpose;
use App\Models\MaritalStatus;
use App\Models\Occupation;
use App\Models\PartnerUser;
use App\Models\Sector;
use App\Rules\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Illuminate\Support\Str;
use Maatwebsite\Excel\Facades\Excel;

class PaymentMandateController extends Controller
{
    public function __construct()
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
    function exportXLS(Request $request)
    {
        return Excel::download(new PaymentMandateExport($request->start_at, $request->end_at, $request->account_type_id, $request->account_id, $request->status, $request->keyword), date('YmdHis') . '-PaymentMandate.xlsx');
    }

    //payment details form
    function accountDetailsForm($uuid = null)
    {
        $title = "Payment Mandate";

        //if uuid =! null
        $customer_account = [];
        if ($uuid != null)
            $customer_account = CustomerAccount::where(['uuid' => $uuid])->firstOrFail();

        //populate data
        $account_types = AccountType::all();
        $accounts = Account::all();

        //render view
        return view('payment_mandate.account_details_form', compact('title', 'customer_account', 'account_types', 'accounts'));
    }

    //store payment details
    function storeAccountDetails(Request $request)
    {
        //validation 
        $validator = Validator::make(
            $request->all(),
            [
                'phone' => ['required', new PhoneNumber()]
            ],
            [
                'phone.required' => 'Phone number required',
            ]
        );

        if ($validator->fails()) {
            //return response
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

        //capture if uuid is not null
        if ($request->input('uuid') != null)
            $uuid = $request->input('uuid');
        else
            $uuid = Str::uuid();

        //register customer
        $customer = Customer::firstOrNew([
            'phone' => $request->input('phone')
        ]);
        $customer->fullname = strtoupper($request->input('account_name'));
        $customer->email = $request->input('email');
        $customer->id_number = $request->input('nida_number');
        $customer->address = $request->input('address');
        $customer->created_by = Auth::user()->id;
        $customer->save();

        //save or update account details
        $customer_account = CustomerAccount::firstOrNew([
            'account_type_id' => $request->input('account_type_id'),
            'account_id' => $request->input('account_id'),
            'account_number' => $request->input('account_number'),
            'account_name' => strtoupper($request->input('account_name')),
            'phone' => $request->input('phone')
        ]);
        $customer_account->customer_id = $customer->id;
        $customer_account->uuid = $uuid;
        $customer_account->created_by = Auth::user()->id;
        $customer_account->save();

        //response
        return response()->json(['success' => true, 'uuid' => $uuid], 200);
    }

    //deduction details
    function deductionDetailsForm($uuid)
    {
        $title = "Payment Mandate";

        //customer account
        $customer_account = CustomerAccount::where(['uuid' => $uuid])->firstOrFail();

        //debit order
        $debit_order = DebitOrder::where(['customer_account_id' => $customer_account->id, 'customer_account_uuid' => $uuid])->first();

        //render view
        return view('payment_mandate.deduction_details_form', compact('title', 'customer_account', 'debit_order'));
    }

    //store deduction details
    function storeDeductionDetails(Request $request)
    {
        $loan_amount = floatval(str_replace(',', '', $request->input('loan_amount')));
        $installment_amount = floatval(str_replace(',', '', $request->input('installment_amount')));
        $min_installment_amount = floatval(str_replace(',', '', $request->input('min_installment_amount')));
        $max_installment_amount = floatval(str_replace(',', '', $request->input('max_installment_amount')));

        //Validate your data
        $validator = Validator::make($request->all(), [
            'installment_amount' => [
                'required_if:debit_type,==,FIXED'
            ],
            'min_installment_amount' => [
                'required_if:debit_type,==,DYNAMIC'
            ],
            'max_installment_amount' => [
                'required_if:debit_type,==,DYNAMIC',
                function ($attribute, $value, $fail) use ($min_installment_amount, $max_installment_amount) {
                    if ($min_installment_amount > $max_installment_amount) {
                        return $fail('Maximum deduction should not be less than minimum deduction amount');
                    }
                }
            ]

        ]);

        if ($validator->fails()) {
            //return response
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 400);
        }

        //post data
        $uuid = $request->input('uuid');

        //customer account
        $customer_account = CustomerAccount::where(['uuid' => $uuid])->firstOrFail();

        //partner user
        $p_user = PartnerUser::where(['user_id' => Auth::user()->id])->first();

        //debit order
        $debit_order = DebitOrder::firstOrNew([
            'customer_account_id' => $customer_account->id,
            'customer_account_uuid' => $uuid,
            'partner_id' => $p_user->partner_id
        ]);
        $debit_order->total_amount = floatval(str_replace(",", "", $request->input('loan_amount')));
        $debit_order->debit_type = $request->input('debit_type');

        //check for installment amount
        if ($request->input('debit_type') == 'FIXED') {
            $debit_order->installment_amount = floatval(str_replace(",", "", $request->input('installment_amount')));
        } else if ($request->input('debit_type') == 'DYNAMIC') {
            $debit_order->max_installment_amount = floatval(str_replace(",", "", $request->input('max_installment_amount')));
            $debit_order->min_installment_amount = floatval(str_replace(",", "", $request->input('min_installment_amount')));
        }

        $debit_order->periodicity = $request->input('peridiocity');
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

        //response
        return response()->json(['success' => true, 'uuid' => $uuid], 200);
    }

    //customer preview
    function preview($uuid)
    {
        $title = "Payment Mandate";

        //customer account
        $customer_account = CustomerAccount::where(['uuid' => $uuid])->firstOrFail();

        //debit order
        $debit_order = DebitOrder::where(['customer_account_id' => $customer_account->id, 'customer_account_uuid' => $uuid])->firstOrFail();

        //render view
        return view('payment_mandate.preview', compact('title', 'customer_account', 'debit_order'));
    }

    //cancel registration
    function cancelRegistration($uuid)
    {
        //customer account
        $customer_account = CustomerAccount::where(['uuid' => $uuid])->firstOrFail();

        //debit order
        $debit_order = DebitOrder::where(['customer_account_id' => $customer_account->id, 'customer_account_uuid' => $uuid])->firstOrFail();

        //delete debit order
        $debit_order->delete();

        //redirect
        return Redirect::route('payment-mandate.lists')->with('danger', 'Debit order cancelled!');
    }

    //confirm application
    function confirmRegistration(Request $request)
    {
        //post data
        $uuid = $request->input('uuid');

        //customer account
        $customer_account = CustomerAccount::where(['uuid' => $uuid])->firstOrFail();

        $status = 'inactive';
        if (isset($_POST['save'])) {
            $status = 'inactive';
            $action = 'save';
            $message = 'Payment mandate information saved!';
        } else if (isset($_POST['submit'])) {
            $status = 'active';
            $action = 'submit';
            $message = 'Payment mandate registration complete, waiting for consent from customer!. <br /><br />Do you want to create new payment mandate?';

            //send sms
            $response = Http::post(
                'https://portal.lainafinance.co.tz/api/send-sms',
                [
                    'phone' => casting_phone($customer_account->phone),
                    'msg' => "Laina Finance imeweka makato yako"
                ]
            );
        }

        //response
        return response()->json(['success' => true, 'action' => $action, 'success_msg' => $message], 200);
    }


    //customer details
    function details($uuid)
    {
        $title = "Debit Order Information";

        //debit order
        $debit_order = DebitOrder::where(['customer_account_uuid' => $uuid])->firstOrFail();

        //render view
        return view('payment_mandate.details', compact('title', 'debit_order'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($uuid)
    {
        $title = "Payment Mandate";

        //debit order
        $debit_order = DebitOrder::where(['customer_account_uuid' => $uuid])->firstOrFail();

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
    public function update(Request $request)
    {
        //validation 
        // $validator = Validator::make(
        //     $request->all(),
        //     [
        //         'phone' => ['required', new PhoneNumber()]
        //     ],
        //     [
        //         'phone.required' => 'Phone number required',
        //     ]
        // );

        // if ($validator->fails()) {
        //     //return response
        //     return response()->json([
        //         'success' => false,
        //         'errors' => $validator->errors()
        //     ], 422);
        // }

        //capture if uuid is not null
        $uuid = $request->input('uuid');

        //save or update account details
        $customer_account = CustomerAccount::firstOrNew([
            'uuid' => $uuid
        ]);
        $customer_account->account_type_id = $request->input('account_type_id');
        $customer_account->account_id = $request->input('account_id');
        $customer_account->account_number = $request->input('account_number');
        $customer_account->account_name = strtoupper($request->input('account_name'));
        $customer_account->phone = $request->input('phone');
        $customer_account->email = $request->input('email');
        $customer_account->id_number = $request->input('nida_number');
        $customer_account->address = $request->input('address');
        $customer_account->created_by = Auth::user()->id;
        $customer_account->save();


        //response
        return response()->json(['success' => true, 'uuid' => $uuid, 'success_msg' => "Deduction channel updated!"], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //
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
