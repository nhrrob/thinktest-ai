<?php

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->artisan('migrate:fresh');

    // Create permissions
    Permission::create(['name' => 'view roles', 'guard_name' => 'web']);
    Permission::create(['name' => 'create roles', 'guard_name' => 'web']);
    Permission::create(['name' => 'edit roles', 'guard_name' => 'web']);
    Permission::create(['name' => 'delete roles', 'guard_name' => 'web']);

    // Create admin role with permissions
    $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'web']);
    $adminRole->givePermissionTo(['view roles', 'create roles', 'edit roles', 'delete roles']);
});

test('admin can view roles index', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get('/admin/roles');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('Admin/Roles/Index'));
});

test('admin can create role', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get('/admin/roles/create');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('Admin/Roles/Create'));
});

test('admin can store role', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $permission = Permission::create(['name' => 'test permission', 'guard_name' => 'web']);

    $response = $this->actingAs($admin)->post('/admin/roles', [
        'name' => 'Test Role',
        'permissions' => [$permission->id],
    ]);

    $response->assertRedirect('/admin/roles');
    expect(Role::where('name', 'Test Role')->exists())->toBeTrue();
});

test('unauthorized user cannot access roles', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/admin/roles');

    $response->assertStatus(403);
});

test('guest cannot access roles', function () {
    $response = $this->get('/admin/roles');

    $response->assertRedirect('/login');
});
