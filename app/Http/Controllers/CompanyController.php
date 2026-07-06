<?php

namespace App\Http\Controllers;

use App\Classes\Authentication;
use App\Classes\Messaging;
use App\Exports\CompanyExport;
use App\Http\Requests\CompanyAccountRequest;
use App\Http\Requests\CompanyDetailRequest;
use App\Http\Requests\CompanyStaffRequest;
use App\Models\Account;
use App\Models\AccountType;
use App\Models\Country;
use App\Models\Currency;
use App\Models\DocumentCategory;
use App\Models\Partner;
use App\Models\PartnerAccount;
use App\Models\PartnerCategory;
use App\Models\PartnerDocument;
use App\Models\PartnerStaff;
use App\Models\Region;
use App\Services\BankApiService;
use App\Services\CompanyService;
use App\Services\CompanySummaryService;
use App\Services\BankPayloadService;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\File;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Stidges\CountryFlags\CountryFlag;
use Maatwebsite\Excel\Facades\Excel;
use Illuminate\Support\Str;
use Barryvdh\DomPDF\Facade\Pdf;
use Illuminate\Support\Facades\Auth;

class CompanyController extends Controller
{
    public function __construct(
        protected CompanyService $companyService,
        protected CompanySummaryService $companySummaryService,
        protected BankPayloadService $payloadService,
        protected Messaging $messaging,
        protected BankApiService $bankApi,
        protected Authentication $auth
    )
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
        $companies = Partner::filterCondition()
            ->with(['partnerCategory', 'region']);

        //all request
        $requests = $request->all();

        if ($request->has('filter')) {
            $companies = $companies->when($requests, function ($query) use ($requests) {
                $query->withFilters($requests);
            });
        }

        $companyStats = [
            'total' => (clone $companies)->count(),
            'approved' => (clone $companies)->where('approved', 1)->count(),
            'pending' => (clone $companies)->where('approved', 0)->count(),
            'failed' => (clone $companies)->where('approved', 99)->count(),
            'active' => (clone $companies)->where('lifecycle_status', Partner::LIFECYCLE_ACTIVE)->count(),
            'pending_account_activation' => (clone $companies)->where('lifecycle_status', Partner::LIFECYCLE_PENDING_ACCOUNT_ACTIVATION)->count(),
            'verified_accounts' => (clone $companies)->whereHas('partnerAccounts', function ($query) {
                $query->where('is_verified', 1);
            })->count(),
        ];

        //number of companies
        $number_of_companies = $companyStats['total'];

        //companies
        if ($request->has('filter')) {
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
            'lifecycle_status' => $request->lifecycle_status ?? null,
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
        $companies = Partner::filterCondition()
            ->with(['partnerCategory', 'region'])
            ->where('complete', 1);

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
        if ($uuid != null) {
            $company = $this->findAccessiblePartner($uuid);
        }

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
        if ($request->input('partner_uuid') != null) {
            $uuid = $request->input('partner_uuid');
            $this->findAccessiblePartner($uuid);
        } else {
            $uuid = Str::uuid();
        }

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

        $partner = DB::transaction(fn () => $this->companyService->createInformation(
            (object) $request->all(),
            $uuid,
            $request->attributes->get(
                'registration_source',
                Partner::REGISTRATION_SOURCE_OFFICER
            )
        ));

        //response
        return response()->json(['success' => true, 'uuid' => $uuid, 'message' => 'Service provider information saved!'], 200);
    }

    //company documents
    public function companyDocumentsForm($uuid)
    {
        $title = 'Company Registration';

        //company
        $company = $this->findAccessiblePartner($uuid);

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
        $partner = $this->findRequestPartner($request);
        $uuid = $partner->uuid;

        //categories
        $categories = DocumentCategory::all();

        $uploadPath = public_path('assets/uploads/docs');
        $newAttachments = [];
        $replacedAttachments = [];

        try {
            DB::transaction(function () use (
                $categories,
                $partner,
                $request,
                $uploadPath,
                $uuid,
                &$newAttachments,
                &$replacedAttachments
            ) {
                $this->companyService->updateNextStep($partner->id, 'companies/accounts/' . $uuid);
                File::ensureDirectoryExists($uploadPath);

                foreach ($categories as $val) {
                    if (!$request->hasFile($val->key)) {
                        continue;
                    }

                    $storedName = Str::uuid() . '.pdf';
                    $request->file($val->key)->move($uploadPath, $storedName);
                    $newAttachments[] = $storedName;

                    $partnerDocument = PartnerDocument::firstOrNew([
                        'partner_id' => $partner->id,
                        'partner_uuid' => $partner->uuid,
                        'key' => $val->key,
                        'name' => $val->name,
                    ]);

                    if ($partnerDocument->attachment) {
                        $replacedAttachments[] = $partnerDocument->attachment;
                    }

                    $partnerDocument->attachment = $storedName;
                    $partnerDocument->save();
                }
            });
        } catch (\Throwable $exception) {
            foreach ($newAttachments as $attachment) {
                File::delete($uploadPath . DIRECTORY_SEPARATOR . basename($attachment));
            }

            throw $exception;
        }

        foreach ($replacedAttachments as $attachment) {
            if (!PartnerDocument::where('attachment', $attachment)->exists()) {
                File::delete($uploadPath . DIRECTORY_SEPARATOR . basename($attachment));
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
        $company = $this->findAccessiblePartner($uuid);

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
        $partner = $this->findRequestPartner($request);
        $uuid = $partner->uuid;

        //TODO: validate account number and customer code

        DB::transaction(function () use ($partner, $request, $uuid) {
            $this->companyService->updateNextStep($partner->id, 'companies/staff/' . $uuid);
            $this->companyService->createCollectionAccount($request->all(), $partner);
        });

        //response
        return response()->json(['success' => true, 'uuid' => $uuid, 'message' => 'Service Provider collection account(s) saved'], 200);
    }

    //company staff
    public function companyStaffForm($uuid)
    {
        $title = 'Company Registration';

        //company
        $company = $this->findAccessiblePartner($uuid);
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
        $partner = $this->findRequestPartner($request);
        $id = $partner->id;
        $uuid = $partner->uuid;

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

        DB::transaction(function () use ($partner, $request, $uuid) {
            $this->companyService->updateNextStep($partner->id, 'companies/preview/' . $uuid);
            $this->companyService->createStaff($request->all(), $partner);
        });

        //response
        return response()->json(['success' => true, 'uuid' => $uuid, 'message' => 'Service Provider staff saved'], 200);
    }

    //authorized person
    public function companyAuthorizedPersonForm($uuid)
    {
        $title = 'Company Registration';

        //company
        $company = $this->findAccessiblePartner($uuid);

        //render view
        return view('companies.create.authorized_person', compact('title', 'company'));
    }

    //store company authorized person
    public function storeCompanyAuthorizedPerson(Request $request)
    {
        //post data
        $partner = $this->findRequestPartner($request);
        $uuid = $partner->uuid;

        //next stage
        $partner = $this->companyService->updateNextStep($partner->id, 'companies/preview/' . $uuid);

        //response
        return response()->json(['success' => true, 'uuid' => $uuid, 'message' => 'Authorised person saved'], 200);
    }

    //new collection account
    public function newCollectionAccountForm($uuid)
    {
        $title = 'New Collection Account';

        //company
        $company = $this->findAccessiblePartner($uuid);

        //populate data
        $account_types = AccountType::toBase()->get();
        $accounts = Account::where(['active' => 1])->toBase()->get();
        $currencies = Currency::toBase()->get();

        //render view
        return view('companies.collection_account', compact('title', 'company', 'account_types', 'accounts', 'currencies'));
    }

    public function storeCollectionAccount(Request $request)
    {
        $validated = $request->validate([
            'partner_id' => ['required', 'integer'],
            'partner_uuid' => ['required', 'uuid'],
            'account_type_id' => ['required', 'integer'],
            'account_id' => ['required', 'integer', 'exists:accounts,id'],
            'customer_code' => ['required', 'string'],
            'account_name' => ['required', 'string'],
            'account_number' => ['required', 'string'],
            'corporate_limit' => ['required'],
            'currency_id' => ['required', 'uuid', 'exists:currencies,id'],
        ]);

        $partner = $this->findRequestPartner($request);
        $id = $partner->id;
        $uuid = $partner->uuid;

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
        $account = Account::findOrFail($validated['account_id']);

        $partner_account = DB::transaction(function () use ($account, $id, $request, $uuid) {
            $partnerAccount = PartnerAccount::firstOrNew([
                'partner_id' => $id,
                'partner_uuid' => $uuid,
                'account_type_id' => $request->account_type_id,
                'account_id' => $request->account_id,
            ]);

            $partnerAccount->send_verification = 1;
            $partnerAccount->customer_code = $request->customer_code;
            $partnerAccount->account_name = $request->account_name;
            $partnerAccount->account_number = $request->account_number;
            $partnerAccount->corporate_limit = floatval(str_replace(',', '', $request->corporate_limit));
            $partnerAccount->branch_code = substr($request->account_number, 0, 3);
            $partnerAccount->currency_id = $request->currency_id;
            $partnerAccount->channel = $account->slug;
            $partnerAccount->save();

            return $partnerAccount;
        });

        //response
        return response()->json(['success' => true, 'uuid' => $uuid, 'message' => "{$partner_account->channel} collection account added"], 200);
    }

    //company info overview
    public function companyPreview($uuid)
    {
        $title = 'Company Registration';

        //company
        $company = $this->findAccessiblePartner($uuid);

        //render view
        return view('companies.create.preview', compact('title', 'company'));
    }

    //confirm  application
    public function confirmRegistration(Request $request)
    {
        $validated = $request->validate([
            'uuid' => ['required', 'uuid'],
        ]);
        $uuid = $validated['uuid'];

        //partner
        $partner = $this->findAccessiblePartner($uuid);

        //update status of partner
        $partner->next_step = $partner->registration_source === Partner::REGISTRATION_SOURCE_SELF_WEB
            ? "service-provider/register/{$uuid}/preview"
            : "companies/preview/{$uuid}";
        $partner->status = 'pending';
        $partner->lifecycle_status = Partner::LIFECYCLE_SUBMITTED;
        $partner->complete = 1;
        $partner->updated_by = Auth::id();
        $partner->save();

        try {
            $this->companyService->sendAssessmentAlert($partner);
        } catch (\Throwable $exception) {
            Log::error('Service provider application submitted, but reviewer notification failed.', [
                'partner_id' => $partner->id,
                'partner_uuid' => $partner->uuid,
                'exception' => $exception->getMessage(),
            ]);
        }

        //response
        return response()->json(['success' => true, 'success_msg' => 'Service provider successful registered', 'uuid' => $uuid], 200);
    }

    //saving application
    public function savingRegistration(Request $request)
    {
        $validated = $request->validate([
            'uuid' => ['required', 'uuid'],
        ]);
        $uuid = $validated['uuid'];

        //partner
        $partner = $this->findAccessiblePartner($uuid);

        //update status of partner
        $partner->status = 'pending';
        $partner->lifecycle_status = Partner::LIFECYCLE_DRAFT;
        $partner->complete = 0;
        $partner->save();

        //response
        return response()->json(['success' => true, 'success_msg' => 'Service Provider information saved!', 'uuid' => $uuid], 200);
    }

    //cancel registration
    public function cancelRegistration($uuid)
    {
        //company
        $company = $this->findAccessiblePartner($uuid);
        $this->companyService->deleteRegistration($company);

        return Redirect::route('companies.lists')->with('success', 'Service Provider registration canceled');
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
        $company = $this->findAccessiblePartner($uuid);

        //is_verified_false partner accounts
        $is_verified_false = PartnerAccount::where(['partner_id' => $company->id, 'is_verified' => 0])->count();

        //nmb is verified_false partner accounts
        $is_nmb_verified_false = PartnerAccount::where(['partner_id' => $company->id, 'is_verified' => 0, 'channel' => 'NMB'])->count();

        $summaryStats = $this->companySummaryService->forPartner($company);

        //render view
        return view('companies.show', compact('title', 'company', 'is_verified_false', 'is_nmb_verified_false', 'summaryStats'));
    }

    //create invoice
    public function createInvoice($uuid)
    {
        //service provider
        $company = $this->findAccessiblePartner($uuid);

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
        $company = $this->findAccessiblePartner($uuid);

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
    public function update(CompanyDetailRequest $request, $uuid)
    {
        $this->findAccessiblePartner($uuid);

        $partner = DB::transaction(
            fn () => $this->companyService->createInformation((object) $request->all(), $uuid)
        );

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
        $company = $this->findAccessiblePartner($uuid);
        $this->companyService->deleteRegistration($company);

        return Redirect::route('companies.lists')->with('success', 'Service provider deleted');
    }

    //confirm/reject company approval
    public function confirmAssessment(Request $request)
    {
        $validated = $request->validate([
            'uuid' => ['required', 'uuid'],
            'action' => ['required', 'in:APPROVE,REJECT'],
            'remarks' => ['nullable', 'required_if:action,REJECT', 'string'],
        ]);

        $partner = $this->findAccessiblePartner($validated['uuid']);

        if ($validated['action'] === 'REJECT') {
            $this->companyService->processAssessment($partner, $validated);

            return response()->json([
                'error' => false,
                'status' => 500,
                'remarks' => $validated['remarks'],
                'message' => 'Service provider rejected and notification sent to service provider.',
            ]);
        }

        if (!$partner->partnerAccounts()->exists()) {
            return response()->json([
                'error' => true,
                'status' => 422,
                'message' => 'Add at least one collection account before approving this service provider.',
            ], 422);
        }

        $nmbAccounts = 0;
        $crdbAccounts = 0;
        $failedAccounts = 0;

        foreach ($partner->partnerAccounts()->where('is_verified', 0)->get() as $partnerAccount) {
            if ($partnerAccount->channel === 'CRDB') {
                DB::transaction(function () use ($partnerAccount) {
                    $partnerAccount->is_verified = 1;
                    $partnerAccount->verified_at = now();
                    $partnerAccount->rejected_at = null;
                    $partnerAccount->remarks = 'Account processing completed successfully and is active.';
                    $partnerAccount->save();
                });
                $crdbAccounts++;
                continue;
            }

            if ($partnerAccount->channel === 'NMB') {
                $payload = $this->payloadService->corporateRegistrationPayload($partner, $partnerAccount);
                [$responseCode] = $this->bankApi->corporateRegistrationRequest($payload, $partnerAccount);

                if ($responseCode === 201) {
                    $nmbAccounts++;
                } else {
                    $failedAccounts++;
                }
                continue;
            }

            $failedAccounts++;
        }

        if ($failedAccounts > 0) {
            $partner->refreshLifecycleStatus()->save();

            return response()->json([
                'error' => true,
                'status' => 400,
                'message' => 'Failed to approve service provider. Review account remarks and try again.',
            ]);
        }

        DB::transaction(function () use ($partner, $validated) {
            $this->companyService->processAssessment($partner, $validated);
            $partner->refresh()->refreshLifecycleStatus()->save();
        });

        $message = $nmbAccounts > 0
            ? 'Service provider approved and pending approval from NMB.'
            : 'Account processing completed successfully and is active.';

        return response()->json([
            'error' => false,
            'status' => 200,
            'message' => $message,
            'processed_accounts' => $nmbAccounts + $crdbAccounts,
        ]);
    }

    //query status
    function fetchCorporateStatus(Request $request)
    {
        $validated = $request->validate([
            'uuid' => ['required', 'uuid'],
        ]);
        $uuid = $validated['uuid'];

        //service provider
        $partner = $this->findAccessiblePartner($uuid);

        $partner_account = PartnerAccount::where(['partner_id' => $partner->id, 'is_verified' => 0, 'channel' => 'NMB'])->first();

        if (!$partner_account) {
            return response()->json(
                [
                    "success" => false,
                    'success_msg' => "No NMB account found",
                    "status" => 400,
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
            $http_status_code = $arr_response[0];
            $remarks = $arr_response[1];

            if ($http_status_code == 200) {
                return response()->json(
                    [
                        "success" => true,
                        'message' => "Corporate status updated",
                        "status" => 200,
                        "remarks" => $remarks
                    ],
                    200
                );
            } else if ($http_status_code == 500) {
                return response()->json(
                    [
                        "success" => false,
                        'message' => "Failed to update corporate status, Try again...",
                        "status" => 400,
                        "remarks" => $remarks
                    ],
                    200
                );
            } else {
                return response()->json(
                    [
                        "success" => false,
                        'message' => "Failed to update corporate status, Try again...",
                        "status" => 400,
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
                    "status" => 404,
                    "remarks" => "API does not response, Kindly try again or contact support."
                ],
                200
            );
        }
    }

    protected function findAccessiblePartner(string $uuid): Partner
    {
        $query = Auth::check()
            ? Partner::filterCondition()
            : Partner::query();

        return $query->where('partners.uuid', $uuid)->firstOrFail();
    }

    protected function findRequestPartner(Request $request): Partner
    {
        $request->validate([
            'partner_id' => ['required', 'integer'],
            'partner_uuid' => ['required', 'uuid'],
        ]);

        $query = Auth::check()
            ? Partner::filterCondition()
            : Partner::query();

        return $query
            ->where('partners.id', $request->input('partner_id'))
            ->where('partners.uuid', $request->input('partner_uuid'))
            ->firstOrFail();
    }
}
