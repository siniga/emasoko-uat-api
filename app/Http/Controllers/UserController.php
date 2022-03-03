<?php

namespace App\Http\Controllers;

use App\Models\Role;
use App\Models\User;
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

        //render view
        return view('users.create', compact('roles'));
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

        //insert roles
        foreach ($request->input('role_ids') as $role_id) {
            $user->roles()->attach($role_id);
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
        $roles = Role::orderBy('sequence')->get();

        //current roles
        $current_roles = DB::table('role_user')->where('user_id', $id)->get();

        //render view
        return view('users.edit', compact('user', 'roles', 'current_roles'));
    }

    function update(Request $request, $id)
    {
        $this->validate(
            $request,
            [
                'name' => 'required|string',
                'email' => 'required|string|max:255',
                'phone' => ['required'],
                'role_ids' => 'required'
            ],
            [
                'name.required' => 'Full name is required',
                'email.required' => 'Email is required',
                'phone.required' => 'Phone is required',
                'role_ids.required' => 'Role(s) required'
            ]
        );

        //validate password
        if ($request->input('password') != null || $request->input('password') != '') {
            $this->validate($request, [
                'password' => 'required|string|min:8|regex:/^(?=.*?[A-Z])(?=.*?[a-z])(?=.*?[0-9])(?=.*?[#?!@$%^&*-]).{8,}$/',
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
        DB::table('role_user')->where('user_id', $id)->delete();

        //insert roles
        foreach ($request->input('role_ids') as $role_id) {
            $user->roles()->attach($role_id);
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

        $user = User::findOrFail($id);
        $user->active = $request->input('status');
        $user->save();

        //redirect
        return Redirect::route('users.index')->with('success', 'Status changed');
    }

    //delete
    function delete($id)
    {
        $title = 'Delete User';
        // has_allowed('cars', 'delete');

        $user = User::findOrFail($id);

        if ($del = $user->delete()) {

            //redirect
            return Redirect::route('users.index')->with('success', 'User deleted');
        } else {
            return Redirect::route('users.index')->with('danger', 'Failed to delete user');
        }
    }
}
