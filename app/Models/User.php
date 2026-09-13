<?php

namespace App\Models;

use App\Rules\PhoneNumber;
use Illuminate\Contracts\Auth\MustVerifyEmail;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Laravel\Passport\HasApiTokens;

class User extends Authenticatable
{
    use HasApiTokens, HasFactory, Notifiable;

    /**
     * The attributes that are mass assignable.
     *
     * @var array<int, string>
     */
    protected $guarded = ['id'];

    /**
     * The attributes that should be hidden for serialization.
     *
     * @var array<int, string>
     */
    protected $hidden = [
        'password',
        'remember_token',
    ];

    /**
     * The attributes that should be cast.
     *
     * @var array<string, string>
     */
    protected $casts = [
        'email_verified_at' => 'datetime',
    ];

    protected static function booted(): void
    {
        static::creating(function (User $user) {
            if (!$user->created_by && Auth::check()) {
                $user->created_by = Auth::id();
            }
        });

        static::updating(function (User $user) {
            $changedAttributes = array_diff(
                array_keys($user->getDirty()),
                ['updated_at', 'updated_by']
            );

            if (Auth::check() && !empty($changedAttributes)) {
                $user->updated_by = Auth::id();
            }
        });
    }

    public function createdBy()
    {
        return $this->belongsTo(self::class, 'created_by');
    }

    public function updatedBy()
    {
        return $this->belongsTo(self::class, 'updated_by');
    }

    //roles
    public function roles()
    {
        return $this->belongsToMany(Role::class);
    }

    /**
     * @param string|array $roles
     * @return bool
     */
    public function authorizeRoles($roles)
    {
        if (is_array($roles)) {
            return $this->hasAnyRole($roles) ||
                abort(401, 'This action is unauthorized.');
        }

        return $this->hasRole($roles) ||
            abort(401, 'This action is unauthorized.');
    }

    /**
     * Check multiple roles
     * @param array $roles
     * @return bool
     */
    public function hasAnyRole($roles)
    {
        $roles = is_array($roles) ? $roles : [$roles];

        return $this->roles->whereIn('name', $roles)->isNotEmpty();
    }

    /**
     * Check one or more roles
     * @param string|array $role
     * @return bool
     */
    public function hasRole($role)
    {
        if (is_array($role)) {
            return $this->hasAnyRole($role);
        }

        return $this->roles->contains('name', $role);
    }

    //rules
    public static function rules(): array
    {
        return [
            'name' => 'required|string',
            'email' => 'required|string|max:255|unique:users',
            'phone' => ['required', new PhoneNumber()],
            'role_id' => 'required',
            'provider_ids' => 'required_if:role_id,==,2,4',
            'company_id' => 'required_if:role_id,==,6',
            'username' => 'required_if:is_api_user,1|nullable|string|max:255|unique:users,username',
            'password' => 'required|string|min:8',
            'password_confirm' => 'required|same:password'
        ];
    }

    //messages
    public static function messages(): array
    {
        return [
            'name.required' => 'Full name is required',
            'email.required' => 'Email is required',
            'email.unique' => 'Email must be unique',
            'phone.required' => 'Phone is required',
            'role_id.required' => 'Role required',
            'provider_ids.required_if' => 'Service providers is required',
            'company_id.required_if' => 'eMakato partner is required',
            'username.required_if' => 'Username is required for API users',
            'username.unique' => 'Username must be unique',
            'password.required' => 'Password is required',
            'password_confirm.required' => 'Confirm password is required',
            'password_confirm.same:password' => 'Password must match',
        ];
    }

    public function partners()
    {
        return $this->hasMany(PartnerUser::class, 'user_id');
    }

    public function companies()
    {
        return $this->hasMany(CompanyUser::class, 'user_id');
    }
}
