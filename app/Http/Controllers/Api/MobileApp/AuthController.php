<?php

namespace App\Http\Controllers\Api\MobileApp;

use App\Http\Controllers\Api\ExternalApp\AuthController as ExternalAuthController;
use App\Models\User;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Validator;

class AuthController extends ExternalAuthController
{
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
            $linkedPartners = $this->getLinkedPartners($user);
            $userGroups = $this->userGroups($user);
            $primaryUserGroup = data_get($userGroups->first(), 'name');
            $primaryUserGroupLabel = data_get($userGroups->first(), 'description');
            $userPayload = array_merge($user->toArray(), [
                'user_group' => $primaryUserGroup,
                'user_group_label' => $primaryUserGroupLabel,
                'user_groups' => $userGroups,
            ]);

            return $this->successResponse('Login successful.', [
                'user' => $userPayload,
                'user_group' => $primaryUserGroup,
                'user_group_label' => $primaryUserGroupLabel,
                'user_groups' => $userGroups,
                'corporate_reference' => $linkedPartners->first()['corporate_reference'] ?? null,
                'corporate_references' => $linkedPartners->pluck('corporate_reference')->values(),
                'partners' => $linkedPartners,
                'token' => $user->createToken('eMakato Mobile')->accessToken,
            ]);
        }

        return $this->errorResponse('Invalid credentials.', null, 401);
    }

    protected function userGroups(User $user)
    {
        return $user->roles()
            ->select('roles.id', 'roles.name', 'roles.description')
            ->orderBy('roles.name')
            ->get()
            ->map(fn ($role) => [
                'id' => $role->id,
                'name' => $role->name,
                'description' => $role->description,
            ])
            ->values();
    }
}
