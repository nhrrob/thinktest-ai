<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\RoleRequest;
use Illuminate\Support\Facades\Log;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Spatie\Permission\Models\Role;

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
                ->with('error', 'Failed to create role: '.$e->getMessage());
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
                ->with('error', 'Failed to update role: '.$e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Role $role)
    {
        try {
            Log::info('Attempting to delete role', ['role_id' => $role->id, 'role_name' => $role->name]);

            // Prevent deletion of super-admin role
            if ($role->name === 'super-admin') {
                Log::warning('Attempted to delete super-admin role', ['role_id' => $role->id]);
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'role' => 'Cannot delete super-admin role.',
                ]);
            }

            // Check if role is assigned to any users
            $userCount = $role->users()->count();
            Log::info('Role user count check', ['role_id' => $role->id, 'user_count' => $userCount]);

            if ($userCount > 0) {
                Log::warning('Cannot delete role with assigned users', [
                    'role_id' => $role->id,
                    'role_name' => $role->name,
                    'user_count' => $userCount,
                ]);
                throw \Illuminate\Validation\ValidationException::withMessages([
                    'role' => 'Cannot delete role that is assigned to users.',
                ]);
            }

            $role->delete();
            Log::info('Role deleted successfully', ['role_id' => $role->id, 'role_name' => $role->name]);

            return back()->with('success', 'Role deleted successfully.');
        } catch (\Illuminate\Validation\ValidationException $e) {
            Log::warning('Validation failed for role deletion', [
                'role_id' => $role->id,
                'role_name' => $role->name,
                'errors' => $e->errors(),
            ]);
            throw $e;
        } catch (\Exception $e) {
            Log::error('Failed to delete role', [
                'role_id' => $role->id,
                'role_name' => $role->name,
                'error' => $e->getMessage(),
                'trace' => $e->getTraceAsString(),
            ]);

            throw \Illuminate\Validation\ValidationException::withMessages([
                'role' => 'Failed to delete role: '.$e->getMessage(),
            ]);
        }
    }
}
