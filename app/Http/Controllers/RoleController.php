<?php

namespace App\Http\Controllers;

use App\Models\Perm;
use App\Models\Role;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Redirect;

class RoleController extends Controller
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
        $title = 'Roles';
        //has_allowed('roles', 'lists');

        //roles
        $roles = Role::all();

        return view('roles.lists', compact('roles', 'title'));
    }

    //register user
    function create()
    {
        $title = 'Create New';
        //has_allowed('roles', 'create');

        return view('roles.create', compact('title'));
    }

    //store users details
    function store(Request $request)
    {
        //validation
        $this->validate(
            $request,
            [
                'name' => 'required|unique:roles',
                'description' => 'required|string'
            ],
            [
                'name.required' => 'Name required',
                'description.required' => 'Description required'
            ]
        );

        //create role to store data
        $role = new Role();
        $role->name = $request->input('name');
        $role->description = $request->input('description');
        $role->save();

        //redirect
        return Redirect::route('roles.index')->with('success', 'Role created');
    }

    //edit
    function edit($id)
    {
        $title = 'Edit Role';
        //has_allowed('roles', 'edit');

        //role
        $role = Role::findOrFail($id);

        return view('backend.roles.edit', compact('role', 'title'));
    }

    //update user
    public function update(Request $request, $id)
    {
        //validation
        $this->validate(
            $request,
            [
                'name' => 'required|string',
                'description' => 'required|string'
            ],
            [
                'name.required' => 'Name required',
                'description.required' => 'Description required'
            ]
        );

        //create role to store data
        $role = Role::find($id);
        $role->name = $request->input('name');
        $role->description = $request->input('description');
        $role->save(); //save

        //redirect
        return Redirect::route('roles.index')->with('success', 'Role updated');
    }

    //assign perms
    public function assign_perms($id)
    {
        //has_allowed('roles', 'assign_perms');

        //role
        $role = Role::findOrFail($id);

        //all perms
        $perms = Perm::get_perms_all();

        //assigned perms
        $assigned_perms = [];
        $perm_role = DB::table("perm_roles")->where('role_id', $id)->first();
        ($perm_role) ? $methods = explode(',', $perm_role->methods) : $methods = [];

        foreach ($methods as $v) {
            array_push($assigned_perms, $v);
        }

        //title
        $title = 'Assign Perms: ' . $role->name;

        //render view
        return view('roles.assign_perms', compact('title'));
    }

    //store perms
    function store_perms(Request $request, $id)
    {
        $perms = $request->perms;
        $role_id = $id;

        if ($perms) {
            $classes = [];
            foreach ($perms as $perm) {
                //method
                $method = DB::table("perm_methods")->where('id', $perm)->first();

                if (!in_array($method->class_id, $classes))
                    array_push($classes, $method->class_id);
            }

            //perm role
            $perm_role = DB::table("perm_roles")->where('role_id', $role_id)->first();

            if ($perm_role) {
                $classes = join(',', $classes);
                $methods = join(',', $perms);

                //update
                DB::table('perm_roles')
                    ->where('role_id', $role_id)
                    ->update(['classes' => $classes, 'methods' => $methods]);
            } else {
                DB::table('perm_roles')->insert(
                    ['role_id' => $role_id, 'classes' => join(',', $classes), 'methods' => join(',', $perms)]
                );
            }

            return Redirect::route('roles.assign-perms', $role_id)->with('success', 'Permission updated');
        } else {
            return Redirect::route('roles.assign-perms', $role_id)->with('failed', 'Failed to update perms');
        }
    }
}
