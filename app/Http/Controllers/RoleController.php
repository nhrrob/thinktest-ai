<?php

namespace App\Http\Controllers;

use App\Http\Requests\RoleRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Redirect;

class RoleController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Check permission using Spatie Laravel Permission
        if (!Auth::user()->can('view roles')) {
            abort(403, 'Unauthorized action.');
        }

        $roles = Role::with('permissions')->paginate(10);

        return Inertia::render('Admin/Roles/Index', [
            'roles' => $roles,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Check permission using Spatie Laravel Permission
        if (!Auth::user()->can('create roles')) {
            abort(403, 'Unauthorized action.');
        }

        $permissions = Permission::all()->groupBy('group_name');

        return Inertia::render('Admin/Roles/Create', [
            'permissions' => $permissions,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(RoleRequest $request)
    {
        // Check permission using Spatie Laravel Permission
        if (!Auth::user()->can('create roles')) {
            abort(403, 'Unauthorized action.');
        }

        $role = Role::create([
            'name' => $request->validated('name'),
            'guard_name' => 'web',
        ]);

        if ($request->has('permissions')) {
            $permissions = Permission::whereIn('id', $request->validated('permissions', []))->get();
            $role->syncPermissions($permissions);
        }

        return Redirect::route('roles.index')
            ->with('success', 'Role created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role)
    {
        // Check permission using Spatie Laravel Permission
        if (!Auth::user()->can('view roles')) {
            abort(403, 'Unauthorized action.');
        }

        $role->load('permissions');

        return Inertia::render('Admin/Roles/Show', [
            'role' => $role,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Role $role)
    {
        // Check permission using Spatie Laravel Permission
        if (!Auth::user()->can('edit roles')) {
            abort(403, 'Unauthorized action.');
        }

        $role->load('permissions');
        $permissions = Permission::all()->groupBy('group_name');

        return Inertia::render('Admin/Roles/Edit', [
            'role' => $role,
            'permissions' => $permissions,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(RoleRequest $request, Role $role)
    {
        // Check permission using Spatie Laravel Permission
        if (!Auth::user()->can('edit roles')) {
            abort(403, 'Unauthorized action.');
        }

        $role->update([
            'name' => $request->validated('name'),
        ]);

        if ($request->has('permissions')) {
            $permissions = Permission::whereIn('id', $request->validated('permissions', []))->get();
            $role->syncPermissions($permissions);
        } else {
            $role->syncPermissions([]);
        }

        return Redirect::route('roles.index')
            ->with('success', 'Role updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        // Check permission using Spatie Laravel Permission
        if (!Auth::user()->can('delete roles')) {
            abort(403, 'Unauthorized action.');
        }

        // Prevent deletion of super-admin role
        if ($role->name === 'super-admin') {
            return Redirect::route('roles.index')
                ->with('error', 'Cannot delete super-admin role.');
        }

        // Check if role is assigned to any users
        if ($role->users()->count() > 0) {
            return Redirect::route('roles.index')
                ->with('error', 'Cannot delete role that is assigned to users.');
        }

        $role->delete();

        return Redirect::route('roles.index')
            ->with('success', 'Role deleted successfully.');
    }
}
