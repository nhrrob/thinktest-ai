<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Spatie\Permission\Models\Role;
use Spatie\Permission\Models\Permission;

class RolePermissionSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Reset cached roles and permissions
        app()[\Spatie\Permission\PermissionRegistrar::class]->forgetCachedPermissions();

        // Create Roles
        $roleSuperAdmin = Role::create(['name' => 'super-admin']);
        $roleAdmin = Role::create(['name' => 'admin']);
        $roleUser = Role::create(['name' => 'user']);
        $roleDemoUser = Role::create(['name' => 'demo']);

        // Create Permissions and Assign Role
        $permissions = $this->getPermissions();

        for ($i = 0; $i < count($permissions); $i++) {
            $permissionGroup = $permissions[$i]['group_name'];

            for ($j = 0; $j < count($permissions[$i]['permissions']); $j++) {
                $permission = Permission::create([
                    'name' => $permissions[$i]['permissions'][$j],
                    'group_name' => $permissionGroup
                ]);

                // Super Admin: Role 1
                // Using Gate::before() => Super admin has access to all features by default : AppServiceProvider@boot

                // Admin: Role 2
                $roleAdmin->givePermissionTo($permission);

                // User: Role 3
                if (
                    $permissionGroup == 'dashboard' ||
                    $permissionGroup == 'ai-test-generation' ||
                    $permissionGroup == 'github-integration' ||
                    $permissionGroup == 'user-profile'
                ) {
                    $roleUser->givePermissionTo($permission);
                }

                // Demo: Role 4 - Limited access
                if (
                    $permissionGroup == 'dashboard' ||
                    $permissionGroup == 'demo-features' ||
                    $permissionGroup == 'user-profile'
                ) {
                    $roleDemoUser->givePermissionTo($permission);
                }
            }
        }

        $this->command->info('Roles and permissions created successfully!');
        $this->command->info('Super Admin role: Full access via Gate::before() (see AppServiceProvider)');
        $this->command->info('Admin role: Full access to all features');
        $this->command->info('User role: Standard access with test generation and GitHub integration');
        $this->command->info('Demo role: Limited access for demonstration purposes');
    }

    public function permissionItem($groupName, $permissions = null)
    {
        if ($permissions === null) {
            $permissions = [
                "$groupName list",
                "$groupName create",
                "$groupName view",
                "$groupName edit",
                "$groupName delete",
            ];
        }

        $permissionItem = [
            'group_name' => "$groupName",
            'permissions' => $permissions
        ];

        return $permissionItem;
    }

    // Generate Permission Array
    public function getPermissions()
    {
        $permissions = [];

        // Dashboard permissions (view only)
        $permissions[] = $this->permissionItem('dashboard', [
            'access dashboard',
            'view dashboard analytics'
        ]);

        // AI Test Generation permissions
        $permissions[] = $this->permissionItem('ai-test-generation', [
            'generate tests',
            'upload files',
            'download test results'
        ]);

        // GitHub Integration permissions
        $permissions[] = $this->permissionItem('github-integration', [
            'connect github',
            'create pull requests',
            'manage repositories'
        ]);

        // Admin permissions
        $permissions[] = $this->permissionItem('admin', [
            'view system health',
            'manage feature flags',
            'access admin panel'
        ]);

        // User Profile permissions
        $permissions[] = $this->permissionItem('user-profile', [
            'edit profile',
            'change password',
            'view conversation history'
        ]);

        // Demo Features permissions (limited access)
        $permissions[] = $this->permissionItem('demo-features', [
            'demo access',
            'limited test generation'
        ]);

        // Granular CRUD permissions for admin resources
        $permissions[] = $this->permissionItem('user', [
            'view users',
            'create users',
            'edit users',
            'delete users'
        ]);

        $permissions[] = $this->permissionItem('role', [
            'view roles',
            'create roles',
            'edit roles',
            'delete roles'
        ]);

        $permissions[] = $this->permissionItem('permission', [
            'view permissions',
            'create permissions',
            'edit permissions',
            'delete permissions'
        ]);

        return $permissions;
    }
}
