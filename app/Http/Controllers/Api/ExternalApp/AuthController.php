<?php

namespace App\Http\Controllers\Api\ExternalApp;

use App\Http\Controllers\Controller;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AuthController extends Controller
{
    public function login(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'email' => 'required|string|email',
            'password' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'response_code' => 403,
                'error_msg' => $validator->errors()
            ]);
        }

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $user = User::findOrFail(Auth::user()->id);

            return response()->json([
                'success' => true,
                'response_code' => 200,
                'user' => $user,
                'token' => $user->createToken('eMakato')->accessToken,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'response_code' => 400,
                'error_msg' => 'Invalid credentials'
            ]);
        }
    }

    public function token(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'username' => 'required|string',
            'password' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'response_code' => 403,
                'error_msg' => $validator->errors()
            ]);
        }

        if (Auth::attempt(['username' => $request->username, 'password' => $request->password])) {
            $user = User::findOrFail(Auth::user()->id);

            // check if the user is api enabled and logout the user if not
            if (!$user->is_api_user) {
                Auth::logout();

                //response if the user is not api enabled
                return response()->json([
                    'success' => false,
                    'response_code' => 403,
                    'error_msg' => 'API access is not enabled for this user.'
                ]);
            }

            return response()->json([
                'success' => true,
                'response_code' => 200,
                'token' => $user->createToken('eMakato')->accessToken,
            ]);
        } else {
            return response()->json([
                'success' => false,
                'response_code' => 400,
                'error_msg' => 'Invalid credentials'
            ]);
        }
    }
}
