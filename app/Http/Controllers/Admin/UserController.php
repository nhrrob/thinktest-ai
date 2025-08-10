<?php

namespace App\Http\Controllers\Admin;

use App\Http\Controllers\Controller;
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
     * Constructor with user permission checking
     */
    public function __construct()
    {
        parent::__construct('user');
    }

    /**
     * Display a listing of the resource.
     */
    public function index()
    {
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
        try {
            $user = User::create([
                'name' => $request->validated('name'),
                'email' => $request->validated('email'),
                'password' => Hash::make($request->validated('password')),
            ]);

            if ($request->has('roles')) {
                $roles = Role::whereIn('id', $request->validated('roles', []))->get();
                $user->syncRoles($roles);
            }

            return Redirect::route('admin.users.index')
                ->with('success', 'User created successfully.');
        } catch (\Exception $e) {
            return Redirect::route('admin.users.index')
                ->with('error', 'Failed to create user: ' . $e->getMessage());
        }
    }

    /**
     * Display the specified resource.
     */
    public function show(User $user)
    {
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
        try {
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

            return Redirect::route('admin.users.index')
                ->with('success', 'User updated successfully.');
        } catch (\Exception $e) {
            return Redirect::route('admin.users.index')
                ->with('error', 'Failed to update user: ' . $e->getMessage());
        }
    }

    /**
     * Remove the specified resource from storage.
     */
    public function destroy(User $user)
    {
        try {
            // Prevent deletion of super-admin users
            if ($user->hasRole('super-admin')) {
                return Redirect::route('admin.users.index')
                    ->with('error', 'Cannot delete super-admin users.');
            }

            // Prevent users from deleting themselves
            if ($user->id === Auth::user()->id) {
                return Redirect::route('admin.users.index')
                    ->with('error', 'You cannot delete your own account.');
            }

            $user->delete();

            return Redirect::route('admin.users.index')
                ->with('success', 'User deleted successfully.');
        } catch (\Exception $e) {
            return Redirect::route('admin.users.index')
                ->with('error', 'Failed to delete user: ' . $e->getMessage());
        }
    }
}
