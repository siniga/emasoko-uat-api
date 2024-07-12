<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;

use App\Models\User;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Facades\Auth;

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
                if (Auth::user()->hasRole('admin')) {
                    // Authentication passed...
                    return Redirect::route('dashboard')->with('success', 'Logged in successful');
                } else {
                    // Authentication passed...
                    return Redirect::route('payment-mandate.create-account')->with('success', 'Logged in successful');
                }
            } else {
                Auth::logout();
                return Redirect::route('login')->with('danger', 'Your account is deactivated, contact with administrator.');
            }
        } else {
            return Redirect::route('login')->with('danger', 'Invalid Email or Password');
        }
    }

    //logout
    public function logout()
    {
        Auth::logout();
        return Redirect::route('login');
    }
}
