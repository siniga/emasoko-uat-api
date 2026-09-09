<?php

namespace App\Http\Controllers\Api\MobileApp;

use App\Http\Controllers\Api\ExternalApp\Concerns\RespondsWithApi;
use App\Http\Controllers\Controller;
use Dedoc\Scramble\Attributes\Group;
use Illuminate\Http\JsonResponse;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Validator;
use Illuminate\Validation\Rule;

#[Group('User', description: 'Mobile APIs for authenticated user profile, sessions, and notifications.', weight: 4)]
class UserController extends Controller
{
    use RespondsWithApi;

    public function profile(Request $request): JsonResponse
    {
        return $this->successResponse('Profile fetched successfully.', [
            'user' => $this->profilePayload($request->user()),
        ]);
    }

    public function updateProfile(Request $request): JsonResponse
    {
        $user = $request->user();
        $validator = Validator::make($request->all(), [
            'name' => ['sometimes', 'required', 'string', 'max:255'],
            'email' => ['sometimes', 'required', 'email', 'max:255', Rule::unique('users', 'email')->ignore($user->id)],
            'phone' => ['sometimes', 'nullable', 'string', 'max:30'],
            'current_password' => ['required_with:password', 'string'],
            'password' => ['sometimes', 'nullable', 'string', 'min:8', 'confirmed'],
        ]);

        if ($validator->fails()) {
            return $this->errorResponse('Validation failed.', $validator->errors(), 422);
        }

        $validated = $validator->validated();

        if (!empty($validated['password'])) {
            if (!Hash::check($validated['current_password'], $user->password)) {
                return $this->errorResponse('Current password is incorrect.', [
                    'current_password' => ['Current password is incorrect.'],
                ], 422);
            }

            $validated['password'] = Hash::make($validated['password']);
        }

        unset($validated['current_password'], $validated['password_confirmation']);

        $user->fill($validated);
        $user->save();

        return $this->successResponse('Profile updated successfully.', [
            'user' => $this->profilePayload($user->fresh()),
        ]);
    }

    public function logout(Request $request): JsonResponse
    {
        $token = $request->user()?->token();

        if ($token) {
            $token->revoke();
        }

        return $this->successResponse('Logged out successfully.');
    }

    public function notifications(Request $request): JsonResponse
    {
        $perPage = min(max((int) $request->input('per_page', 20), 1), 100);

        $notifications = $request->user()
            ->notifications()
            ->latest()
            ->paginate($perPage);

        $items = $notifications->getCollection()
            ->map(function ($notification) {
                return [
                    'id' => $notification->id,
                    'type' => $notification->type,
                    'title' => $notification->data['title'] ?? null,
                    'message' => $notification->data['message'] ?? null,
                    'data' => $notification->data,
                    'is_read' => $notification->read_at !== null,
                    'read_at' => optional($notification->read_at)->toDateTimeString(),
                    'created_at' => optional($notification->created_at)->toDateTimeString(),
                ];
            })
            ->values();

        return $this->successResponse('Notifications fetched successfully.', [
            'notifications' => $items,
            'unread_count' => $request->user()->unreadNotifications()->count(),
            'pagination' => [
                'current_page' => $notifications->currentPage(),
                'per_page' => $notifications->perPage(),
                'last_page' => $notifications->lastPage(),
                'total' => $notifications->total(),
            ],
        ]);
    }

    public function markNotificationAsRead(Request $request, string $id): JsonResponse
    {
        $notification = $request->user()->notifications()->where('id', $id)->first();

        if (!$notification) {
            return $this->errorResponse('Notification not found.', null, 404);
        }

        $notification->markAsRead();

        return $this->successResponse('Notification marked as read.', [
            'notification_id' => $notification->id,
            'unread_count' => $request->user()->unreadNotifications()->count(),
        ]);
    }

    public function markAllNotificationsAsRead(Request $request): JsonResponse
    {
        $request->user()->unreadNotifications->markAsRead();

        return $this->successResponse('All notifications marked as read.', [
            'unread_count' => 0,
        ]);
    }

    protected function profilePayload($user): array
    {
        $userGroups = $this->userGroups($user);

        return [
            'id' => $user->id,
            'name' => $user->name,
            'email' => $user->email,
            'phone' => $user->phone ?? null,
            'username' => $user->username ?? null,
            'user_group' => data_get($userGroups->first(), 'name'),
            'user_group_label' => data_get($userGroups->first(), 'description'),
            'user_groups' => $userGroups,
        ];
    }

    protected function userGroups($user)
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
