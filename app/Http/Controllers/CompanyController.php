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
use App\Models\PartnerUser;
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

    //collection account actions
    public function storeCollectionAccount(Request $request)
    {
        $validated = $request->validate([
            'partner_id' => ['required', 'integer'],
            'partner_uuid' => ['required', 'uuid'],
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

        $account = Account::where('active', 1)->findOrFail($validated['account_id']);
        $existingAccount = PartnerAccount::where('partner_id', $id)
            ->where('account_id', $account->id)
            ->first();

        if ($existingAccount) {
            return response()->json([
                'success' => false,
                'message' => "{$existingAccount->channel} collection account already exists.",
            ], 422);
        }

        $partner_account = DB::transaction(function () use ($account, $id, $request, $uuid) {
            $partnerAccount = PartnerAccount::firstOrNew([
                'partner_id' => $id,
                'partner_uuid' => $uuid,
                'account_type_id' => $account->account_type_id,
                'account_id' => $request->account_id,
            ]);

            $partnerAccount->send_verification = 1;
            $partnerAccount->is_verified = 0;
            $partnerAccount->verified_at = null;
            $partnerAccount->rejected_at = null;
            $partnerAccount->onboarding_status = PartnerAccount::ONBOARDING_PENDING_REVIEW;
            $partnerAccount->customer_code = $request->customer_code;
            $partnerAccount->account_name = $request->account_name;
            $partnerAccount->account_number = $request->account_number;
            $partnerAccount->corporate_limit = floatval(str_replace(',', '', $request->corporate_limit));
            $partnerAccount->branch_code = substr($request->account_number, 0, 3);
            $partnerAccount->currency_id = $request->currency_id;
            $partnerAccount->channel = $account->slug;
            $partnerAccount->save();
            $partnerAccount->partner->refreshLifecycleStatus()->save();

            return $partnerAccount;
        });

        //response
        return response()->json(['success' => true, 'uuid' => $uuid, 'message' => "{$partner_account->channel} collection account added and is pending eMakato review"], 200);
    }

    public function updateCollectionAccount(
        Request $request,
        string $uuid,
        PartnerAccount $partnerAccount
    ) {
        $this->authorizeProviderApproval();

        $company = $this->findAccessiblePartner($uuid);
        abort_unless((int) $partnerAccount->partner_id === (int) $company->id, 404);

        if ($partnerAccount->isBankApproved()) {
            return response()->json([
                'success' => false,
                'message' => 'An NMB collection account cannot be edited after bank approval.',
            ], 422);
        }

        $validated = $request->validate([
            'customer_code' => ['required', 'string'],
            'account_name' => ['required', 'string'],
            'account_number' => ['required', 'string'],
            'corporate_limit' => ['required'],
            'currency_id' => ['required', 'uuid', 'exists:currencies,id'],
        ]);

        $channel = strtoupper((string) $partnerAccount->channel);

        DB::transaction(function () use ($validated, $channel, $company, $partnerAccount) {
            $isLocallyOperational = str_contains($channel, 'CRDB') && (int) $company->approved === 1;

            $partnerAccount->forceFill([
                'customer_code' => $validated['customer_code'],
                'account_name' => $validated['account_name'],
                'account_number' => $validated['account_number'],
                'corporate_limit' => floatval(str_replace(',', '', $validated['corporate_limit'])),
                'branch_code' => substr($validated['account_number'], 0, 3),
                'currency_id' => $validated['currency_id'],
                'is_verified' => $isLocallyOperational ? 1 : 0,
                'send_verification' => $isLocallyOperational ? 0 : 1,
                'verified_at' => $isLocallyOperational ? now() : null,
                'rejected_at' => null,
                'onboarding_status' => $isLocallyOperational
                    ? PartnerAccount::ONBOARDING_NOT_REQUIRED
                    : PartnerAccount::ONBOARDING_PENDING_REVIEW,
                'remarks' => $isLocallyOperational
                    ? 'Updated and verified by eMakato. CRDB bank onboarding is not required.'
                    : 'Collection account updated and pending eMakato review.',
                'bank_response_code' => null,
                'bank_correlation_id' => null,
                'bank_response_payload' => null,
                'bank_submitted_at' => null,
                'bank_status_checked_at' => null,
            ])->save();

            $company->refreshLifecycleStatus()->save();
        });

        return response()->json([
            'success' => true,
            'uuid' => $company->uuid,
            'message' => str_contains($channel, 'CRDB')
                ? 'CRDB collection account updated successfully.'
                : 'NMB collection account updated and returned for approval.',
        ]);
    }

    public function destroyCollectionAccount(string $uuid, PartnerAccount $partnerAccount)
    {
        $this->authorizeProviderApproval();

        $company = $this->findAccessiblePartner($uuid);
        abort_unless((int) $partnerAccount->partner_id === (int) $company->id, 404);

        if (!$partnerAccount->canBeDeleted()) {
            return response()->json([
                'success' => false,
                'message' => 'An approved collection account cannot be deleted.',
            ], 422);
        }

        DB::transaction(function () use ($company, $partnerAccount) {
            $partnerAccount->delete();
            $company->refreshLifecycleStatus()->save();
        });

        return response()->json([
            'success' => true,
            'message' => 'Collection account deleted successfully.',
        ]);
    }

    public function deactivatePartnerUser(
        string $uuid,
        PartnerUser $partnerUser
    ) {
        $this->authorizeProviderApproval();

        $company = $this->findAccessiblePartner($uuid);
        abort_unless((int) $partnerUser->partner_id === (int) $company->id, 404);

        $user = $partnerUser->user;
        abort_unless($user, 404);

        if ((int) $user->id === (int) Auth::id()) {
            return response()->json([
                'success' => false,
                'message' => 'You cannot deactivate your own login.',
            ], 422);
        }

        DB::transaction(function () use ($user) {
            $user->active = 0;
            $user->remember_token = null;
            $user->save();

            PartnerUser::where('user_id', $user->id)->update(['authorize' => 0]);
            $user->tokens()->update(['revoked' => true]);
        });

        return response()->json([
            'success' => true,
            'message' => "{$user->name}'s login has been deactivated.",
        ]);
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

        $canReviewRegistration = Auth::user()->hasAnyRole(['admin', 'Manager']);
        $accounts = Account::where('active', 1)->toBase()->get();
        $currencies = Currency::toBase()->get();
        $partnerUsers = $company->users()->with(['user.roles'])->get();

        $is_verified_false = $company->approved != 1
            || $company->partnerAccounts()->where('onboarding_status', PartnerAccount::ONBOARDING_PENDING_REVIEW)->exists();
        $is_nmb_verified_false = $company->partnerAccounts()
            ->where('channel', 'like', '%NMB%')
            ->whereNotIn('onboarding_status', [
                PartnerAccount::ONBOARDING_ACTIVE,
                PartnerAccount::ONBOARDING_REJECTED,
            ])
            ->exists();

        $summaryStats = $this->companySummaryService->forPartner($company);

        //render view
        return view('companies.show', compact('title', 'company', 'is_verified_false', 'is_nmb_verified_false', 'canReviewRegistration', 'accounts', 'currencies', 'partnerUsers', 'summaryStats'));
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
        $this->authorizeProviderApproval();

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
                'status' => 200,
                'approved' => 99,
                'remarks' => $validated['remarks'],
                'message' => 'Service provider rejected and notification sent.',
            ]);
        }

        $readinessErrors = $this->approvalReadinessErrors($partner);
        if ($readinessErrors !== []) {
            return response()->json([
                'error' => true,
                'status' => 422,
                'message' => 'Complete the required registration information before approval: ' . implode(' ', $readinessErrors),
                'errors' => $readinessErrors,
            ], 422);
        }

        DB::transaction(function () use ($partner, $validated) {
            $this->companyService->processAssessment($partner, $validated);

            foreach ($partner->partnerAccounts as $account) {
                if (!str_contains(strtoupper((string) $account->channel), 'CRDB')) {
                    continue;
                }

                $account->forceFill([
                    'is_verified' => 1,
                    'send_verification' => 0,
                    'verified_at' => now(),
                    'rejected_at' => null,
                    'onboarding_status' => PartnerAccount::ONBOARDING_NOT_REQUIRED,
                    'remarks' => 'Verified by eMakato. CRDB bank onboarding is not required.',
                ])->save();
            }

            $partner->refreshLifecycleStatus()->save();
        });

        $submitted = 0;
        $bankFailures = [];

        foreach ($partner->partnerAccounts()->where('channel', 'like', '%NMB%')->get() as $account) {
            if ($account->onboarding_status === PartnerAccount::ONBOARDING_ACTIVE) {
                continue;
            }

            $payload = $this->payloadService->corporateRegistrationPayload($partner, $account);
            [$responseCode, $remarks] = $this->bankApi->corporateRegistrationRequest($payload, $account);

            if ($responseCode === 201) {
                $submitted++;
                continue;
            }

            $bankFailures[] = $remarks;
        }

        $partner->refresh()->refreshLifecycleStatus()->save();

        $message = match (true) {
            $bankFailures !== [] => 'Service provider approved by eMakato, but NMB onboarding needs attention: ' . implode(' ', array_unique($bankFailures)),
            $submitted > 0 => 'Service provider approved by eMakato and submitted to NMB for activation.',
            default => 'Service provider approved and collection accounts are active.',
        };

        return response()->json([
            'error' => false,
            'status' => 200,
            'approved' => 1,
            'message' => $message,
            'nmb_accounts_submitted' => $submitted,
            'bank_warnings' => $bankFailures,
        ]);
    }

    public function fetchCorporateStatus(Request $request)
    {
        $this->authorizeProviderApproval();

        $validated = $request->validate([
            'uuid' => ['required', 'uuid'],
        ]);

        $partner = $this->findAccessiblePartner($validated['uuid']);
        $accounts = $partner->partnerAccounts()
            ->where('channel', 'NMB')
            ->whereNotIn('onboarding_status', [
                PartnerAccount::ONBOARDING_ACTIVE,
                PartnerAccount::ONBOARDING_REJECTED,
            ])
            ->get();

        if ($accounts->isEmpty()) {
            return response()->json([
                'success' => false,
                'status' => 422,
                'message' => 'No pending NMB collection account was found.',
                'remarks' => 'No pending NMB collection account was found.',
            ], 422);
        }

        $results = [];
        foreach ($accounts as $account) {
            $payload = $this->payloadService->corporateStatusPayload($partner);
            [$responseCode, $remarks] = $this->bankApi->corporateStatusRequest($payload, $account);
            $results[] = compact('responseCode', 'remarks');
        }

        $partner->refresh()->refreshLifecycleStatus()->save();
        $hasFailure = collect($results)->contains(fn (array $result) => $result['responseCode'] >= 400);
        $isPending = collect($results)->contains(fn (array $result) => $result['responseCode'] === 202);
        $httpStatus = $hasFailure ? 422 : 200;

        return response()->json([
            'success' => !$hasFailure,
            'status' => $hasFailure ? 422 : ($isPending ? 202 : 200),
            'message' => $hasFailure
                ? 'NMB status could not be confirmed for every account.'
                : ($isPending ? 'NMB activation is still pending.' : 'NMB account status updated.'),
            'remarks' => implode(' ', array_unique(array_column($results, 'remarks'))),
        ], $httpStatus);
    }

    protected function authorizeProviderApproval(): void
    {
        abort_unless(
            Auth::user()?->hasAnyRole(['admin', 'Manager']),
            403,
            'Only eMakato administrators and managers can approve service providers.'
        );
    }

    protected function approvalReadinessErrors(Partner $partner): array
    {
        $errors = [];
        $requiredFields = [
            'business_name' => 'Business name',
            'tin_number' => 'TIN number',
            'partner_category_id' => 'Business category',
            'year_of_incorporation' => 'Year of incorporation',
            'region_id' => 'Region',
            'phone' => 'Phone number',
            'email' => 'Email address',
            'physical_address' => 'Physical address',
        ];

        foreach ($requiredFields as $field => $label) {
            if (blank($partner->{$field})) {
                $errors[] = $label . ' is missing.';
            }
        }

        $requiredDocumentKeys = DocumentCategory::where('required', 1)->pluck('key');
        $uploadedDocumentKeys = $partner->partnerDocuments()
            ->whereIn('key', $requiredDocumentKeys)
            ->whereNotNull('attachment')
            ->where('attachment', '!=', '')
            ->pluck('key');

        foreach ($requiredDocumentKeys->diff($uploadedDocumentKeys) as $key) {
            $errors[] = 'Required document missing: '
                . (DocumentCategory::where('key', $key)->value('name') ?? $key) . '.';
        }

        if (!$partner->partnerStaff()->exists()) {
            $errors[] = 'Add at least one authorized representative.';
        }

        $accounts = $partner->partnerAccounts()->with('account')->get();
        if ($accounts->isEmpty()) {
            $errors[] = 'Add at least one collection account.';
        }

        foreach ($accounts as $account) {
            $channel = strtoupper((string) $account->channel);
            if (!in_array($channel, ['NMB', 'CRDB'], true)) {
                $errors[] = "Collection account {$account->account_number} must use NMB or CRDB.";
            }

            if (!$account->account || !$account->account->active) {
                $errors[] = "Collection account {$account->account_number} uses an inactive bank.";
            }

            foreach (['account_name', 'account_number', 'customer_code', 'currency_id', 'corporate_limit'] as $field) {
                if (blank($account->{$field})) {
                    $errors[] = "Collection account {$account->id} is missing {$field}.";
                }
            }
        }

        return array_values(array_unique($errors));
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
