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
use App\Models\DebitOrder;
use App\Models\Installment;
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
use Barryvdh\DomPDF\Facade\Pdf;
use Carbon\Carbon;

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
        $companies = Partner::filterCondition();

        //all request
        $requests = $request->all();

        if (isset($_POST['filter'])) {
            Log::debug($requests);

            $companies = $companies->when($requests, function ($query) use ($requests) {
                $query->withFilters($requests);
            });
        }

        $companyStats = [
            'total' => (clone $companies)->count(),
            'approved' => (clone $companies)->where('approved', 1)->count(),
            'pending' => (clone $companies)->where('approved', 0)->count(),
            'failed' => (clone $companies)->where('approved', 99)->count(),
            'verified_accounts' => (clone $companies)->whereHas('partnerAccounts', function ($query) {
                $query->where('is_verified', 1);
            })->count(),
        ];

        //number of companies
        $number_of_companies = $companyStats['total'];

        //companies
        if (isset($_POST['filter'])) {
            $companies = $companies->get();
        } else {
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
            'approved' => $request->approved ?? null,
            'is_verified' => $request->is_verified ?? null,
            'keyword' => $request->keyword ?? null
        ];

        //render view
        return view('companies.lists', compact('title', 'companies', 'number_of_companies', 'companyStats', 'regions', 'categories', 'post_data'));
    }

    //export xls
    public function exportXLS(Request $request)
    {
        return Excel::download(new CompanyExport($request->all()), date('YmdHis') . '-Companies.xlsx');
    }

    //export pdf
    public function exportPDF(Request $request)
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
        $pdf = Pdf::loadView('companies.export_pdf', compact('companies'))->setPaper('a4', 'landscape');

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
    public function registrationForm($uuid = null)
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
    public function storeCompanyDetail(CompanyDetailRequest $request)
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
    public function companyDocumentsForm($uuid)
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
    public function storeCompanyDocuments(Request $request)
    {
        //validate documents
        $validator = Validator::make(
            $request->all(),
            [
                'Business_Licence' => 'file|max:5048|mimes:pdf',
                'Certificate_of_incorporation' => 'file|max:5048|mimes:pdf',
                'TIN_Certificate' => 'file|max:5048|mimes:pdf',
                'BRELA_report_for_shareholders' => 'file|max:5048|mimes:pdf',
                'Audited_financial_statement' => 'file|max:5048|mimes:pdf',
                'Introduction_letter' => 'file|max:5048|mimes:pdf',
                'Annual_return' => 'file|max:5048|mimes:pdf',
            ],
            [
                'Business_Licence.max' => 'The Business Licence should not exceed 5 MB.',
                'Certificate_of_incorporation.max' => 'The Certificate of incorporation should not exceed 5 MB.',
                'TIN_Certificate.max' => 'The TIN Certificate should not exceed 5 MB.',
                'BRELA_report_for_shareholders.max' => 'The BRELA report for shareholders should not exceed 5 MB.',
                'Audited_financial_statement.max' => 'The Audited financial statement should not exceed 5 MB.',
                'Introduction_letter.max' => 'The Introduction letter should not exceed 5 MB.',
                'Annual_return.max' => 'The Annual return should not exceed 5 MB.',
            ]
        );

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'errors' => $validator->errors()
            ], 422);
        }

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
    public function companyAccountForm($uuid)
    {
        $title = 'Company Registration';

        //company
        $company = Partner::where(['uuid' => $uuid])->firstOrFail();

        //populate data
        $account_types = AccountType::toBase()->get();
        $accounts = Account::where(['active' => 1])->toBase()->get();
        $currencies = Currency::toBase()->get();

        //render view
        return view('companies.create.accounts', compact('title', 'company', 'account_types', 'accounts', 'currencies'));
    }

    //store company accounts
    public function storeCompanyAccounts(CompanyAccountRequest $request)
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
    public function companyStaffForm($uuid)
    {
        $title = 'Company Registration';

        //company
        $company = Partner::where(['uuid' => $uuid])->firstOrFail();
        $countries = Country::all();

        //render view
        return view('companies.create.staff', compact('title', 'company', 'countries'));
    }

    //store company staff
    public function storeCompanyStaff(CompanyStaffRequest $request)
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
    public function companyAuthorizedPersonForm($uuid)
    {
        $title = 'Company Registration';

        //company
        $company = Partner::where(['uuid' => $uuid])->firstOrFail();

        //render view
        return view('companies.create.authorized_person', compact('title', 'company'));
    }

    //store company authorized person
    public function storeCompanyAuthorizedPerson(Request $request)
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

        // DB::beginTransaction();
        // $this->companyService->createCredential((object) $request->all(), $partner);
        // DB::commit();

        //response
        return response()->json(['success' => true, 'uuid' => $uuid, 'message' => 'Authorised person saved'], 200);
    }

    //new collection account
    public function newCollectionAccountForm($uuid)
    {
        $title = 'New Collection Account';

        //company
        $company = Partner::where(['uuid' => $uuid])->firstOrFail();

        //populate data
        $account_types = AccountType::toBase()->get();
        $accounts = Account::where(['active' => 1])->toBase()->get();
        $currencies = Currency::toBase()->get();

        //render view
        return view('companies.collection_account', compact('title', 'company', 'account_types', 'accounts', 'currencies'));
    }

    public function storeCollectionAccount(Request $request)
    {
        //post data
        $id = $request->input('partner_id');
        $uuid = $request->input('partner_uuid');

        //check if partner account exists with account number, account type, currency, is_verified
        $partner_account = PartnerAccount::where(
            [
                'partner_uuid' => $uuid,
                'account_type_id' => $request->account_type_id,
                'account_id' => $request->account_id,
                'is_verified' => 1
            ]
        )->first();

        if ($partner_account) {
            return response()->json(['success' => false, 'message' => "{$partner_account->channel} Collection Account already exists"], 200);
        }

        // detect account channel
        $account = Account::where(['id' => $request->account_id])->first();

        //create new partner account
        $partner_account = PartnerAccount::firstOrNew([
            'partner_id' => $id,
            'partner_uuid' => $uuid,
            'account_type_id' => $request->account_type_id,
            'account_id' => $request->account_id
        ]);

        $partner_account->send_verification = 1;
        $partner_account->customer_code = $request->customer_code;
        $partner_account->account_name = $request->account_name;
        $partner_account->account_number = $request->account_number;
        $partner_account->corporate_limit = floatval(str_replace(',', '', $request->corporate_limit));
        $partner_account->branch_code = substr($request->account_number, 0, 3);
        $partner_account->currency_id = $request->currency_id;
        $partner_account->channel = $account->slug;
        $partner_account->save();

        //response
        return response()->json(['success' => true, 'uuid' => $uuid, 'message' => "{$partner_account->channel} collection account added"], 200);
    }

    //company info overview
    public function companyPreview($uuid)
    {
        $title = 'Company Registration';

        //company
        $company = Partner::where(['uuid' => $uuid])->firstOrFail();

        //render view
        return view('companies.create.preview', compact('title', 'company'));
    }

    //confirm  application
    public function confirmRegistration(Request $request)
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
    public function savingRegistration(Request $request)
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
    public function cancelRegistration($uuid)
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

        //is_verified_false partner accounts
        $is_verified_false = PartnerAccount::where(['partner_id' => $company->id, 'is_verified' => 0])->count();

        //nmb is verified_false partner accounts
        $is_nmb_verified_false = PartnerAccount::where(['partner_id' => $company->id, 'is_verified' => 0, 'channel' => 'NMB'])->count();

        $currentMonth = (int) now()->format('m');
        $currentYear = (int) now()->format('Y');
        $today = now()->toDateString();

        $mandateBase = DebitOrder::query()
            ->where('partner_id', $company->id)
            ->where('deleted', 0);

        $installmentBase = Installment::query()
            ->whereHas('debitOrder', function ($query) use ($company) {
                $query->where('partner_id', $company->id)
                    ->where('approved', 1)
                    ->where('status', 240)
                    ->where('deleted', 0);
            });

        $monthlyCollection = Installment::monthlyCollectionStats($company->id, $currentMonth, $currentYear, 240, 0, null);
        $todayCollection = Installment::todayCollectionStats($company->id, $today, 240, 0, null);

        $overdue = (clone $installmentBase)
            ->whereHas('debitOrder', function ($query) {
                $query->where('contract_status', 'OPEN');
            })
            ->whereDate('payment_date', '<', $today)
            ->whereIn('status', ['OPEN', 'FAILED', 'FUNDSCHECK'])
            ->selectRaw('COUNT(*) as overdue_count')
            ->selectRaw('COALESCE(SUM(outstanding_amount), 0) as overdue_amount')
            ->first();

        $summaryStats = [
            'documents' => $company->partnerDocuments->count(),
            'accounts' => $company->partnerAccounts->count(),
            'verified_accounts' => $company->partnerAccounts->where('is_verified', 1)->count(),
            'representatives' => $company->partnerStaff->count(),
            'total_mandates' => (clone $mandateBase)->count(),
            'active_mandates' => (clone $mandateBase)
                ->where('approved', 1)
                ->where('status', 240)
                ->where('contract_status', 'OPEN')
                ->count(),
            'this_month_expected' => (float) $monthlyCollection->total_amount,
            'this_month_collected' => (float) $monthlyCollection->paid_amount,
            'today_expected' => (float) $todayCollection->total_amount,
            'today_collected' => (float) $todayCollection->paid_amount,
            'overdue_count' => (int) $overdue->overdue_count,
            'overdue_amount' => (float) $overdue->overdue_amount,
        ];

        $summaryStats['collection_rate'] = $summaryStats['this_month_expected'] > 0
            ? round(($summaryStats['this_month_collected'] / $summaryStats['this_month_expected']) * 100, 2)
            : 0;

        //render view
        return view('companies.show', compact('title', 'company', 'is_verified_false', 'is_nmb_verified_false', 'summaryStats'));
    }

    //create invoice
    public function createInvoice($uuid)
    {
        //service provider
        $company = Partner::where(['uuid' => $uuid])->firstOrFail();

        //Create Evidence of Agreement
        view()->share('company', $company);

        //pdf creation        
        $pdf = PDF::loadView('companies.pdf_invoice');

        //create filename
        $filename = "{$company->reference}.pdf";

        $path = 'assets/uploads/pdf/';
        $pdf->save($path  . $filename);

        return $filename;
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
    public function update(Request $request, $uuid)
    {
        //todo: validate data

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
    public function destroy($uuid)
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

    //confirm/reject company approval
    public function confirmAssessment(Request $request)
    {
        //requests
        $uuid = $request->post('uuid');

        //service provider
        $partner = Partner::where(['uuid' => $uuid])->firstOrFail();

        //process assessment
        $http_response_code = $this->companyService->processAssessment($partner, $request->all());

        if ($http_response_code == 200) {
            //TODO: Check if corporate has NMB or CRDB partner accounts
            $partner_account = PartnerAccount::where(['partner_id' => $partner->id, 'is_verified' => 0]);

            $success_msg = "";
            $nmb_account = 0;
            $crdb_account = 0;
            if ($partner_account->count() > 0) {
                // iterate through partner accounts
                foreach ($partner_account->get() as $partner_account) {
                    if ($partner_account->channel == "CRDB") {
                        //update partner account
                        $partner_account->is_verified = 1;
                        $partner_account->verified_at = Carbon::now();
                        $partner_account->remarks = "Account processing completed successfully and is active.";
                        $partner_account->save();

                        $crdb_account += 1; //iterate crdb account
                    } else if ($partner_account->channel == "NMB") {
                        //create payload
                        $payload = $this->payloadService->corporateRegistrationPayload($partner, $partner_account);

                        //arr response
                        $arrResponse = $this->bankApi->corporateRegistrationRequest($payload);

                        if ($arrResponse)
                            $nmb_account += 1; //iterate nmb account
                    }
                }

                //check if account is NMB/CRDB
                $http_response_code = 200;

                if ($nmb_account && $crdb_account) {
                    $success_msg = "Service provider approved and pending approval from NMB.";
                } elseif ($nmb_account) {
                    $success_msg = "Service provider approved and pending approval from NMB.";
                } elseif ($crdb_account) {
                    $success_msg = "Account processing completed successfully and is active";
                } else {
                    $http_response_code = 400;
                    $success_msg = "Failed to approve service provider. Review accounts remarks and try again.";
                }
            }

            //response
            return response()->json(['error' => false, "status" => $http_response_code, "message" => $success_msg], 200);
        } else if ($http_response_code == 500) {
            return response()->json(['error' => false, "status" => $http_response_code, "remarks" => $request->remarks, "message" => 'Service provider rejected and notification sent to service provider.'], 200);
        }
    }

    //query status
    function fetchCorporateStatus(Request $request)
    {
        //requests
        $uuid = $request->post('uuid');

        //service provider
        $partner = Partner::where(['uuid' => $uuid])->firstOrFail();

        $partner_account = PartnerAccount::where(['partner_id' => $partner->id, 'is_verified' => 0, 'channel' => 'NMB'])->first();

        if (!$partner_account) {
            return response()->json(
                [
                    "success" => false,
                    'success_msg' => "No NMB account found",
                    "http_response_code" => 400,
                    "remarks" => "No NMB account found"
                ],
                200
            );
        }

        //create payload
        $payload = $this->payloadService->corporateStatusPayload($partner);

        //api service
        $arr_response = $this->bankApi->corporateStatusRequest($payload);

        if ($arr_response) {
            $http_response_code = $arr_response[0];
            $remarks = $arr_response[1];

            if ($http_response_code == 202 || $http_response_code == 203) {
                return response()->json(
                    [
                        "success" => true,
                        'message' => "Corporate status updated",
                        "http_response_code" => 200,
                        "remarks" => $remarks
                    ],
                    200
                );
            } else if ($http_response_code == 500) {
                return response()->json(
                    [
                        "success" => false,
                        'message' => "Failed to update corporate status, Try again...",
                        "http_response_code" => 400,
                        "remarks" => $remarks
                    ],
                    200
                );
            } else {
                return response()->json(
                    [
                        "success" => false,
                        'message' => "Failed to update corporate status, Try again...",
                        "http_response_code" => 400,
                        "remarks" => $remarks
                    ],
                    200
                );
            }
        } else {
            return response()->json(
                [
                    "success" => false,
                    'message' => "Failed to update corporate status, Try again...",
                    "http_response_code" => 404,
                    "remarks" => "API does not response, Kindly try again or contact support."
                ],
                200
            );
        }
    }
}
