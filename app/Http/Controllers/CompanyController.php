<?php

namespace App\Http\Controllers;

use App\Classes\Authentication;
use App\Classes\Messaging;
use App\Events\CorporateStatus;
use App\Exports\CompanyExport;
use App\Http\Requests\CompanyAccountRequest;
use App\Http\Requests\CompanyDetailRequest;
use App\Http\Requests\CompanyStaffRequest;
use App\Jobs\CorporateRegistrationJob;
use App\Listeners\FetchCorporateStatus;
use App\Models\Account;
use App\Models\AccountType;
use App\Models\Country;
use App\Models\Currency;
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
use App\Services\BankApiService;
use App\Services\CompanyService;
use App\Services\BankPayloadService;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Stidges\CountryFlags\CountryFlag;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;
use PDF;


class CompanyController extends Controller
{
    public function __construct(protected CompanyService $companyService, protected BankPayloadService $payloadService, protected Messaging $messaging, protected BankApiService $bankApi, protected Authentication $auth)
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
        $companies = Partner::orderBy('created_at', 'desc');

        if (isset($_POST['filter'])) {
            //all request
            $requests = $request->all();

            $companies = $companies->when($requests, function ($query) use ($requests) {
                $query->withFilters($requests);
            });

            //number of companies
            $number_of_companies = $companies->count();

            //companies
            $companies = $companies->get();
        } else {
            //number of companies
            $number_of_companies = $companies->count();
            //companies
            $companies = $companies->paginate(50);
        }

        //populate data
        $categories = PartnerCategory::toBase()->get();
        $regions = Region::orderBy('name', 'ASC')->toBase()->get();

        $post_data = [
            'start_at' => $request->start_at ?? null,
            'end_at' => $request->end_at ?? null,
            'category_id' => $request->category_id ?? null,
            'region_id' => $request->region_id ?? null,
            'status' => $request->status ?? null,
            'keyword' => $request->keyword ?? null
        ];

        //render view
        return view('companies.lists', compact('title', 'companies', 'number_of_companies', 'regions', 'categories', 'post_data'));
    }

    //export xls
    function exportXLS(Request $request)
    {
        return Excel::download(new CompanyExport($request->all()), date('YmdHis') . '-Companies.xlsx');
    }

    //export pdf
    function exportPDF(Request $request)
    {
        //all request
        $requests = $request->all();

        //companies
        $companies = Partner::where('complete', '=', 1);

        $companies = $companies->when($requests, function ($query) use ($requests) {
            $query->withFilters($requests);
        });

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
    function storeCompanyDetail(CompanyDetailRequest $request)
    {
        // validated request
        $validated = $request->validated();

        //capture if uuid is not null
        if ($request->input('partner_uuid') != null)
            $uuid = $request->input('partner_uuid');
        else
            $uuid = Str::uuid();

        //validate tin number
        $partner = Partner::where(['tin_number' => $request->input('tin_number')])->first();
        if ($partner) {
            //check if uuid with db_uuid
            if ($partner->uuid != $uuid)
                return response()->json(['success' => false, 'message' => 'Tin number already exist.'], 200);
        }

        //validate email
        $partner = Partner::where(['email' => $request->input('company_email')])->first();
        if ($partner) {
            //check if uuid with db_uuid
            if ($partner->uuid != $uuid)
                return response()->json(['success' => false, 'message' => 'Email already taken.'], 200);
        }

        DB::beginTransaction();
        $partner = $this->companyService->createInformation((object) $request->all(), $uuid);
        DB::commit();

        //response
        return response()->json(['success' => true, 'uuid' => $uuid, 'message' => 'Service provider information saved!'], 200);
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
        //validate documents

        //post data
        $id = $request->input('partner_id');
        $uuid = $request->input('partner_uuid');

        //next stage
        $partner = $this->companyService->updateNextStep($id, 'companies/accounts/' . $uuid);

        //categories
        $categories = DocumentCategory::all();

        foreach ($categories as $val) {
            if ($request->hasFile($val->key)) {
                $path = $request->file($val->key);
                $docsName = $path->getClientOriginalName();

                $path->move('assets/uploads/docs', $docsName);

                //partner documents
                $partner_document = PartnerDocument::firstOrNew([
                    'partner_id' => $partner->id,
                    'partner_uuid' => $partner->uuid,
                    'key' => $val->key,
                    'name' => $val->name
                ]);
                $partner_document->attachment = $docsName;
                $partner_document->save();
            }
        }

        //response
        return response()->json(['success' => true, 'uuid' => $uuid, 'message' => 'Service provider documents uploaded'], 200);
    }

    //company Account
    function companyAccountForm($uuid)
    {
        $title = 'Company Registration';

        //company
        $company = Partner::where(['uuid' => $uuid])->firstOrFail();

        //populate data
        $account_types = AccountType::toBase()->get();
        $accounts = Account::toBase()->get();
        $currencies = Currency::toBase()->get();

        //render view
        return view('companies.create.accounts', compact('title', 'company', 'account_types', 'accounts', 'currencies'));
    }

    //store company accounts
    function storeCompanyAccounts(CompanyAccountRequest $request)
    {
        // validated request
        $validated = $request->validated();

        //post data
        $id = $request->input('partner_id');
        $uuid = $request->input('partner_uuid');

        //TODO: validate account number and customer code

        //next stage
        $partner = $this->companyService->updateNextStep($id, 'companies/staff/' . $uuid);

        DB::beginTransaction();
        $this->companyService->createCollectionAccount($request->all(), $partner);
        DB::commit();

        //response
        return response()->json(['success' => true, 'uuid' => $uuid, 'message' => 'Service Provider collection account(s) saved'], 200);
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
    function storeCompanyStaff(CompanyStaffRequest $request)
    {
        // validated request
        $validated = $request->validated();

        //post data
        $id = $request->input('partner_id');
        $uuid = $request->input('partner_uuid');

        //validate email and nida number
        if ($validated['staff_name']) {
            for ($i = 0; $i < sizeof($validated['staff_name']); $i++) {
                //validate email
                $partner_staff = PartnerStaff::where(['email' => $validated['staff_email'][$i]])->first();
                if ($partner_staff) {
                    if ($partner_staff->partner_id != $id) {
                        return response()->json(['success' => false, 'message' => 'Email already exists'], 200);
                    }
                }

                //validate nida number
                $partner_staff = PartnerStaff::where(['nida_number' => $validated['nida_number'][$i]])->first();
                if ($partner_staff) {
                    if ($partner_staff->partner_id != $id) {
                        return response()->json(['success' => false, 'message' => 'NIDA number already exists'], 200);
                    }
                }
            }
        }

        //next stage
        $partner = $this->companyService->updateNextStep($id, 'companies/preview/' . $uuid);

        DB::beginTransaction();
        $this->companyService->createStaff($request->all(), $partner);
        DB::commit();

        //response
        return response()->json(['success' => true, 'uuid' => $uuid, 'message' => 'Service Provider staff saved'], 200);
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
        //validate the request data
        Log::debug("== validation ==");
        Log::debug($request->all());


        // $validator = Validator::make($request->all(), [
        //     'email'  =>  'required|email|max:255|unique:users',
        // ]);

        // if ($validator->fails()) {
        //     return new JsonResponse(['success' => false, 'message' => $validator->errors()], 422);
        // }

        //post data
        $id = $request->input('partner_id');
        $uuid = $request->input('partner_uuid');

        //next stage
        $partner = $this->companyService->updateNextStep($id, 'companies/preview/' . $uuid);

        DB::beginTransaction();
        $this->companyService->createCredential((object) $request->all(), $partner);
        DB::commit();

        //response
        return response()->json(['success' => true, 'uuid' => $uuid, 'message' => 'Service Provider credentials saved'], 200);
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

    //confirm  application
    function confirmRegistration(Request $request)
    {
        //post data
        $uuid = $request->input('uuid');

        //partner
        $partner = Partner::where(['uuid' => $uuid])->firstOrFail();

        //update status of partner
        $partner->next_step = 'companies/preview/' . $uuid;
        $partner->status = 100;
        $partner->complete = 1;
        $partner->updated_by = auth()->id();
        $partner->save();

        //send assessment alert
        $this->companyService->sendAssessmentAlert($partner);

        //response
        return response()->json(['success' => false, 'success_msg' => 'Service provider successful registered', 'uuid' => $uuid], 200);
    }

    //saving application
    function savingRegistration(Request $request)
    {
        //post data
        $uuid = $request->input('uuid');

        //partner
        $partner = Partner::where(['uuid' => $uuid])->firstOrFail();

        //update status of partner
        $partner->status = 0;
        $partner->complete = 0;
        $partner->save();

        //response
        return response()->json(['success' => false, 'success_msg' => 'Service Provider information saved!', 'uuid' => $uuid], 200);
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
            return Redirect::route('companies.index')->with('success', 'Service Provider registration canceled');
        } else {
            return Redirect::route('companies.index')->with('danger', 'Failed to cancel service provider registration');
        }
    }

    /**
     * Display the specified resource.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    public function show($uuid)
    {
        $title = 'Service Provider Information';

        //service provider
        $company = Partner::where(['uuid' => $uuid])->firstOrFail();

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
    public function edit($uuid)
    {
        $title = 'Edit Service Provider';

        //service provider
        $company = Partner::where(['uuid' => $uuid])->firstOrFail();

        //populate data
        $flag = new CountryFlag;
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
    public function update(CompanyDetailRequest $request)
    {
        // validated request
        $validated = $request->validated();
        $uuid = $request->input('partner_uuid');

        //create or update service provider    
        $partner = $this->companyService->createInformation((object) $request->all(), $uuid);

        //response
        return response()->json(['success' => true, 'uuid' => $uuid, 'message' => 'Service provider information updated'], 200);
    }

    /**
     * Remove the specified resource from storage.
     *
     * @param  int  $id
     * @return \Illuminate\Http\Response
     */
    function destroy($uuid)
    {
        //'Delete Company';
        // has_allowed('cars', 'delete');

        //service provider
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
            return Redirect::route('companies.lists')->with('success', 'Service provider deleted');
        } else {
            return Redirect::route('companies.lists')->with('danger', 'Failed to delete service provider');
        }
    }

    //company assessment
    function companyAssessment($uuid)
    {
        $title = "Company Assessment";

        //service provider
        $company = Partner::where(['uuid' => $uuid])->firstOrFail();

        //render view
        return view('companies.assessments', compact('title', 'company'));
    }

    //get assessment approval
    function companyAssessmentStages(Request $request)
    {
        $uuid = $request->input('uuid');

        //service provider
        $company = Partner::where(['uuid' => $uuid])->firstOrFail();

        //partner approval
        $approval = PartnerApproval::where(['partner_id' => $company->id])->orderBy('sequence', 'asc')->get();

        //response
        return response()->json(['error' => false, 'data' => $approval], 200);
    }

    //get assessment approval
    function companyAssessmentApproval(Request $request)
    {
        $uuid = $request->input('uuid');
        $stage = $request->input('stage');

        //service provider
        $company = Partner::where(['uuid' => $uuid])->firstOrFail();

        //partner approval
        $approval = PartnerApproval::where(['partner_id' => $company->id, 'key' => $stage])->first();

        //assign data
        $data = [
            'uuid' => $uuid,
            'stage' => $stage,
            'criteria' => $approval->status,
            'remarks' => $approval->remarks
        ];

        //response
        return response()->json(['error' => false, 'data' => $data], 200);
    }

    //store company assessment
    function storeAssessment(Request $request)
    {
        //requests
        $uuid = $request->input('uuid');
        $stage = $request->input('stage');
        $criteria = $request->input('criteria');

        //service provider
        $company = Partner::where(['uuid' => $uuid])->firstOrFail();
        $company->status = 2;
        $company->save();

        //update partner approval
        $partner_approval = PartnerApproval::where(['partner_id' => $company->id, 'key' => $stage])->first();
        $partner_approval->status = $criteria;
        $partner_approval->remarks = $request->input('remarks');
        $partner_approval->save();

        //response
        return response()->json(['error' => false, 'uuid' => $uuid, 'criteria' => $criteria], 200);
    }

    //confirm/reject company assessment
    public function confirmAssessment(Request $request)
    {
        //requests
        $uuid = $request->post('uuid');

        //service provider
        $partner = Partner::where(['uuid' => $uuid])->firstOrFail();

        //process assessment
        $http_status_code = $this->companyService->processAssessment($partner, $request->all());
        Log::debug($http_status_code);

        if ($http_status_code == 200) {
            //TODO: Check if corporate has NMB partner accounts
            $partner_account = PartnerAccount::where(['partner_id' => $partner->id, 'account_id' => 1, 'send_verification' => 1])->first();

            if ($partner_account) {
                //create payload
                $payload = $this->payloadService->corporateRegistrationPayload($partner, $partner_account);

                //Create Request to NMB
                dispatch(new CorporateRegistrationJob($payload));
            }

            //response
            return response()->json(['error' => false, "status" => $http_status_code, "message" => 'Corporate Registration approved and pending approval from NMB.'], 200);
        } else if ($http_status_code == 500) {
            return response()->json(['error' => false, "status" => $http_status_code, "remarks" => $request->remarks, "message" => 'Corporate registration rejected.'], 200);
        }
    }

    //query status
    function fetchCorporateStatus(Request $request)
    {
        //requests
        $uuid = $request->post('uuid');

        //service provider
        $partner = Partner::where(['uuid' => $uuid])->firstOrFail();

        //create payload
        $payload = $this->payloadService->corporateStatusPayload($partner);

        //api service
        $arr_response = $this->bankApi->corporateStatusRequest($payload);

        if ($arr_response) {
            $http_response_code = $arr_response[0];
            $remarks = $arr_response[1];

            if ($http_response_code == 202) {
                return response()->json(
                    [
                        "success" => true,
                        'success_msg' => "Corporate status updated",
                        "http_response_code" => $http_response_code,
                        "remarks" => $remarks
                    ],
                    200
                );
            } else if ($http_response_code == 500) {
                return response()->json(
                    [
                        "success" => false,
                        'success_msg' => "Failed to update corporate status, Try again...",
                        "http_response_code" => $http_response_code,
                        "remarks" => $remarks
                    ],
                    200
                );
            } else {
                return response()->json(
                    [
                        "success" => false,
                        'success_msg' => "Failed to update corporate status, Try again...",
                        "http_response_code" => 500,
                        "remarks" => $remarks
                    ],
                    200
                );
            }
        } else {
            return response()->json(
                [
                    "success" => false,
                    'success_msg' => "Failed to update corporate status, Try again...",
                    "http_response_code" => 500,
                    "remarks" => "API does not response, Kindly try again or contact support."
                ],
                200
            );
        }
    }
}
