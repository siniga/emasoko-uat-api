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
use Barryvdh\DomPDF\Facade\Pdf;

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
        $customer_accounts = CustomerAccount::filterCondition();
        $customer_accounts->with(['customer', 'account', 'createdBy.partners.partner']);
        $requests = $request->all();

        if (isset($_POST['filter'])) {
            $customer_accounts = $customer_accounts->when($requests, function ($query) use ($requests) {
                $query->withFilters($requests);
            });

            //customer accounts
            $customer_accounts = $customer_accounts->orderBy('customer_accounts.created_at', 'DESC')->get();
        } else {
            //customer accounts
            $customer_accounts = $customer_accounts->orderBy('customer_accounts.created_at', 'DESC')->paginate(50);
        }

        $statsBase = CustomerAccount::filterCondition();
        $statsBase = $statsBase->when($requests, function ($query) use ($requests) {
            $query->withFilters($requests);
        });

        $listStats = [
            'total' => (clone $statsBase)->count(),
            'approved' => (clone $statsBase)->where('status', 'approved')->count(),
            'partial' => (clone $statsBase)->where('status', 'partial')->count(),
            'rejected' => (clone $statsBase)->where('status', 'rejected')->count(),
        ];

        //post data
        $data = [
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
        return view('customer_accounts.lists', compact('title', 'account_types', 'accounts', 'customer_accounts', 'data', 'listStats'));
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

        $mandates = $customer_account->debitOrders;
        $approvedMandates = $mandates->where('approved', 1);
        $detailStats = [
            'mandates_total' => $mandates->count(),
            'mandates_approved' => $approvedMandates->count(),
            'mandates_pending' => $mandates->where('approved', 0)->count(),
            'mandates_total_value' => (float) $mandates->sum('total_amount'),
            'mandates_approved_value' => (float) $approvedMandates->sum('total_amount'),
        ];

        //render view
        return view('customer_accounts.show', compact('title', 'customer_account', 'detailStats'));
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
        //all request
        $requests = $request->all();

         //customer accounts
         $customer_accounts = CustomerAccount::filterCondition();

        $customer_accounts = $customer_accounts->when($requests, function ($query) use ($requests) {
            $query->withFilters($requests);
        });

        //customer accounts
        $customer_accounts = $customer_accounts->orderBy('customer_accounts.created_at', 'DESC')->get();

        //pdf
        $pdf = Pdf::loadView('customer_accounts.pdf.report', compact('customer_accounts'))->setPaper('a4', 'landscape');

        // download PDF file with download method
        return $pdf->download(date('YmdHis') . '-Customer_Accounts.pdf');
    }
}
