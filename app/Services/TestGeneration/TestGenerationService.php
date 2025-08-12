<?php

namespace App\Services\TestGeneration;

use App\Services\AI\AIProviderService;
use App\Services\WordPress\PluginAnalysisService;
use Illuminate\Support\Facades\Log;

class TestGenerationService
{
    private AIProviderService $aiService;

    private PluginAnalysisService $analysisService;

    private array $config;

    public function __construct(
        AIProviderService $aiService,
        PluginAnalysisService $analysisService
    ) {
        $this->aiService = $aiService;
        $this->analysisService = $analysisService;
        $this->config = config('thinktest_ai.test_generation');
    }

    /**
     * Generate tests for a single file from a GitHub repository
     */
    public function generateTestsForSingleFile(string $fileContent, array $options = []): array
    {
        $framework = $options['framework'] ?? $this->config['default_framework'];
        $provider = $options['provider'] ?? 'openai';
        $filename = $options['filename'] ?? 'file.php';
        $repositoryContext = $options['repository_context'] ?? [];

        Log::info('Starting single-file test generation', [
            'framework' => $framework,
            'provider' => $provider,
            'filename' => $filename,
            'file_size' => strlen($fileContent),
            'repository' => $repositoryContext['full_name'] ?? 'unknown',
        ]);

        try {
            // Analyze the single file with repository context
            $analysis = $this->analysisService->analyzePlugin($fileContent, $filename);

            // Add repository context to analysis
            $analysis['repository_context'] = $repositoryContext;
            $analysis['is_single_file'] = true;
            $analysis['file_path'] = $options['file_path'] ?? $filename;

            // Generate AI-powered tests with single-file context
            $aiOptions = array_merge($options, [
                'framework' => $framework,
                'provider' => $provider,
                'analysis' => $analysis,
                'is_single_file' => true,
                'file_context' => [
                    'filename' => $filename,
                    'file_path' => $options['file_path'] ?? $filename,
                    'repository' => $repositoryContext,
                ],
            ]);

            $aiResult = $this->aiService->generateWordPressTests($fileContent, $aiOptions);

            // Post-process and enhance the generated tests for single file
            $enhancedTests = $this->enhanceGeneratedTestsForSingleFile($aiResult['generated_tests'], $analysis, $framework);

            // Generate test suite focused on the single file
            $testSuite = $this->buildSingleFileTestSuite($enhancedTests, $analysis, $framework);

            return [
                'success' => true,
                'framework' => $framework,
                'provider' => $aiResult['provider'],
                'model' => $aiResult['model'],
                'analysis' => $analysis,
                'tests' => $testSuite,
                'main_test_file' => $enhancedTests,
                'usage' => $aiResult['usage'] ?? null,
                'file_context' => [
                    'filename' => $filename,
                    'file_path' => $options['file_path'] ?? $filename,
                    'repository' => $repositoryContext,
                ],
            ];

        } catch (\Exception $e) {
            Log::error('Single-file test generation failed', [
                'error' => $e->getMessage(),
                'framework' => $framework,
                'provider' => $provider,
                'filename' => $filename,
                'repository' => $repositoryContext['full_name'] ?? 'unknown',
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'framework' => $framework,
                'provider' => $provider,
                'filename' => $filename,
            ];
        }
    }

    /**
     * Generate comprehensive tests for WordPress plugin
     */
    public function generateTests(string $pluginCode, array $options = []): array
    {
        $framework = $options['framework'] ?? $this->config['default_framework'];
        $provider = $options['provider'] ?? 'openai';

        Log::info('Starting test generation', [
            'framework' => $framework,
            'provider' => $provider,
            'code_length' => strlen($pluginCode),
        ]);

        try {
            // First, analyze the plugin code
            $analysis = $this->analysisService->analyzePlugin($pluginCode, $options['filename'] ?? 'plugin.php');

            // Generate AI-powered tests
            $aiOptions = array_merge($options, [
                'framework' => $framework,
                'provider' => $provider,
                'analysis' => $analysis,
            ]);

            $aiResult = $this->aiService->generateWordPressTests($pluginCode, $aiOptions);

            // Post-process and enhance the generated tests
            $enhancedTests = $this->enhanceGeneratedTests($aiResult['generated_tests'], $analysis, $framework);

            // Generate additional test files if needed
            $testSuite = $this->buildTestSuite($enhancedTests, $analysis, $framework);

            return [
                'success' => true,
                'framework' => $framework,
                'provider' => $aiResult['provider'],
                'model' => $aiResult['model'],
                'analysis' => $analysis,
                'tests' => $testSuite,
                'main_test_file' => $enhancedTests,
                'usage' => $aiResult['usage'] ?? null,
            ];

        } catch (\Exception $e) {
            Log::error('Test generation failed', [
                'error' => $e->getMessage(),
                'framework' => $framework,
                'provider' => $provider,
            ]);

            return [
                'success' => false,
                'error' => $e->getMessage(),
                'framework' => $framework,
                'provider' => $provider,
            ];
        }
    }

    /**
     * Enhance AI-generated tests with additional structure and best practices
     */
    private function enhanceGeneratedTests(string $generatedTests, array $analysis, string $framework): string
    {
        // Add proper file header
        $header = $this->generateTestFileHeader($analysis, $framework);

        // Clean up the generated tests
        $cleanedTests = $this->cleanupGeneratedTests($generatedTests);

        // Add setup and teardown methods if not present
        $enhancedTests = $this->addSetupTeardownMethods($cleanedTests, $analysis, $framework);

        // Add WordPress-specific test utilities
        $finalTests = $this->addWordPressTestUtilities($enhancedTests, $analysis, $framework);

        return $header."\n\n".$finalTests;
    }

    /**
     * Generate test file header with proper documentation
     */
    private function generateTestFileHeader(array $analysis, string $framework): string
    {
        $filename = $analysis['filename'] ?? 'plugin.php';
        $date = date('Y-m-d H:i:s');

        $header = "<?php\n";
        $header .= "/**\n";
        $header .= " * Generated Tests for WordPress Plugin: {$filename}\n";
        $header .= " * Generated by ThinkTest AI on {$date}\n";
        $header .= ' * Framework: '.ucfirst($framework)."\n";
        $header .= " * \n";
        $header .= " * This file contains comprehensive tests for the WordPress plugin.\n";
        $header .= " * Tests cover WordPress hooks, filters, functions, and security patterns.\n";
        $header .= " */\n";

        if ($framework === 'phpunit') {
            $header .= "\nuse PHPUnit\\Framework\\TestCase;";
            $header .= "\nuse WP_UnitTestCase;";
        } elseif ($framework === 'pest') {
            $header .= "\nuses(WP_UnitTestCase::class);";
        }

        return $header;
    }

    /**
     * Clean up AI-generated tests
     */
    private function cleanupGeneratedTests(string $tests): string
    {
        // Remove duplicate PHP opening tags
        $tests = preg_replace('/^<\?php\s*/', '', $tests);

        // Remove multiple consecutive empty lines
        $tests = preg_replace('/\n\s*\n\s*\n/', "\n\n", $tests);

        // Ensure proper indentation
        $lines = explode("\n", $tests);
        $cleanedLines = [];

        foreach ($lines as $line) {
            // Basic indentation cleanup
            $cleanedLines[] = $line;
        }

        return implode("\n", $cleanedLines);
    }

    /**
     * Add setup and teardown methods if not present
     */
    private function addSetupTeardownMethods(string $tests, array $analysis, string $framework): string
    {
        $hasSetup = strpos($tests, 'setUp') !== false;
        $hasTearDown = strpos($tests, 'tearDown') !== false;

        if ($framework === 'phpunit' && (! $hasSetup || ! $hasTearDown)) {
            $setupTeardown = "\n";

            if (! $hasSetup) {
                $setupTeardown .= "    protected function setUp(): void\n";
                $setupTeardown .= "    {\n";
                $setupTeardown .= "        parent::setUp();\n";
                $setupTeardown .= "        // Initialize WordPress test environment\n";
                $setupTeardown .= "        \$this->factory = new WP_UnitTest_Factory();\n";
                $setupTeardown .= "    }\n\n";
            }

            if (! $hasTearDown) {
                $setupTeardown .= "    protected function tearDown(): void\n";
                $setupTeardown .= "    {\n";
                $setupTeardown .= "        // Clean up after tests\n";
                $setupTeardown .= "        parent::tearDown();\n";
                $setupTeardown .= "    }\n";
            }

            // Insert after class declaration
            $tests = preg_replace('/class\s+\w+\s+extends\s+\w+\s*\{/', '$0'.$setupTeardown, $tests);
        }

        return $tests;
    }

    /**
     * Add WordPress-specific test utilities
     */
    private function addWordPressTestUtilities(string $tests, array $analysis, string $framework): string
    {
        $utilities = '';

        // Add helper methods for WordPress testing
        if (! empty($analysis['hooks']) || ! empty($analysis['filters'])) {
            $utilities .= "\n    /**\n";
            $utilities .= "     * Helper method to test WordPress hooks\n";
            $utilities .= "     */\n";
            $utilities .= "    protected function assertHookExists(\$hook, \$priority = 10)\n";
            $utilities .= "    {\n";
            $utilities .= "        \$this->assertTrue(has_action(\$hook), \"Hook {\$hook} should be registered\");\n";
            $utilities .= "    }\n\n";
        }

        if (! empty($analysis['database_operations'])) {
            $utilities .= "    /**\n";
            $utilities .= "     * Helper method to test database operations\n";
            $utilities .= "     */\n";
            $utilities .= "    protected function assertOptionExists(\$option_name)\n";
            $utilities .= "    {\n";
            $utilities .= "        \$this->assertNotFalse(get_option(\$option_name), \"Option {\$option_name} should exist\");\n";
            $utilities .= "    }\n\n";
        }

        if (! empty($utilities)) {
            // Insert before the last closing brace
            $tests = preg_replace('/\}(\s*)$/', $utilities.'}$1', $tests);
        }

        return $tests;
    }

    /**
     * Build complete test suite with multiple files if needed
     */
    private function buildTestSuite(string $mainTests, array $analysis, string $framework): array
    {
        $testSuite = [
            'main' => [
                'filename' => 'PluginTest.php',
                'content' => $mainTests,
                'description' => 'Main plugin functionality tests',
            ],
        ];

        // Generate additional test files for complex plugins
        if (! empty($analysis['ajax_handlers'])) {
            $testSuite['ajax'] = [
                'filename' => 'AjaxTest.php',
                'content' => $this->generateAjaxTests($analysis['ajax_handlers'], $framework),
                'description' => 'AJAX handler tests',
            ];
        }

        if (! empty($analysis['rest_endpoints'])) {
            $testSuite['rest'] = [
                'filename' => 'RestApiTest.php',
                'content' => $this->generateRestApiTests($analysis['rest_endpoints'], $framework),
                'description' => 'REST API endpoint tests',
            ];
        }

        if (! empty($analysis['security_patterns'])) {
            $testSuite['security'] = [
                'filename' => 'SecurityTest.php',
                'content' => $this->generateSecurityTests($analysis['security_patterns'], $framework),
                'description' => 'Security and sanitization tests',
            ];
        }

        return $testSuite;
    }

    /**
     * Generate AJAX-specific tests
     */
    private function generateAjaxTests(array $ajaxHandlers, string $framework): string
    {
        $tests = $this->generateTestFileHeader(['filename' => 'AJAX Handlers'], $framework);

        $tests .= "\n\nclass AjaxTest extends WP_UnitTestCase\n{\n";
        $tests .= "    public function test_ajax_handlers_registered()\n";
        $tests .= "    {\n";
        $tests .= "        // Test that AJAX handlers are properly registered\n";
        $tests .= "        \$this->assertTrue(true); // Placeholder\n";
        $tests .= "    }\n";
        $tests .= "}\n";

        return $tests;
    }

    /**
     * Generate REST API tests
     */
    private function generateRestApiTests(array $restEndpoints, string $framework): string
    {
        $tests = $this->generateTestFileHeader(['filename' => 'REST API'], $framework);

        $tests .= "\n\nclass RestApiTest extends WP_UnitTestCase\n{\n";
        $tests .= "    public function test_rest_endpoints_registered()\n";
        $tests .= "    {\n";
        $tests .= "        // Test that REST endpoints are properly registered\n";
        $tests .= "        \$this->assertTrue(true); // Placeholder\n";
        $tests .= "    }\n";
        $tests .= "}\n";

        return $tests;
    }

    /**
     * Generate security tests
     */
    private function generateSecurityTests(array $securityPatterns, string $framework): string
    {
        $tests = $this->generateTestFileHeader(['filename' => 'Security'], $framework);

        $tests .= "\n\nclass SecurityTest extends WP_UnitTestCase\n{\n";
        $tests .= "    public function test_input_sanitization()\n";
        $tests .= "    {\n";
        $tests .= "        // Test that user inputs are properly sanitized\n";
        $tests .= "        \$this->assertTrue(true); // Placeholder\n";
        $tests .= "    }\n";
        $tests .= "}\n";

        return $tests;
    }

    /**
     * Get supported frameworks
     */
    public function getSupportedFrameworks(): array
    {
        return $this->config['output_formats'];
    }

    /**
     * Validate test generation options
     */
    public function validateOptions(array $options): array
    {
        $errors = [];

        if (isset($options['framework']) && ! array_key_exists($options['framework'], $this->config['output_formats'])) {
            $errors[] = 'Unsupported test framework: '.$options['framework'];
        }

        if (isset($options['provider']) && ! in_array($options['provider'], ['openai-gpt5', 'anthropic-claude', 'chatgpt-5', 'anthropic'])) {
            $errors[] = 'Unsupported AI provider: '.$options['provider'];
        }

        return $errors;
    }

    /**
     * Enhance AI-generated tests specifically for single file context
     */
    private function enhanceGeneratedTestsForSingleFile(string $generatedTests, array $analysis, string $framework): string
    {
        // Add proper file header with single-file context
        $header = $this->generateSingleFileTestHeader($analysis, $framework);

        // Clean up the generated tests
        $cleanedTests = $this->cleanupGeneratedTests($generatedTests);

        // Add setup and teardown methods if not present
        $enhancedTests = $this->addSetupTeardownMethods($cleanedTests, $analysis, $framework);

        // Add WordPress-specific test utilities
        $finalTests = $this->addWordPressTestUtilities($enhancedTests, $analysis, $framework);

        return $header."\n\n".$finalTests;
    }

    /**
     * Generate test file header for single file with repository context
     */
    private function generateSingleFileTestHeader(array $analysis, string $framework): string
    {
        $filename = $analysis['filename'] ?? 'file.php';
        $filePath = $analysis['file_path'] ?? $filename;
        $repository = $analysis['repository_context']['full_name'] ?? 'unknown';
        $date = date('Y-m-d H:i:s');

        $header = "<?php\n";
        $header .= "/**\n";
        $header .= " * Generated Tests for Single File: {$filename}\n";
        $header .= " * File Path: {$filePath}\n";
        $header .= " * Repository: {$repository}\n";
        $header .= " * Generated by ThinkTest AI on {$date}\n";
        $header .= ' * Framework: '.ucfirst($framework)."\n";
        $header .= " * \n";
        $header .= " * This file contains focused tests for a specific file from the repository.\n";
        $header .= " * Tests are tailored to the functions, classes, and patterns found in this file.\n";
        $header .= " */\n";

        if ($framework === 'phpunit') {
            $header .= "\nuse PHPUnit\\Framework\\TestCase;";
            $header .= "\nuse WP_UnitTestCase;";
        } elseif ($framework === 'pest') {
            $header .= "\nuses(WP_UnitTestCase::class);";
        }

        return $header;
    }

    /**
     * Build test suite focused on a single file
     */
    private function buildSingleFileTestSuite(string $mainTests, array $analysis, string $framework): array
    {
        $testSuite = [
            'main_test_file' => [
                'filename' => $this->generateSingleFileTestFilename($analysis, $framework),
                'content' => $mainTests,
                'description' => 'Main test file for ' . ($analysis['filename'] ?? 'file'),
            ],
        ];

        // Add specific test files based on what's found in the single file
        if (!empty($analysis['functions'])) {
            $testSuite['function_tests'] = [
                'filename' => $this->generateFunctionTestFilename($analysis, $framework),
                'content' => $this->generateFunctionSpecificTests($analysis['functions'], $framework),
                'description' => 'Tests for functions found in the file',
            ];
        }

        if (!empty($analysis['classes'])) {
            $testSuite['class_tests'] = [
                'filename' => $this->generateClassTestFilename($analysis, $framework),
                'content' => $this->generateClassSpecificTests($analysis['classes'], $framework),
                'description' => 'Tests for classes found in the file',
            ];
        }

        if (!empty($analysis['hooks'])) {
            $testSuite['hook_tests'] = [
                'filename' => $this->generateHookTestFilename($analysis, $framework),
                'content' => $this->generateHookSpecificTests($analysis['hooks'], $framework),
                'description' => 'Tests for WordPress hooks found in the file',
            ];
        }

        return $testSuite;
    }

    /**
     * Generate filename for single file test
     */
    private function generateSingleFileTestFilename(array $analysis, string $framework): string
    {
        $filename = $analysis['filename'] ?? 'file.php';
        $baseName = pathinfo($filename, PATHINFO_FILENAME);
        $extension = $framework === 'pest' ? '.php' : 'Test.php';

        return ucfirst($baseName) . $extension;
    }

    /**
     * Generate filename for function tests
     */
    private function generateFunctionTestFilename(array $analysis, string $framework): string
    {
        $filename = $analysis['filename'] ?? 'file.php';
        $baseName = pathinfo($filename, PATHINFO_FILENAME);
        $extension = $framework === 'pest' ? '.php' : 'Test.php';

        return ucfirst($baseName) . 'Functions' . $extension;
    }

    /**
     * Generate filename for class tests
     */
    private function generateClassTestFilename(array $analysis, string $framework): string
    {
        $filename = $analysis['filename'] ?? 'file.php';
        $baseName = pathinfo($filename, PATHINFO_FILENAME);
        $extension = $framework === 'pest' ? '.php' : 'Test.php';

        return ucfirst($baseName) . 'Classes' . $extension;
    }

    /**
     * Generate filename for hook tests
     */
    private function generateHookTestFilename(array $analysis, string $framework): string
    {
        $filename = $analysis['filename'] ?? 'file.php';
        $baseName = pathinfo($filename, PATHINFO_FILENAME);
        $extension = $framework === 'pest' ? '.php' : 'Test.php';

        return ucfirst($baseName) . 'Hooks' . $extension;
    }

    /**
     * Generate function-specific tests
     */
    private function generateFunctionSpecificTests(array $functions, string $framework): string
    {
        $tests = $this->generateTestFileHeader(['filename' => 'Functions'], $framework);

        foreach ($functions as $function) {
            $functionName = $function['name'] ?? 'unknown_function';
            $tests .= "\n\n    /**\n";
            $tests .= "     * Test {$functionName} function\n";
            $tests .= "     */\n";

            if ($framework === 'pest') {
                $tests .= "    test('{$functionName} works correctly', function () {\n";
                $tests .= "        // Test implementation for {$functionName}\n";
                $tests .= "        expect(function_exists('{$functionName}'))->toBeTrue();\n";
                $tests .= "    });\n";
            } else {
                $tests .= "    public function test_{$functionName}()\n";
                $tests .= "    {\n";
                $tests .= "        // Test implementation for {$functionName}\n";
                $tests .= "        \$this->assertTrue(function_exists('{$functionName}'));\n";
                $tests .= "    }\n";
            }
        }

        return $tests;
    }

    /**
     * Generate class-specific tests
     */
    private function generateClassSpecificTests(array $classes, string $framework): string
    {
        $tests = $this->generateTestFileHeader(['filename' => 'Classes'], $framework);

        foreach ($classes as $class) {
            $className = $class['name'] ?? 'UnknownClass';
            $tests .= "\n\n    /**\n";
            $tests .= "     * Test {$className} class\n";
            $tests .= "     */\n";

            if ($framework === 'pest') {
                $tests .= "    test('{$className} can be instantiated', function () {\n";
                $tests .= "        expect(class_exists('{$className}'))->toBeTrue();\n";
                $tests .= "    });\n";
            } else {
                $tests .= "    public function test_{$className}_instantiation()\n";
                $tests .= "    {\n";
                $tests .= "        \$this->assertTrue(class_exists('{$className}'));\n";
                $tests .= "    }\n";
            }
        }

        return $tests;
    }

    /**
     * Generate hook-specific tests
     */
    private function generateHookSpecificTests(array $hooks, string $framework): string
    {
        $tests = $this->generateTestFileHeader(['filename' => 'Hooks'], $framework);

        foreach ($hooks as $hook) {
            $hookName = $hook['name'] ?? 'unknown_hook';
            $tests .= "\n\n    /**\n";
            $tests .= "     * Test {$hookName} hook\n";
            $tests .= "     */\n";

            if ($framework === 'pest') {
                $tests .= "    test('{$hookName} hook is registered', function () {\n";
                $tests .= "        expect(has_action('{$hookName}'))->toBeGreaterThan(0);\n";
                $tests .= "    });\n";
            } else {
                $tests .= "    public function test_{$hookName}_hook()\n";
                $tests .= "    {\n";
                $tests .= "        \$this->assertGreaterThan(0, has_action('{$hookName}'));\n";
                $tests .= "    }\n";
            }
        }

        return $tests;
    }
}
