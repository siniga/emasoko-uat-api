<?php

namespace App\Http\Controllers;

use App\Models\Company;
use App\Models\CompanyUser;
use App\Models\Partner;
use App\Models\PartnerUser;
use App\Models\Role;
use App\Models\RoleUser;
use App\Models\User;
use App\Models\UserOtp;
use App\Rules\PhoneNumber;
use Carbon\Carbon;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Illuminate\Support\Str;

class UserController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    public function index(Request $request)
    {
        $title = 'Users';
        //has_allowed('roles', 'lists');

        $baseUsersQuery = User::query()->with('roles', 'partners.partner');

        if (isset($_POST['filter'])) {
            //request 
            $start_date  = $request->start_at ?? null;
            $end_date    = $request->end_at ?? null;
            $partner_id  = $request->partner_id ?? null;
            $status      = $request->status ?? null;
            $keyword     = $request->keyword ?? null;

            //users
            $users = (clone $baseUsersQuery)->orderBy('name');

            //start date and end data
            if ($request->has('start_at') || $request->has('end_at')) {
                $users = $users->when($start_date, function ($query) use ($start_date, $end_date) {
                    if ($start_date != null && $end_date != null)
                        $query->whereBetween('users.created_at', [$start_date, $end_date]);

                    else if ($start_date != null)
                        $query->where('users.created_at', '>=', $start_date);

                    else if ($end_date != null)
                        $query->where('users.created_at', '<=', $end_date);
                });
            }

            //status
            if ($request->has('status')) {
                $users = $users->when($status, function ($query) use ($status) {
                    $query->where('users.active', '=', $status);
                });
            }

            //partner id    
            if ($request->has('partner_id')) {
                $users = $users->when($partner_id, function ($query) use ($partner_id) {
                    $query->whereHas("partners", function ($q) use ($partner_id) {
                        $q->where('partner_id', '=', $partner_id);
                    });
                });
            }

            //keyword
            if ($request->has('keyword')) {
                $users = $users->when($keyword, function ($query) use ($keyword) {
                    $query->where(function ($q) use ($keyword) {
                        $q->where(DB::raw('LOWER(name)'), 'like', "%" . strtolower($keyword) . "%")
                            ->orWhere(DB::raw('LOWER(phone)'), 'like', "%" . strtolower($keyword) . "%")
                            ->orWhere(DB::raw('LOWER(email)'), 'like', "%" . strtolower($keyword) . "%");
                    });
                });
            }

            //users
            $users = $users->get();
        } else {
            $users = (clone $baseUsersQuery)->orderBy('name')->paginate(50);
        }

        $statsQuery = (clone $baseUsersQuery);

        if (isset($_POST['filter'])) {
            $start_date  = $request->start_at ?? null;
            $end_date    = $request->end_at ?? null;
            $partner_id  = $request->partner_id ?? null;
            $status      = $request->status ?? null;
            $keyword     = $request->keyword ?? null;

            if ($request->has('start_at') || $request->has('end_at')) {
                $statsQuery = $statsQuery->when($start_date, function ($query) use ($start_date, $end_date) {
                    if ($start_date != null && $end_date != null)
                        $query->whereBetween('users.created_at', [$start_date, $end_date]);

                    else if ($start_date != null)
                        $query->where('users.created_at', '>=', $start_date);

                    else if ($end_date != null)
                        $query->where('users.created_at', '<=', $end_date);
                });
            }

            if ($request->has('status')) {
                $statsQuery = $statsQuery->when($status, function ($query) use ($status) {
                    $query->where('users.active', '=', $status);
                });
            }

            if ($request->has('partner_id')) {
                $statsQuery = $statsQuery->when($partner_id, function ($query) use ($partner_id) {
                    $query->whereHas("partners", function ($q) use ($partner_id) {
                        $q->where('partner_id', '=', $partner_id);
                    });
                });
            }

            if ($request->has('keyword')) {
                $statsQuery = $statsQuery->when($keyword, function ($query) use ($keyword) {
                    $query->where(function ($q) use ($keyword) {
                        $q->where(DB::raw('LOWER(name)'), 'like', "%" . strtolower($keyword) . "%")
                            ->orWhere(DB::raw('LOWER(phone)'), 'like', "%" . strtolower($keyword) . "%")
                            ->orWhere(DB::raw('LOWER(email)'), 'like', "%" . strtolower($keyword) . "%");
                    });
                });
            }
        }

        $listStats = [
            'total' => (clone $statsQuery)->count(),
            'active' => (clone $statsQuery)->where('active', 1)->count(),
            'inactive' => (clone $statsQuery)->where('active', 0)->count(),
            'api_users' => (clone $statsQuery)->where('is_api_user', 1)->count(),
        ];

        //post data
        $post_data = [
            'start_at' => $request->start_at ?? null,
            'end_at' => $request->end_at ?? null,
            'partner_id' => $request->partner_id ?? null,
            'status' => $request->status ?? null,
            'keyword' => $request->keyword ?? null,
        ];

        //service provider
        $providers = Partner::orderBy('business_name')->get();

        //render view
        return view('users.lists', compact('users', 'providers', 'post_data', 'title', 'listStats'));
    }

    public function create()
    {
        $title = 'Register New User';

        //roles
        $roles = Role::all();

        //service provider
        $providers = Partner::orderBy('business_name')->get();

        //companies
        $companies = Company::orderBy('company_name')->get();

        //render view
        return view('users.create', compact('title', 'roles', 'providers', 'companies'));
    }

    public function store(Request $request)
    {
        $this->validate($request, User::rules(), User::messages());

        //password
        $password = $request->input('password');

        //create user to store data
        $user = new User([
            'uuid' => Str::uuid(),
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
            'username' => $request->input('is_api_user') ? $request->input('username') : null,
            'password' => Hash::make($password),
            'otp_password' => base64_encode($password),
            'is_api_user' => $request->input('is_api_user') ? 1 : 0,
            'active' => 1,
        ]);
        $user->save();

        //insert or create role
        $user->roles()->attach($request->input('role_id'));

        $providerIds = collect($request->input('provider_ids', []))->filter()->unique()->values();

        //service provider
        if ($request->input('role_id') == 2 || $request->input('role_id') == 4 || ($request->input('is_api_user') && $providerIds->isNotEmpty())) {
            foreach ($providerIds as $provider_id) {
                //partner
                $partner = Partner::where(['id' => $provider_id])->first();

                if ($partner) {
                    //partner user
                    $partner_user = PartnerUser::firstOrNew([
                        'user_id' => $user->id,
                        'partner_id' => $partner->id,
                        'partner_uuid' => $partner->uuid
                    ]);
                    $partner_user->authorize = 1;
                    $partner_user->save();
                }
            }
        } else if ($request->input('role_id') == 6) {
            //create company user
            $company_user = CompanyUser::firstOrNew([
                'user_id' => $user->id,
                'company_id' => $request->input('company_id')
            ]);
            $company_user->authorize = 1;
            $company_user->save();
        }

        //redirect
        return Redirect::route('users.index')->with('success', 'User registered');
    }

    public function edit($id)
    {
        $title = 'Edit User';

        //user
        $user = User::findOrFail($id);

        //user current role
        $current_role = $user->roles[0] ?? null;

        //company user
        $company_user = $user->companies[0] ?? null;

        //assigned partners
        $assigned_partners = [];
        $partners = $user->partners->where('authorize', '=', 1);

        foreach ($partners as $partner) {
            array_push($assigned_partners, $partner->partner_id);
        }

        //roles
        $roles = Role::all();

        //service provider
        $providers = Partner::orderBy('business_name')->get();

        //companies
        $companies = Company::orderBy('company_name')->get();

        //render view
        return view('users.edit', compact('title', 'user', 'roles', 'providers', 'companies', 'current_role', 'company_user', 'assigned_partners'));
    }

    public function update(Request $request, $id)
    {
        $this->validate(
            $request,
            [
                'name' => 'required|string',
                'email' => 'required|string|max:255',
                'phone' => ['required', new PhoneNumber()],
                'role_id' => 'required',
                'provider_ids' => 'required_if:role_id,==,2,4',
                'company_id' => 'required_if:role_id,==,6',
                'username' => 'required_if:is_api_user,1|nullable|string|max:255|unique:users,username,' . $id,
            ],
            [
                'name.required' => 'Full name is required',
                'email.required' => 'Email is required',
                'phone.required' => 'Phone is required',
                'role_id.required' => 'Role required',
                'provider_ids.required_if' => 'Service providers required',
                'company_id.required_if' => 'eMakato partner is required',
                'username.required_if' => 'Username is required for API users',
                'username.unique' => 'Username must be unique',
            ]
        );

        //validate password
        if ($request->input('password') != null || $request->input('password') != '') {
            $this->validate($request, [
                'password' => 'required|string|min:8',
                'password_confirm' => 'required|string|min:8|same:password'
            ], [
                'password.required' => 'Password is required',
                'password.min:8' => 'Password must be 8 or more than character',
                'password_confirm.required' => 'Password confirm is required',
                'password_confirm.same:password' => 'Password confirm must match password'
            ]);
        }

        //password
        $password = $request->input('password');

        //create object to store data
        $user = User::find($id);
        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $user->phone = $request->input('phone');
        $user->is_api_user = $request->input('is_api_user') ? 1 : 0;
        $user->username = $request->input('is_api_user') ? $request->input('username') : null;

        if ($request->input('password') != null || $request->input('password') != '') {
            $user->password = Hash::make($password);
            $user->otp_password = base64_encode($password);
        }

        $user->save();

        //remove role from role_user
        RoleUser::where('user_id', $id)->delete();

        //insert or create role
        $user->roles()->attach($request->input('role_id'));

        $providerIds = collect($request->input('provider_ids', []))->filter()->unique()->values();

        //service provider
        if ($request->input('role_id') == 2 || $request->input('role_id') == 4 || ($request->input('is_api_user') && $providerIds->isNotEmpty())) {
            //deactivate all the partners by user_id
            $deactivate_pt = PartnerUser::where('user_id', '=', $user->id)->update(['authorize' => 0]);

            //activate selected users
            foreach ($providerIds as $provider_id) {
                //partner
                $partner = Partner::where(['id' => $provider_id])->first();

                if ($partner) {
                    $partner_user = PartnerUser::firstOrNew([
                        'user_id' => $user->id,
                        'partner_id' => $partner->id,
                        'partner_uuid' => $partner->uuid
                    ]);
                    $partner_user->authorize = 1;
                    $partner_user->save();
                }
            }
        } else if ($request->input('role_id') == 6) {
            //create company user
            $company_user = CompanyUser::firstOrNew([
                'user_id' => $user->id,
                'company_id' => $request->input('company_id')
            ]);
            $company_user->authorize = 1;
            $company_user->save();
        }

        //redirect
        return Redirect::route('users.index')->with('success', 'User updated');
    }

    public function show($id) {}

    //change status
    public function change_status($id)
    {
        $title = 'Change status';
        //has_allowed('roles', 'edit');

        //user 
        $user = User::findOrFail($id);

        //render view
        return view('users.change_status', compact('user'));
    }

    //store status
    public function store_status(Request $request, $id)
    {
        $this->validate(
            $request,
            ['status' => 'required'],
            ['status.required' => 'Status required']
        );

        //status
        $status = $request->input('status');


        //update user status
        $user = User::findOrFail($id);
        $user->active = $status;
        $user->save();

        //design message
        if ($status == 1)
            $message = 'User account activated';
        else if ($status == 0)
            $message = 'User account deactivated';

        //redirect
        return Redirect::route('users.index')->with('success', $message);
    }

    //delete
    public function delete($id)
    {
        $title = 'Delete User';
        // has_allowed('cars', 'delete');

        $user = User::findOrFail($id);

        if ($del = $user->delete()) {
            //todo => delete other user information

            //redirect
            return Redirect::route('users.index')->with('success', 'User deleted');
        } else {
            return Redirect::route('users.index')->with('danger', 'Failed to delete user');
        }
    }

    public function view_otp()
    {
        $title = 'View OTP';
        //has_allowed('roles', 'lists');

        $users_otp = UserOtp::whereDate('expire_at', '>=', Carbon::now())->orderBy('created_at', 'desc')->get();

        //render view
        return view('users.otp', compact('users_otp', 'title'));
    }
}
