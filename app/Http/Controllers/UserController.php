<?php

namespace App\Http\Controllers;

use App\Models\Partner;
use App\Models\PartnerUser;
use App\Models\Role;
use App\Models\RoleUser;
use App\Models\User;
use App\Rules\PhoneNumber;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;

class UserController extends Controller
{
    /**
     * Create a new controller instance.
     */
    public function __construct()
    {
        $this->middleware('auth');
    }

    function index()
    {
        $title = 'Users';
        //has_allowed('roles', 'lists');

        $users = User::all();

        //render view
        return view('users.lists', compact('users', 'title'));
    }

    function create()
    {
        $title = 'Register New User';

        //roles
        $roles = Role::all();

        //service provider
        $providers = Partner::orderBy('business_name')->get();

        //render view
        return view('users.create', compact('title', 'roles', 'providers'));
    }

    function store(Request $request)
    {
        $this->validate($request, User::rules(), User::messages());

        $password = $request->input('password');

        //create user to store data
        $user = new User([
            'name' => $request->input('name'),
            'email' => $request->input('email'),
            'phone' => $request->input('phone'),
            'password' => Hash::make($password),
            'active' => 1,
        ]);
        $user->save();

        //insert or create role
        $user->roles()->attach($request->input('role_id'));

        //service provider
        if ($request->input('role_id') == 2 || $request->input('role_id') == 4) {
            //partner
            $partner = Partner::where(['id' => $request->input('provider_id')])->first();

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

        //redirect
        return Redirect::route('users.index')->with('success', 'User registered');
    }

    function edit($id)
    {
        $title = 'Edit User';

        //user
        $user = User::findOrFail($id);

        //roles
        $roles = Role::all();

        //service provider
        $providers = Partner::orderBy('business_name')->get();

        //current roles
        $current_role = RoleUser::where('user_id', $id)->first();

        //partner user
        $partner_user = PartnerUser::where('user_id', $id)->first();

        //render view
        return view('users.edit', compact('title', 'user', 'roles', 'providers', 'current_role', 'partner_user'));
    }

    function update(Request $request, $id)
    {
        $this->validate(
            $request,
            [
                'name' => 'required|string',
                'email' => 'required|string|max:255',
                'phone' => ['required'],
                'role_id' => 'required',
                'provider_id' => 'required_if:role_id,==,2,4',
            ],
            [
                'name.required' => 'Full name is required',
                'email.required' => 'Email is required',
                'phone.required' => 'Phone is required',
                'role_id.required' => 'Role required',
                'provider_id.required_if' => 'Service provider required',
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

        //create object to store data
        $user = User::find($id);
        $user->name = $request->input('name');
        $user->email = $request->input('email');
        $user->phone = $request->input('phone');

        if ($request->input('password') != null || $request->input('password') != '')
            $user->password = Hash::make($request->input('password'));

        $user->save();

        //remove role from role_user
        RoleUser::where('user_id', $id)->delete();

        //insert or create role
        $user->roles()->attach($request->input('role_id'));

        //service provider
        if ($request->input('role_id') == 2 || $request->input('role_id') == 4) {
            //partner
            $partner = Partner::where(['id' => $request->input('provider_id')])->first();

            if ($partner) {
                //partner user
                $partner_user = PartnerUser::firstOrNew([
                    'user_id' => $user->id,
                ]);
                $partner_user->partner_id = $partner->id;
                $partner_user->partner_uuid = $partner->uuid;
                $partner_user->authorize = 1;
                $partner_user->save();
            }
        }

        //redirect
        return Redirect::route('users.index')->with('success', 'User updated');
    }

    //change status
    function change_status($id)
    {
        $title = 'Change status';
        //has_allowed('roles', 'edit');

        //user 
        $user = User::findOrFail($id);

        //render view
        return view('users.change_status', compact('user'));
    }

    //store status
    function store_status(Request $request, $id)
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
    function delete($id)
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
}
