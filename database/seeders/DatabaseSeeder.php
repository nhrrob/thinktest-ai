<?php

namespace Database\Seeders;

// use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        // Seed demo users for development and testing
        $this->call([
            RolePermissionSeeder::class,
            UserSeeder::class,
        ]);

        // Uncomment to create additional random users for testing
        // User::factory(10)->create();
    }
}
