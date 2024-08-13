<?php

namespace App\Http\Controllers\Auth;

use App\Events\LoginOtpRequest;
use App\Http\Controllers\Controller;
use App\Models\OtpRequest;
use Illuminate\Http\Request;

use App\Models\User;
use App\Models\UserOtp;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Session;

class LoginController extends Controller
{
    /*
    |--------------------------------------------------------------------------
    | Login Controller
    |--------------------------------------------------------------------------
    |
    | This controller handles authenticating users for the application and
    | redirecting them to your home screen. The controller uses a trait
    | to conveniently provide its functionality to your applications.
    |
    */

    /**
     * Where to redirect users after login.
     *
     * @var string
     */
    protected $redirectTo = '/';

    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('guest')->except('logout');
    }

    //login form
    function showLoginForm()
    {
        $title = 'Login';

        //check if user already login
        if (Auth::check()) {
            if (Auth::user()->hasRole('admin')) {
                // Authentication passed...
                return Redirect::route('dashboard')->with('success', 'Logged in successful');
            } else {
                // Authentication passed...
                return Redirect::route('payment-mandate.create-account')->with('success', 'Logged in successful');
            }
        }

        //render view
        return view('auth.login', compact('title'));
    }

    //authenticate
    public function login(Request $request)
    {
        //validation
        $this->validate(
            $request,
            [
                'email' => 'required',
                'password' => 'required'
            ],
            [
                'email.required' => 'Email required',
                'password.required' => 'Password required',
            ]
        );

        //post data
        $email = $request->input('email');
        $password = $request->input('password');
        $remember = $request->input('remember');

        if (Auth::attempt(['email' => $email, 'password' => $password], $remember)) {
            $user = User::find(auth()->user()->id);

            if ($user->active == 1) {
                event(new LoginOtpRequest($user)); //dispatch login otp request

                //create token
                $token = $user->createToken('appToken')->accessToken;
                Session::put('token', $token);

                //redirect
                return Redirect::route('otp.verification.form')->with('success', $user->name . ', Welcome at eMakato. Enter OTP Pin sent to your mobile phone.');
            } else {
                Auth::logout();
                return Redirect::route('login')->with('danger', 'Your account is deactivated, contact with administrator.');
            }
        } else {
            return Redirect::route('login')->with('danger', 'Invalid Email or Password');
        }
    }

    public function otpVerification(Request $request)
    {
        $this->validate(
            $request,
            [
                'otp_pin' => 'required',
            ],
            [
                'otp_pin.required' => 'OTP Pin is required',
            ]
        );

        $otp = $request->input('otp_pin');
        $user = Auth::user();

        //check if user exists
        if (!$user) {
            return Redirect('login')->with('danger', 'Session Expired Please Login Again');
        }

        //check if otp is correct
        $check_otp = UserOtp::where(['user_id' => $user->id, 'otp' => $otp, 'status' => 0])
            ->whereBetween('created_at', [
                now()->format('Y-m-d H:00:00'),
                now()->addHours(1)->format('Y-m-d H:00:00')
            ])->first();

        if (!$check_otp) {
            return Redirect::back()->with('danger', 'OTP Already Expired or Not Valid');
        }

        $check_otp->status = 1;
        $check_otp->save();

        //put session verified in session
        Session::put('otp_verified', true);

        //TODO: check if is first Login - Change password


        return $this->proceedLogin($user);
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

    //logout
    public function logout()
    {
        Auth::logout();
        return Redirect::route('login');
    }
}
