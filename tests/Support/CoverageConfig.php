<?php

namespace Tests\Support;

class CoverageConfig
{
    /**
     * Minimum coverage requirements by component
     */
    public const COVERAGE_REQUIREMENTS = [
        // Overall project minimum
        'overall' => 80,
        
        // Critical components requiring higher coverage
        'critical' => [
            'app/Services/AI' => 90,
            'app/Services/CreditService.php' => 90,
            'app/Services/StripePaymentService.php' => 90,
            'app/Services/GitHub' => 85,
            'app/Services/TestGeneration' => 90,
            'app/Http/Controllers/PaymentController.php' => 85,
            'app/Http/Controllers/ThinkTestController.php' => 85,
            'app/Models' => 80,
        ],
        
        // Standard coverage requirements
        'standard' => [
            'app/Http/Controllers' => 85,
            'app/Services' => 90,
            'app/Models' => 80,
            'app/Http/Middleware' => 75,
        ],
        
        // Lower priority components
        'low_priority' => [
            'app/Console' => 60,
            'app/Providers' => 50,
        ],
    ];

    /**
     * Files and directories to exclude from coverage
     */
    public const COVERAGE_EXCLUSIONS = [
        'app/Console/Kernel.php',
        'app/Http/Kernel.php',
        'app/Providers/RouteServiceProvider.php',
        'app/Exceptions/Handler.php',
        'bootstrap/',
        'config/',
        'database/migrations/',
        'database/seeders/',
        'public/',
        'resources/',
        'routes/',
        'storage/',
        'vendor/',
        'tests/',
    ];

    /**
     * Test categories and their execution time limits (in milliseconds)
     */
    public const PERFORMANCE_LIMITS = [
        'unit' => 100,        // Unit tests should complete in < 100ms
        'integration' => 500, // Integration tests should complete in < 500ms
        'feature' => 2000,    // Feature tests should complete in < 2s
        'browser' => 10000,   // Browser tests should complete in < 10s
    ];

    /**
     * Test quality metrics
     */
    public const QUALITY_METRICS = [
        'max_assertions_per_test' => 10,
        'max_test_method_length' => 50, // lines
        'max_setup_complexity' => 20,   // cyclomatic complexity
        'min_test_description_length' => 10, // characters
    ];

    /**
     * Get coverage requirement for a specific file or directory
     */
    public static function getCoverageRequirement(string $path): int
    {
        // Check critical components first
        foreach (self::COVERAGE_REQUIREMENTS['critical'] as $criticalPath => $requirement) {
            if (str_starts_with($path, $criticalPath)) {
                return $requirement;
            }
        }

        // Check standard components
        foreach (self::COVERAGE_REQUIREMENTS['standard'] as $standardPath => $requirement) {
            if (str_starts_with($path, $standardPath)) {
                return $requirement;
            }
        }

        // Check low priority components
        foreach (self::COVERAGE_REQUIREMENTS['low_priority'] as $lowPriorityPath => $requirement) {
            if (str_starts_with($path, $lowPriorityPath)) {
                return $requirement;
            }
        }

        // Default to overall requirement
        return self::COVERAGE_REQUIREMENTS['overall'];
    }

    /**
     * Check if a file should be excluded from coverage
     */
    public static function shouldExcludeFromCoverage(string $path): bool
    {
        foreach (self::COVERAGE_EXCLUSIONS as $exclusion) {
            if (str_starts_with($path, $exclusion) || str_contains($path, $exclusion)) {
                return true;
            }
        }

        return false;
    }

    /**
     * Get performance limit for a test category
     */
    public static function getPerformanceLimit(string $category): int
    {
        return self::PERFORMANCE_LIMITS[$category] ?? self::PERFORMANCE_LIMITS['feature'];
    }

    /**
     * Validate test quality metrics
     */
    public static function validateTestQuality(array $testMetrics): array
    {
        $violations = [];

        if ($testMetrics['assertions'] > self::QUALITY_METRICS['max_assertions_per_test']) {
            $violations[] = "Too many assertions ({$testMetrics['assertions']}). Maximum: " . self::QUALITY_METRICS['max_assertions_per_test'];
        }

        if ($testMetrics['method_length'] > self::QUALITY_METRICS['max_test_method_length']) {
            $violations[] = "Test method too long ({$testMetrics['method_length']} lines). Maximum: " . self::QUALITY_METRICS['max_test_method_length'];
        }

        if ($testMetrics['setup_complexity'] > self::QUALITY_METRICS['max_setup_complexity']) {
            $violations[] = "Test setup too complex ({$testMetrics['setup_complexity']}). Maximum: " . self::QUALITY_METRICS['max_setup_complexity'];
        }

        if (strlen($testMetrics['description']) < self::QUALITY_METRICS['min_test_description_length']) {
            $violations[] = "Test description too short. Minimum: " . self::QUALITY_METRICS['min_test_description_length'] . " characters";
        }

        return $violations;
    }

    /**
     * Get coverage report configuration
     */
    public static function getCoverageReportConfig(): array
    {
        return [
            'html' => [
                'output_directory' => 'coverage-report',
                'low_upper_bound' => 50,
                'high_lower_bound' => 80,
            ],
            'text' => [
                'output_file' => 'coverage.txt',
                'show_uncovered_files' => false,
                'show_only_summary' => true,
            ],
            'clover' => [
                'output_file' => 'coverage.xml',
            ],
            'cobertura' => [
                'output_file' => 'cobertura.xml',
            ],
        ];
    }

    /**
     * Get test execution configuration
     */
    public static function getTestExecutionConfig(): array
    {
        return [
            'parallel' => [
                'enabled' => true,
                'processes' => 4,
            ],
            'memory_limit' => '512M',
            'time_limit' => 300, // 5 minutes
            'stop_on_failure' => false,
            'stop_on_error' => false,
            'stop_on_warning' => false,
        ];
    }

    /**
     * Get test categories configuration
     */
    public static function getTestCategories(): array
    {
        return [
            'unit' => [
                'pattern' => 'tests/Unit/**/*Test.php',
                'description' => 'Unit tests for individual classes and methods',
                'coverage_requirement' => 90,
            ],
            'integration' => [
                'pattern' => 'tests/Feature/**/*Test.php',
                'description' => 'Integration tests for component interactions',
                'coverage_requirement' => 85,
            ],
            'feature' => [
                'pattern' => 'tests/Feature/**/*Test.php',
                'description' => 'Feature tests for complete workflows',
                'coverage_requirement' => 80,
            ],
            'browser' => [
                'pattern' => 'tests/Browser/**/*Test.php',
                'description' => 'End-to-end browser tests',
                'coverage_requirement' => 70,
            ],
        ];
    }

    /**
     * Get CI/CD configuration for testing
     */
    public static function getCiCdConfig(): array
    {
        return [
            'required_checks' => [
                'unit_tests' => true,
                'integration_tests' => true,
                'coverage_threshold' => true,
                'code_style' => true,
                'static_analysis' => true,
            ],
            'coverage_threshold' => self::COVERAGE_REQUIREMENTS['overall'],
            'fail_on_coverage_decrease' => true,
            'coverage_diff_threshold' => 5, // Fail if coverage decreases by more than 5%
            'test_timeout' => 600, // 10 minutes
        ];
    }
}
