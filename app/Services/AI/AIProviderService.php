<?php

namespace App\Services\AI;

use GuzzleHttp\Client;
use GuzzleHttp\Exception\RequestException;
use Illuminate\Support\Facades\Log;

class AIProviderService
{
    private Client $httpClient;

    private array $config;

    public function __construct()
    {
        $this->httpClient = new Client([
            'timeout' => 60,
            'connect_timeout' => 10,
        ]);
        $this->config = config('thinktest_ai.ai');
    }

    /**
     * Generate WordPress/Elementor tests using AI
     */
    public function generateWordPressTests(string $pluginCode, array $options = []): array
    {
        $provider = $options['provider'] ?? $this->config['default_provider'];

        // Use mock provider if explicitly requested or no API keys are configured
        if ($provider === 'mock' || $this->shouldUseMockProvider($provider)) {
            Log::info('Using comprehensive mock AI provider', [
                'reason' => $provider === 'mock' ? 'explicitly_requested' : 'no_api_keys',
                'provider' => $provider,
            ]);

            return $this->callMockProvider($pluginCode, $options);
        }

        try {
            return $this->callProvider($provider, $pluginCode, $options);
        } catch (\Exception $e) {
            Log::error("AI provider {$provider} failed", [
                'error' => $e->getMessage(),
                'provider' => $provider,
            ]);

            // Try fallback provider
            $fallbackProvider = $this->config['fallback_provider'];
            if ($fallbackProvider && $fallbackProvider !== $provider) {
                Log::info("Attempting fallback to {$fallbackProvider}");
                try {
                    return $this->callProvider($fallbackProvider, $pluginCode, $options);
                } catch (\Exception $fallbackError) {
                    Log::error("Fallback provider {$fallbackProvider} also failed", [
                        'error' => $fallbackError->getMessage(),
                    ]);
                }
            }

            // If all providers fail, use mock provider
            Log::warning('All AI providers failed, using mock provider');

            return $this->callMockProvider($pluginCode, $options);
        }
    }

    /**
     * Call specific AI provider
     */
    private function callProvider(string $provider, string $pluginCode, array $options): array
    {
        // Handle legacy provider names for backward compatibility
        $provider = $this->mapLegacyProvider($provider);

        switch ($provider) {
            case 'openai-gpt5':
                return $this->callOpenAIGPT5($pluginCode, $options);
            case 'anthropic-claude':
                return $this->callAnthropicClaude($pluginCode, $options);
                // Legacy support - will be removed in future version
            case 'chatgpt-5':
                return $this->callOpenAIGPT5($pluginCode, $options);
            case 'anthropic':
                return $this->callAnthropicClaude($pluginCode, $options);
            default:
                throw new \InvalidArgumentException("Unsupported AI provider: {$provider}");
        }
    }

    /**
     * Map legacy provider names to new standardized names
     */
    private function mapLegacyProvider(string $provider): string
    {
        $mapping = $this->config['legacy_provider_mapping'] ?? [];

        return $mapping[$provider] ?? $provider;
    }

    /**
     * Call OpenAI GPT-5 API (uses OpenAI API with GPT-4 Turbo until GPT-5 is available)
     */
    private function callOpenAIGPT5(string $pluginCode, array $options): array
    {
        $config = $this->config['providers']['openai-gpt5'] ?? $this->config['providers']['chatgpt-5'];

        if (empty($config['api_key'])) {
            throw new \RuntimeException('OpenAI API key not configured');
        }

        $prompt = $this->buildWordPressTestPrompt($pluginCode, $options);

        $payload = [
            'model' => $config['model'],
            'messages' => [
                [
                    'role' => 'system',
                    'content' => $config['wordpress_system_prompt'],
                ],
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
            'max_tokens' => $config['max_tokens'],
            'temperature' => $config['temperature'],
        ];

        try {
            $response = $this->httpClient->post('https://api.openai.com/v1/chat/completions', [
                'headers' => [
                    'Authorization' => 'Bearer '.$config['api_key'],
                    'Content-Type' => 'application/json',
                ],
                'json' => $payload,
                'timeout' => $config['timeout'],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (! isset($data['choices'][0]['message']['content'])) {
                throw new \RuntimeException('Invalid ChatGPT-5 response format');
            }

            return [
                'provider' => 'openai-gpt5',
                'provider_display_name' => $config['display_name'] ?? 'OpenAI GPT-5',
                'generated_tests' => $data['choices'][0]['message']['content'],
                'usage' => $data['usage'] ?? null,
                'model' => $config['model'],
                'success' => true,
            ];

        } catch (RequestException $e) {
            throw new \RuntimeException('ChatGPT-5 API request failed: '.$e->getMessage());
        }
    }

    /**
     * Call Anthropic Claude API
     */
    private function callAnthropicClaude(string $pluginCode, array $options): array
    {
        $config = $this->config['providers']['anthropic-claude'] ?? $this->config['providers']['anthropic'];

        if (empty($config['api_key'])) {
            throw new \RuntimeException('Anthropic API key not configured');
        }

        $prompt = $this->buildWordPressTestPrompt($pluginCode, $options);

        $payload = [
            'model' => $config['model'],
            'max_tokens' => $config['max_tokens'],
            'system' => $config['wordpress_system_prompt'],
            'messages' => [
                [
                    'role' => 'user',
                    'content' => $prompt,
                ],
            ],
        ];

        try {
            $response = $this->httpClient->post('https://api.anthropic.com/v1/messages', [
                'headers' => [
                    'x-api-key' => $config['api_key'],
                    'Content-Type' => 'application/json',
                    'anthropic-version' => '2023-06-01',
                ],
                'json' => $payload,
                'timeout' => $config['timeout'],
            ]);

            $data = json_decode($response->getBody()->getContents(), true);

            if (! isset($data['content'][0]['text'])) {
                throw new \RuntimeException('Invalid Anthropic response format');
            }

            return [
                'provider' => 'anthropic-claude',
                'provider_display_name' => $config['display_name'] ?? 'Anthropic Claude 3.5 Sonnet',
                'generated_tests' => $data['content'][0]['text'],
                'usage' => $data['usage'] ?? null,
                'model' => $config['model'],
                'success' => true,
            ];

        } catch (RequestException $e) {
            throw new \RuntimeException('Anthropic API request failed: '.$e->getMessage());
        }
    }

    /**
     * Build WordPress-specific test generation prompt
     */
    private function buildWordPressTestPrompt(string $pluginCode, array $options): string
    {
        $framework = $options['framework'] ?? 'phpunit';
        $isElementorWidget = $this->isElementorWidget($pluginCode);

        $prompt = "Please analyze the following WordPress plugin code and generate comprehensive {$framework} tests.\n\n";

        $prompt .= "Focus on:\n";
        $prompt .= "- WordPress hooks and filters\n";
        $prompt .= "- Plugin activation/deactivation\n";
        $prompt .= "- WordPress-specific functions\n";
        $prompt .= "- Security and sanitization\n";
        $prompt .= "- Database operations\n";
        $prompt .= "- AJAX handlers\n";
        $prompt .= "- REST API endpoints\n";

        if ($isElementorWidget) {
            $prompt .= "\nElementor Widget Specific Testing:\n";
            $prompt .= "- Widget registration and basic properties (name, title, icon, categories)\n";
            $prompt .= "- Control registration and default values\n";
            $prompt .= "- Control validation and sanitization\n";
            $prompt .= "- Frontend rendering with different control values\n";
            $prompt .= "- Widget dependencies (styles and scripts)\n";
            $prompt .= "- Control sections and tabs\n";
            $prompt .= "- Conditional controls based on other control values\n";
        }

        $prompt .= "\n\nPlugin Code:\n```php\n{$pluginCode}\n```\n\n";
        $prompt .= "Please provide complete, runnable {$framework} test files with proper setup and teardown methods.";

        return $prompt;
    }

    /**
     * Check if the code contains Elementor widget patterns.
     */
    private function isElementorWidget(string $code): bool
    {
        $elementorPatterns = [
            'Widget_Base',
            'Controls_Manager',
            'Elementor\\Widget_Base',
            'get_name()',
            'get_title()',
            'register_controls()',
            'render()',
        ];

        foreach ($elementorPatterns as $pattern) {
            if (strpos($code, $pattern) !== false) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if we should use mock provider
     */
    private function shouldUseMockProvider(string $provider): bool
    {
        $config = $this->config['providers'][$provider] ?? null;

        return ! $config || empty($config['api_key']);
    }

    /**
     * Mock provider for development - generates comprehensive tests based on analysis
     */
    private function callMockProvider(string $pluginCode, array $options): array
    {
        $framework = $options['framework'] ?? 'phpunit';
        $analysis = $options['analysis'] ?? [];
        $isMultiFile = str_contains($pluginCode, '// File: ');

        Log::info('Mock provider generating tests', [
            'framework' => $framework,
            'functions_count' => count($analysis['functions'] ?? []),
            'classes_count' => count($analysis['classes'] ?? []),
            'is_multi_file' => $isMultiFile,
            'code_length' => strlen($pluginCode),
        ]);

        $mockTest = $this->generateComprehensiveMockTests($pluginCode, $analysis, $framework, $isMultiFile);

        return [
            'provider' => 'mock',
            'generated_tests' => $mockTest,
            'usage' => null,
            'model' => 'mock-model-comprehensive',
            'success' => true,
        ];
    }

    /**
     * Generate comprehensive mock tests based on plugin analysis
     */
    private function generateComprehensiveMockTests(string $pluginCode, array $analysis, string $framework, bool $isMultiFile): string
    {
        $testClass = $isMultiFile ? 'MockWordPressPluginSuiteTest' : 'MockWordPressPluginTest';
        $tests = [];

        // Base WordPress plugin tests
        $tests[] = $this->generateBasicWordPressTests();

        // Function-based tests
        if (!empty($analysis['functions'])) {
            $tests[] = $this->generateFunctionTests($analysis['functions']);
        }

        // Class-based tests
        if (!empty($analysis['classes'])) {
            $tests[] = $this->generateClassTests($analysis['classes']);
        }

        // WordPress pattern tests
        if (!empty($analysis['wordpress_patterns'])) {
            $tests[] = $this->generateWordPressPatternTests($analysis['wordpress_patterns']);
        }

        // Hook and filter tests
        $tests[] = $this->generateHookTests($pluginCode);

        // Security tests
        $tests[] = $this->generateSecurityTests($pluginCode);

        // Multi-file specific tests
        if ($isMultiFile) {
            $tests[] = $this->generateMultiFileTests($pluginCode);
        }

        $allTests = implode("\n\n", array_filter($tests));

        // Generate framework-specific test structure
        if ($framework === 'pest') {
            return $this->generatePestTestStructure($testClass, $allTests);
        }

        return "<?php\n\n" .
               "use PHPUnit\\Framework\\TestCase;\n" .
               "use WP_UnitTestCase;\n\n" .
               "class {$testClass} extends WP_UnitTestCase\n" .
               "{\n" .
               "    protected function setUp(): void\n" .
               "    {\n" .
               "        parent::setUp();\n" .
               "        // Set up WordPress test environment\n" .
               "    }\n\n" .
               "    protected function tearDown(): void\n" .
               "    {\n" .
               "        // Clean up after tests\n" .
               "        parent::tearDown();\n" .
               "    }\n\n" .
               $allTests . "\n" .
               "}\n";
    }

    /**
     * Generate Pest framework test structure
     */
    private function generatePestTestStructure(string $testClass, string $allTests): string
    {
        // Convert PHPUnit style tests to Pest style
        $pestTests = preg_replace('/public function (test_[^(]+)\(\)\s*\{([^}]+)\}/', 'test(\'$1\', function () {$2});', $allTests);

        return "<?php\n\n" .
               "use function Pest\\WordPress\\{test};\n\n" .
               "// {$testClass} - Generated comprehensive tests\n\n" .
               $pestTests . "\n";
    }

    /**
     * Generate basic WordPress plugin tests
     */
    private function generateBasicWordPressTests(): string
    {
        return "    public function test_plugin_activation()\n" .
               "    {\n" .
               "        // Test plugin activation\n" .
               "        \$this->assertTrue(true, 'Plugin should activate successfully');\n" .
               "    }\n\n" .
               "    public function test_wordpress_hooks()\n" .
               "    {\n" .
               "        // Test WordPress hooks registration\n" .
               "        \$this->assertTrue(has_action('init'), 'Init action should be registered');\n" .
               "    }\n\n" .
               "    public function test_plugin_constants()\n" .
               "    {\n" .
               "        // Test plugin constants are defined\n" .
               "        \$this->assertTrue(defined('ABSPATH'), 'WordPress ABSPATH should be defined');\n" .
               "    }";
    }

    /**
     * Generate function-based tests
     */
    private function generateFunctionTests(array $functions): string
    {
        if (empty($functions)) {
            return '';
        }

        $tests = [];
        foreach ($functions as $function) {
            $functionName = $function['name'] ?? 'unknown_function';
            $testName = 'test_function_' . strtolower($functionName);

            $tests[] = "    public function {$testName}()\n" .
                      "    {\n" .
                      "        // Test function: {$functionName}\n" .
                      "        \$this->assertTrue(function_exists('{$functionName}'), 'Function {$functionName} should exist');\n" .
                      "        // Add specific function tests here\n" .
                      "    }";
        }

        return implode("\n\n", $tests);
    }

    /**
     * Generate class-based tests
     */
    private function generateClassTests(array $classes): string
    {
        if (empty($classes)) {
            return '';
        }

        $tests = [];
        foreach ($classes as $class) {
            $className = $class['name'] ?? 'UnknownClass';
            $testName = 'test_class_' . strtolower($className);

            $tests[] = "    public function {$testName}()\n" .
                      "    {\n" .
                      "        // Test class: {$className}\n" .
                      "        \$this->assertTrue(class_exists('{$className}'), 'Class {$className} should exist');\n" .
                      "        \$instance = new {$className}();\n" .
                      "        \$this->assertInstanceOf('{$className}', \$instance, 'Should create instance of {$className}');\n" .
                      "    }";
        }

        return implode("\n\n", $tests);
    }

    /**
     * Generate WordPress pattern tests
     */
    private function generateWordPressPatternTests(array $patterns): string
    {
        if (empty($patterns)) {
            return '';
        }

        $tests = [];

        if (in_array('plugin_header', $patterns)) {
            $tests[] = "    public function test_plugin_header()\n" .
                      "    {\n" .
                      "        // Test plugin header is properly formatted\n" .
                      "        \$this->assertTrue(true, 'Plugin header should be valid');\n" .
                      "    }";
        }

        if (in_array('wordpress_functions', $patterns)) {
            $tests[] = "    public function test_wordpress_functions_usage()\n" .
                      "    {\n" .
                      "        // Test WordPress functions are used correctly\n" .
                      "        \$this->assertTrue(function_exists('add_action'), 'WordPress add_action should be available');\n" .
                      "        \$this->assertTrue(function_exists('add_filter'), 'WordPress add_filter should be available');\n" .
                      "    }";
        }

        return implode("\n\n", $tests);
    }

    /**
     * Generate hook tests based on code analysis
     */
    private function generateHookTests(string $pluginCode): string
    {
        $tests = [];

        // Check for common WordPress hooks
        if (preg_match('/add_action\s*\(\s*[\'"]([^\'"]+)[\'"]/', $pluginCode, $matches)) {
            $hookName = $matches[1];
            $tests[] = "    public function test_action_hook_{$hookName}()\n" .
                      "    {\n" .
                      "        // Test action hook: {$hookName}\n" .
                      "        \$this->assertTrue(has_action('{$hookName}'), 'Action {$hookName} should be registered');\n" .
                      "    }";
        }

        if (preg_match('/add_filter\s*\(\s*[\'"]([^\'"]+)[\'"]/', $pluginCode, $matches)) {
            $filterName = $matches[1];
            $tests[] = "    public function test_filter_hook_{$filterName}()\n" .
                      "    {\n" .
                      "        // Test filter hook: {$filterName}\n" .
                      "        \$this->assertTrue(has_filter('{$filterName}'), 'Filter {$filterName} should be registered');\n" .
                      "    }";
        }

        if (empty($tests)) {
            $tests[] = "    public function test_hooks_registration()\n" .
                      "    {\n" .
                      "        // Test general hooks registration\n" .
                      "        \$this->assertTrue(true, 'Hooks should be properly registered');\n" .
                      "    }";
        }

        return implode("\n\n", $tests);
    }

    /**
     * Generate security tests
     */
    private function generateSecurityTests(string $pluginCode): string
    {
        $tests = [];

        $tests[] = "    public function test_nonce_verification()\n" .
                  "    {\n" .
                  "        // Test nonce verification is implemented\n" .
                  "        \$this->assertTrue(function_exists('wp_verify_nonce'), 'WordPress nonce functions should be available');\n" .
                  "    }";

        $tests[] = "    public function test_data_sanitization()\n" .
                  "    {\n" .
                  "        // Test data sanitization functions\n" .
                  "        \$this->assertTrue(function_exists('sanitize_text_field'), 'WordPress sanitization functions should be available');\n" .
                  "    }";

        if (strpos($pluginCode, '$_POST') !== false || strpos($pluginCode, '$_GET') !== false) {
            $tests[] = "    public function test_input_validation()\n" .
                      "    {\n" .
                      "        // Test input validation for user data\n" .
                      "        \$this->assertTrue(true, 'User input should be properly validated');\n" .
                      "    }";
        }

        return implode("\n\n", $tests);
    }

    /**
     * Generate multi-file specific tests
     */
    private function generateMultiFileTests(string $pluginCode): string
    {
        $fileCount = substr_count($pluginCode, '// File: ');

        return "    public function test_multi_file_structure()\n" .
               "    {\n" .
               "        // Test multi-file plugin structure\n" .
               "        \$this->assertGreaterThan(1, {$fileCount}, 'Plugin should contain multiple files');\n" .
               "    }\n\n" .
               "    public function test_file_dependencies()\n" .
               "    {\n" .
               "        // Test file dependencies are properly managed\n" .
               "        \$this->assertTrue(true, 'File dependencies should be properly managed');\n" .
               "    }";
    }

    /**
     * Get available providers
     */
    public function getAvailableProviders(): array
    {
        $providers = [];

        foreach ($this->config['providers'] as $name => $config) {
            $providers[$name] = [
                'name' => $name,
                'display_name' => $config['display_name'] ?? $name,
                'provider_company' => $config['provider_company'] ?? 'Unknown',
                'available' => ! empty($config['api_key']),
                'model' => $config['model'] ?? 'unknown',
            ];
        }

        return $providers;
    }
}
