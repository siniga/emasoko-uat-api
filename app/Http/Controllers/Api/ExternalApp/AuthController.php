<?php

namespace App\Http\Controllers\Api\ExternalApp;

use App\Http\Controllers\Controller;
use App\Models\PartnerUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
// use Dedoc\Scramble\Attributes\ExcludeRouteFromDocs;

class AuthController extends Controller
{
    protected function getPartnerReference(User $user): ?string
    {
        $partnerUser = PartnerUser::with('partner')
            ->where('user_id', $user->id)
            ->orderByDesc('authorize')
            ->first();

        if ($partnerUser && $partnerUser->partner) {
            return $partnerUser->partner->reference;
        }

        return null;
    }

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
            $partnerReference = $this->getPartnerReference($user);

            return response()->json([
                'success' => true,
                'response_code' => 200,
                'user' => $user,
                'corporate_reference' => $partnerReference,
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
            'partnerId' => 'required|string',
            'password' => 'required|string'
        ]);

        if ($validator->fails()) {
            return response()->json([
                'success' => false,
                'response_code' => 403,
                'error_msg' => $validator->errors()
            ]);
        }

        if (Auth::attempt(['username' => $request->partnerId, 'password' => $request->password])) {
            $user = User::findOrFail(Auth::user()->id);
            $partnerReference = $this->getPartnerReference($user);

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
                'corporate_reference' => $partnerReference,
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
