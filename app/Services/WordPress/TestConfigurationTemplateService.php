<?php

namespace App\Services\WordPress;

class TestConfigurationTemplateService
{
    /**
     * Generate PHPUnit configuration template
     */
    public function generatePhpUnitConfig(array $options = []): string
    {
        $pluginName = $options['plugin_name'] ?? 'WordPress Plugin';
        $testDirectory = $options['test_directory'] ?? 'tests';
        $bootstrapFile = $options['bootstrap_file'] ?? 'tests/bootstrap/bootstrap.php';

        return <<<XML
<?xml version="1.0" encoding="UTF-8"?>
<phpunit xmlns:xsi="http://www.w3.org/2001/XMLSchema-instance"
         xsi:noNamespaceSchemaLocation="https://schema.phpunit.de/10.0/phpunit.xsd"
         bootstrap="{$bootstrapFile}"
         colors="true"
         verbose="true"
         failOnRisky="true"
         failOnWarning="true"
         convertErrorsToExceptions="true"
         convertNoticesToExceptions="true"
         convertWarningsToExceptions="true"
         processIsolation="false"
         stopOnFailure="false"
         cacheDirectory=".phpunit.cache"
         backupGlobals="false">

    <testsuites>
        <testsuite name="{$pluginName} Test Suite">
            <directory>{$testDirectory}</directory>
        </testsuite>
        <testsuite name="Unit Tests">
            <directory>{$testDirectory}/Unit</directory>
        </testsuite>
        <testsuite name="Integration Tests">
            <directory>{$testDirectory}/Integration</directory>
        </testsuite>
    </testsuites>

    <source>
        <include>
            <directory suffix=".php">./</directory>
        </include>
        <exclude>
            <directory>./vendor</directory>
            <directory>./tests</directory>
            <directory>./node_modules</directory>
            <file>./wp-config.php</file>
        </exclude>
    </source>

    <coverage>
        <report>
            <html outputDirectory="coverage-html"/>
            <text outputFile="coverage.txt"/>
            <clover outputFile="coverage.xml"/>
        </report>
    </coverage>

    <logging>
        <junit outputFile="tests/_output/report.xml"/>
    </logging>

    <php>
        <const name="WP_TESTS_DOMAIN" value="example.org"/>
        <const name="WP_TESTS_EMAIL" value="admin@example.org"/>
        <const name="WP_TESTS_TITLE" value="Test Blog"/>
        <const name="WP_PHP_BINARY" value="php"/>
        <const name="WP_TESTS_FORCE_KNOWN_BUGS" value="true"/>
        <server name="SERVER_NAME" value="http://example.org"/>
    </php>
</phpunit>
XML;
    }

    /**
     * Generate Pest configuration template
     */
    public function generatePestConfig(array $options = []): string
    {
        $pluginName = $options['plugin_name'] ?? 'WordPress Plugin';

        return <<<PHP
<?php

/*
|--------------------------------------------------------------------------
| Test Case
|--------------------------------------------------------------------------
|
| The closure you provide to your test functions is always bound to a specific PHPUnit test
| case class. By default, that class is "PHPUnit\Framework\TestCase". Of course, you may
| need to change it using the "pest()" function to bind a different classes or traits.
|
*/

// Import WordPress test utilities
use Brain\Monkey;
use Brain\Monkey\Functions;
use Brain\Monkey\Actions;
use Brain\Monkey\Filters;

/*
|--------------------------------------------------------------------------
| Expectations
|--------------------------------------------------------------------------
|
| When you're writing tests, you often need to check that values meet certain conditions. The
| "expect()" function gives you access to a set of "expectations" methods that you can use
| to assert different things. Of course, you may extend the Expectation API at any time.
|
*/

expect()->extend('toBeOne', function () {
    return \$this->toBe(1);
});

expect()->extend('toBeWordPressHook', function () {
    return \$this->toBeString()->toMatch('/^[a-zA-Z_][a-zA-Z0-9_]*\$/');
});

/*
|--------------------------------------------------------------------------
| Functions
|--------------------------------------------------------------------------
|
| While Pest is very powerful out-of-the-box, you may have some testing code specific to your
| project that you don't want to repeat in every file. Here you can also expose helpers as
| global functions to help you to reduce the number of lines of code in your test files.
|
*/

/**
 * Set up WordPress environment for testing
 */
function setupWordPress(): void
{
    Monkey\setUp();
    
    // Mock common WordPress functions
    Functions\when('wp_enqueue_script')->justReturn();
    Functions\when('wp_enqueue_style')->justReturn();
    Functions\when('plugin_dir_url')->returnArg();
    Functions\when('plugin_dir_path')->returnArg();
    Functions\when('get_option')->justReturn('');
    Functions\when('update_option')->justReturn(true);
    Functions\when('add_option')->justReturn(true);
    Functions\when('delete_option')->justReturn(true);
}

/**
 * Clean up WordPress environment after testing
 */
function tearDownWordPress(): void
{
    Monkey\tearDown();
}

/*
|--------------------------------------------------------------------------
| Test Configuration
|--------------------------------------------------------------------------
|
| Configure Pest to use WordPress-specific setup and teardown for all tests.
| This ensures that each test starts with a clean WordPress environment.
|
*/

uses()
    ->beforeEach(fn() => setupWordPress())
    ->afterEach(fn() => tearDownWordPress())
    ->in('Unit', 'Integration');

// Group tests by type
uses()->group('unit')->in('Unit');
uses()->group('integration')->in('Integration');
uses()->group('wordpress')->in('Unit', 'Integration');
PHP;
    }

    /**
     * Generate composer.json template with test dependencies
     */
    public function generateComposerJson(array $options = []): string
    {
        $pluginName = $options['plugin_name'] ?? 'wordpress-plugin';
        $pluginDescription = $options['plugin_description'] ?? 'A WordPress plugin';
        $framework = $options['framework'] ?? 'phpunit';
        $namespace = $options['namespace'] ?? 'WordPressPlugin';

        $testDependencies = $framework === 'pest' ? [
            'pestphp/pest' => '^2.0',
            'pestphp/pest-plugin-wordpress' => '^2.0',
            'brain/monkey' => '^2.6',
            'mockery/mockery' => '^1.5'
        ] : [
            'phpunit/phpunit' => '^10.0',
            'brain/monkey' => '^2.6',
            'yoast/phpunit-polyfills' => '^2.0',
            'mockery/mockery' => '^1.5'
        ];

        $scripts = $framework === 'pest' ? [
            'test' => 'pest',
            'test:unit' => 'pest --group=unit',
            'test:integration' => 'pest --group=integration',
            'test:coverage' => 'pest --coverage --coverage-html=coverage'
        ] : [
            'test' => 'phpunit',
            'test:unit' => 'phpunit --testsuite="Unit Tests"',
            'test:integration' => 'phpunit --testsuite="Integration Tests"',
            'test:coverage' => 'phpunit --coverage-html=coverage'
        ];

        $config = [
            'name' => $pluginName,
            'description' => $pluginDescription,
            'type' => 'wordpress-plugin',
            'license' => 'GPL-2.0-or-later',
            'autoload' => [
                'psr-4' => [
                    $namespace . '\\' => 'src/'
                ]
            ],
            'autoload-dev' => [
                'psr-4' => [
                    $namespace . '\\Tests\\' => 'tests/'
                ]
            ],
            'require' => [
                'php' => '>=7.4'
            ],
            'require-dev' => $testDependencies,
            'scripts' => $scripts,
            'config' => [
                'allow-plugins' => [
                    'pestphp/pest-plugin' => true
                ],
                'optimize-autoloader' => true,
                'sort-packages' => true
            ],
            'minimum-stability' => 'stable',
            'prefer-stable' => true
        ];

        return json_encode($config, JSON_PRETTY_PRINT | JSON_UNESCAPED_SLASHES);
    }

    /**
     * Generate bootstrap file for WordPress testing
     */
    public function generateBootstrapFile(array $options = []): string
    {
        $framework = $options['framework'] ?? 'phpunit';

        return <<<PHP
<?php
/**
 * WordPress Plugin Test Bootstrap
 * 
 * This file is used to bootstrap the WordPress testing environment.
 * It sets up the necessary WordPress functions and constants for testing.
 */

// Prevent direct access
if (!defined('ABSPATH')) {
    define('ABSPATH', dirname(__FILE__) . '/');
}

// Define test constants
define('WP_TESTS_DOMAIN', 'example.org');
define('WP_TESTS_EMAIL', 'admin@example.org');
define('WP_TESTS_TITLE', 'Test Blog');
define('WP_PHP_BINARY', 'php');

// Load Composer autoloader
if (file_exists(dirname(__DIR__, 2) . '/vendor/autoload.php')) {
    require_once dirname(__DIR__, 2) . '/vendor/autoload.php';
}

// Initialize Brain Monkey for WordPress function mocking
if (class_exists('Brain\Monkey')) {
    Brain\Monkey\setUp();
    
    // Register shutdown function to clean up
    register_shutdown_function(function() {
        Brain\Monkey\tearDown();
    });
}

// Mock essential WordPress functions
if (function_exists('Brain\Monkey\Functions\when')) {
    // Core WordPress functions
    Brain\Monkey\Functions\when('wp_enqueue_script')->justReturn();
    Brain\Monkey\Functions\when('wp_enqueue_style')->justReturn();
    Brain\Monkey\Functions\when('wp_register_script')->justReturn();
    Brain\Monkey\Functions\when('wp_register_style')->justReturn();
    Brain\Monkey\Functions\when('plugin_dir_url')->returnArg();
    Brain\Monkey\Functions\when('plugin_dir_path')->returnArg();
    Brain\Monkey\Functions\when('plugins_url')->returnArg();
    
    // Database functions
    Brain\Monkey\Functions\when('get_option')->justReturn('');
    Brain\Monkey\Functions\when('update_option')->justReturn(true);
    Brain\Monkey\Functions\when('add_option')->justReturn(true);
    Brain\Monkey\Functions\when('delete_option')->justReturn(true);
    
    // User functions
    Brain\Monkey\Functions\when('current_user_can')->justReturn(true);
    Brain\Monkey\Functions\when('is_admin')->justReturn(false);
    Brain\Monkey\Functions\when('is_user_logged_in')->justReturn(false);
    
    // Sanitization functions
    Brain\Monkey\Functions\when('sanitize_text_field')->returnArg();
    Brain\Monkey\Functions\when('sanitize_email')->returnArg();
    Brain\Monkey\Functions\when('esc_html')->returnArg();
    Brain\Monkey\Functions\when('esc_attr')->returnArg();
    Brain\Monkey\Functions\when('esc_url')->returnArg();
}

// Load the plugin file for testing
\$plugin_file = dirname(__DIR__, 2) . '/plugin.php';
if (file_exists(\$plugin_file)) {
    require_once \$plugin_file;
}

echo "WordPress Plugin Test Bootstrap Loaded\n";
PHP;
    }

    /**
     * Generate a sample test file
     */
    public function generateSampleTest(array $options = []): string
    {
        $framework = $options['framework'] ?? 'phpunit';
        $pluginName = $options['plugin_name'] ?? 'WordPress Plugin';
        $className = $options['class_name'] ?? 'SampleTest';

        if ($framework === 'pest') {
            return <<<PHP
<?php

/**
 * Sample Pest test for {$pluginName}
 * 
 * This is a basic example of how to write tests for your WordPress plugin using Pest.
 * You can use this as a starting point and modify it according to your plugin's functionality.
 */

describe('{$pluginName} Basic Tests', function () {
    
    it('can be instantiated', function () {
        expect(true)->toBeTrue();
    });
    
    it('has required WordPress functions available', function () {
        expect(function_exists('add_action'))->toBeTrue();
        expect(function_exists('add_filter'))->toBeTrue();
    });
    
    it('can mock WordPress functions', function () {
        // Example of mocking a WordPress function
        Brain\Monkey\Functions\when('get_option')
            ->justReturn('test_value');
            
        expect(get_option('test_option'))->toBe('test_value');
    });
    
    it('can test WordPress hooks', function () {
        // Example of testing action hooks
        Brain\Monkey\Actions\expectAdded('init');
        
        // Trigger your plugin's initialization
        do_action('init');
        
        // The expectation will be automatically verified
    });
    
    it('can test WordPress filters', function () {
        // Example of testing filter hooks
        Brain\Monkey\Filters\expectApplied('the_content')
            ->once()
            ->with('original content')
            ->andReturn('modified content');
            
        \$result = apply_filters('the_content', 'original content');
        expect(\$result)->toBe('modified content');
    });
});
PHP;
        } else {
            return <<<PHP
<?php

use PHPUnit\Framework\TestCase;
use Brain\Monkey;
use Brain\Monkey\Functions;
use Brain\Monkey\Actions;
use Brain\Monkey\Filters;

/**
 * Sample PHPUnit test for {$pluginName}
 * 
 * This is a basic example of how to write tests for your WordPress plugin using PHPUnit.
 * You can use this as a starting point and modify it according to your plugin's functionality.
 */
class {$className} extends TestCase
{
    /**
     * Set up test environment before each test
     */
    protected function setUp(): void
    {
        parent::setUp();
        Monkey\setUp();
    }

    /**
     * Clean up test environment after each test
     */
    protected function tearDown(): void
    {
        Monkey\tearDown();
        parent::tearDown();
    }

    /**
     * Test basic functionality
     */
    public function testBasicFunctionality(): void
    {
        \$this->assertTrue(true);
    }

    /**
     * Test WordPress functions are available
     */
    public function testWordPressFunctionsAvailable(): void
    {
        \$this->assertTrue(function_exists('add_action'));
        \$this->assertTrue(function_exists('add_filter'));
    }

    /**
     * Test WordPress function mocking
     */
    public function testWordPressFunctionMocking(): void
    {
        // Mock get_option function
        Functions\when('get_option')
            ->justReturn('test_value');
            
        \$this->assertEquals('test_value', get_option('test_option'));
    }

    /**
     * Test WordPress action hooks
     */
    public function testWordPressActionHooks(): void
    {
        // Expect that 'init' action is added
        Actions\expectAdded('init');
        
        // Trigger your plugin's initialization
        do_action('init');
    }

    /**
     * Test WordPress filter hooks
     */
    public function testWordPressFilterHooks(): void
    {
        // Mock a filter
        Filters\expectApplied('the_content')
            ->once()
            ->with('original content')
            ->andReturn('modified content');
            
        \$result = apply_filters('the_content', 'original content');
        \$this->assertEquals('modified content', \$result);
    }
}
PHP;
        }
    }

    /**
     * Get all available templates
     */
    public function getAvailableTemplates(): array
    {
        return [
            'phpunit_config' => [
                'name' => 'phpunit.xml',
                'description' => 'PHPUnit configuration file',
                'framework' => 'phpunit'
            ],
            'pest_config' => [
                'name' => 'tests/Pest.php',
                'description' => 'Pest configuration file',
                'framework' => 'pest'
            ],
            'composer_json' => [
                'name' => 'composer.json',
                'description' => 'Composer configuration with test dependencies',
                'framework' => 'both'
            ],
            'bootstrap' => [
                'name' => 'tests/bootstrap/bootstrap.php',
                'description' => 'WordPress test bootstrap file',
                'framework' => 'both'
            ],
            'sample_test' => [
                'name' => 'tests/SampleTest.php',
                'description' => 'Sample test file to get started',
                'framework' => 'both'
            ]
        ];
    }
}
