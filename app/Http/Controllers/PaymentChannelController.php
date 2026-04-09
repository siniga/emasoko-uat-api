<?php

namespace App\Http\Controllers;

use App\Models\Account;
use App\Models\AccountType;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Redirect;

class PaymentChannelController extends Controller
{
    /**
     * Display a listing of the resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function index()
    {
        $title = 'Payment Channels';
        //has_allowed('roles', 'lists');

        //roles
        $accounts = Account::all();

        return view('payment_channels.lists', compact('accounts', 'title'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        $title = 'Create New';
        //has_allowed('roles', 'create');

        //account types
        $account_types = AccountType::all();
        $periodicity_options = Account::periodicityOptions();

        return view('payment_channels.create', compact('title', 'account_types', 'periodicity_options'));
    }

    /**
     * Store a newly created resource in storage.
     *
     * @param  \Illuminate\Http\Request  $request
     * @return \Illuminate\Http\Response
     */
    public function store(Request $request)
    {
        //validation
        $this->validate(
            $request,
            [
                'account_type_id' => 'required',
                'name' => 'required|unique:accounts',
                'status' => 'required',
                'description' => 'required|string',
                'allowed_periodicities' => 'required|array|min:1',
                'allowed_periodicities.*' => 'required|string|in:DAILY,WEEKLY,MONTHLY,QUARTERLY,YEARLY',
            ],
            [
                'account_type_id.required' => 'Payment Channel required',
                'name.required' => 'Name required',
                'status.required' => 'Status required',
                'description.required' => 'Description required',
                'allowed_periodicities.required' => 'At least one periodicity required',
            ]
        );

        //create new channel
        $account = new Account();
        $account->account_type_id = $request->input('account_type_id');
        $account->name = $request->input('name');
        $account->active = $request->input('status');
        $account->description = $request->input('description');
        $account->allowed_periodicities = array_values($request->input('allowed_periodicities', []));
        $account->save();

        //redirect
        return Redirect::route('payment-channels.index')->with('success', 'Payment channel registered');
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($id)
    {
        //
    }

    /**
     * Show the form for editing the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function edit($id)
    {
        $title = 'Edit Payment Channel';
        //has_allowed('roles', 'edit');

        //account
        $account = Account::findOrFail($id);

        //account types
        $account_types = AccountType::all();
        $periodicity_options = Account::periodicityOptions();

        return view('payment_channels.edit', compact('account', 'account_types', 'title', 'periodicity_options'));
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
        //validation
        $this->validate(
            $request,
            [
                'account_type_id' => 'required',
                'name' => 'required',
                'status' => 'required',
                'description' => 'required|string',
                'allowed_periodicities' => 'required|array|min:1',
                'allowed_periodicities.*' => 'required|string|in:DAILY,WEEKLY,MONTHLY,QUARTERLY,YEARLY',
            ],
            [
                'account_type_id.required' => 'Payment Channel required',
                'name.required' => 'Name required',
                'status.required' => 'Status required',
                'description.required' => 'Description required',
                'allowed_periodicities.required' => 'At least one periodicity required',
            ]
        );

        //create new channel
        $account = Account::findOrFail($id);
        $account->account_type_id = $request->input('account_type_id');
        $account->name = $request->input('name');
        $account->active = $request->input('status');
        $account->description = $request->input('description');
        $account->allowed_periodicities = array_values($request->input('allowed_periodicities', []));
        $account->save();

        //redirect
        return Redirect::route('payment-channels.index')->with('success', 'Payment channel updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function delete($id)
    {
        $title = 'Delete Payment Channel';
        //has_allowed('roles', 'edit');

        //account
        $account = Account::findOrFail($id);

        //delete account
        if ($del = $account->delete()) {
            return Redirect::route('payment-channels.index')->with('success', 'Payment channel deleted.');
        }else{
            return Redirect::route('payment-channels.index')->with('danger', 'Failed to delete payment channel.');
        }
    }
}
