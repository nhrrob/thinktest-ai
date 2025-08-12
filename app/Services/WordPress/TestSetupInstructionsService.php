<?php

namespace App\Services\WordPress;

use Illuminate\Support\Facades\Log;

class TestSetupInstructionsService
{
    private TestConfigurationTemplateService $templateService;

    public function __construct(TestConfigurationTemplateService $templateService)
    {
        $this->templateService = $templateService;
    }

    /**
     * Generate comprehensive setup instructions based on detection results
     */
    public function generateInstructions(array $detection, array $options = []): array
    {
        $framework = $options['framework'] ?? 'phpunit';
        $pluginName = $options['plugin_name'] ?? 'WordPress Plugin';
        $difficulty = $this->determineDifficulty($detection);

        $instructions = [
            'framework' => $framework,
            'plugin_name' => $pluginName,
            'difficulty' => $difficulty,
            'estimated_time' => $this->estimateTime($detection),
            'prerequisites' => $this->getPrerequisites(),
            'steps' => $this->generateSteps($detection, $framework, $pluginName),
            'files_to_create' => $this->getFilesToCreate($detection, $framework),
            'commands' => $this->getCommands($detection, $framework),
            'troubleshooting' => $this->getTroubleshootingTips(),
            'next_steps' => $this->getNextSteps($framework),
            'resources' => $this->getResources($framework),
        ];

        Log::info('Generated test setup instructions', [
            'framework' => $framework,
            'difficulty' => $difficulty,
            'steps_count' => count($instructions['steps']),
        ]);

        return $instructions;
    }

    /**
     * Determine setup difficulty based on missing components
     */
    private function determineDifficulty(array $detection): string
    {
        $missingCount = count($detection['missing_components']);

        if ($missingCount >= 4) {
            return 'beginner'; // Complete setup needed
        } elseif ($missingCount >= 2) {
            return 'intermediate'; // Some components missing
        } else {
            return 'advanced'; // Minor adjustments needed
        }
    }

    /**
     * Estimate time required for setup
     */
    private function estimateTime(array $detection): string
    {
        $missingCount = count($detection['missing_components']);

        if ($missingCount >= 4) {
            return '15-20 minutes';
        } elseif ($missingCount >= 2) {
            return '10-15 minutes';
        } else {
            return '5-10 minutes';
        }
    }

    /**
     * Get prerequisites for test setup
     */
    private function getPrerequisites(): array
    {
        return [
            [
                'title' => 'PHP 7.4 or higher',
                'description' => 'Required for modern testing frameworks',
                'check_command' => 'php --version',
            ],
            [
                'title' => 'Composer',
                'description' => 'PHP dependency manager for installing test frameworks',
                'check_command' => 'composer --version',
                'install_url' => 'https://getcomposer.org/download/',
            ],
            [
                'title' => 'WordPress Development Environment',
                'description' => 'Local WordPress installation for testing',
                'options' => ['Local by Flywheel', 'XAMPP', 'MAMP', 'Docker'],
            ],
        ];
    }

    /**
     * Generate step-by-step instructions
     */
    private function generateSteps(array $detection, string $framework, string $pluginName): array
    {
        $steps = [];
        $stepNumber = 1;

        // Step 1: Initialize Composer if needed
        if (in_array('composer_json', $detection['missing_components'])) {
            $steps[] = [
                'number' => $stepNumber++,
                'title' => 'Initialize Composer',
                'description' => 'Set up Composer for dependency management in your plugin directory',
                'commands' => [
                    'cd /path/to/your/plugin',
                    'composer init --no-interaction --name="your-vendor/'.strtolower(str_replace(' ', '-', $pluginName)).'"',
                ],
                'explanation' => 'This creates a composer.json file that will manage your test dependencies.',
                'files_created' => ['composer.json'],
            ];
        }

        // Step 2: Install test dependencies
        if (in_array('test_dependencies', $detection['missing_components'])) {
            $dependencies = $this->getTestDependencies($framework);

            $steps[] = [
                'number' => $stepNumber++,
                'title' => 'Install Test Dependencies',
                'description' => "Install {$framework} and WordPress testing utilities",
                'commands' => [
                    'composer require --dev '.implode(' ', $dependencies),
                ],
                'explanation' => "This installs the {$framework} testing framework and WordPress-specific testing tools.",
                'files_created' => ['composer.lock', 'vendor/'],
            ];
        }

        // Step 3: Create directory structure
        if (in_array('test_directory', $detection['missing_components'])) {
            $steps[] = [
                'number' => $stepNumber++,
                'title' => 'Create Test Directory Structure',
                'description' => 'Set up organized directories for your tests',
                'commands' => [
                    'mkdir -p tests/Unit',
                    'mkdir -p tests/Integration',
                    'mkdir -p tests/bootstrap',
                ],
                'explanation' => 'Organizing tests into Unit and Integration directories helps maintain clean test structure.',
                'files_created' => ['tests/', 'tests/Unit/', 'tests/Integration/', 'tests/bootstrap/'],
            ];
        }

        // Step 4: Create configuration files
        if (in_array('test_config', $detection['missing_components'])) {
            if ($framework === 'pest') {
                $steps[] = [
                    'number' => $stepNumber++,
                    'title' => 'Create Pest Configuration',
                    'description' => 'Set up Pest configuration for WordPress plugin testing',
                    'explanation' => 'The Pest.php file configures how Pest runs your tests and sets up WordPress mocking.',
                    'files_created' => ['tests/Pest.php'],
                ];
            } else {
                $steps[] = [
                    'number' => $stepNumber++,
                    'title' => 'Create PHPUnit Configuration',
                    'description' => 'Set up PHPUnit configuration for WordPress plugin testing',
                    'explanation' => 'The phpunit.xml file tells PHPUnit how to run your tests and where to find them.',
                    'files_created' => ['phpunit.xml'],
                ];
            }
        }

        // Step 5: Create bootstrap file
        $steps[] = [
            'number' => $stepNumber++,
            'title' => 'Create Bootstrap File',
            'description' => 'Set up the test bootstrap to initialize WordPress environment',
            'explanation' => 'The bootstrap file loads WordPress functions and your plugin for testing.',
            'files_created' => ['tests/bootstrap/bootstrap.php'],
        ];

        // Step 6: Create sample test
        $steps[] = [
            'number' => $stepNumber++,
            'title' => 'Create Your First Test',
            'description' => 'Generate a sample test file to verify everything works',
            'commands' => $framework === 'pest' ? ['./vendor/bin/pest'] : ['./vendor/bin/phpunit'],
            'explanation' => 'Running this sample test confirms your testing environment is properly configured.',
            'files_created' => ['tests/SampleTest.php'],
        ];

        return $steps;
    }

    /**
     * Get test dependencies for framework
     */
    private function getTestDependencies(string $framework): array
    {
        if ($framework === 'pest') {
            return [
                'pestphp/pest:^2.0',
                'pestphp/pest-plugin-wordpress:^2.0',
                'brain/monkey:^2.6',
                'mockery/mockery:^1.5',
            ];
        }

        return [
            'phpunit/phpunit:^10.0',
            'brain/monkey:^2.6',
            'yoast/phpunit-polyfills:^2.0',
            'mockery/mockery:^1.5',
        ];
    }

    /**
     * Get files that need to be created
     */
    private function getFilesToCreate(array $detection, string $framework): array
    {
        $files = [];

        if (in_array('composer_json', $detection['missing_components'])) {
            $files[] = [
                'name' => 'composer.json',
                'description' => 'Composer configuration with test dependencies',
                'template' => 'composer_json',
            ];
        }

        if (in_array('test_config', $detection['missing_components'])) {
            if ($framework === 'pest') {
                $files[] = [
                    'name' => 'tests/Pest.php',
                    'description' => 'Pest configuration file',
                    'template' => 'pest_config',
                ];
            } else {
                $files[] = [
                    'name' => 'phpunit.xml',
                    'description' => 'PHPUnit configuration file',
                    'template' => 'phpunit_config',
                ];
            }
        }

        $files[] = [
            'name' => 'tests/bootstrap/bootstrap.php',
            'description' => 'WordPress test bootstrap file',
            'template' => 'bootstrap',
        ];

        $files[] = [
            'name' => 'tests/SampleTest.php',
            'description' => 'Sample test file to get started',
            'template' => 'sample_test',
        ];

        return $files;
    }

    /**
     * Get commands to run
     */
    private function getCommands(array $detection, string $framework): array
    {
        $commands = [];

        if (in_array('composer_json', $detection['missing_components'])) {
            $commands[] = [
                'title' => 'Initialize Composer',
                'command' => 'composer init --no-interaction',
                'description' => 'Creates composer.json file',
            ];
        }

        if (in_array('test_dependencies', $detection['missing_components'])) {
            $dependencies = implode(' ', $this->getTestDependencies($framework));
            $commands[] = [
                'title' => 'Install Dependencies',
                'command' => "composer require --dev {$dependencies}",
                'description' => 'Installs testing framework and utilities',
            ];
        }

        $commands[] = [
            'title' => 'Run Tests',
            'command' => $framework === 'pest' ? './vendor/bin/pest' : './vendor/bin/phpunit',
            'description' => 'Execute your test suite',
        ];

        return $commands;
    }

    /**
     * Get troubleshooting tips
     */
    private function getTroubleshootingTips(): array
    {
        return [
            [
                'issue' => 'Composer command not found',
                'solution' => 'Install Composer from https://getcomposer.org/download/',
                'details' => 'Make sure Composer is in your system PATH',
            ],
            [
                'issue' => 'PHP version too old',
                'solution' => 'Update PHP to version 7.4 or higher',
                'details' => 'Modern testing frameworks require PHP 7.4+',
            ],
            [
                'issue' => 'Tests fail with WordPress function errors',
                'solution' => 'Check that Brain Monkey is properly configured in bootstrap',
                'details' => 'Brain Monkey mocks WordPress functions for testing',
            ],
            [
                'issue' => 'Permission denied errors',
                'solution' => 'Make sure you have write permissions in the plugin directory',
                'details' => 'Use chmod or run commands with appropriate permissions',
            ],
        ];
    }

    /**
     * Get next steps after setup
     */
    private function getNextSteps(string $framework): array
    {
        return [
            'Write tests for your plugin\'s main functionality',
            'Add tests for WordPress hooks and filters',
            'Test database operations and options',
            'Set up continuous integration (CI) with GitHub Actions',
            'Configure code coverage reporting',
            'Add integration tests for complex workflows',
        ];
    }

    /**
     * Get helpful resources
     */
    private function getResources(string $framework): array
    {
        $common = [
            [
                'title' => 'Brain Monkey Documentation',
                'url' => 'https://brain-wp.github.io/BrainMonkey/',
                'description' => 'WordPress function mocking for tests',
            ],
            [
                'title' => 'WordPress Plugin Testing Guide',
                'url' => 'https://developer.wordpress.org/plugins/testing/',
                'description' => 'Official WordPress testing documentation',
            ],
        ];

        if ($framework === 'pest') {
            return array_merge($common, [
                [
                    'title' => 'Pest Documentation',
                    'url' => 'https://pestphp.com/',
                    'description' => 'Official Pest testing framework docs',
                ],
                [
                    'title' => 'Pest WordPress Plugin',
                    'url' => 'https://github.com/pestphp/pest-plugin-wordpress',
                    'description' => 'WordPress-specific Pest utilities',
                ],
            ]);
        }

        return array_merge($common, [
            [
                'title' => 'PHPUnit Documentation',
                'url' => 'https://phpunit.de/documentation.html',
                'description' => 'Official PHPUnit testing framework docs',
            ],
            [
                'title' => 'PHPUnit Polyfills',
                'url' => 'https://github.com/Yoast/PHPUnit-Polyfills',
                'description' => 'Compatibility layer for different PHPUnit versions',
            ],
        ]);
    }
}
