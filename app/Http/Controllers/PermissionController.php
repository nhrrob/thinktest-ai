<?php

namespace App\Http\Controllers;

use App\Http\Requests\PermissionRequest;
use Illuminate\Http\Request;
use Illuminate\Support\Facades\Auth;
use Inertia\Inertia;
use Spatie\Permission\Models\Permission;
use Illuminate\Support\Facades\Redirect;

class PermissionController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Check permission using Spatie Laravel Permission
        if (!Auth::user()->can('view permissions')) {
            abort(403, 'Unauthorized action.');
        }

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
        // Check permission using Spatie Laravel Permission
        if (!Auth::user()->can('create permissions')) {
            abort(403, 'Unauthorized action.');
        }

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
        // Check permission using Spatie Laravel Permission
        if (!Auth::user()->can('create permissions')) {
            abort(403, 'Unauthorized action.');
        }

        Permission::create([
            'name' => $request->validated('name'),
            'group_name' => $request->validated('group_name'),
            'guard_name' => 'web',
        ]);

        return Redirect::route('permissions.index')
            ->with('success', 'Permission created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(Permission $permission)
    {
        // Check permission using Spatie Laravel Permission
        if (!Auth::user()->can('view permissions')) {
            abort(403, 'Unauthorized action.');
        }

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
        // Check permission using Spatie Laravel Permission
        if (!Auth::user()->can('edit permissions')) {
            abort(403, 'Unauthorized action.');
        }

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
        // Check permission using Spatie Laravel Permission
        if (!Auth::user()->can('edit permissions')) {
            abort(403, 'Unauthorized action.');
        }

        $permission->update([
            'name' => $request->validated('name'),
            'group_name' => $request->validated('group_name'),
        ]);

        return Redirect::route('permissions.index')
            ->with('success', 'Permission updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(Permission $permission)
    {
        // Check permission using Spatie Laravel Permission
        if (!Auth::user()->can('delete permissions')) {
            abort(403, 'Unauthorized action.');
        }

        // Check if permission is assigned to any roles
        if ($permission->roles()->count() > 0) {
            return Redirect::route('permissions.index')
                ->with('error', 'Cannot delete permission that is assigned to roles.');
        }

        $permission->delete();

        return Redirect::route('permissions.index')
            ->with('success', 'Permission deleted successfully.');
    }
}
