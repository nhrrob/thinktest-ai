<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Foundation\Testing\WithFaker;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;
use Tests\TestCase;

class UserControllerTest extends TestCase
{
    use RefreshDatabase, WithFaker;

    protected function setUp(): void
    {
        parent::setUp();
        
        // Create permissions
        Permission::create(['name' => 'view users', 'guard_name' => 'web']);
        Permission::create(['name' => 'create users', 'guard_name' => 'web']);
        Permission::create(['name' => 'edit users', 'guard_name' => 'web']);
        Permission::create(['name' => 'delete users', 'guard_name' => 'web']);
        Permission::create(['name' => 'access admin panel', 'guard_name' => 'web']);

        // Create roles
        $adminRole = Role::create(['name' => 'admin', 'guard_name' => 'web']);
        $userRole = Role::create(['name' => 'user', 'guard_name' => 'web']);

        // Assign permissions to admin role
        $adminRole->givePermissionTo(['view users', 'create users', 'edit users', 'delete users', 'access admin panel']);
    }

    public function test_index_displays_users_for_authorized_user()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $users = User::factory()->count(3)->create();
        
        $response = $this->actingAs($admin)->get(route('admin.users.index'));
        
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Admin/Users/Index')
                ->has('users.data', 4) // 3 created + 1 admin
        );
    }

    public function test_index_denies_access_for_unauthorized_user()
    {
        $user = User::factory()->create();
        $user->assignRole('user');
        
        $response = $this->actingAs($user)->get(route('admin.users.index'));

        $response->assertStatus(403);
    }

    public function test_create_displays_form_for_authorized_user()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->get(route('admin.users.create'));
        
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Admin/Users/Create')
                ->has('roles')
        );
    }

    public function test_store_creates_user_with_valid_data()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $role = Role::where('name', 'user')->first();
        
        $userData = [
            'name' => 'Test User',
            'email' => 'test@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
            'roles' => [$role->id],
        ];
        
        $response = $this->actingAs($admin)->post(route('admin.users.store'), $userData);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success', 'User created successfully.');
        
        $this->assertDatabaseHas('users', [
            'name' => 'Test User',
            'email' => 'test@example.com',
        ]);
        
        $user = User::where('email', 'test@example.com')->first();
        $this->assertTrue($user->hasRole('user'));
    }

    public function test_store_validates_required_fields()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $response = $this->actingAs($admin)->post(route('admin.users.store'), []);

        $response->assertSessionHasErrors(['name', 'email', 'password']);
    }

    public function test_store_validates_unique_email()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $existingUser = User::factory()->create(['email' => 'existing@example.com']);

        $userData = [
            'name' => 'Test User',
            'email' => 'existing@example.com',
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ];

        $response = $this->actingAs($admin)->post(route('admin.users.store'), $userData);
        
        $response->assertSessionHasErrors(['email']);
    }

    public function test_show_displays_user_details()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $user = User::factory()->create();
        $user->assignRole('user');
        
        $response = $this->actingAs($admin)->get(route('admin.users.show', $user));

        $response->assertStatus(200);
        $response->assertInertia(fn ($page) =>
            $page->component('Admin/Users/Show')
                ->where('user.id', $user->id)
                ->where('user.name', $user->name)
        );
    }

    public function test_edit_displays_form_with_user_data()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $user = User::factory()->create();
        $user->assignRole('user');

        $response = $this->actingAs($admin)->get(route('admin.users.edit', $user));
        
        $response->assertStatus(200);
        $response->assertInertia(fn ($page) => 
            $page->component('Admin/Users/Edit')
                ->where('user.id', $user->id)
                ->has('roles')
        );
    }

    public function test_update_modifies_user_with_valid_data()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $user = User::factory()->create(['name' => 'Old Name']);
        $user->assignRole('user');
        
        $updateData = [
            'name' => 'New Name',
            'email' => $user->email,
            'roles' => [$user->roles->first()->id],
        ];
        
        $response = $this->actingAs($admin)->put(route('admin.users.update', $user), $updateData);

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success', 'User updated successfully.');
        
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'New Name',
        ]);
    }

    public function test_update_changes_password_when_provided()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $user = User::factory()->create();
        $originalPassword = $user->password;
        
        $updateData = [
            'name' => $user->name,
            'email' => $user->email,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
            'roles' => [],
        ];
        
        $response = $this->actingAs($admin)->put(route('admin.users.update', $user), $updateData);

        $response->assertRedirect(route('admin.users.index'));

        $user->refresh();
        $this->assertNotEquals($originalPassword, $user->password);
        $this->assertTrue(Hash::check('newpassword123', $user->password));
    }

    public function test_destroy_deletes_user()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $user = User::factory()->create();

        $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $user));

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('success', 'User deleted successfully.');
        
        $this->assertDatabaseMissing('users', ['id' => $user->id]);
    }

    public function test_destroy_prevents_deleting_super_admin()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');
        
        $superAdmin = User::factory()->create();
        $superAdminRole = Role::create(['name' => 'super-admin', 'guard_name' => 'web']);
        $superAdmin->assignRole('super-admin');
        
        $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $superAdmin));

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('error', 'Cannot delete super-admin users.');

        $this->assertDatabaseHas('users', ['id' => $superAdmin->id]);
    }

    public function test_destroy_prevents_self_deletion()
    {
        $admin = User::factory()->create();
        $admin->assignRole('admin');

        $response = $this->actingAs($admin)->delete(route('admin.users.destroy', $admin));

        $response->assertRedirect(route('admin.users.index'));
        $response->assertSessionHas('error', 'You cannot delete your own account.');
        
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }
}
