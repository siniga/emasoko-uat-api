<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
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
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Validator;
use Stidges\CountryFlags\CountryFlag;

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
            $message = 'Company registration complete, Please wait for approval from eMakato Team!';

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
            //create otp token
            $otp_token = $this->generateOtpToken();

            //update company token
            $partner->otp_token = $otp_token;
            $partner->save();

            //send verification code through email
            //construct message
            $message = "<p>Enter this 6 digit code on the verify company page to confirm your identity:</p>";
            $message .= "{$otp_token}";
            $message .= "<p>You can start using your eMakato account now. Login to your account by <a href=" . url('login') . ">click here.</a></p>";

            //send email for verification
            $data = [
                "email" => $partner->email,
                "name" => $partner->business_name,
                "subject" => "Company Verification Code",
                "body" => $message
            ];
            dispatch(new SendEmailJob($data));

            //redirect to verify company registration
            return Redirect::route('company.verify-registration', $partner->uuid)->with('success', 'Verification code sent to your email.');
        }
    }


    //verify application
    function verifyCompanyRegistrationForm($uuid)
    {
        $title = 'Track Application';

        //company
        $company = Partner::where(['uuid' => $uuid])->firstOrFail();

        //render view
        return view('auth.verify_company_registration', compact('title', 'company'));
    }

    //track company registration
    function verifyCompanyRegistration(Request $request, $uuid)
    {
        //validation
        $this->validate(
            $request,
            [
                'verification_code' => 'required'
            ],
            [
                'verification_code.required' => 'Verification code required',
            ]
        );

        //post data
        $verification_code = $request->input('verification_code');

        $partner = Partner::where(['uuid' => $uuid, 'otp_token' => $verification_code])->first();

        if (!$partner) {
            //redirect
            return Redirect::back()->with('danger', 'Invalid verification code!');
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
                return Redirect::route('company.verify-registration-success')->withSuccess($message);
            }
        }
    }

    //verify application
    function verifyCompanyRegistrationSuccess()
    {
        $title = 'Track Application';

        //render view
        return view('auth.verify_company_registration_success', compact('title'));
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

    //generate message_id
    function generateOtpToken()
    {
        //the characters you want in your id
        $characters = '0123456789';
        $max = strlen($characters) - 1;
        $string = '';

        for ($i = 1; $i <= 6; $i++) {
            $string .= $characters[mt_rand(0, $max)];
        }
        return $string;
    }
}
