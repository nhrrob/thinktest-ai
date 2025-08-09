<?php

return [

    /*
    |--------------------------------------------------------------------------
    | ThinkTest AI Configuration
    |--------------------------------------------------------------------------
    |
    | This file contains all global settings, API configurations, and
    | project-specific constants for the ThinkTest AI platform.
    | This serves as the single source of truth for configuration values.
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Application Information
    |--------------------------------------------------------------------------
    */
    'app' => [
        'name' => 'ThinkTest AI',
        'description' => 'Smarter Unit Tests for WordPress Plugins',
        'version' => '1.0.0',
        'environment' => env('APP_ENV', 'local'),
    ],

    /*
    |--------------------------------------------------------------------------
    | AI Integration Configuration
    |--------------------------------------------------------------------------
    */
    'ai' => [
        'providers' => [
            'openai' => [
                'api_key' => env('OPENAI_API_KEY'), // Environment-specific
                'organization' => env('OPENAI_ORGANIZATION'), // Environment-specific
                'model' => 'gpt-4', // Application constant
                'max_tokens' => 4000, // Application constant
                'temperature' => 0.7, // Application constant
                'timeout' => env('AI_TIMEOUT', 60), // May vary by environment
                'wordpress_system_prompt' => 'You are an expert WordPress plugin developer specializing in intelligent PHPUnit test generation. You understand WordPress hooks, filters, actions, plugin patterns, and WordPress testing best practices.',
            ],
            'anthropic' => [
                'api_key' => env('ANTHROPIC_API_KEY'), // Environment-specific
                'model' => 'claude-3-sonnet-20240229', // Application constant
                'max_tokens' => 4000, // Application constant
                'timeout' => env('AI_TIMEOUT', 60), // May vary by environment
                'wordpress_system_prompt' => 'You are an expert WordPress plugin developer specializing in intelligent PHPUnit test generation. You understand WordPress hooks, filters, actions, plugin patterns, and WordPress testing best practices.',
            ],
        ],
        'default_provider' => 'openai', // Application constant
        'fallback_provider' => 'anthropic', // Application constant
        'rate_limits' => [
            'requests_per_minute' => env('AI_RATE_LIMIT_RPM', 60), // Environment-specific
            'tokens_per_minute' => env('AI_RATE_LIMIT_TPM', 100000), // Environment-specific
        ],
        'usage_management' => [
            'monthly_budget' => env('AI_MONTHLY_BUDGET', 1000), // Environment-specific
            'alert_threshold' => 0.8, // Application constant
            'emergency_stop_threshold' => 0.95, // Application constant
        ],
        'wordpress_patterns' => [
            'hooks' => ['add_action', 'add_filter', 'do_action', 'apply_filters'],
            'elementor_widgets' => ['Widget_Base', 'Controls_Manager', 'Group_Control'],
            'wordpress_functions' => ['wp_enqueue_script', 'wp_enqueue_style', 'register_post_type'],
            'test_patterns' => [
                'wp_unittest_case' => 'WP_UnitTestCase',
                'elementor_test_base' => 'Elementor\\Testing\\Elementor_Test_Base',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Plugin Parsing Configuration
    |--------------------------------------------------------------------------
    */
    'plugin_parsing' => [
        'supported_formats' => ['php', 'js', 'css', 'json'], // Application constant
        'max_file_size' => env('PLUGIN_MAX_FILE_SIZE', 10485760), // Environment-specific
        'timeout' => env('PLUGIN_PARSE_TIMEOUT', 300), // Environment-specific
        'security' => [
            'sandbox_enabled' => true, // Application constant
            'allowed_functions' => [
                'file_get_contents', 'json_decode', 'preg_match', 'preg_match_all',
                'str_replace', 'substr', 'strlen', 'strpos', 'explode', 'implode'
            ],
            'blocked_functions' => [
                'exec', 'shell_exec', 'system', 'passthru', 'eval', 'file_put_contents',
                'fopen', 'fwrite', 'unlink', 'rmdir', 'mkdir'
            ],
        ],
        'static_analysis' => [
            'enabled' => true, // Application constant
            'parser' => 'nikic/php-parser',
            'rules' => [
                'detect_hooks' => true,
                'detect_filters' => true,
                'detect_shortcodes' => true,
                'detect_ajax_handlers' => true,
                'detect_rest_endpoints' => true,
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Test Generation Configuration
    |--------------------------------------------------------------------------
    */
    'test_generation' => [
        'frameworks' => [
            'phpunit' => [
                'version' => '^10.0', // Application constant
                'test_case_base' => 'PHPUnit\\Framework\\TestCase',
                'assertions_library' => 'phpunit',
                'wordpress_base' => 'WP_UnitTestCase',
            ],
            'pest' => [
                'version' => '^2.0', // Application constant
                'enabled' => true, // Application constant
                'wordpress_base' => 'uses(WP_UnitTestCase::class)',
            ],
        ],
        'default_framework' => 'phpunit', // Application constant
        'platform_framework' => 'pest', // Application constant - For ThinkTest AI itself
        'output_formats' => [
            'phpunit' => 'PHPUnit XML format',
            'pest' => 'Pest PHP format',
        ],
        'coverage' => [
            'enabled' => env('TEST_COVERAGE_ENABLED', true), // Environment-specific
            'minimum_threshold' => env('TEST_COVERAGE_THRESHOLD', 80), // Environment-specific
            'format' => 'html', // Application constant
        ],
        'quality' => [
            'complexity_threshold' => 10, // Application constant
            'duplication_threshold' => 5, // Application constant
            'maintainability_index' => 70, // Application constant
        ],
        'mock_data' => [
            'wordpress_factories' => [
                'posts' => 'WP_UnitTest_Factory_For_Post',
                'users' => 'WP_UnitTest_Factory_For_User',
                'comments' => 'WP_UnitTest_Factory_For_Comment',
                'terms' => 'WP_UnitTest_Factory_For_Term',
                'attachments' => 'WP_UnitTest_Factory_For_Attachment',
            ],
            'elementor_factories' => [
                'widgets' => 'Elementor\\Testing\\Factories\\Widget_Factory',
                'controls' => 'Elementor\\Testing\\Factories\\Control_Factory',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Queue Configuration
    |--------------------------------------------------------------------------
    |
    | Application-specific queue configurations for different job types.
    | These extend Laravel's base queue configuration.
    |
    */
    'queue' => [
        'queues' => [
            'ai_processing' => [
                'name' => 'ai-processing',
                'timeout' => env('AI_QUEUE_TIMEOUT', 600), // 10 minutes
                'retry_after' => env('AI_QUEUE_RETRY_AFTER', 300), // 5 minutes
                'max_tries' => env('AI_QUEUE_MAX_TRIES', 3),
                'priority' => 'high',
            ],
            'plugin_parsing' => [
                'name' => 'plugin-parsing',
                'timeout' => env('PLUGIN_QUEUE_TIMEOUT', 300), // 5 minutes
                'retry_after' => env('PLUGIN_QUEUE_RETRY_AFTER', 180), // 3 minutes
                'max_tries' => env('PLUGIN_QUEUE_MAX_TRIES', 2),
                'priority' => 'medium',
            ],
            'test_generation' => [
                'name' => 'test-generation',
                'timeout' => env('TEST_QUEUE_TIMEOUT', 900), // 15 minutes
                'retry_after' => env('TEST_QUEUE_RETRY_AFTER', 450), // 7.5 minutes
                'max_tries' => env('TEST_QUEUE_MAX_TRIES', 2),
                'priority' => 'medium',
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Security Configuration Reference
    |--------------------------------------------------------------------------
    |
    | Security settings have been moved to config/security.php for better
    | organization. Access security config using config('security.key').
    |
    */

    /*
    |--------------------------------------------------------------------------
    | Performance Configuration
    |--------------------------------------------------------------------------
    */
    'performance' => [
        'caching' => [
            'enabled' => env('CACHING_ENABLED', true),
            'default_ttl' => env('CACHE_DEFAULT_TTL', 3600), // 1 hour
            'ai_responses_ttl' => env('CACHE_AI_RESPONSES_TTL', 86400), // 24 hours
            'plugin_analysis_ttl' => env('CACHE_PLUGIN_ANALYSIS_TTL', 7200), // 2 hours
        ],
        'database' => [
            'query_timeout' => env('DB_QUERY_TIMEOUT', 30),
            'connection_pool_size' => env('DB_CONNECTION_POOL_SIZE', 10),
            'slow_query_threshold' => env('DB_SLOW_QUERY_THRESHOLD', 1000), // ms
        ],
        'monitoring' => [
            'enabled' => env('MONITORING_ENABLED', true),
            'metrics_retention' => env('METRICS_RETENTION_DAYS', 30),
            'alert_thresholds' => [
                'response_time' => env('ALERT_RESPONSE_TIME_MS', 5000),
                'error_rate' => env('ALERT_ERROR_RATE_PERCENT', 5),
                'memory_usage' => env('ALERT_MEMORY_USAGE_PERCENT', 85),
                'cpu_usage' => env('ALERT_CPU_USAGE_PERCENT', 80),
            ],
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Enterprise Features
    |--------------------------------------------------------------------------
    */
    'enterprise' => [
        'multi_tenancy' => [
            'enabled' => env('MULTI_TENANCY_ENABLED', true),
            'isolation_level' => env('TENANT_ISOLATION_LEVEL', 'database'), // database, schema, or row
        ],
        'compliance' => [
            'gdpr_enabled' => env('GDPR_COMPLIANCE_ENABLED', true),
            'soc2_enabled' => env('SOC2_COMPLIANCE_ENABLED', true),
            'audit_logging' => env('AUDIT_LOGGING_ENABLED', true),
            'data_retention_days' => env('DATA_RETENTION_DAYS', 2555), // 7 years
        ],
        'sla' => [
            'uptime_target' => env('SLA_UPTIME_TARGET', 99.9), // 99.9%
            'response_time_target' => env('SLA_RESPONSE_TIME_TARGET', 2000), // 2 seconds
            'support_hours' => env('SLA_SUPPORT_HOURS', '24/7'),
        ],
    ],

    /*
    |--------------------------------------------------------------------------
    | Frontend Configuration
    |--------------------------------------------------------------------------
    */
    'frontend' => [
        'framework' => env('FRONTEND_FRAMEWORK', 'react'),
        'css_framework' => env('CSS_FRAMEWORK', 'tailwind'),
        'build_tool' => env('BUILD_TOOL', 'vite'),
        'api_base_url' => env('API_BASE_URL', '/api/v1'),
        'upload_max_size' => env('FRONTEND_UPLOAD_MAX_SIZE', '10MB'),
        'supported_file_types' => ['.php', '.js', '.css', '.json'],
    ],

    /*
    |--------------------------------------------------------------------------
    | Database Configuration
    |--------------------------------------------------------------------------
    */
    'database' => [
        'default_connection' => env('DB_CONNECTION', 'mysql'),
        'tenant_isolation' => env('DB_TENANT_ISOLATION', 'database'), // database, schema, row
        'backup_retention_days' => env('DB_BACKUP_RETENTION_DAYS', 30),
        'query_logging' => env('DB_QUERY_LOGGING', false),
        'slow_query_threshold' => env('DB_SLOW_QUERY_THRESHOLD', 1000), // milliseconds
    ],

    /*
    |--------------------------------------------------------------------------
    | GitHub Integration
    |--------------------------------------------------------------------------
    */
    'github' => [
        'enabled' => env('GITHUB_INTEGRATION_ENABLED', true),
        'client_id' => env('GITHUB_CLIENT_ID'),
        'client_secret' => env('GITHUB_CLIENT_SECRET'),
        'webhook_secret' => env('GITHUB_WEBHOOK_SECRET'),
        'default_branch_prefix' => env('GITHUB_BRANCH_PREFIX', 'thinktest-ai'),
        'auto_push_enabled' => env('GITHUB_AUTO_PUSH_ENABLED', true),
        'commit_message_template' => env('GITHUB_COMMIT_MESSAGE', 'Add ThinkTest AI generated tests'),
    ],

    /*
    |--------------------------------------------------------------------------
    | Feature Flags
    |--------------------------------------------------------------------------
    */
    'features' => [
        'ai_conversation_v2' => env('FEATURE_AI_CONVERSATION_V2', false),
        'advanced_plugin_analysis' => env('FEATURE_ADVANCED_PLUGIN_ANALYSIS', true),
        'real_time_collaboration' => env('FEATURE_REAL_TIME_COLLABORATION', false),
        'automated_test_execution' => env('FEATURE_AUTOMATED_TEST_EXECUTION', true),
        'integration_testing' => env('FEATURE_INTEGRATION_TESTING', false),
        'performance_profiling' => env('FEATURE_PERFORMANCE_PROFILING', true),
        'github_integration' => env('FEATURE_GITHUB_INTEGRATION', true),
        'pest_output_format' => env('FEATURE_PEST_OUTPUT_FORMAT', false),
    ],

];
