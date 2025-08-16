<?php

namespace Tests\Support;

use App\Services\AI\AIProviderService;
use App\Services\GitHub\GitHubService;
use App\Services\GitHub\GitHubValidationService;
use App\Services\StripePaymentService;
use App\Services\TestGeneration\TestGenerationService;
use Illuminate\Support\Facades\Http;

trait MocksExternalApis
{
    /**
     * Set up API mocks before each test
     */
    protected function setUpApiMocks(): void
    {
        // Reset any existing mocks
        Http::fake();
        
        // Set up default mocks for all external APIs
        $this->mockGitHubApiResponses();
        $this->mockAiProviderResponses();
        $this->mockStripeApiResponses();
    }

    /**
     * Mock GitHub API responses
     */
    protected function mockGitHubApiResponses(): void
    {
        ApiMockService::mockGitHubApi();
    }

    /**
     * Mock AI provider responses (OpenAI, Anthropic)
     */
    protected function mockAiProviderResponses(): void
    {
        ApiMockService::mockOpenAiApi();
        ApiMockService::mockAnthropicApi();
    }

    /**
     * Mock Stripe API responses
     */
    protected function mockStripeApiResponses(): void
    {
        ApiMockService::mockStripeApi();
    }

    /**
     * Mock GitHub service for testing without API calls
     */
    protected function mockGitHubService(): void
    {
        $this->mock(GitHubService::class, function ($mock) {
            $mock->shouldReceive('validateRepository')
                ->andReturn([
                    'success' => true,
                    'repository' => [
                        'owner' => 'owner',
                        'repo' => 'test-repo',
                        'full_name' => 'owner/test-repo',
                        'name' => 'test-repo',
                        'description' => 'Test repository',
                        'private' => false,
                        'default_branch' => 'main',
                        'size' => 1024,
                        'language' => 'PHP',
                    ],
                ]);

            $mock->shouldReceive('getBranches')
                ->andReturn([
                    'success' => true,
                    'branches' => [
                        ['name' => 'main', 'protected' => false],
                        ['name' => 'develop', 'protected' => false],
                    ],
                ]);

            $mock->shouldReceive('getRepositoryTree')
                ->andReturn([
                    'success' => true,
                    'tree' => [
                        [
                            'path' => 'index.php',
                            'type' => 'file',
                            'size' => 256,
                        ],
                        [
                            'path' => 'src/class.php',
                            'type' => 'file',
                            'size' => 512,
                        ],
                    ],
                ]);

            $mock->shouldReceive('getFileContent')
                ->andReturn([
                    'success' => true,
                    'file' => [
                        'name' => 'index.php',
                        'path' => 'index.php',
                        'content' => '<?php echo "Hello World"; ?>',
                        'size' => 256,
                    ],
                ]);
        });
    }

    /**
     * Mock GitHub validation service
     */
    protected function mockGitHubValidationService(): void
    {
        $this->mock(GitHubValidationService::class, function ($mock) {
            $mock->shouldReceive('validateRateLimit')
                ->andReturn(true);

            $mock->shouldReceive('validateRepositoryComponents')
                ->andReturn(true);

            $mock->shouldReceive('validateRepositoryAccess')
                ->andReturn(['success' => true]);

            $mock->shouldReceive('validateBranchName')
                ->andReturnUsing(function ($branchName) {
                    // Simulate the actual validation logic for invalid branch names
                    if (str_contains($branchName, '..')) {
                        throw new \InvalidArgumentException("Invalid branch name: {$branchName}");
                    }
                    return true;
                });

            $mock->shouldReceive('validateOwnerAndRepo')
                ->andReturn(true);

            $mock->shouldReceive('logSecurityEvent')
                ->andReturn(null);
        });
    }

    /**
     * Mock AI provider service for testing
     */
    protected function mockAiProviderService(): void
    {
        $this->mock(AIProviderService::class, function ($mock) {
            $mock->shouldReceive('generateWordPressTests')
                ->andReturn([
                    'success' => true,
                    'provider' => 'mock',
                    'model' => 'mock-model',
                    'generated_tests' => '<?php

use PHPUnit\Framework\TestCase;

class TestPluginTest extends TestCase
{
    public function test_function_returns_expected_value()
    {
        $result = test_function();
        $this->assertEquals("test", $result);
    }
}',
                    'usage' => [
                        'prompt_tokens' => 150,
                        'completion_tokens' => 200,
                        'total_tokens' => 350,
                    ],
                ]);

            $mock->shouldReceive('generateTests')
                ->andReturn([
                    'success' => true,
                    'provider' => 'mock',
                    'model' => 'mock-model',
                    'tests' => [
                        'main_test_file' => [
                            'filename' => 'TestPluginTest.php',
                            'content' => '<?php

use PHPUnit\Framework\TestCase;

class TestPluginTest extends TestCase
{
    public function test_function_returns_expected_value()
    {
        $result = test_function();
        $this->assertEquals("test", $result);
    }
}',
                        ],
                    ],
                    'usage' => [
                        'prompt_tokens' => 150,
                        'completion_tokens' => 200,
                        'total_tokens' => 350,
                    ],
                ]);
        });
    }

    /**
     * Mock test generation service
     */
    protected function mockTestGenerationService(): void
    {
        $this->mock(TestGenerationService::class, function ($mock) {
            $mock->shouldReceive('generateTestsForSingleFile')
                ->andReturn([
                    'success' => true,
                    'framework' => 'phpunit',
                    'provider' => 'mock',
                    'model' => 'mock-model',
                    'analysis' => [
                        'functions' => ['test_function'],
                        'classes' => [],
                    ],
                    'tests' => [
                        'main_test_file' => [
                            'filename' => 'TestPluginTest.php',
                            'content' => '<?php

use PHPUnit\Framework\TestCase;

class TestPluginTest extends TestCase
{
    public function test_function_returns_expected_value()
    {
        $result = test_function();
        $this->assertEquals("test", $result);
    }
}',
                        ],
                    ],
                    'main_test_file' => '<?php

use PHPUnit\Framework\TestCase;

class TestPluginTest extends TestCase
{
    public function test_function_returns_expected_value()
    {
        $result = test_function();
        $this->assertEquals("test", $result);
    }
}',
                    'file_context' => [
                        'filename' => 'index.php',
                        'file_path' => 'index.php',
                        'repository' => ['full_name' => 'owner/test-repo'],
                    ],
                ]);

            $mock->shouldReceive('generateTestsForRepository')
                ->andReturn([
                    'success' => true,
                    'framework' => 'phpunit',
                    'provider' => 'mock',
                    'model' => 'mock-model',
                    'analysis' => [
                        'total_files' => 2,
                        'php_files' => 2,
                        'functions' => ['test_function'],
                        'classes' => [],
                    ],
                    'tests' => [
                        'IndexTest.php' => [
                            'filename' => 'IndexTest.php',
                            'content' => '<?php // Test content for index.php',
                        ],
                        'ClassTest.php' => [
                            'filename' => 'ClassTest.php',
                            'content' => '<?php // Test content for class.php',
                        ],
                    ],
                ]);
        });
    }

    /**
     * Mock Stripe payment service
     */
    protected function mockStripePaymentService(): void
    {
        $this->mock(StripePaymentService::class, function ($mock) {
            $mock->shouldReceive('createPaymentIntent')
                ->andReturn([
                    'success' => true,
                    'client_secret' => 'pi_test_123_secret_test',
                    'payment_intent_id' => 'pi_test_123',
                    'amount' => 10.00,
                    'currency' => 'usd',
                ]);

            $mock->shouldReceive('handleWebhook')
                ->andReturn([
                    'success' => true,
                    'message' => 'Payment processed successfully',
                ]);

            $mock->shouldReceive('getPaymentIntentStatus')
                ->andReturn([
                    'success' => true,
                    'status' => 'succeeded',
                    'amount' => 10.00,
                ]);

            $mock->shouldReceive('processRefund')
                ->andReturn([
                    'success' => true,
                    'refund_id' => 're_test_123',
                ]);
        });
    }

    /**
     * Mock rate limiting scenarios
     */
    protected function mockRateLimitingScenarios(): void
    {
        // Mock GitHub rate limiting
        ApiMockService::mockGitHubRateLimit();
        
        // Mock AI provider rate limiting
        ApiMockService::mockOpenAiApiError();
        ApiMockService::mockAnthropicApiError();
    }

    /**
     * Mock successful API responses for integration tests
     */
    protected function mockSuccessfulApiResponses(): void
    {
        $this->setUpApiMocks();
        $this->mockGitHubService();
        $this->mockGitHubValidationService();
        $this->mockAiProviderService();
        $this->mockTestGenerationService();
        $this->mockStripePaymentService();
    }

    /**
     * Mock API error scenarios for error handling tests
     */
    protected function mockApiErrorScenarios(): void
    {
        $this->mockRateLimitingScenarios();
    }

    /**
     * Clean up mocks after test
     */
    protected function tearDownApiMocks(): void
    {
        Http::fake();
    }
}
