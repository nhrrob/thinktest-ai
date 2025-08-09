<?php

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->artisan('migrate:fresh');

    // Create permissions
    Permission::create(['name' => 'manage roles', 'guard_name' => 'web']);
    Permission::create(['name' => 'manage permissions', 'guard_name' => 'web']);

    // Create admin role with permissions
    $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'web']);
    $adminRole->givePermissionTo(['manage roles', 'manage permissions']);
});

test('admin can view roles index', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get('/roles');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('Admin/Roles/Index'));
});

test('admin can create role', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get('/roles/create');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('Admin/Roles/Create'));
});

test('admin can store role', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $permission = Permission::create(['name' => 'test permission', 'guard_name' => 'web']);

    $response = $this->actingAs($admin)->post('/roles', [
        'name' => 'Test Role',
        'permissions' => [$permission->id],
    ]);

    $response->assertRedirect('/roles');
    expect(Role::where('name', 'Test Role')->exists())->toBeTrue();
});

test('unauthorized user cannot access roles', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/roles');

    $response->assertStatus(403);
});

test('guest cannot access roles', function () {
    $response = $this->get('/roles');

    $response->assertRedirect('/login');
});
