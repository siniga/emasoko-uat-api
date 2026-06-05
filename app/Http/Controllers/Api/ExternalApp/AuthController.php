<?php

namespace App\Http\Controllers\Api\ExternalApp;

use App\Http\Controllers\Api\ExternalApp\Concerns\RespondsWithApi;
use App\Http\Controllers\Controller;
use App\Models\PartnerUser;
use App\Models\User;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;
// use Dedoc\Scramble\Attributes\ExcludeRouteFromDocs;

#[Group('Auth', description: 'APIs for external API authentication.', weight: 1)]
class AuthController extends Controller
{
    use RespondsWithApi;

    protected function getLinkedPartners(User $user)
    {
        return PartnerUser::with('partner')
            ->where('user_id', $user->id)
            ->where('authorize', 1)
            ->get()
            ->filter(fn ($partnerUser) => $partnerUser->partner)
            ->map(fn ($partnerUser) => [
                'id' => $partnerUser->partner->id,
                'uuid' => $partnerUser->partner->uuid,
                'name' => $partnerUser->partner->business_name,
                'corporate_reference' => $partnerUser->partner->reference,
            ])
            ->values();
    }

    protected function getPartnerReference(User $user): ?string
    {
        return $this->getLinkedPartners($user)->first()['corporate_reference'] ?? null;
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
            $linkedPartners = $this->getLinkedPartners($user);
            $partnerReference = $linkedPartners->first()['corporate_reference'] ?? null;

            // check if the user is api enabled and logout the user if not
            if (!$user->is_api_user) {
                Auth::logout();

                return $this->errorResponse('API access is not enabled for this user.', null, 403);
            }

            return $this->successResponse('Token generated successfully.', [
                'corporate_references' => $linkedPartners->pluck('corporate_reference')->values(),
                'token' => $user->createToken('eMakato')->accessToken,
            ]);
        }

        return $this->errorResponse('Invalid credentials.', null, 401);
    }
}
