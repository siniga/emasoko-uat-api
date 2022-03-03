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

class CustomerController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $title = 'Customers';
        //has_allowed('roles', 'lists');

        $customers = Customer::where_cond()->get();

        //render view
        return view('customers.lists', compact('title', 'customers'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $title = "Customer Registration";

        //populate data
        $genders = Gender::all();
        $marital_status = MaritalStatus::all();
        $sectors = Sector::all();
        $occupations = Occupation::all();
        $account_types = AccountType::all();
        $accounts = Account::all();

        //loans
        $loan_purposes = LoanPurpose::all();

        //render view
        return view('customers.create', compact('title', 'genders', 'marital_status', 'sectors', 'occupations', 'account_types', 'accounts', 'loan_purposes'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        $uuid = Str::uuid();

        //get customer id
        $user_id = Auth::user()->id;

        //partner user
        $partner_user = PartnerUser::where(['user_id' => $user_id])->first();

        //dob
        $year = $request->input('year');
        $month = $request->input('month');
        $day = $request->input('day');
        $dob = $year . '-' . $month . '-' . $day;

        //save customer details
        $customer = Customer::firstOrNew([
            'uuid' => $uuid,
        ]);
        $customer->partner_id = $partner_user->partner_id;
        $customer->first_name = $request->input('first_name');
        $customer->middle_name = $request->input('middle_name');
        $customer->last_name = $request->input('surname');
        $customer->dob = $dob;
        $customer->id_type = 1;
        $customer->id_number = $request->input('nida_number');
        $customer->gender_id = $request->input('gender_id');
        $customer->marital_status_id = $request->input('marital_status_id');
        $customer->sector_id = $request->input('sector_id');
        $customer->occupation_id = $request->input('occupation_id');
        $customer->created_by = Auth::user()->id;
        $customer->save();

        //account details
        $customer_account = CustomerAccount::firstOrNew([
            'customer_id' => $customer->id,
            'account_type_id' => $request->input('account_type_id'),
            'account_id' => $request->input('account_id')
        ]);
        $customer_account->account_number = $request->input('account_number');
        $customer_account->phone = $request->input('phone');
        $customer_account->created_by = Auth::user()->id;
        $customer_account->save();

        //loan details
        $debit_order = DebitOrder::firstOrNew([
            'customer_account_id' => $customer_account->id,
        ]);
        $debit_order->loan_purpose_id = $request->input('loan_purpose_id');
        $debit_order->total_amount = floatval(str_replace(",", "", $request->input('loan_amount')));
        $debit_order->installment_amount = floatval(str_replace(",", "", $request->input('installment_amount')));
        $debit_order->installment_number = $request->input('number_of_installment');
        $debit_order->periodicity = $request->input('peridiocity');
        $debit_order->start_date = $request->input('start_date');

        //calculate end data
        //$debit_order->end_date = $request->input('loan_purpose_id');

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
    public function edit($id)
    {
        //
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
        //
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
