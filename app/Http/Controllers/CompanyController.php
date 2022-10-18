<?php

namespace App\Http\Controllers;

use App\Classes\CompanyAssessment;
use App\Models\Partner;
use App\Models\PartnerApproval;
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
        $title = 'Subscribers';
        //has_allowed('roles', 'lists');

        $companies = Partner::where('complete', '=', 1)->get();

        //render view
        return view('companies.lists', compact('title', 'companies'));
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

        //company information  approval
        $company_info_approval = PartnerApproval::where(['partner_id' => $company->id, 'key' => 'Company_Information'])->first();

        //company documents
        $company_docs_approval = PartnerApproval::where(['partner_id' => $company->id, 'key' => 'Company_Documents'])->first();

        //collection accounts
        $company_accounts_approval = PartnerApproval::where(['partner_id' => $company->id, 'key' => 'Company_Collection_Accounts'])->first();

        //authorized personnels
        $company_staff_approval = PartnerApproval::where(['partner_id' => $company->id, 'key' => 'Company_Authorized_Representative'])->first();

        //render view
        return view('companies.show', compact('title', 'company', 'company_info_approval', 'company_docs_approval', 'company_accounts_approval', 'company_staff_approval'));
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

    //company assessment
    function companyAssessment(Request $request, $id)
    {
        $title = "Company Assessment";

        //page
        $page = $request->get('page');

        //company
        $company = Partner::findOrFail($id);

        if ($page == 'companyInformation')
            $view = 'companies.information_assessment';
        else if ($page == 'companyDocuments')
            $view = 'companies.documents_assessment';
        else if ($page == 'collectionAccount')
            $view = 'companies.collection_account_assessment';
        else if ($page == 'authorizedRespesentative')
            $view = 'companies.authorized_representative_assessment';
        else if ($page == 'ConfirmAssessment')
            $view = 'companies.confirm_assessment';

        //render view
        return view($view, compact('title', 'company'));
    }

    //company assessment
    function store_assessment(Request $request)
    {
        //validation

        //requests
        $company_id = $request->input('company_id');
        $key = $request->input('key');

        //company
        $company = Partner::findOrFail($company_id);
        $company->status = 2;
        $company->save();

        //update partner approval
        $partner_approval = PartnerApproval::where(['partner_id' => $company->id, 'key' => $key])->first();
        $partner_approval->status = $request->input('assessment_criteria');
        $partner_approval->remarks = $request->input('remarks');
        $partner_approval->save();

        //check for key
        if ($key == 'Company_Information')
            $message = 'Company information assessment saved';
        else if ($key == 'Company_Documents')
            $message = 'Company documents assessment saved';
        else if ($key == 'Company_Collection_Accounts')
            $message = 'Company collection accounts assessment saved';
        else if ($key == 'Company_Authorized_Representative')
            $message = 'Company authorized representative assessment saved';

        //response
        return response()->json(['error' => false, 'success_msg' => $message], 200);
    }

    //verify company
    function confirm_assessment(Request $request)
    {
        //validation

        //requests
        $company_id = $request->input('company_id');

        //company
        $company = Partner::findOrFail($company_id);

        //call company assessment 
        $company_assessment = new CompanyAssessment();
        $boolean = $company_assessment->process($company->id);

        if ($boolean == true) {
            $message = 'Company assessment completed and approval email sent to company authorized representative!';
        } else if ($boolean == false) {
            $message = 'Company assessment confirmed but still there are pending or rejected criteria!';
        }

        //response
        return response()->json(['error' => false, 'success_msg' => $message], 200);
    }
}
