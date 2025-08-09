<?php

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->artisan('migrate:fresh');

    // Create permissions
    Permission::create(['name' => 'view permissions', 'guard_name' => 'web']);
    Permission::create(['name' => 'create permissions', 'guard_name' => 'web']);
    Permission::create(['name' => 'edit permissions', 'guard_name' => 'web']);
    Permission::create(['name' => 'delete permissions', 'guard_name' => 'web']);
    Permission::create(['name' => 'view roles', 'guard_name' => 'web']);
    Permission::create(['name' => 'create roles', 'guard_name' => 'web']);
    Permission::create(['name' => 'edit roles', 'guard_name' => 'web']);
    Permission::create(['name' => 'delete roles', 'guard_name' => 'web']);

    // Create admin role with permissions
    $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'web']);
    $adminRole->givePermissionTo(['view permissions', 'create permissions', 'edit permissions', 'delete permissions', 'view roles', 'create roles', 'edit roles', 'delete roles']);
});

test('admin can view permissions index', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get('/admin/permissions');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('Admin/Permissions/Index'));
});

test('admin can create permission', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get('/admin/permissions/create');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('Admin/Permissions/Create'));
});

test('admin can store permission', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->post('/admin/permissions', [
        'name' => 'test new permission',
        'group_name' => 'testing',
    ]);

    $response->assertRedirect('/admin/permissions');
    expect(Permission::where('name', 'test new permission')->exists())->toBeTrue();
});

test('unauthorized user cannot access permissions', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/admin/permissions');

    $response->assertStatus(403);
});

test('guest cannot access permissions', function () {
    $response = $this->get('/admin/permissions');

    $response->assertRedirect('/login');
});
