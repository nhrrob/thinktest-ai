<?php

namespace Database\Seeders;

use Illuminate\Database\Seeder;
use Illuminate\Support\Facades\DB;

class CreditPackageSeeder extends Seeder
{
    /**
     * Run the database seeds.
     */
    public function run(): void
    {
        $packages = [
            [
                'name' => 'Starter Pack',
                'slug' => 'starter-pack',
                'description' => 'Perfect for trying out ThinkTest AI with your own projects',
                'credits' => 25.00,
                'price' => 9.99,
                'price_per_credit' => 0.40,
                'bonus_credits' => 0,
                'is_popular' => false,
                'is_active' => true,
                'sort_order' => 1,
                'features' => json_encode([
                    '25 AI test generation credits',
                    'Access to all AI providers (GPT-5, Claude 4)',
                    'No expiration',
                    'Email support'
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Professional Pack',
                'slug' => 'professional-pack',
                'description' => 'Great for regular use and small teams',
                'credits' => 100.00,
                'price' => 29.99,
                'price_per_credit' => 0.30,
                'bonus_credits' => 10,
                'is_popular' => true,
                'is_active' => true,
                'sort_order' => 2,
                'features' => json_encode([
                    '100 AI test generation credits',
                    '10 bonus credits included',
                    'Access to all AI providers (GPT-5, Claude 4)',
                    'Priority processing',
                    'No expiration',
                    'Priority email support'
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Enterprise Pack',
                'slug' => 'enterprise-pack',
                'description' => 'Best value for teams and heavy usage',
                'credits' => 500.00,
                'price' => 99.99,
                'price_per_credit' => 0.20,
                'bonus_credits' => 100,
                'is_popular' => false,
                'is_active' => true,
                'sort_order' => 3,
                'features' => json_encode([
                    '500 AI test generation credits',
                    '100 bonus credits included',
                    'Access to all AI providers (GPT-5, Claude 4)',
                    'Priority processing',
                    'No expiration',
                    'Priority email support',
                    'Usage analytics',
                    'Team management features'
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
            [
                'name' => 'Developer Pack',
                'slug' => 'developer-pack',
                'description' => 'Perfect for individual developers',
                'credits' => 50.00,
                'price' => 19.99,
                'price_per_credit' => 0.40,
                'bonus_credits' => 5,
                'is_popular' => false,
                'is_active' => true,
                'sort_order' => 4,
                'features' => json_encode([
                    '50 AI test generation credits',
                    '5 bonus credits included',
                    'Access to all AI providers (GPT-5, Claude 4)',
                    'No expiration',
                    'Email support'
                ]),
                'created_at' => now(),
                'updated_at' => now(),
            ],
        ];

        DB::table('credit_packages')->insert($packages);
    }
}
