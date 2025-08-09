<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class ConsolidatedRequestsTest extends TestCase
{
    use RefreshDatabase;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create permissions
        Permission::create(['name' => 'manage roles', 'guard_name' => 'web']);
        Permission::create(['name' => 'manage permissions', 'guard_name' => 'web']);
        Permission::create(['name' => 'manage users', 'guard_name' => 'web']);
        
        // Create admin role
        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $adminRole->givePermissionTo(['manage roles', 'manage permissions', 'manage users']);
    }

    public function test_role_request_validates_unique_name_on_create()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        Role::create(['name' => 'existing-role', 'guard_name' => 'web']);
        
        $response = $this->actingAs($admin)->post(route('roles.store'), [
            'name' => 'existing-role',
        ]);
        
        $response->assertSessionHasErrors(['name']);
    }

    public function test_role_request_allows_same_name_on_update()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $role = Role::create(['name' => 'test-role', 'guard_name' => 'web']);
        
        $response = $this->actingAs($admin)->put(route('roles.update', $role), [
            'name' => 'test-role', // Same name should be allowed
        ]);
        
        $response->assertRedirect(route('roles.index'));
        $response->assertSessionDoesntHaveErrors(['name']);
    }

    public function test_permission_request_validates_unique_name_on_create()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        Permission::create(['name' => 'existing-permission', 'guard_name' => 'web']);
        
        $response = $this->actingAs($admin)->post(route('permissions.store'), [
            'name' => 'existing-permission',
        ]);
        
        $response->assertSessionHasErrors(['name']);
    }

    public function test_permission_request_allows_same_name_on_update()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $permission = Permission::create(['name' => 'test-permission', 'guard_name' => 'web']);
        
        $response = $this->actingAs($admin)->put(route('permissions.update', $permission), [
            'name' => 'test-permission', // Same name should be allowed
        ]);
        
        $response->assertRedirect(route('permissions.index'));
        $response->assertSessionDoesntHaveErrors(['name']);
    }

    public function test_user_request_validates_unique_email_on_create()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        User::factory()->create(['email' => 'existing@example.com']);
        
        $response = $this->actingAs($admin)->post(route('users.store'), [
            'name' => 'Test User',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);
        
        $response->assertSessionHasErrors(['email']);
    }

    public function test_user_request_allows_same_email_on_update()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $user = User::factory()->create(['email' => 'test@example.com']);
        
        $response = $this->actingAs($admin)->put(route('users.update', $user), [
            'name' => 'Updated Name',
            'email' => 'test@example.com', // Same email should be allowed
        ]);
        
        $response->assertRedirect(route('users.index'));
        $response->assertSessionDoesntHaveErrors(['email']);
    }
}
