<?php

namespace App\Http\Controllers;

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
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;

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
    public function lists()
    {
        $title = 'Payment Mandate';
        //has_allowed('roles', 'lists');

        $debit_orders = DebitOrder::where_cond()->get();

        //render view
        return view('payment_mandate.lists', compact('title', 'debit_orders'));
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
    function storePaymentDetails(Request $request)
    {
        //validation 

        //capture if uuid is not null
        if ($request->input('uuid') != null)
            $uuid = $request->input('uuid');
        else
            $uuid = Str::uuid();

        //save or update account details
        $customer_account = CustomerAccount::firstOrNew([
            'uuid' => $uuid,
            'account_type_id' => $request->input('account_type_id'),
            'account_id' => $request->input('account_id'),
            'account_number' => $request->input('account_number'),
            'account_name' => $request->input('account_name')
        ]);
        $customer_account->phone = $request->input('phone');
        $customer_account->email = $request->input('email');
        $customer_account->id_number = $request->input('nida_number');
        $customer_account->address = $request->input('address');
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

        //render view
        return view('payment_mandate.deduction_details', compact('title', 'customer_account'));
    }

    //store deduction details
    function storeDeductionDetails(Request $request)
    {
        //validation 

        //post data
        $uuid = $request->input('uuid');

        //customer account
        $customer_account = CustomerAccount::where(['uuid' => $uuid])->firstOrFail();

        //loan details
        $debit_order = DebitOrder::firstOrNew([
            'customer_account_id' => $customer_account->id,
        ]);
        $debit_order->total_amount = floatval(str_replace(",", "", $request->input('loan_amount')));
        $debit_order->debit_type = $request->input('debit_type');

        //check for installment amount
        if ($request->input('debit_type') == 'FIXED') {
            $debit_order->max_installment_amount = floatval(str_replace(",", "", $request->input('installment_amount')));
            $debit_order->min_installment_amount = 0;
        } else if ($request->input('debit_type') == 'DYNAMIC') {
            $debit_order->max_installment_amount = floatval(str_replace(",", "", $request->input('max_installment_amount')));
            $debit_order->min_installment_amount = floatval(str_replace(",", "", $request->input('min_installment_amount')));
        }

        $debit_order->periodicity = $request->input('peridiocity');
        $debit_order->installment_number = $request->input('number_of_installment');
        $debit_order->start_date = $request->input('start_date');
        $debit_order->deduction_end_date = $request->input('deduction_end_date');

        //calculate end date
        if ($request->input('deduction_end_date') == 'total_loan_covered') {
            //calculate end date from start date
            $debit_order->end_date = date('Y-m-d', strtotime($request->input('start_date') . '+2 year'));
        } else if ($request->input('deduction_end_date') == 'specific_date') {
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

        //render view
        return view('payment_mandate.preview', compact('title', 'customer_account'));
    }

    //confirm application
    function confirmRegistration(Request $request)
    {
        //validation 

        //post data
        $uuid = $request->input('uuid');

        $status = 'inactive';
        if (isset($_POST['save'])) {
            $status = 'inactive';
            $action = 'save';
            $message = 'Payment mandate information saved!';
        } else if (isset($_POST['submit'])) {
            $status = 'active';
            $action = 'submit';
            $message = 'Payment mandate registration complete, waiting for consent from customer!. <br /><br />Do you want to create new payment mandate?';
        }

        //todo: update approve status

        //response
        return response()->json(['success' => true, 'action' => $action, 'success_msg' => $message], 200);
    }


    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
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

        $customer = Customer::where(['uuid' => $uuid])->firstOrFail();

        //populate data
        $genders = Gender::all();
        $marital_status = MaritalStatus::all();
        $sectors = Sector::all();
        $occupations = Occupation::all();
        $account_types = AccountType::all();
        $accounts = Account::all();

        //render view
        return view('customers.edit', compact('title', 'customer', 'genders', 'marital_status', 'sectors', 'occupations', 'account_types', 'accounts'));
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

        //post data
        $id = $request->input('customer_id');
        $uuid = $request->input('customer_uuid');

        //dob
        $year = $request->input('year');
        $month = $request->input('month');
        $day = $request->input('day');
        $dob = $year . '-' . $month . '-' . $day;

        //save customer details
        $customer = Customer::findOrFail($id);
        $customer->first_name = $request->input('first_name');
        $customer->middle_name = $request->input('middle_name');
        $customer->last_name = $request->input('surname');
        $customer->dob = $dob;

        //check if nida number provided
        if ($request->input('nida_number') != null) {
            $customer->id_type = 1;
            $customer->id_number = $request->input('nida_number');
        }

        $customer->gender_id = $request->input('gender_id');
        $customer->marital_status_id = $request->input('marital_status_id');
        $customer->sector_id = $request->input('sector_id');
        $customer->occupation_id = $request->input('occupation_id');
        $customer->updated_by = Auth::user()->id;
        $customer->save();


        //save or update account details
        $customer_account = CustomerAccount::firstOrNew([
            'customer_id' => $customer->id,
            'account_type_id' => $request->input('account_type_id'),
            'account_id' => $request->input('account_id'),
            'account_number' => $request->input('account_number')
        ]);
        $customer_account->account_name = $request->input('account_name');
        $customer_account->phone = $request->input('phone');
        $customer_account->created_by = Auth::user()->id;
        $customer_account->save();

        //response
        return response()->json(['success' => true, 'uuid' => $uuid, 'success_msg' => "Customer Information updated!"], 200);
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

    //customer details
    function details($uuid)
    {
        $title = "Customer Information";

        //customer
        $customer = Customer::where(['uuid' => $uuid])->firstOrFail();

        //render view
        return view('customers.show', compact('title', 'customer'));
    }
}
