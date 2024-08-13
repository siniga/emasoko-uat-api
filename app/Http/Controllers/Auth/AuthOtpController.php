<?php

namespace App\Http\Controllers\Auth;

use App\Classes\Messaging;
use App\Http\Controllers\Controller;
use App\Jobs\SendSMSJob;
use App\Models\OtpRequest;
use App\Models\User;
use App\Models\UserOtp;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;

class AuthOtpController extends Controller
{
    // Return View of OTP Login Page
    public function login()
    {
        return view('auth.otp-login');
    }

    // Generate OTP
    public function generate(Request $request)
    {
        # Validate Data
        $request->validate([
            'email' => 'required|exists:users,email',
            'phone' => 'required|exists:users,phone'
        ]);

        # Verify phone and email match
        $user = User::where(['email' => $request->email, 'phone' => $request->phone])->first();


        if (!$user) {
            return Redirect::route('otp.login')->with('danger', 'Email address and phone number does not match');
        }

        # Generate An OTP
        $verificationCode = $this->generateOtp($request->phone);

        //format mobile phone
        $phone =  cast_phone($request->phone);

        //construct message
        $message = "Your OTP to Login is - " . $verificationCode->otp;

        //send sms
        $data = ["phone" => $phone, "message" => $message];
        dispatch(new SendSMSJob($data));

        //redirect
        return Redirect::route('otp.verification', ['user_id' => $verificationCode->user_id])->with('success', $user->name . ', Welcome at eMakato. Enter OTP Pin sent to your mobile phone.');
    }

    //generate and save otp
    public function generateOtp($mobile_no)
    {
        $user = User::where('phone', $mobile_no)->first();

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
    public function verification($user_id)
    {
        return view('auth.otp-verification')->with([
            'user_id' => $user_id
        ]);
    }

    //login with otp
    public function loginWithOtp(Request $request)
    {
        Log::debug("Reached hapa 1");
        Log::debug($request->all());

        #Validation
        $request->validate([
            'user_id' => 'required|exists:users,id',
            'otp' => 'required'
        ]);

        #Validation Logic
        $verificationCode = UserOtp::where('user_id', $request->user_id)->where('otp', $request->otp)->first();

        Log::debug("Reached hapa 2");
        Log::debug($verificationCode);

        $now = Carbon::now();
        if (!$verificationCode) {
            return redirect()->back()->with('error', 'Your OTP is not correct');
        } elseif ($verificationCode && $now->isAfter($verificationCode->expire_at)) {
            return redirect()->route('otp.login')->with('error', 'Your OTP has been expired');
        }

        //query the user
        $user = User::whereId($request->user_id)->first();

        Log::debug("Reached hapa 3");
        Log::debug($user);

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
        if (Auth::user()->hasRole('admin')) {
            // Authentication passed...
            return Redirect::route('dashboard')->with('success', 'Logged in successful');
        } else {
            // Authentication passed...
            return Redirect::route('payment-mandate.create-account')->with('success', 'Logged in successful');
        }
    }
}
