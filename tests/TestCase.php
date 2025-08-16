<?php

namespace Tests;

use Illuminate\Foundation\Testing\TestCase as BaseTestCase;
use Tests\Support\TestDataFactory;

abstract class TestCase extends BaseTestCase
{
    /**
     * Set up the test environment
     */
    protected function setUp(): void
    {
        parent::setUp();
    }

    /**
     * Clean up after each test
     */
    protected function tearDown(): void
    {
        // Clean up any test data that might persist
        if (!$this->app->environment('testing')) {
            TestDataFactory::cleanup();
        }

        parent::tearDown();
    }

    /**
     * Seed credit packages for testing
     */
    protected function seedCreditPackages(): void
    {
        // Only seed if no packages exist and we're not in a transaction
        if (!\App\Models\CreditPackage::exists() && !$this->app->runningUnitTests()) {
            $this->seed(\Database\Seeders\CreditPackageSeeder::class);
        }
    }

    /**
     * Create a test user with permissions
     */
    protected function createTestUser(array $permissions = ['generate tests']): \App\Models\User
    {
        return TestDataFactory::createUserWithPermissions($permissions);
    }

    /**
     * Create a test user with credits
     */
    protected function createTestUserWithCredits(float $credits = 10.0): \App\Models\User
    {
        return TestDataFactory::createUserWithCredits($credits);
    }

    /**
     * Create an admin user for testing
     */
    protected function createAdminUser(): \App\Models\User
    {
        return TestDataFactory::createAdminUser();
    }
}
