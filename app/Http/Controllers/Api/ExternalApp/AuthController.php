<?php

namespace App\Http\Controllers\Api\ExternalApp;

use App\Http\Controllers\Api\ExternalApp\Concerns\RespondsWithApi;
use App\Http\Controllers\Controller;
use App\Models\PartnerUser;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
// use Dedoc\Scramble\Attributes\ExcludeRouteFromDocs;

class AuthController extends Controller
{
    use RespondsWithApi;

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
            return $this->errorResponse('Validation failed.', $validator->errors(), 422);
        }

        if (Auth::attempt(['email' => $request->email, 'password' => $request->password])) {
            $user = User::findOrFail(Auth::user()->id);
            $partnerReference = $this->getPartnerReference($user);

            return $this->successResponse('Login successful.', [
                'user' => $user,
                'corporate_reference' => $partnerReference,
                'token' => $user->createToken('eMakato')->accessToken,
            ]);
        }

        return $this->errorResponse('Invalid credentials.', null, 401);
    }

    public function token(Request $request)
    {
        $validator = Validator::make($request->all(), [
            'partnerId' => 'required|string',
            'password' => 'required|string'
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed.', $validator->errors(), 422);
        }

        if (Auth::attempt(['username' => $request->partnerId, 'password' => $request->password])) {
            $user = User::findOrFail(Auth::user()->id);
            $partnerReference = $this->getPartnerReference($user);

            // check if the user is api enabled and logout the user if not
            if (!$user->is_api_user) {
                Auth::logout();

                return $this->errorResponse('API access is not enabled for this user.', null, 403);
            }

            return $this->successResponse('Token generated successfully.', [
                'corporate_reference' => $partnerReference,
                'token' => $user->createToken('eMakato')->accessToken,
            ]);
        }

        return $this->errorResponse('Invalid credentials.', null, 401);
    }
}
