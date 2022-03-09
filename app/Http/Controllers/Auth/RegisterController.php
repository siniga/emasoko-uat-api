<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use App\Models\Account;
use App\Models\AccountType;
use App\Models\Country;
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
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;

//use Interve


class RegisterController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Register Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles the registration of new users as well as their
    | validation and creation. By default this controller uses a trait to
    | provide this functionality without requiring any additional code.
    |
    */


    /**
     * Where to redirect users after registration.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Create a new controller instance.
     *
     * @return void
     */
    public function __construct()
    {
        $this->middleware('guest');
    }

    //show company registration
    function registrationForm($uuid = null)
    {
        $title = 'Company Registration';

        //if uuid =! null
        $company = [];
        if ($uuid != null)
            $company = Partner::where(['uuid' => $uuid])->firstOrFail();

        //populate data
        $categories = PartnerCategory::all();
        $countries = Country::all();
        $regions = Region::orderBy('name', 'ASC')->get();

        //render view
        return view('auth.register', compact('title', 'categories', 'countries', 'regions', 'company'));
    }

    //store company details
    function storeCompanyDetails(Request $request)
    {
        //validation 
        $validator = Validator::make($request->all(), [
            'tin_number' => 'required|unique',
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

        //response
        return response()->json(['success' => true, 'uuid' => $uuid], 200);
    }

    //company documents
    function companyDocumentsForm($uuid)
    {
        $title = 'Company Registration';

        //company
        $company = Partner::where(['uuid' => $uuid])->firstOrFail();

        //assigned documents
        $arr_documents = [];

        if ($company->partnerDocuments->isNotEmpty()) {
            foreach ($company->partnerDocuments as $val) {
                array_push($arr_documents, $val->key);
            }
        }

        //render view
        return view('auth.company_documents', compact('title', 'company', 'arr_documents'));
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

        //attachments
        $arr_attachments = [
            'business_licence' => 'Business Licence',
            'certificate_of_incorporation' => 'Certificate of incorporation',
            'TIN_certificate' => 'TIN Certificate',
            'brela_certificate' => 'BRELA certificate',
            'audited_financial_statement' => 'Audited financial statement',
            'introduction_letter' => 'Introduction letter introducing director as a custodian of the account.',
            'annual_return' => 'Annual return',
        ];

        foreach ($arr_attachments as $k => $val) {
            //business licennce
            if ($request->hasFile($k)) {
                $path = $request->file($k);
                $docsName = $path->getClientOriginalName();

                $path->move('assets/uploads/docs', $docsName);

                //partner documents
                $partner_document = PartnerDocument::firstOrNew([
                    'partner_id' => $id,
                    'partner_uuid' => $uuid,
                    'key' => $k,
                    'name' => $val
                ]);
                $partner_document->attachment = $docsName;
                $partner_document->save();
            }
        }

        //response
        return response()->json(['success' => true, 'uuid' => $uuid], 200);
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
        return view('auth.company_accounts', compact('title', 'company', 'account_types', 'accounts'));
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

        //response
        return response()->json(['success' => true, 'uuid' => $uuid], 200);
    }


    //company staff
    function companyStaffForm($uuid)
    {
        $title = 'Company Registration';

        //company
        $company = Partner::where(['uuid' => $uuid])->firstOrFail();
        $countries = Country::all();

        //render view
        return view('auth.company_staff', compact('title', 'company', 'countries'));
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

        //response
        return response()->json(['success' => true, 'uuid' => $uuid], 200);
    }

    //authorized person
    function companyAuthorizedPersonForm($uuid)
    {
        $title = 'Company Registration';

        //company
        $company = Partner::where(['uuid' => $uuid])->firstOrFail();

        //render view
        return view('auth.company_authorized_person', compact('title', 'company'));
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
        $user = new User([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'verify_token' => $token,
            'password' => Hash::make($password),
        ]);
        $user->save();

        //insert role
        $user->roles()->attach(2);

        //partner user
        $partner_user = new PartnerUser();
        $partner_user->user_id = $user->id;
        $partner_user->partner_id = $id;
        $partner_user->partner_uuid = $uuid;
        $partner_user->authorize = 1;
        $partner_user->save();

        //response
        return response()->json(['success' => true, 'uuid' => $uuid], 200);
    }

    //company info overview
    function companyInfoOverview($uuid)
    {
        $title = 'Company Registration';

        //company
        $company = Partner::where(['uuid' => $uuid])->firstOrFail();

        //render view
        return view('auth.company_info_overview', compact('title', 'company'));
    }

    //confirm application
    function confirmCompanyRegistration(Request $request)
    {
        //validation 

        //post data
        $id = $request->input('partner_id');
        $uuid = $request->input('partner_uuid');

        $complete = 0;
        $status = 0;
        if (isset($_POST['save'])) {
            $status = 0;
            $complete = 0;
            $action = 'save';
            $message = 'Company information saved, please complete application';
        } else if (isset($_POST['submit'])) {
            $status = 1;
            $complete = 1;
            $action = 'submit';
            $message = 'Company registration complete, Please wait for approval!';

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

        //response
        return response()->json(['success' => true, 'action' => $action, 'success_msg' => $message], 200);
    }

    //cancel registration
    function cancelCompanyRegistration($uuid)
    {
        //company
        $company = Partner::where(['uuid' => $uuid])->firstOrFail();


        //redirect
        return Redirect::route('company.register')->withErrors('Company registration cancelled');
    }

    //track application
    function trackCompanyRegistrationForm()
    {
        $title = 'Track Application';

        //render view
        return view('auth.track_company_registration', compact('title'));
    }

    //track company registration
    function trackCompanyRegistration(Request $request)
    {
        //validation
        $this->validate(
            $request,
            [
                'email' => 'required',
                'tin_number' => 'required'
            ],
            [
                'email.required' => 'Email required',
                'tin_number.required' => 'TIN Number required',
            ]
        );

        //post data
        $email = $request->input('email');
        $tin_number = $request->input('tin_number');

        $partner = Partner::where(['email' => $email, 'tin_number' => $tin_number])->first();

        if (!$partner) {
            //redirect
            return Redirect::back()->with('danger', 'Company does not exist, please Apply Now!');
        } else {
            if ($partner->complete == 0) {
                $message = "Company registration does not complete, please complete application by <a href='$partner->next_step'><b>Click here</b></a>.";

                //redirect 
                return Redirect::back()->with('danger', $message);
            } elseif ($partner->complete == 1) {
                if ($partner->status == 1)
                    $message = "Company registration is complete, Waiting for verification stage";
                elseif ($partner->status == 2)
                    $message = "Company registration is verified, Waiting for approval stage";
                elseif ($partner->status == 3)
                    $message = "Company registration is approved, Waiting for training stage";
                elseif ($partner->status == 4)
                    $message = "Company is in training stage";

                //redirect 
                return Redirect::back()->withSuccess($message);
            }
        }
    }

    //verify account
    function verify_account($token)
    {
        if ($token != null) {
            $user = User::where('verify_token', $token)->first();

            if ($user) {
                $user->active = 1;
                $user->save();
                //redirect
                return Redirect::route('login')->with('success', 'Account activated, please login');
            } else {
                //redirect
                return Redirect::route('login')->with('danger', 'Failed to activate account');
            }
        } else {
            //redirect
            return Redirect::route('login')->with('danger', 'Failed to activate account');
        }
    }
}
