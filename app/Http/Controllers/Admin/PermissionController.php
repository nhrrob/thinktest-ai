<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
use App\Http\Requests\PermissionRequest;
use Illuminate\Http\Request;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Redirect;

class PermissionController extends Controller
{
    /**
     * Constructor with permission permission checking
     */
    public function __construct()
    {
        parent::__construct('permission');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        $permissions = Permission::orderBy('group_name')
            ->orderBy('name')
            ->paginate(15);

        return Inertia::render('Admin/Permissions/Index', [
            'permissions' => $permissions,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        $groups = Permission::select('group_name')
            ->distinct()
            ->whereNotNull('group_name')
            ->orderBy('group_name')
            ->pluck('group_name');

        return Inertia::render('Admin/Permissions/Create', [
            'groups' => $groups,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(PermissionRequest $request)
    {
        try {
            Permission::create([
                'name' => $request->validated('name'),
                'group_name' => $request->validated('group_name'),
                'guard_name' => 'web',
            ]);

            return Redirect::route('admin.permissions.index')
                ->with('success', 'Permission created successfully.');
        } catch (\Exception $e) {
            return Redirect::route('admin.permissions.index')
                ->with('error', 'Failed to create permission: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(Permission $permission)
    {
        $permission->load('roles');

        return Inertia::render('Admin/Permissions/Show', [
            'permission' => $permission,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(Permission $permission)
    {
        $groups = Permission::select('group_name')
            ->distinct()
            ->whereNotNull('group_name')
            ->orderBy('group_name')
            ->pluck('group_name');

        return Inertia::render('Admin/Permissions/Edit', [
            'permission' => $permission,
            'groups' => $groups,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(PermissionRequest $request, Permission $permission)
    {
        try {
            $permission->update([
                'name' => $request->validated('name'),
                'group_name' => $request->validated('group_name'),
            ]);

            return Redirect::route('admin.permissions.index')
                ->with('success', 'Permission updated successfully.');
        } catch (\Exception $e) {
            return Redirect::route('admin.permissions.index')
                ->with('error', 'Failed to update permission: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Permission $permission)
    {
        try {
            // Check if permission is assigned to any roles
            if ($permission->roles()->count() > 0) {
                return Redirect::route('admin.permissions.index')
                    ->with('error', 'Cannot delete permission that is assigned to roles.');
            }

            $permission->delete();

            return Redirect::route('admin.permissions.index')
                ->with('success', 'Permission deleted successfully.');
        } catch (\Exception $e) {
            return Redirect::route('admin.permissions.index')
                ->with('error', 'Failed to delete permission: ' . $e->getMessage());
        }
    }
}
