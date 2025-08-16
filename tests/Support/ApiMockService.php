<?php

namespace Tests\Support;

use Illuminate\Support\Facades\Http;

class ApiMockService
{
    /**
     * Mock GitHub API responses for testing
     */
    public static function mockGitHubApi(): void
    {
        Http::fake([
            // Repository validation
            'api.github.com/repos/*/*' => Http::response([
                'id' => 123456,
                'name' => 'test-repo',
                'full_name' => 'owner/test-repo',
                'description' => 'Test repository for ThinkTest AI',
                'private' => false,
                'default_branch' => 'main',
                'size' => 1024,
                'language' => 'PHP',
                'languages_url' => 'https://api.github.com/repos/owner/test-repo/languages',
                'clone_url' => 'https://github.com/owner/test-repo.git',
                'html_url' => 'https://github.com/owner/test-repo',
                'updated_at' => '2024-01-01T00:00:00Z',
            ], 200),

            // Repository branches
            'api.github.com/repos/*/*/branches*' => Http::response([
                [
                    'name' => 'main',
                    'commit' => [
                        'sha' => 'abc123',
                        'url' => 'https://api.github.com/repos/owner/test-repo/commits/abc123',
                    ],
                    'protected' => false,
                ],
                [
                    'name' => 'develop',
                    'commit' => [
                        'sha' => 'def456',
                        'url' => 'https://api.github.com/repos/owner/test-repo/commits/def456',
                    ],
                    'protected' => false,
                ],
            ], 200),

            // Repository tree (file listing)
            'api.github.com/repos/*/*/git/trees/*' => Http::response([
                'sha' => 'abc123',
                'url' => 'https://api.github.com/repos/owner/test-repo/git/trees/abc123',
                'tree' => [
                    [
                        'path' => 'index.php',
                        'mode' => '100644',
                        'type' => 'blob',
                        'sha' => 'file123',
                        'size' => 256,
                        'url' => 'https://api.github.com/repos/owner/test-repo/git/blobs/file123',
                    ],
                    [
                        'path' => 'src',
                        'mode' => '040000',
                        'type' => 'tree',
                        'sha' => 'tree456',
                        'url' => 'https://api.github.com/repos/owner/test-repo/git/trees/tree456',
                    ],
                    [
                        'path' => 'src/class.php',
                        'mode' => '100644',
                        'type' => 'blob',
                        'sha' => 'file789',
                        'size' => 512,
                        'url' => 'https://api.github.com/repos/owner/test-repo/git/blobs/file789',
                    ],
                ],
                'truncated' => false,
            ], 200),

            // File content
            'api.github.com/repos/*/*/contents/*' => Http::response([
                'name' => 'index.php',
                'path' => 'index.php',
                'sha' => 'file123',
                'size' => 256,
                'url' => 'https://api.github.com/repos/owner/test-repo/contents/index.php',
                'html_url' => 'https://github.com/owner/test-repo/blob/main/index.php',
                'git_url' => 'https://api.github.com/repos/owner/test-repo/git/blobs/file123',
                'download_url' => 'https://raw.githubusercontent.com/owner/test-repo/main/index.php',
                'type' => 'file',
                'content' => base64_encode('<?php echo "Hello World"; ?>'),
                'encoding' => 'base64',
            ], 200),

            // Repository languages
            'api.github.com/repos/*/*/languages' => Http::response([
                'PHP' => 15432,
                'JavaScript' => 2341,
                'CSS' => 1234,
            ], 200),
        ]);
    }

    /**
     * Mock GitHub API rate limiting responses
     */
    public static function mockGitHubRateLimit(): void
    {
        Http::fake([
            'api.github.com/*' => Http::response([
                'message' => 'API rate limit exceeded',
                'documentation_url' => 'https://docs.github.com/rest/overview/resources-in-the-rest-api#rate-limiting',
            ], 403, [
                'X-RateLimit-Limit' => '60',
                'X-RateLimit-Remaining' => '0',
                'X-RateLimit-Reset' => (string)(time() + 3600),
            ]),
        ]);
    }

    /**
     * Mock OpenAI API responses for testing
     */
    public static function mockOpenAiApi(): void
    {
        Http::fake([
            'api.openai.com/v1/chat/completions' => Http::response([
                'id' => 'chatcmpl-test123',
                'object' => 'chat.completion',
                'created' => time(),
                'model' => 'gpt-5',
                'choices' => [
                    [
                        'index' => 0,
                        'message' => [
                            'role' => 'assistant',
                            'content' => '<?php

use PHPUnit\Framework\TestCase;

class TestPluginTest extends TestCase
{
    public function test_function_returns_expected_value()
    {
        $result = test_function();
        $this->assertEquals("test", $result);
    }

    public function test_function_returns_string()
    {
        $result = test_function();
        $this->assertIsString($result);
    }
}',
                        ],
                        'finish_reason' => 'stop',
                    ],
                ],
                'usage' => [
                    'prompt_tokens' => 150,
                    'completion_tokens' => 200,
                    'total_tokens' => 350,
                ],
            ], 200),
        ]);
    }

    /**
     * Mock OpenAI API errors for testing
     */
    public static function mockOpenAiApiError(): void
    {
        Http::fake([
            'api.openai.com/*' => Http::response([
                'error' => [
                    'message' => 'You exceeded your current quota, please check your plan and billing details.',
                    'type' => 'insufficient_quota',
                    'param' => null,
                    'code' => 'insufficient_quota',
                ],
            ], 429),
        ]);
    }

    /**
     * Mock Anthropic API responses for testing
     */
    public static function mockAnthropicApi(): void
    {
        Http::fake([
            'api.anthropic.com/v1/messages' => Http::response([
                'id' => 'msg_test123',
                'type' => 'message',
                'role' => 'assistant',
                'content' => [
                    [
                        'type' => 'text',
                        'text' => '<?php

use PHPUnit\Framework\TestCase;

class TestPluginTest extends TestCase
{
    public function test_function_returns_expected_value()
    {
        $result = test_function();
        $this->assertEquals("test", $result);
    }

    public function test_function_returns_string()
    {
        $result = test_function();
        $this->assertIsString($result);
    }
}',
                    ],
                ],
                'model' => 'claude-3-5-sonnet-20241022',
                'stop_reason' => 'end_turn',
                'stop_sequence' => null,
                'usage' => [
                    'input_tokens' => 150,
                    'output_tokens' => 200,
                ],
            ], 200),
        ]);
    }

    /**
     * Mock Anthropic API errors for testing
     */
    public static function mockAnthropicApiError(): void
    {
        Http::fake([
            'api.anthropic.com/*' => Http::response([
                'type' => 'error',
                'error' => [
                    'type' => 'rate_limit_error',
                    'message' => 'Rate limit exceeded',
                ],
            ], 429),
        ]);
    }

    /**
     * Mock Stripe API responses for testing
     */
    public static function mockStripeApi(): void
    {
        Http::fake([
            'api.stripe.com/v1/payment_intents' => Http::response([
                'id' => 'pi_test_123',
                'object' => 'payment_intent',
                'amount' => 1000,
                'currency' => 'usd',
                'status' => 'requires_payment_method',
                'client_secret' => 'pi_test_123_secret_test',
                'metadata' => [
                    'user_id' => '1',
                    'package_id' => '1',
                ],
            ], 200),

            'api.stripe.com/v1/payment_intents/*' => Http::response([
                'id' => 'pi_test_123',
                'object' => 'payment_intent',
                'amount' => 1000,
                'currency' => 'usd',
                'status' => 'succeeded',
                'metadata' => [
                    'user_id' => '1',
                    'package_id' => '1',
                ],
            ], 200),

            'api.stripe.com/v1/refunds' => Http::response([
                'id' => 're_test_123',
                'object' => 'refund',
                'amount' => 1000,
                'currency' => 'usd',
                'status' => 'succeeded',
                'payment_intent' => 'pi_test_123',
            ], 200),
        ]);
    }

    /**
     * Mock all external APIs for comprehensive testing
     */
    public static function mockAllApis(): void
    {
        self::mockGitHubApi();
        self::mockOpenAiApi();
        self::mockAnthropicApi();
        self::mockStripeApi();
    }

    /**
     * Reset all HTTP fakes
     */
    public static function resetMocks(): void
    {
        Http::fake();
    }
}
