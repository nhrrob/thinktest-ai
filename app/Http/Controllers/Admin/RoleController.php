<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoleRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Redirect;

class RoleController extends Controller
{
    /**
     * Constructor with role permission checking
     */
    public function __construct()
    {
        parent::__construct('role');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
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
        try {
            $role = Role::create([
                'name' => $request->validated('name'),
                'guard_name' => 'web',
            ]);

            if ($request->has('permissions')) {
                $permissions = Permission::whereIn('id', $request->validated('permissions', []))->get();
                $role->syncPermissions($permissions);
            }

            return Redirect::route('admin.roles.index')
                ->with('success', 'Role created successfully.');
        } catch (\Exception $e) {
            return Redirect::route('admin.roles.index')
                ->with('error', 'Failed to create role: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Role $role)
    {
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
        try {
            $role->update([
                'name' => $request->validated('name'),
            ]);

            if ($request->has('permissions')) {
                $permissions = Permission::whereIn('id', $request->validated('permissions', []))->get();
                $role->syncPermissions($permissions);
            } else {
                $role->syncPermissions([]);
            }

            return Redirect::route('admin.roles.index')
                ->with('success', 'Role updated successfully.');
        } catch (\Exception $e) {
            return Redirect::route('admin.roles.index')
                ->with('error', 'Failed to update role: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        try {
            // Prevent deletion of super-admin role
            if ($role->name === 'super-admin') {
                return Redirect::route('admin.roles.index')
                    ->with('error', 'Cannot delete super-admin role.');
            }

            // Check if role is assigned to any users
            if ($role->users()->count() > 0) {
                return Redirect::route('admin.roles.index')
                    ->with('error', 'Cannot delete role that is assigned to users.');
            }

            $role->delete();

            return Redirect::route('admin.roles.index')
                ->with('success', 'Role deleted successfully.');
        } catch (\Exception $e) {
            return Redirect::route('admin.roles.index')
                ->with('error', 'Failed to delete role: ' . $e->getMessage());
        }
    }
}
