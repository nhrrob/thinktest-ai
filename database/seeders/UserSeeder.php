<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\Hash;
use Spatie\Permission\Models\Role;

class UserSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        // Ensure roles exist (run RolePermissionSeeder first if needed)
        if (Role::count() === 0) {
            $this->call(RolePermissionSeeder::class);
        }

        // Create demo users with specific roles
        $users = [
            [
                'name' => 'Super Admin',
                'email' => 'superadmin@example.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'spatie_role' => 'super-admin',
            ],
            [
                'name' => 'Admin User',
                'email' => 'admin@example.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'spatie_role' => 'admin',
            ],
            [
                'name' => 'Demo User',
                'email' => 'user@example.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'spatie_role' => 'user',
            ],
            [
                'name' => 'Guest Demo',
                'email' => 'demo@example.com',
                'password' => Hash::make('password'),
                'email_verified_at' => now(),
                'spatie_role' => 'demo',
            ],
        ];

        foreach ($users as $userData) {
            $roleName = $userData['spatie_role'];
            unset($userData['spatie_role']); // Remove from user data

            // Create or update user
            $user = User::updateOrCreate(
                ['email' => $userData['email']], // Find by email
                $userData // Update or create with this data
            );

            // Assign role using Spatie Permission
            if (!$user->hasRole($roleName)) {
                $user->assignRole($roleName);
                $this->command->info("Assigned {$roleName} role to {$user->email}");
            }
        }

        $this->command->info('Demo users created successfully!');
        $this->command->info('Super Admin: superadmin@example.com (super-admin role)');
        $this->command->info('Admin User: admin@example.com (admin role)');
        $this->command->info('Regular User: user@example.com (user role)');
        $this->command->info('Demo User: demo@example.com (demo role)');
        $this->command->info('Password for all users: password');
        $this->command->info('');
        $this->command->info('Role capabilities:');
        $this->command->info('- Super Admin: Full access to all features via Gate::before() (see AppServiceProvider)');
        $this->command->info('- Admin: Full access to all features including admin panel');
        $this->command->info('- User: Standard access with test generation and GitHub integration');
        $this->command->info('- Demo: Limited access for demonstration purposes');
    }
}
