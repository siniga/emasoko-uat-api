<?php

namespace App\Http\Controllers;

use App\Classes\CompanyAssessment;
use App\Exports\CompanyExport;
use App\Jobs\SendEmailJob;
use App\Models\Account;
use App\Models\AccountType;
use App\Models\Country;
use App\Models\DocumentCategory;
use App\Models\Partner;
use App\Models\PartnerAccount;
use App\Models\PartnerApproval;
use App\Models\PartnerCategory;
use App\Models\PartnerDocument;
use App\Models\PartnerStaff;
use App\Models\PartnerUser;
use App\Models\Region;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Stidges\CountryFlags\CountryFlag;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;
use PDF;

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
    public function index(Request $request)
    {
        $title = 'Service Providers';
        //has_allowed('roles', 'lists');

        //companies
        $companies = Partner::where('complete', '=', 1);

        if (isset($_POST['filter'])) {
            //passing variables
            $start_at = $request->input('start_at');
            $end_at = $request->input('end_at');
            $category_id = $request->input('category_id');
            $region_id = $request->input('region_id');
            $status = $request->input('status');
            $keyword = $request->input('keyword');

            //start data and end date
            if ($start_at != null && $end_at != null) {
                $start_at = date('Y-m-d', strtotime($start_at));
                $end_at = date('Y-m-d', strtotime($end_at));

                $companies = $companies->whereBetween('created_at', [$start_at, $end_at]);
            }

            //category
            if ($category_id != null) {
                $companies = $companies->where('partner_category_id', $category_id);
            }

            //region
            if ($region_id != null) {
                $companies = $companies->where('region_id', $region_id);
            }

            //status
            if ($status != null) {
                $companies = $companies->where('status', $status);
            }

            //keyword
            if ($keyword != null) {
                $companies = $companies->where(function ($query) use ($keyword) {
                    $query->orWhere('business_name', 'LIKE', "%$keyword%")
                        ->orWhere('tin_number', 'LIKE', "%$keyword%");
                });
            }

            //companies
            $companies = $companies->get();
        } else {
            //passing variables
            $start_at = null;
            $end_at = null;
            $category_id = null;
            $region_id = null;
            $status = null;
            $keyword = null;

            //companies
            $companies = $companies->get();
        }

        //populate data
        $categories = PartnerCategory::all();
        $regions = Region::orderBy('name', 'ASC')->get();

        $post_data = [
            'start_at' => $start_at,
            'end_at' => $end_at,
            'category_id' => $category_id,
            'region_id' => $region_id,
            'status' => $status,
            'keyword' => $keyword
        ];

        //render view
        return view('companies.lists', compact('title', 'companies', 'regions', 'categories', 'post_data'));
    }

    //export xls
    function export_xls(Request $request)
    {
        return Excel::download(new CompanyExport($request->start_at, $request->end_at, $request->category_id, $request->region_id, $request->status, $request->keyword), date('YmdHis') . '-Companies.xlsx');
    }

    //export pdf
    function export_pdf(Request $request)
    {
        //get data
        $start_at = $request->start_at;
        $end_at = $request->end_at;
        $category_id = $request->category_id;
        $region_id = $request->region_id;
        $status = $request->status;
        $keyword = $request->keyword;

        //companies
        $companies = Partner::where('complete', '=', 1);

        //start data and end date
        if ($start_at != null && $end_at != null) {
            $start_at = date('Y-m-d', strtotime($start_at));
            $end_at = date('Y-m-d', strtotime($end_at));

            $companies = $companies->whereBetween('created_at', [$start_at, $end_at]);
        }

        //category
        if ($category_id != null) {
            $companies = $companies->where('partner_category_id', $category_id);
        }

        //region
        if ($region_id != null) {
            $companies = $companies->where('region_id', $region_id);
        }

        //status
        if ($status != null) {
            $companies = $companies->where('status', $status);
        }

        //keyword
        if ($keyword != null) {
            $companies = $companies->where(function ($query) use ($keyword) {
                $query->orWhere('business_name', 'LIKE', "%$keyword%")
                    ->orWhere('tin_number', 'LIKE', "%$keyword%");
            });
        }

        //companies
        $companies = $companies->get();

        //pdf
        $pdf = PDF::loadView('companies.export_pdf', compact('companies'))->setPaper('a4', 'landscape');

        // download PDF file with download method
        return $pdf->download(date('YmdHis') . '-Companies.pdf');
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

    //company registration
    function registrationForm($uuid = null)
    {
        $title = 'Company Registration';

        $flag = new CountryFlag;

        //if uuid =! null
        $company = [];
        if ($uuid != null)
            $company = Partner::where(['uuid' => $uuid])->firstOrFail();

        //populate data
        $categories = PartnerCategory::all();
        $countries = Country::all();
        $regions = Region::orderBy('name', 'ASC')->get();

        $arr_countries = [];
        foreach ($countries as $val) {
            $arr_countries[$val->code] = $flag->get(strtoupper($val->initial)) . ' ' . $val->code;
        }

        //render view
        return view('companies.create.information', compact('title', 'categories', 'arr_countries', 'regions', 'company'));
    }

    //store company details
    function storeCompanyDetail(Request $request)
    {
        //validation 
        $validator = Validator::make($request->all(), [
            'tin_number' => 'required|unique:partners,tin_number',
            'business_name' => 'required',
            'partner_category_id' => 'required',
            'year_of_incorporation' => 'required|numeric',
            'number_of_staff' => 'required|numeric',
            'country_code' => 'required',
            'company_phone' => 'required',
            'company_email' => 'required|email|unique:partners,email',
            'region_id' => 'required',
            'physical_address' => 'required',
        ]);

        //capture if uuid is not null
        if ($request->input('partner_uuid') != null)
            $uuid = $request->input('partner_uuid');
        else
            $uuid = Str::uuid();

        //phone 
        $phone = $request->input('country_code') . cast_phone($request->input('company_phone'));

        //save company details
        $partner = Partner::firstOrNew([
            'uuid' => $uuid,
            'tin_number' => $request->input('tin_number')
        ]);
        $partner->partner_category_id = ucwords($request->input('category_id'));
        $partner->business_name = ucwords($request->input('business_name'));
        $partner->year_of_incorporation = ucwords($request->input('year_of_incorporation'));
        $partner->number_of_staff = $request->input('number_of_staff');
        $partner->region_id = $request->input('region_id');
        $partner->phone = $phone;
        $partner->email = $request->input('company_email');
        $partner->postal_address = $request->input('postal_address');
        $partner->physical_address = $request->input('physical_address');
        $partner->website = $request->input('company_website');
        $partner->next_step = 'company-documents/' . $uuid;
        $partner->save();

        //registration
        return Redirect::route('companies.register-documents', $uuid)->with('success', "Company documents saved!");
    }

    //company documents
    function companyDocumentsForm($uuid)
    {
        $title = 'Company Registration';

        //company
        $company = Partner::where(['uuid' => $uuid])->firstOrFail();

        //document categories
        $categories = DocumentCategory::all();

        //assigned documents
        $arr_documents = [];

        if ($company->partnerDocuments->isNotEmpty()) {
            foreach ($company->partnerDocuments as $val) {
                array_push($arr_documents, $val->key);
            }
        }

        //render view
        return view('companies.create.documents', compact('title', 'company', 'categories', 'arr_documents'));
    }

    //store company details
    function storeCompanyDocuments(Request $request)
    {
        //validation 

        //post data
        $id = $request->input('partner_id');
        $uuid = $request->input('partner_uuid');

        //partner
        $partner = Partner::findOrFail($id);
        $partner->next_step = 'company-accounts/' . $uuid;
        $partner->save();

        //document categories
        $categories = DocumentCategory::all();

        foreach ($categories as $val) {
            //business licennce
            if ($request->hasFile($val->key)) {
                $path = $request->file($val->key);
                $docsName = $path->getClientOriginalName();

                $path->move('assets/uploads/docs', $docsName);

                //partner documents
                $partner_document = PartnerDocument::firstOrNew([
                    'partner_id' => $id,
                    'partner_uuid' => $uuid,
                    'key' => $val->key,
                    'name' => $val->name
                ]);
                $partner_document->attachment = $docsName;
                $partner_document->save();
            }
        }

        //redirect
        return Redirect::route('companies.register-accounts', $uuid)->with('success', "Company documents saved!");
    }

    //company Account
    function companyAccountForm($uuid)
    {
        $title = 'Company Registration';

        //company
        $company = Partner::where(['uuid' => $uuid])->firstOrFail();

        //populate data
        $account_types = AccountType::all();
        $accounts = Account::all();

        //render view
        return view('companies.create.accounts', compact('title', 'company', 'account_types', 'accounts'));
    }

    //store company accounts
    function storeCompanyAccounts(Request $request)
    {
        //validation 

        //post data
        $id = $request->input('partner_id');
        $uuid = $request->input('partner_uuid');

        //partner
        $partner = Partner::findOrFail($id);
        $partner->next_step = 'company-staff/' . $uuid;
        $partner->save();

        //partner account
        if ($request->input('account_id') != '' && $request->input('account_name') != '' && $request->input('account_number') != '') {
            for ($i = 0; $i < sizeof($request->input('account_id')); $i++) {
                if ($request->input('account_id')[$i] != '' && $request->input('account_name')[$i] != '' && $request->input('account_name')[$i] != '') {
                    $partner_account = PartnerAccount::firstOrNew([
                        'partner_id' => $id,
                        'partner_uuid' => $uuid,
                        'account_type_id' => $request->input('account_type_id')[$i],
                        'account_id' => $request->input('account_id')[$i],
                        'account_name' => $request->input('account_name')[$i],
                        'account_number' => $request->input('account_number')[$i]
                    ]);
                    $partner_account->save();
                }
            }
        }

        //redirect
        return Redirect::route('companies.register-staff', $uuid)->with('success', "Company collection account(s) saved!");
    }


    //company staff
    function companyStaffForm($uuid)
    {
        $title = 'Company Registration';

        //company
        $company = Partner::where(['uuid' => $uuid])->firstOrFail();
        $countries = Country::all();

        //render view
        return view('companies.create.staff', compact('title', 'company', 'countries'));
    }


    //store company staff
    function storeCompanyStaff(Request $request)
    {
        //validation 

        //post data
        $id = $request->input('partner_id');
        $uuid = $request->input('partner_uuid');

        //partner
        $partner = Partner::findOrFail($id);
        $partner->next_step = 'company-authorized-person/' . $uuid;
        $partner->save();

        //partner staff
        if ($request->input('staff_name') != '' && $request->input('nida_number') != '' && $request->input('staff_phone') != '' && $request->input('staff_title') != '') {
            for ($i = 0; $i < sizeof($request->input('staff_name')); $i++) {
                if ($request->input('staff_name')[$i] != '' && $request->input('nida_number')[$i] != '' && $request->input('staff_phone')[$i] != '' && $request->input('staff_title')[$i] != '') {
                    $partner_staff = PartnerStaff::firstOrNew([
                        'partner_id' => $id,
                        'partner_uuid' => $uuid,
                        'NIDA_number' => $request->input('nida_number')[$i],
                        'name' => $request->input('staff_name')[$i],
                        'phone' => $request->input('staff_phone')[$i],
                        'email' => $request->input('staff_email')[$i],
                        'title' => $request->input('staff_title')[$i],

                    ]);
                    $partner_staff->save();
                }
            }
        }

        //redirect
        return Redirect::route('companies.register-authorized-person', $uuid)->with('success', "Company authorized personnel(s) saved!");
    }


    //authorized person
    function companyAuthorizedPersonForm($uuid)
    {
        $title = 'Company Registration';

        //company
        $company = Partner::where(['uuid' => $uuid])->firstOrFail();

        //render view
        return view('companies.create.authorized_person', compact('title', 'company'));
    }

    //store company authorized person
    function storeCompanyAuthorizedPerson(Request $request)
    {
        //validation 

        //post data
        $id = $request->input('partner_id');
        $uuid = $request->input('partner_uuid');

        //partner
        $partner = Partner::findOrFail($id);
        $partner->next_step = 'company-info-overview/' . $uuid;
        $partner->save();

        //token and password
        $token = Str::random(64);
        $password = $request->input('password');

        //create user to store data
        $user = User::firstOrNew(['email' => $request->input('email')]);
        $user->name = $request->input('name');
        $user->verify_token = $token;
        $user->password = Hash::make($password);
        $user->save();

        //insert role
        $user->roles()->attach(2);

        //partner user
        $partner_user = PartnerUser::firstOrNew([
            'user_id' => $user->id,
            'partner_id' => $id,
            'partner_uuid' => $uuid
        ]);
        $partner_user->authorize = 1;
        $partner_user->save();

        //redirect
        return Redirect::route('companies.preview', $uuid)->with('success', "Company authorized credentials saved!");
    }

    //company info overview
    function companyPreview($uuid)
    {
        $title = 'Company Registration';

        //company
        $company = Partner::where(['uuid' => $uuid])->firstOrFail();

        //render view
        return view('companies.create.preview', compact('title', 'company'));
    }

    //confirm application
    function confirmRegistration(Request $request)
    {
        //validation 

        //post data
        $id = $request->input('partner_id');
        $uuid = $request->input('partner_uuid');

        $complete = 0;
        $status = 0;
        $action = "";
        if (isset($_POST['save'])) {
            $status = 0;
            $complete = 0;
            $action = 'save';
        } else if (isset($_POST['submit'])) {
            $status = 1;
            $complete = 1;
            $action = 'submit';

            //store company aapprovals
            $arr_assessment_criteria = [
                'Company_Information' => 'Company Information',
                'Company_Documents' => 'Company Documents',
                'Company_Collection_Accounts' => 'Company Collection Accounts',
                'Company_Authorized_Representative' => 'Company Authorized Representative',
            ];

            foreach ($arr_assessment_criteria as $key => $label) {
                $partner_approval = PartnerApproval::firstOrNew([
                    'partner_id' => $id,
                    'key' => $key,
                    'label' => $label
                ]);
                $partner_approval->save();
            }
        }

        //partner
        $partner = Partner::findOrFail($id);
        $partner->next_step = 'company-info-overview/' . $uuid;
        $partner->complete = $complete;
        $partner->status = $status;
        $partner->save();

        //redirect
        if ($action == 'save')
            return Redirect::route('companies.preview', $uuid)->with('success', 'Service provider information saved');
        elseif ($action == 'submit')
            return Redirect::route('companies.preview', $uuid)->with('success', 'Service provider registration complete');
    }

    //cancel registration
    function cancelRegistration($uuid)
    {
        //company
        $company = Partner::where(['uuid' => $uuid])->firstOrFail();

        if ($del = $company->delete()) {
            //delete partner accounts
            PartnerAccount::where(['partner_id' => $company->id])->delete();

            //delete partner approvals
            PartnerApproval::where(['partner_id' => $company->id])->delete();

            //delete partner docs
            PartnerDocument::where(['partner_id' => $company->id])->delete();

            //delete partner staff
            PartnerStaff::where(['partner_id' => $company->id])->delete();

            //delete partner users
            PartnerUser::where(['partner_id' => $company->id])->delete();

            //redirect
            return Redirect::route('companies.index')->with('success', 'Company registration canceled');
        } else {
            return Redirect::route('companies.index')->with('danger', 'Failed to cancel company registration');
        }
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
        $title = 'Edit Company';

        $flag = new CountryFlag;

        //company
        $company = Partner::findOrFail($id);

        //populate data
        $categories = PartnerCategory::all();
        $countries = Country::all();
        $regions = Region::orderBy('name', 'ASC')->get();

        $arr_countries = [];
        foreach ($countries as $val) {
            $arr_countries[$val->code] = $flag->get(strtoupper($val->initial)) . ' ' . $val->code;
        }

        //document categories
        $docs_categories = DocumentCategory::all();

        //assigned documents
        $arr_documents = [];

        if ($company->partnerDocuments->isNotEmpty()) {
            foreach ($company->partnerDocuments as $val) {
                $arr_documents[] = [
                    'key' => $val->key,
                    'attachment' => $val->attachment
                ];
            }
        }

        //render view
        return view('companies.edit', compact('title', 'company', 'categories', 'countries', 'regions', 'arr_countries', 'docs_categories', 'arr_documents'));
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
        $validator = Validator::make($request->all(), [
            'tin_number' => 'required',
            'business_name' => 'required',
            'partner_category_id' => 'required',
            'year_of_incorporation' => 'required|numeric',
            'number_of_staff' => 'required|numeric',
            'country_code' => 'required',
            'company_phone' => 'required',
            'company_email' => 'required|email',
            'region_id' => 'required',
            'physical_address' => 'required',
        ]);

        //phone 
        $phone = $request->input('country_code') . cast_phone($request->input('company_phone'));

        //company
        $partner = Partner::findOrFail($id);
        $partner->tin_number = $request->input('tin_number');
        $partner->partner_category_id = ucwords($request->input('category_id'));
        $partner->business_name = ucwords($request->input('business_name'));
        $partner->year_of_incorporation = ucwords($request->input('year_of_incorporation'));
        $partner->number_of_staff = $request->input('number_of_staff');
        $partner->region_id = $request->input('region_id');
        $partner->phone = $phone;
        $partner->email = $request->input('company_email');
        $partner->postal_address = $request->input('postal_address');
        $partner->physical_address = $request->input('physical_address');
        $partner->website = $request->input('company_website');
        $partner->save();

        //redirect
        return Redirect::route('companies.index')->with('success', 'Company information updated');
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    function destroy($id)
    {
        //'Delete Company';
        // has_allowed('cars', 'delete');

        //company
        $company = Partner::findOrFail($id);

        if ($del = $company->delete()) {
            //delete partner accounts
            PartnerAccount::where(['partner_id' => $company->id])->delete();

            //delete partner approvals
            PartnerApproval::where(['partner_id' => $company->id])->delete();

            //delete partner docs
            PartnerDocument::where(['partner_id' => $company->id])->delete();

            //delete partner staff
            PartnerStaff::where(['partner_id' => $company->id])->delete();

            //delete partner users
            PartnerUser::where(['partner_id' => $company->id])->delete();

            //redirect
            return Redirect::route('companies.index')->with('success', 'Company information deleted');
        } else {
            return Redirect::route('companies.index')->with('danger', 'Failed to delete company');
        }
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
            $view = 'companies.assessment.information';
        else if ($page == 'companyDocuments')
            $view = 'companies.assessment.documents';
        else if ($page == 'collectionAccount')
            $view = 'companies.assessment.collection_account';
        else if ($page == 'authorizedRespesentative')
            $view = 'companies.assessment.authorized_representative';
        else if ($page == 'ConfirmAssessment')
            $view = 'companies.assessment.confirm';

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
        $boolean = $company_assessment->process_assessment($company->id);

        if ($boolean == true) {
            $message = 'Company assessment completed and approval email sent to company authorized representative!';
        } else if ($boolean == false) {
            $message = 'Company assessment confirmed but still there are pending or rejected criteria!';
        }

        //response
        return response()->json(['error' => false, 'success_msg' => $message], 200);
    }
}
