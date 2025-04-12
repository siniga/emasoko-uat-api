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
        }else{
            return response()->json([
                'success' => false,
                'response_code' => 400,
                'error_msg' => 'Invalid credentials'
            ]);
        }
    }
}
