<?php

namespace App\Http\Controllers\Auth;

use App\Classes\Messaging;
use App\Http\Controllers\Controller;
use App\Jobs\SendEmailJob;
use App\Jobs\SendSMSJob;
use App\Models\OtpRequest;
use App\Models\PartnerUser;
use App\Models\User;
use App\Models\UserOtp;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Foundation\Auth\ThrottlesLogins;

class AuthOtpController extends Controller
{
    use ThrottlesLogins;

    public function username()
    {
        return 'email';
    }

    protected function maxAttempts()
    {
        return 3;
    }

    protected function decayMinutes()
    {
        return 5;
    }

    // Return View of OTP Login Page
    public function login()
    {
        $title = 'Login';

        //check if user already login
        if (Auth::check()) {
            //user
            $user = User::whereId(Auth::user()->id)->first();

            if ($user->hasRole('admin')) {
                // Authentication passed...
                return Redirect::route('dashboard')->with('success', 'Logged in successful');
            } else {
                // Authentication passed...
                return Redirect::route('payment-mandate.create-account')->with('success', 'Logged in successful');
            }
        }

        return view('auth.otp-login', compact('title'));
    }

    // Generate OTP
    public function generate(Request $request)
    {
        # Validate Data
        $request->validate([
            'email' => 'required|exists:users,email',
            'password' => 'required'
        ]);

        if ($this->hasTooManyLoginAttempts($request)) {
            $this->fireLockoutEvent($request);
            return $this->sendLockoutResponse($request);
        }

        //password
        $otp_password = base64_encode($request->input('password'));

        # Verify phone and email match
        $user = User::where(['email' => $request->email, 'otp_password' => $otp_password])->first();

        if (!$user) {
            $this->incrementLoginAttempts($request);

            //redirect back
            return Redirect::route('otp.login')->with('danger', 'Invalid email or password');
        }

        //check if logged in user can create mandate
        if ($user->hasRole('Company_Approver') || $user->hasRole('Company_User')) {
            $partner_user = PartnerUser::whereHas('partner', function ($query) {
                $query->where('status', '=', 202);
            })->where('user_id', '=', $user->id);

            if ($partner_user->count() == 0)
                return Redirect::route('otp.login')->with('danger', "Service provider is not activated, Kindly contact eMakato Team.");
        }

        //clear login attempts
        $this->clearLoginAttempts($request);

        # Generate An OTP
        $verificationCode = $this->generateOtp($user);

        //format mobile phone
        $phone =  casting_phone($user->phone);

        //construct message
        $message = "Your Emakato One Time Password(OTP) to Login is " . $verificationCode->otp;

        //send sms
        $messaging = new Messaging();
        $response = $messaging->sendSMS($phone, $message);
        Log::debug("== SMS sent ==");

        //send email
        $data = [
            "to_email" => $user->email,
            "from_email" => "support@solocodes.io",
            "name" => $user->name,
            "subject" => "eMakato OTP Verification",
            "body" => $message
        ];
        dispatch(new SendEmailJob($data));

        //redirect
        return Redirect::route('otp.verification', ['uuid' => $user->uuid])->with('success', $user->name . ', Welcome at eMakato. Enter OTP Pin sent to your phone and email.');
    }

    //generate and save otp
    public function generateOtp(User $user)
    {
        # User Does not Have Any Existing OTP
        $verificationCode = UserOtp::where(['user_id' => $user->id])->latest()->first();

        $now = Carbon::now();

        if ($verificationCode && $now->isBefore($verificationCode->expire_at)) {
            return $verificationCode;
        }

        // Create a New OTP
        return UserOtp::create([
            'user_id' => $user->id,
            'phone' => $user->phone,
            'otp' => rand(10000, 99999),
            'expire_at' => Carbon::now()->addMinutes(10)
        ]);
    }

    //verification
    public function verification($uuid)
    {
        $user = User::where('uuid', $uuid)->first();

        //render view
        return view('auth.otp-verification')->with([
            'uuid' => $uuid,
            'user_id' => $user->id
        ]);
    }

    //login with otp
    public function loginWithOtp(Request $request)
    {
        #Validation
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'otp' => 'required'
        ]);

        #Validation Logic
        $verificationCode = UserOtp::where('user_id', $request->user_id)->where('otp', $request->otp)->first();

        $now = Carbon::now();

        //check for verification
        if (!$verificationCode)
            return redirect()->back()->with('danger', 'Your OTP is not correct');
        elseif ($verificationCode && $now->isAfter($verificationCode->expire_at))
            return redirect()->route('otp.login')->with('danger', 'Your OTP has been expired');

        //query the user
        $user = User::whereId($request->user_id)->first();

        if ($user) {
            // Expire The OTP
            $verificationCode->update([
                'expire_at' => Carbon::now()
            ]);

            Auth::login($user); //login

            //redirect to next page
            return $this->proceedLogin($user);
        }

        //redirect
        return redirect()->route('otp.login')->with('error', 'Your Otp is not correct');
    }

    //proceed with login
    public function proceedLogin($user)
    {
        if ($user->hasRole('admin'))
            return Redirect::route('dashboard')->with('success', 'Logged in successful');
        else
            return Redirect::route('payment-mandate.create-account')->with('success', 'Logged in successful');
    }

    public function sendEmail()
    {
        //send email
        $data = [
            "to_email" => "renfridfrancis@gmail.com",
            "from_email" => "support@solocodes.io",
            "name" => "Renfrid William",
            "subject" => "eMakato OTP Verification",
            "body" => "Hello message"
        ];
        dispatch(new SendEmailJob($data));

        return response()->json(['error' => false, 'success_msg' => 'Message sent']);
    }
}
