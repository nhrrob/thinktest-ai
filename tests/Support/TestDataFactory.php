<?php

namespace Tests\Support;

use App\Models\User;
use App\Models\CreditPackage;
use App\Models\PaymentIntent;
use App\Models\GitHubRepository;
use App\Models\GitHubFileTestGeneration;
use Illuminate\Support\Facades\Hash;

class TestDataFactory
{
    /**
     * Create a test user with specific permissions
     */
    public static function createUserWithPermissions(array $permissions = ['generate tests']): User
    {
        $user = User::factory()->create([
            'email_verified_at' => now(),
            'password' => Hash::make('password'),
        ]);

        // Create permissions if they don't exist
        foreach ($permissions as $permission) {
            if (!\Spatie\Permission\Models\Permission::where('name', $permission)->exists()) {
                \Spatie\Permission\Models\Permission::create([
                    'name' => $permission,
                    'group_name' => 'ai-test-generation',
                ]);
            }
        }

        $user->givePermissionTo($permissions);
        return $user;
    }

    /**
     * Create an admin user with all permissions
     */
    public static function createAdminUser(): User
    {
        $permissions = [
            'generate tests',
            'manage-payments',
            'view-admin-panel',
            'manage-users',
            'manage-roles',
            'manage-permissions',
        ];

        return self::createUserWithPermissions($permissions);
    }

    /**
     * Create a user with credits
     */
    public static function createUserWithCredits(float $credits = 10.0): User
    {
        $user = self::createUserWithPermissions();
        
        // Add credits to user
        $creditService = app(\App\Services\CreditService::class);
        $creditService->addCredits($user->id, $credits, 'Test setup');
        
        return $user;
    }

    /**
     * Create test credit packages
     */
    public static function createCreditPackages(): array
    {
        $packages = [
            [
                'name' => 'Starter Pack',
                'slug' => 'starter-pack',
                'description' => 'Perfect for small projects',
                'price' => 9.99,
                'total_credits' => 10.0,
                'is_active' => true,
                'sort_order' => 1,
            ],
            [
                'name' => 'Professional Pack',
                'slug' => 'professional-pack',
                'description' => 'Great for professional developers',
                'price' => 29.99,
                'total_credits' => 35.0,
                'is_active' => true,
                'sort_order' => 2,
            ],
            [
                'name' => 'Enterprise Pack',
                'slug' => 'enterprise-pack',
                'description' => 'For large teams and projects',
                'price' => 99.99,
                'total_credits' => 150.0,
                'is_active' => true,
                'sort_order' => 3,
            ],
        ];

        $createdPackages = [];
        foreach ($packages as $packageData) {
            $createdPackages[] = CreditPackage::create($packageData);
        }

        return $createdPackages;
    }

    /**
     * Create a test payment intent
     */
    public static function createPaymentIntent(User $user, CreditPackage $package, string $status = 'pending'): PaymentIntent
    {
        return PaymentIntent::create([
            'user_id' => $user->id,
            'credit_package_id' => $package->id,
            'stripe_payment_intent_id' => 'pi_test_' . uniqid(),
            'status' => $status,
            'amount' => $package->price,
            'credits_to_add' => $package->total_credits,
            'completed_at' => $status === 'succeeded' ? now() : null,
        ]);
    }

    /**
     * Create a test GitHub repository
     */
    public static function createGitHubRepository(User $user, array $attributes = []): GitHubRepository
    {
        $defaults = [
            'user_id' => $user->id,
            'owner' => 'test-owner',
            'repo' => 'test-repo',
            'full_name' => 'test-owner/test-repo',
            'branch' => 'main',
            'processing_status' => 'pending',
            'repository_data' => [
                'name' => 'test-repo',
                'description' => 'Test repository for ThinkTest AI',
                'private' => false,
                'default_branch' => 'main',
                'size' => 1024,
                'language' => 'PHP',
            ],
        ];

        return GitHubRepository::create(array_merge($defaults, $attributes));
    }

    /**
     * Create a test file generation record
     */
    public static function createFileTestGeneration(User $user, array $attributes = []): GitHubFileTestGeneration
    {
        $defaults = [
            'user_id' => $user->id,
            'owner' => 'test-owner',
            'repo' => 'test-repo',
            'file_path' => 'src/TestClass.php',
            'branch' => 'main',
            'provider' => 'openai-gpt5',
            'framework' => 'phpunit',
            'status' => 'completed',
            'file_content' => '<?php class TestClass { public function test() { return "test"; } }',
            'generated_tests' => '<?php use PHPUnit\Framework\TestCase; class TestClassTest extends TestCase { public function test_test() { $this->assertEquals("test", (new TestClass())->test()); } }',
            'analysis_data' => [
                'functions' => ['test'],
                'classes' => ['TestClass'],
                'complexity' => 'low',
            ],
            'usage_data' => [
                'prompt_tokens' => 150,
                'completion_tokens' => 200,
                'total_tokens' => 350,
            ],
        ];

        return GitHubFileTestGeneration::create(array_merge($defaults, $attributes));
    }

    /**
     * Create sample file content for testing
     */
    public static function createSamplePhpFile(string $type = 'class'): string
    {
        switch ($type) {
            case 'class':
                return '<?php

namespace App\Services;

class SampleService
{
    private $data;

    public function __construct(array $data = [])
    {
        $this->data = $data;
    }

    public function process(string $input): string
    {
        if (empty($input)) {
            throw new \InvalidArgumentException("Input cannot be empty");
        }

        return strtoupper($input);
    }

    public function getData(): array
    {
        return $this->data;
    }

    public function setData(array $data): void
    {
        $this->data = $data;
    }
}';

            case 'function':
                return '<?php

function calculate_total(array $items): float
{
    $total = 0.0;
    
    foreach ($items as $item) {
        if (!isset($item["price"]) || !is_numeric($item["price"])) {
            throw new \InvalidArgumentException("Invalid item price");
        }
        
        $total += (float) $item["price"];
    }
    
    return $total;
}

function format_currency(float $amount, string $currency = "USD"): string
{
    return sprintf("%.2f %s", $amount, $currency);
}';

            case 'wordpress':
                return '<?php
/**
 * Plugin Name: Sample WordPress Plugin
 * Description: A sample plugin for testing
 * Version: 1.0.0
 */

if (!defined("ABSPATH")) {
    exit;
}

class SampleWordPressPlugin
{
    public function __construct()
    {
        add_action("init", [$this, "init"]);
        add_filter("the_content", [$this, "filter_content"]);
    }

    public function init(): void
    {
        // Plugin initialization
    }

    public function filter_content(string $content): string
    {
        return $content . "\n<!-- Processed by Sample Plugin -->";
    }

    public function get_plugin_data(): array
    {
        return [
            "name" => "Sample WordPress Plugin",
            "version" => "1.0.0",
            "active" => true,
        ];
    }
}

new SampleWordPressPlugin();';

            default:
                return '<?php echo "Hello World";';
        }
    }

    /**
     * Create test repository tree structure
     */
    public static function createRepositoryTree(): array
    {
        return [
            [
                'path' => 'index.php',
                'type' => 'file',
                'size' => 256,
                'content' => self::createSamplePhpFile('function'),
            ],
            [
                'path' => 'src/SampleService.php',
                'type' => 'file',
                'size' => 512,
                'content' => self::createSamplePhpFile('class'),
            ],
            [
                'path' => 'plugins/sample-plugin.php',
                'type' => 'file',
                'size' => 1024,
                'content' => self::createSamplePhpFile('wordpress'),
            ],
            [
                'path' => 'README.md',
                'type' => 'file',
                'size' => 128,
                'content' => '# Test Repository\n\nThis is a test repository for ThinkTest AI.',
            ],
            [
                'path' => 'composer.json',
                'type' => 'file',
                'size' => 256,
                'content' => json_encode([
                    'name' => 'test/repository',
                    'description' => 'Test repository',
                    'require' => [
                        'php' => '^8.0',
                    ],
                    'require-dev' => [
                        'phpunit/phpunit' => '^9.0',
                    ],
                ], JSON_PRETTY_PRINT),
            ],
        ];
    }

    /**
     * Clean up test data
     */
    public static function cleanup(): void
    {
        // Clean up test users (except factory-created ones in transactions)
        User::where('email', 'like', '%@example.%')->delete();
        
        // Clean up test repositories
        GitHubRepository::where('owner', 'test-owner')->delete();
        
        // Clean up test file generations
        GitHubFileTestGeneration::where('owner', 'test-owner')->delete();
        
        // Clean up test payment intents
        PaymentIntent::where('stripe_payment_intent_id', 'like', 'pi_test_%')->delete();
    }
}
