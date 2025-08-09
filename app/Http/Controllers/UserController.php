<?php

namespace App\Http\Controllers;

use App\Http\Requests\UserRequest;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\Hash;
use Illuminate\Support\Facades\Redirect;
use Inertia\Inertia;
use Spatie\Permission\Models\Role;

class UserController extends Controller
{
    /**
     * Display a listing of the resource.
     */
    public function index()
    {
        // Check permission using Spatie Laravel Permission
        if (!Auth::user()->hasPermissionTo('view users')) {
            abort(403, 'Unauthorized action.');
        }

        $users = User::with('roles')->paginate(10);

        return Inertia::render('Admin/Users/Index', [
            'users' => $users,
        ]);
    }

    /**
     * Show the form for creating a new resource.
     */
    public function create()
    {
        // Check permission using Spatie Laravel Permission
        if (!Auth::user()->hasPermissionTo('create users')) {
            abort(403, 'Unauthorized action.');
        }

        $roles = Role::all();

        return Inertia::render('Admin/Users/Create', [
            'roles' => $roles,
        ]);
    }

    /**
     * Store a newly created resource in storage.
     */
    public function store(UserRequest $request)
    {
        // Check permission using Spatie Laravel Permission
        if (!Auth::user()->hasPermissionTo('create users')) {
            abort(403, 'Unauthorized action.');
        }

        $user = User::create([
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
            'password' => Hash::make($request->validated('password')),
        ]);

        if ($request->has('roles')) {
            $roles = Role::whereIn('id', $request->validated('roles', []))->get();
            $user->syncRoles($roles);
        }

        return Redirect::route('users.index')
            ->with('success', 'User created successfully.');
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
        // Check permission using Spatie Laravel Permission
        if (!Auth::user()->hasPermissionTo('view users')) {
            abort(403, 'Unauthorized action.');
        }

        $user->load('roles.permissions');

        return Inertia::render('Admin/Users/Show', [
            'user' => $user,
        ]);
    }

    /**
     * Show the form for editing the specified resource.
     */
    public function edit(User $user)
    {
        // Check permission using Spatie Laravel Permission
        if (!Auth::user()->hasPermissionTo('edit users')) {
            abort(403, 'Unauthorized action.');
        }

        $user->load('roles');
        $roles = Role::all();

        return Inertia::render('Admin/Users/Edit', [
            'user' => $user,
            'roles' => $roles,
        ]);
    }

    /**
     * Update the specified resource in storage.
     */
    public function update(UserRequest $request, User $user)
    {
        // Check permission using Spatie Laravel Permission
        if (!Auth::user()->hasPermissionTo('edit users')) {
            abort(403, 'Unauthorized action.');
        }

        $updateData = [
            'name' => $request->validated('name'),
            'email' => $request->validated('email'),
        ];

        // Only update password if provided
        if ($request->filled('password')) {
            $updateData['password'] = Hash::make($request->validated('password'));
        }

        $user->update($updateData);

        if ($request->has('roles')) {
            $roles = Role::whereIn('id', $request->validated('roles', []))->get();
            $user->syncRoles($roles);
        } else {
            $user->syncRoles([]);
        }

        return Redirect::route('users.index')
            ->with('success', 'User updated successfully.');
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        // Check permission using Spatie Laravel Permission
        if (!Auth::user()->hasPermissionTo('delete users')) {
            abort(403, 'Unauthorized action.');
        }

        // Prevent deletion of super-admin users
        if ($user->hasRole('super-admin')) {
            return Redirect::route('users.index')
                ->with('error', 'Cannot delete super-admin users.');
        }

        // Prevent users from deleting themselves
        if ($user->id === Auth::user()->id) {
            return Redirect::route('users.index')
                ->with('error', 'You cannot delete your own account.');
        }

        $user->delete();

        return Redirect::route('users.index')
            ->with('success', 'User deleted successfully.');
    }
}
