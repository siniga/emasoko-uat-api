<?php

namespace App\Http\Controllers;

use App\Exports\CustomerAccountExport;
use App\Http\Requests\CustomerAccountRequest;
use App\Models\Account;
use App\Models\AccountType;
use App\Models\Customer;
use App\Models\CustomerAccount;
use App\Rules\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Maatwebsite\Excel\Facades\Excel;
use PDF;

class CustomerAccountController extends Controller
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
    public function lists(Request $request)
    {
        $title = 'Customer Accounts';
        //has_allowed('roles', 'lists');

        //customer accounts
        $customer_accounts = CustomerAccount::where_cond();

        if (isset($_POST['filter'])) {
            $start_at = $request->input('start_at');
            $end_at = $request->input('end_at');
            $account_type_id = $request->input('account_type_id');
            $account_id = $request->input('account_id');
            $status = $request->input('status');
            $keyword = $request->input('keyword');

            //start data and end date
            if ($start_at != null && $end_at != null) {
                $start_at = date('Y-m-d', strtotime($start_at));
                $end_at = date('Y-m-d', strtotime($end_at));

                $customer_accounts = $customer_accounts->whereBetween('customer_accounts.created_at', [$start_at, $end_at]);
            }

            //payment channel
            if ($account_type_id != null)
                $customer_accounts = $customer_accounts->where('customer_accounts.account_type_id', $account_type_id);

            //bank account
            if ($account_id != null)
                $customer_accounts = $customer_accounts->where('customer_accounts.account_id', $account_id);

            //status
            if ($status != null)
                $customer_accounts = $customer_accounts->where('customer_accounts.status', $status);

            //keyword
            if ($keyword != null) {
                $customer_accounts = $customer_accounts->where(function ($query) use ($keyword) {
                    $query->orWhere('customer_accounts.account_name', 'LIKE', "%$keyword%")
                        ->orWhere('customer_accounts.account_number', 'LIKE', "%$keyword%");
                });
            }

            //customer accounts
            $customer_accounts = $customer_accounts->get();
        } else {
            //passing variables
            $start_at = null;
            $end_at = null;
            $account_type_id = null;
            $account_id = null;
            $status = null;
            $keyword = null;

            //customer accounts
            $customer_accounts = $customer_accounts->get();
        }

        //passing data
        $data = [
            'start_at' => $start_at,
            'end_at' => $end_at,
            'account_type_id' => $account_type_id,
            'account_id' => $account_id,
            'status' => $status,
            'keyword' => $keyword
        ];

        //populate data
        $account_types = AccountType::all();
        $accounts = Account::all();

        //render view
        return view('customer_accounts.lists', compact('title', 'account_types', 'accounts', 'data', 'customer_accounts'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //title
        $title = 'Customer Account Information';

        //customer account
        $customer_account = CustomerAccount::findOrFail($id);

        //render view
        return view('customer_accounts.show', compact('title', 'customer_account'));
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        //title
        $title = 'Edit Customer Account';

        //customer account
        $customer_account = CustomerAccount::findOrFail($id);

        //check for status 
        if ($customer_account->status == 'approved') {
            //redirect
            return Redirect::route('customer-accounts.lists')->with('warning', "You can't edit approved customer account!");
        }

        //populate data
        $account_types = AccountType::all();
        $accounts = Account::all();

        //render view
        return view('customer_accounts.edit', compact('title', 'customer_account', 'account_types', 'accounts'));
    }

    /**
     * Update the specified resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function update(CustomerAccountRequest $request, $id)
    {
         // validated request
         $validated = $request->validated();

        //unique phone
        if ($validated['account_type_id'] == 1)
            $unique_phone = casting_phone($validated['phone']);
        else if ($request->input('account_type_id') == 2)
            $unique_phone =  casting_phone($validated['account_number_phone']);

        //update customer account
        $customer_account = CustomerAccount::findOrFail($id);
        $customer_account->account_type_id = $validated['account_type_id'];
        $customer_account->account_id = $validated['account_id'];
        $customer_account->phone = $unique_phone;
        $customer_account->account_name = $validated['account_name'];
        $customer_account->account_number = $validated['account_number'];
        $customer_account->updated_by = Auth::user()->id;
        $customer_account->save();

        //update customer 
        $customer = Customer::findOrFail($customer_account->customer_id);
        $customer->phone = $unique_phone;
        $customer->fullname = $validated['account_name'];
        $customer->email = $request->input('email');
        $customer->id_number = $request->input('nida_number');
        $customer->address = $request->input('address');
        $customer->updated_by = Auth::user()->id;
        $customer->save();

        //redirect
        return Redirect::route('customer-accounts.lists')->with('success', 'Customer account updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function destroy($id)
    {
        //'Delete Customer Accounts';
        // has_allowed('customer-accounts', 'delete');

        //customer account
        $customer_account = CustomerAccount::findOrFail($id);

        //check for status 
        if ($customer_account->status == 'approved') {
            //redirect
            return Redirect::route('customer-accounts.lists')->with('warning', "You can't delete approved customer account!");
        }

        if ($del = $customer_account->delete()) {
            //delete payment mandate
            $customer_account->debitOrders()->delete();

            //redirect
            return Redirect::route('customer-accounts.lists')->with('success', 'Customer account deleted');
        } else {
            //redirect
            return Redirect::route('customer-accounts.lists')->with('danger', 'Failed to delete customer account!');
        }
    }

    //export xls
    function export_xls(Request $request)
    {
        return Excel::download(new CustomerAccountExport($request->start_at, $request->end_at, $request->account_type_id, $request->account_id, $request->status, $request->keyword), date('YmdHis') . '-CustomerAccounts.xlsx');
    }

    //export pdf
    function export_pdf(Request $request)
    {
        //variables
        $start_at = $request->start_at;
        $end_at = $request->end_at;
        $account_type_id = $request->account_type_id;
        $account_id = $request->account_id;
        $status = $request->status;
        $keyword = $request->keyword;

        //customer accounts
        $customer_accounts = CustomerAccount::where_cond();

        //start data and end date
        if ($start_at != null && $end_at != null) {
            $start_at = date('Y-m-d', strtotime($start_at));
            $end_at = date('Y-m-d', strtotime($end_at));

            $customer_accounts = $customer_accounts->whereBetween('customer_accounts.created_at', [$start_at, $end_at]);
        }

        //payment channel
        if ($account_type_id != null)
            $customer_accounts = $customer_accounts->where('customer_accounts.account_type_id', $account_type_id);

        //bank account
        if ($account_id != null)
            $customer_accounts = $customer_accounts->where('customer_accounts.account_id', $account_id);

        //status
        if ($status != null)
            $customer_accounts = $customer_accounts->where('customer_accounts.status', $status);

        //keyword
        if ($keyword != null) {
            $customer_accounts = $customer_accounts->where(function ($query) use ($keyword) {
                $query->orWhere('customer_accounts.account_name', 'LIKE', "%$keyword%")
                    ->orWhere('customer_accounts.account_number', 'LIKE', "%$keyword%");
            });
        }

        //customer accounts
        $customer_accounts = $customer_accounts->get();

        //pdf
        $pdf = PDF::loadView('customer_accounts.export_pdf', compact('customer_accounts'))->setPaper('a4', 'landscape');

        // download PDF file with download method
        return $pdf->download(date('YmdHis') . '-CustomerAccounts.pdf');
    }
}
