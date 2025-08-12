<?php

namespace App\Services\WordPress;

use Illuminate\Support\Facades\Log;

class TestInfrastructureDetectionService
{
    /**
     * Detect missing test infrastructure in WordPress plugin
     */
    public function detectMissingInfrastructure(string $pluginCode, string $filename = 'plugin.php', array $additionalFiles = []): array
    {
        $detection = [
            'has_phpunit_config' => false,
            'has_pest_config' => false,
            'has_composer_json' => false,
            'has_test_directory' => false,
            'has_test_dependencies' => false,
            'missing_components' => [],
            'recommendations' => [],
            'setup_priority' => 'high', // high, medium, low
        ];

        // Check for PHPUnit configuration
        $detection['has_phpunit_config'] = $this->hasPhpUnitConfig($additionalFiles);

        // Check for Pest configuration
        $detection['has_pest_config'] = $this->hasPestConfig($additionalFiles);

        // Check for composer.json
        $detection['has_composer_json'] = $this->hasComposerJson($additionalFiles);

        // Check for test directory
        $detection['has_test_directory'] = $this->hasTestDirectory($additionalFiles);

        // Check for test dependencies in composer.json
        if ($detection['has_composer_json']) {
            $detection['has_test_dependencies'] = $this->hasTestDependencies($additionalFiles);
        }

        // Determine missing components and recommendations
        $detection = $this->analyzeMissingComponents($detection);

        Log::info('Test infrastructure detection completed', [
            'filename' => $filename,
            'missing_components' => count($detection['missing_components']),
            'setup_priority' => $detection['setup_priority'],
        ]);

        return $detection;
    }

    /**
     * Check if PHPUnit configuration exists
     */
    private function hasPhpUnitConfig(array $files): bool
    {
        $phpunitFiles = ['phpunit.xml', 'phpunit.xml.dist', 'phpunit.dist.xml'];

        foreach ($files as $file) {
            if (in_array(strtolower($file['name']), $phpunitFiles)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if Pest configuration exists
     */
    private function hasPestConfig(array $files): bool
    {
        foreach ($files as $file) {
            if (strtolower($file['name']) === 'pest.php' ||
                (isset($file['path']) && str_contains(strtolower($file['path']), 'tests/pest.php'))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if composer.json exists
     */
    private function hasComposerJson(array $files): bool
    {
        foreach ($files as $file) {
            if (strtolower($file['name']) === 'composer.json') {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if test directory exists
     */
    private function hasTestDirectory(array $files): bool
    {
        foreach ($files as $file) {
            if (isset($file['type']) && $file['type'] === 'directory' &&
                in_array(strtolower($file['name']), ['tests', 'test'])) {
                return true;
            }

            // Also check for files in test directories
            if (isset($file['path']) &&
                (str_contains(strtolower($file['path']), '/tests/') ||
                 str_contains(strtolower($file['path']), '/test/'))) {
                return true;
            }
        }

        return false;
    }

    /**
     * Check if test dependencies exist in composer.json
     */
    private function hasTestDependencies(array $files): bool
    {
        foreach ($files as $file) {
            if (strtolower($file['name']) === 'composer.json' && isset($file['content'])) {
                $composerData = json_decode($file['content'], true);

                if ($composerData && isset($composerData['require-dev'])) {
                    $devDeps = array_keys($composerData['require-dev']);

                    // Check for common test dependencies
                    $testDependencies = [
                        'phpunit/phpunit',
                        'pestphp/pest',
                        'pestphp/pest-plugin-laravel',
                        'brain/monkey',
                        'wp-phpunit/wp-phpunit',
                        'yoast/phpunit-polyfills',
                    ];

                    foreach ($testDependencies as $dep) {
                        if (in_array($dep, $devDeps)) {
                            return true;
                        }
                    }
                }
            }
        }

        return false;
    }

    /**
     * Analyze missing components and generate recommendations
     */
    private function analyzeMissingComponents(array $detection): array
    {
        $missing = [];
        $recommendations = [];

        // Check for missing components
        if (! $detection['has_composer_json']) {
            $missing[] = 'composer_json';
            $recommendations[] = [
                'type' => 'critical',
                'title' => 'Missing composer.json',
                'description' => 'Composer is required for managing test dependencies and autoloading.',
                'action' => 'Create composer.json with test dependencies',
            ];
        }

        if (! $detection['has_test_directory']) {
            $missing[] = 'test_directory';
            $recommendations[] = [
                'type' => 'critical',
                'title' => 'Missing test directory',
                'description' => 'A dedicated tests/ directory is needed to organize your test files.',
                'action' => 'Create tests/ directory structure',
            ];
        }

        if (! $detection['has_test_dependencies']) {
            $missing[] = 'test_dependencies';
            $recommendations[] = [
                'type' => 'critical',
                'title' => 'Missing test dependencies',
                'description' => 'PHPUnit or Pest testing framework dependencies are not installed.',
                'action' => 'Install testing framework dependencies',
            ];
        }

        if (! $detection['has_phpunit_config'] && ! $detection['has_pest_config']) {
            $missing[] = 'test_config';
            $recommendations[] = [
                'type' => 'high',
                'title' => 'Missing test configuration',
                'description' => 'No PHPUnit or Pest configuration file found.',
                'action' => 'Create test framework configuration file',
            ];
        }

        // Determine setup priority
        $criticalCount = count(array_filter($recommendations, fn ($r) => $r['type'] === 'critical'));

        if ($criticalCount >= 3) {
            $detection['setup_priority'] = 'high';
        } elseif ($criticalCount >= 1) {
            $detection['setup_priority'] = 'medium';
        } else {
            $detection['setup_priority'] = 'low';
        }

        $detection['missing_components'] = $missing;
        $detection['recommendations'] = $recommendations;

        return $detection;
    }

    /**
     * Generate setup instructions based on detected issues
     */
    public function generateSetupInstructions(array $detection, string $framework = 'phpunit'): array
    {
        $instructions = [
            'framework' => $framework,
            'steps' => [],
            'files_to_create' => [],
            'commands_to_run' => [],
            'estimated_time' => '5-10 minutes',
        ];

        // Step 1: Initialize Composer if needed
        if (in_array('composer_json', $detection['missing_components'])) {
            $instructions['steps'][] = [
                'title' => 'Initialize Composer',
                'description' => 'Set up Composer for dependency management',
                'commands' => ['composer init --no-interaction'],
                'files' => ['composer.json'],
            ];
        }

        // Step 2: Install test dependencies
        if (in_array('test_dependencies', $detection['missing_components'])) {
            $dependencies = $framework === 'pest'
                ? ['pestphp/pest', 'pestphp/pest-plugin-wordpress', 'brain/monkey']
                : ['phpunit/phpunit', 'brain/monkey', 'yoast/phpunit-polyfills'];

            $instructions['steps'][] = [
                'title' => 'Install Test Dependencies',
                'description' => "Install {$framework} and WordPress testing utilities",
                'commands' => ['composer require --dev '.implode(' ', $dependencies)],
                'files' => ['composer.json', 'composer.lock'],
            ];
        }

        // Step 3: Create test directory
        if (in_array('test_directory', $detection['missing_components'])) {
            $instructions['steps'][] = [
                'title' => 'Create Test Directory Structure',
                'description' => 'Set up the tests directory with proper organization',
                'commands' => [
                    'mkdir -p tests/Unit',
                    'mkdir -p tests/Integration',
                    'mkdir -p tests/bootstrap',
                ],
                'files' => ['tests/', 'tests/Unit/', 'tests/Integration/'],
            ];
        }

        // Step 4: Create configuration files
        if (in_array('test_config', $detection['missing_components'])) {
            if ($framework === 'pest') {
                $instructions['steps'][] = [
                    'title' => 'Create Pest Configuration',
                    'description' => 'Set up Pest configuration for WordPress plugin testing',
                    'files' => ['tests/Pest.php', 'tests/bootstrap/bootstrap.php'],
                ];
            } else {
                $instructions['steps'][] = [
                    'title' => 'Create PHPUnit Configuration',
                    'description' => 'Set up PHPUnit configuration for WordPress plugin testing',
                    'files' => ['phpunit.xml', 'tests/bootstrap/bootstrap.php'],
                ];
            }
        }

        return $instructions;
    }
}
