<?php

namespace App\Http\Controllers\Auth;

use App\Http\Controllers\Controller;
use Illuminate\Http\Request;
use Illuminate\Support\Str;
use Illuminate\Support\Facades\DB;
use App\Models\User;
use Illuminate\Support\Facades\Mail;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;

use App\Models\Make;
use App\Models\BodyType;
use App\Models\Color;

class ForgotPasswordController extends Controller
{
    /**
     * Write code on Method
     *
     * @return response()
     */
    public function showForgetPasswordForm()
    {
        //populate data
        // $makes = Mak::where('icon', '!=', null)->orderBy('total_make', 'DESC')->get();
        // $body_types = BodyType::where('icon', '!=', null)->get();
        // $colors = Color::all();

        //render view        
        return view('auth.forget_password', compact('makes', 'body_types'));
    }

    /**
     * Write code on Method
     *
     * @return response()
     */
    public function submitForgetPasswordForm(Request $request)
    {
        $request->validate(
            ['email' => 'required|email|exists:users'],
            [
                'email.required' => 'Email required',
                'email.email' => 'Invalid email',
                'email.exists:users' => 'Email does not exists',
            ]
        );

        $token = Str::random(64);
        $request_date_time = date('Y-m-d H:i:s');

        DB::table('password_resets')->insert([
            'email' => $request->email,
            'token' => $token,
            'created_at' => $request_date_time
        ]);

        //user details
        $user = User::where('email', '=', $request->email)->first();

        //send email
        Mail::send(
            ['html' => 'emails.forget_password'],
            ['token' => $token, 'name' => $user->name, 'username' => $user->email, 'request_date_time' => date('d-m-Y H:i', strtotime($request_date_time))],
            function ($message) use ($request) {
                $message->to($request->email);
                $message->subject('Reset Password');
                $message->from('williamgudluck87@gmail.com', 'ProinMotors');
            }
        );

        //redirect
        return back()->with('success', 'We have e-mailed your password reset link!');
    }
    /**
     * Write code on Method
     *
     * @return response()
     */
    public function showResetPasswordForm($token)
    {
        return view('auth.forget_Password_link', ['token' => $token]);
    }

    /**
     * Write code on Method
     *
     * @return response()
     */
    public function submitResetPasswordForm(Request $request)
    {
        $request->validate(
            [
                'email' => 'required|email|exists:users',
                'password' => 'required|string',
                'password_confirmation' => ['required', 'same:password'],
            ],
            [
                'email.required' => 'Email required',
                'email.email' => 'Invalid email',
                'email.exists:users' => 'Email does not exists',
                'password.required' => 'Password required',
                'password_confirmation.required' => 'Confirmation password required',
                'password_confirmation.same:password' => 'Password must match'
            ]
        );

        $updatePassword = DB::table('password_resets')
            ->where([
                'email' => $request->email,
                'token' => $request->token
            ])
            ->first();

        if (!$updatePassword) {
            return back()->withInput()->with('danger', 'Invalid token!');
        }

        $user = User::where('email', $request->email)
            ->update(['password' => Hash::make($request->password)]);

        DB::table('password_resets')->where(['email' => $request->email])->delete();

        return Redirect::route('login')->with('success', 'Your password has been changed!');
    }
}
