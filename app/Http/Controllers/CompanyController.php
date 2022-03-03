<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use App\Models\PartnerUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;

class CompanyController extends Controller
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
    public function index()
    {
        $title = 'Companies';
        //has_allowed('roles', 'lists');

        $companies = Partner::all();

        //render view
        return view('companies.lists', compact('companies', 'title'));
    }

    /**
     * Show the form for creating a new resource.
     *
     * @return \Illuminate\Http\Response
     */
    public function create()
    {
        //render view
        return view('companies.create');
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
        $title = 'Company Information';

        //company
        $company = Partner::findOrFail($id);

        //routes
        $action = "";
        $route = 'companies.show';
        if ($company->status == 1) {
            $action = "Verify Company";
            $route = 'companies.verify';
        } else if ($company->status == 2) {
            $action = "Approve Company";
            $route = 'companies.approve';
        }

        //render view
        return view('companies.show', compact('title', 'company', 'route', 'action'));
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

    //verify company
    function verify($id)
    {
        //company
        $company = Partner::findOrFail($id);
        $company->status = 2;
        $company->save();

        //send email

        //redirect 
        return Redirect::back()->withSuccess("Company verified, Waiting for approval");
    }

    //approve company
    function approve($id)
    {
        //company
        $company = Partner::findOrFail($id);
        $company->status = 3;
        $company->save();

        //update user
        $p_user = PartnerUser::where(['partner_id' => $id])->first();

        //user
        $user = User::findOrFail($p_user->user_id);
        $user->active = 1;
        $user->save();

        //construct message
        $message = "Company registration has been approved by eMakato.";
        $message .= "<br/>You can start using your eMakato account now. Login to your account by <a href=" . url('login') . ">click here.</a>";

        //send email
        Mail::send(
            ['html' => 'emails.template'],
            ['name' => $user->name, 'body' => $message],
            function ($message) use ($user) {
                $message->to($user->email);
                $message->subject('Company Approval');
                $message->from('renfridfrancis@gmail.com', 'eMakato');
            }
        );

        //redirect 
        return Redirect::back()->withSuccess("Company approved");
    }
}
