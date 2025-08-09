<?php

use App\Models\User;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

beforeEach(function () {
    $this->artisan('migrate:fresh');

    // Create permissions
    Permission::create(['name' => 'manage permissions', 'guard_name' => 'web']);
    Permission::create(['name' => 'manage roles', 'guard_name' => 'web']);

    // Create admin role with permissions
    $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'web']);
    $adminRole->givePermissionTo(['manage permissions', 'manage roles']);
});

test('admin can view permissions index', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get('/permissions');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('Admin/Permissions/Index'));
});

test('admin can create permission', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->get('/permissions/create');

    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page->component('Admin/Permissions/Create'));
});

test('admin can store permission', function () {
    $admin = User::factory()->create();
    $admin->assignRole('admin');

    $response = $this->actingAs($admin)->post('/permissions', [
        'name' => 'test new permission',
        'group_name' => 'testing',
    ]);

    $response->assertRedirect('/permissions');
    expect(Permission::where('name', 'test new permission')->exists())->toBeTrue();
});

test('unauthorized user cannot access permissions', function () {
    $user = User::factory()->create();

    $response = $this->actingAs($user)->get('/permissions');

    $response->assertStatus(403);
});

test('guest cannot access permissions', function () {
    $response = $this->get('/permissions');

    $response->assertRedirect('/login');
});
