<?php

namespace App\Services\WordPress;

use Illuminate\Support\Facades\Log;
use PhpParser\Error;
use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;

class PluginAnalysisService
{
    private $parser;

    private $nodeFinder;

    public function __construct()
    {
        $this->parser = (new ParserFactory)->createForNewestSupportedVersion();
        $this->nodeFinder = new NodeFinder;
    }

    /**
     * Analyze WordPress plugin code and extract patterns
     */
    public function analyzePlugin(string $pluginCode, string $filename = 'plugin.php'): array
    {
        try {
            // If this is a multi-file content (from GitHub repository),
            // try to analyze it as separate files
            if (str_contains($pluginCode, '// File: ') && str_contains($filename, '@')) {
                return $this->analyzeMultiFileContent($pluginCode, $filename);
            }

            $ast = $this->parser->parse($pluginCode);

            if ($ast === null) {
                throw new \RuntimeException('Failed to parse PHP code');
            }

            $analysis = [
                'filename' => $filename,
                'wordpress_patterns' => $this->detectWordPressPatterns($ast),
                'functions' => $this->extractFunctions($ast),
                'classes' => $this->extractClasses($ast),
                'hooks' => $this->extractHooks($ast),
                'filters' => $this->extractFilters($ast),
                'ajax_handlers' => $this->extractAjaxHandlers($ast),
                'rest_endpoints' => $this->extractRestEndpoints($ast),
                'database_operations' => $this->extractDatabaseOperations($ast),
                'security_patterns' => $this->extractSecurityPatterns($ast),
                'test_recommendations' => $this->generateTestRecommendations($ast),
            ];

            return $analysis;

        } catch (Error $e) {
            Log::warning('PHP parsing error, attempting fallback analysis', [
                'filename' => $filename,
                'error' => $e->getMessage(),
                'line' => $e->getStartLine() ?? 'unknown',
            ]);

            // Fallback to regex-based analysis for files with syntax errors
            return $this->performFallbackAnalysis($pluginCode, $filename, $e->getMessage());
        }
    }

    private function detectWordPressPatterns(array $ast): array
    {
        $patterns = [];
        $wpHooks = ['add_action', 'add_filter', 'do_action', 'apply_filters'];

        $functionCalls = $this->nodeFinder->findInstanceOf($ast, Node\Expr\FuncCall::class);

        foreach ($functionCalls as $call) {
            if ($call->name instanceof \PhpParser\Node\Name) {
                $functionName = $call->name->toString();

                if (in_array($functionName, $wpHooks)) {
                    $patterns[] = [
                        'type' => 'hook',
                        'function' => $functionName,
                        'line' => $call->getStartLine(),
                    ];
                }
            }
        }

        return $patterns;
    }

    private function extractFunctions(array $ast): array
    {
        $functions = [];
        $functionNodes = $this->nodeFinder->findInstanceOf($ast, Node\Stmt\Function_::class);

        foreach ($functionNodes as $function) {
            $functions[] = [
                'name' => $function->name->toString(),
                'line' => $function->getStartLine(),
            ];
        }

        return $functions;
    }

    private function extractClasses(array $ast): array
    {
        $classes = [];
        $classNodes = $this->nodeFinder->findInstanceOf($ast, Node\Stmt\Class_::class);

        foreach ($classNodes as $class) {
            $classes[] = [
                'name' => $class->name->toString(),
                'line' => $class->getStartLine(),
            ];
        }

        return $classes;
    }

    /**
     * Analyze multi-file content from GitHub repositories
     */
    private function analyzeMultiFileContent(string $content, string $filename): array
    {
        $analysis = [
            'filename' => $filename,
            'wordpress_patterns' => [],
            'functions' => [],
            'classes' => [],
            'hooks' => [],
            'filters' => [],
            'ajax_handlers' => [],
            'rest_endpoints' => [],
            'database_operations' => [],
            'security_patterns' => [],
            'test_recommendations' => [],
            'parsed_files' => 0,
            'failed_files' => 0,
        ];

        // Split content by file separators
        $files = preg_split('/\n\n\/\/ File: (.+?)\n/', $content, -1, PREG_SPLIT_DELIM_CAPTURE);

        for ($i = 1; $i < count($files); $i += 2) {
            $filePath = $files[$i];
            $fileContent = $files[$i + 1] ?? '';

            if (empty(trim($fileContent))) {
                continue;
            }

            try {
                // Try to parse individual file
                $ast = $this->parser->parse($fileContent);

                if ($ast !== null) {
                    // Merge analysis results
                    $fileAnalysis = [
                        'wordpress_patterns' => $this->detectWordPressPatterns($ast),
                        'functions' => $this->extractFunctions($ast),
                        'classes' => $this->extractClasses($ast),
                    ];

                    $analysis['wordpress_patterns'] = array_merge($analysis['wordpress_patterns'], $fileAnalysis['wordpress_patterns']);
                    $analysis['functions'] = array_merge($analysis['functions'], $fileAnalysis['functions']);
                    $analysis['classes'] = array_merge($analysis['classes'], $fileAnalysis['classes']);
                    $analysis['parsed_files']++;
                }
            } catch (Error $e) {
                Log::debug('Skipping file with syntax error', [
                    'file' => $filePath,
                    'error' => $e->getMessage(),
                    'line' => $e->getStartLine() ?? 'unknown',
                ]);

                // Try regex-based analysis for this file
                $regexAnalysis = $this->performRegexAnalysis($fileContent, $filePath);
                $analysis['wordpress_patterns'] = array_merge($analysis['wordpress_patterns'], $regexAnalysis['wordpress_patterns']);
                $analysis['failed_files']++;
            }
        }

        Log::info('Multi-file analysis completed', [
            'filename' => $filename,
            'parsed_files' => $analysis['parsed_files'],
            'failed_files' => $analysis['failed_files'],
            'total_patterns' => count($analysis['wordpress_patterns']),
            'total_functions' => count($analysis['functions']),
            'total_classes' => count($analysis['classes']),
        ]);

        return $analysis;
    }

    /**
     * Perform fallback analysis when PHP parsing fails
     */
    private function performFallbackAnalysis(string $content, string $filename, string $error): array
    {
        Log::info('Performing fallback regex-based analysis', [
            'filename' => $filename,
            'parse_error' => $error,
        ]);

        return $this->performRegexAnalysis($content, $filename);
    }

    /**
     * Perform regex-based analysis for files with syntax errors
     */
    private function performRegexAnalysis(string $content, string $filename): array
    {
        $analysis = [
            'filename' => $filename,
            'wordpress_patterns' => [],
            'functions' => [],
            'classes' => [],
            'hooks' => [],
            'filters' => [],
            'ajax_handlers' => [],
            'rest_endpoints' => [],
            'database_operations' => [],
            'security_patterns' => [],
            'test_recommendations' => [],
            'analysis_method' => 'regex_fallback',
        ];

        // Detect WordPress patterns using regex
        $wpFunctions = ['add_action', 'add_filter', 'do_action', 'apply_filters', 'wp_enqueue_script', 'wp_enqueue_style'];

        foreach ($wpFunctions as $function) {
            if (preg_match_all('/\b'.preg_quote($function).'\s*\(/i', $content, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $line = substr_count(substr($content, 0, $match[1]), "\n") + 1;
                    $analysis['wordpress_patterns'][] = [
                        'type' => 'hook',
                        'function' => $function,
                        'line' => $line,
                    ];
                }
            }
        }

        // Extract function names using regex
        if (preg_match_all('/function\s+([a-zA-Z_][a-zA-Z0-9_]*)\s*\(/i', $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[1] as $match) {
                $line = substr_count(substr($content, 0, $match[1]), "\n") + 1;
                $analysis['functions'][] = [
                    'name' => $match[0],
                    'line' => $line,
                ];
            }
        }

        // Extract class names using regex
        if (preg_match_all('/class\s+([a-zA-Z_][a-zA-Z0-9_]*)/i', $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[1] as $match) {
                $line = substr_count(substr($content, 0, $match[1]), "\n") + 1;
                $analysis['classes'][] = [
                    'name' => $match[0],
                    'line' => $line,
                ];
            }
        }

        // Extract hooks using regex
        if (preg_match_all('/add_action\s*\(\s*[\'"]([^\'"]+)[\'"]/', $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[1] as $match) {
                $line = substr_count(substr($content, 0, $match[1]), "\n") + 1;
                $analysis['hooks'][] = [
                    'name' => $match[0],
                    'line' => $line,
                    'callback' => 'unknown',
                    'priority' => 10,
                ];
            }
        }

        // Extract filters using regex
        if (preg_match_all('/add_filter\s*\(\s*[\'"]([^\'"]+)[\'"]/', $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[1] as $match) {
                $line = substr_count(substr($content, 0, $match[1]), "\n") + 1;
                $analysis['filters'][] = [
                    'name' => $match[0],
                    'line' => $line,
                    'callback' => 'unknown',
                    'priority' => 10,
                ];
            }
        }

        // Extract AJAX handlers using regex
        if (preg_match_all('/add_action\s*\(\s*[\'"]wp_ajax_([^\'"]+)[\'"]/', $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[1] as $match) {
                $line = substr_count(substr($content, 0, $match[1]), "\n") + 1;
                $analysis['ajax_handlers'][] = [
                    'action' => $match[0],
                    'hook' => 'wp_ajax_' . $match[0],
                    'line' => $line,
                    'callback' => 'unknown',
                    'is_public' => false,
                ];
            }
        }

        // Extract REST endpoints using regex
        if (preg_match_all('/register_rest_route\s*\(\s*[\'"]([^\'"]+)[\'"]/', $content, $matches, PREG_OFFSET_CAPTURE)) {
            foreach ($matches[1] as $match) {
                $line = substr_count(substr($content, 0, $match[1]), "\n") + 1;
                $analysis['rest_endpoints'][] = [
                    'namespace' => $match[0],
                    'route' => 'unknown',
                    'line' => $line,
                    'methods' => ['GET'],
                ];
            }
        }

        // Extract database operations using regex
        $dbFunctions = ['get_option', 'update_option', 'delete_option', 'wp_insert_post', 'wp_update_post'];
        foreach ($dbFunctions as $function) {
            if (preg_match_all('/\b'.preg_quote($function).'\s*\(/i', $content, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $line = substr_count(substr($content, 0, $match[1]), "\n") + 1;
                    $analysis['database_operations'][] = [
                        'type' => $function,
                        'line' => $line,
                        'category' => $this->categorizeDbOperation($function),
                    ];
                }
            }
        }

        // Extract security patterns using regex
        $securityFunctions = ['wp_verify_nonce', 'sanitize_text_field', 'esc_html', 'current_user_can'];
        foreach ($securityFunctions as $function) {
            if (preg_match_all('/\b'.preg_quote($function).'\s*\(/i', $content, $matches, PREG_OFFSET_CAPTURE)) {
                foreach ($matches[0] as $match) {
                    $line = substr_count(substr($content, 0, $match[1]), "\n") + 1;
                    $analysis['security_patterns'][] = [
                        'type' => $function,
                        'line' => $line,
                        'category' => $this->categorizeSecurityFunction($function),
                    ];
                }
            }
        }

        // Generate basic test recommendations
        $analysis['test_recommendations'] = [
            [
                'type' => 'unit_tests',
                'description' => 'Create unit tests for detected functions',
                'priority' => 'high',
            ],
            [
                'type' => 'integration_tests',
                'description' => 'Create integration tests for WordPress hooks',
                'priority' => 'medium',
            ],
        ];

        return $analysis;
    }

    /**
     * Extract WordPress hooks (add_action calls)
     */
    private function extractHooks(array $ast): array
    {
        $hooks = [];
        $functionCalls = $this->nodeFinder->findInstanceOf($ast, Node\Expr\FuncCall::class);

        foreach ($functionCalls as $call) {
            if ($call->name instanceof Node\Name && $call->name->toString() === 'add_action') {
                if (isset($call->args[0]) && $call->args[0]->value instanceof Node\Scalar\String_) {
                    $hookName = $call->args[0]->value->value;
                    $priority = 10; // default priority
                    $callback = 'unknown';

                    // Extract callback function name if available
                    if (isset($call->args[1])) {
                        if ($call->args[1]->value instanceof Node\Scalar\String_) {
                            $callback = $call->args[1]->value->value;
                        } elseif ($call->args[1]->value instanceof Node\Expr\Array_) {
                            $callback = 'array_callback';
                        }
                    }

                    // Extract priority if available
                    if (isset($call->args[2]) && $call->args[2]->value instanceof Node\Scalar\LNumber) {
                        $priority = $call->args[2]->value->value;
                    }

                    $hooks[] = [
                        'name' => $hookName,
                        'callback' => $callback,
                        'priority' => $priority,
                        'line' => $call->getStartLine(),
                    ];
                }
            }
        }

        return $hooks;
    }

    /**
     * Extract WordPress filters (add_filter calls)
     */
    private function extractFilters(array $ast): array
    {
        $filters = [];
        $functionCalls = $this->nodeFinder->findInstanceOf($ast, Node\Expr\FuncCall::class);

        foreach ($functionCalls as $call) {
            if ($call->name instanceof Node\Name && $call->name->toString() === 'add_filter') {
                if (isset($call->args[0]) && $call->args[0]->value instanceof Node\Scalar\String_) {
                    $filterName = $call->args[0]->value->value;
                    $priority = 10; // default priority
                    $callback = 'unknown';

                    // Extract callback function name if available
                    if (isset($call->args[1])) {
                        if ($call->args[1]->value instanceof Node\Scalar\String_) {
                            $callback = $call->args[1]->value->value;
                        } elseif ($call->args[1]->value instanceof Node\Expr\Array_) {
                            $callback = 'array_callback';
                        }
                    }

                    // Extract priority if available
                    if (isset($call->args[2]) && $call->args[2]->value instanceof Node\Scalar\LNumber) {
                        $priority = $call->args[2]->value->value;
                    }

                    $filters[] = [
                        'name' => $filterName,
                        'callback' => $callback,
                        'priority' => $priority,
                        'line' => $call->getStartLine(),
                    ];
                }
            }
        }

        return $filters;
    }

    /**
     * Extract AJAX handlers
     */
    private function extractAjaxHandlers(array $ast): array
    {
        $ajaxHandlers = [];
        $functionCalls = $this->nodeFinder->findInstanceOf($ast, Node\Expr\FuncCall::class);

        foreach ($functionCalls as $call) {
            if ($call->name instanceof Node\Name && $call->name->toString() === 'add_action') {
                if (isset($call->args[0]) && $call->args[0]->value instanceof Node\Scalar\String_) {
                    $hookName = $call->args[0]->value->value;

                    // Check if it's an AJAX hook
                    if (strpos($hookName, 'wp_ajax_') === 0) {
                        $actionName = str_replace('wp_ajax_', '', $hookName);
                        $callback = 'unknown';

                        // Extract callback function name if available
                        if (isset($call->args[1]) && $call->args[1]->value instanceof Node\Scalar\String_) {
                            $callback = $call->args[1]->value->value;
                        }

                        $ajaxHandlers[] = [
                            'action' => $actionName,
                            'hook' => $hookName,
                            'callback' => $callback,
                            'line' => $call->getStartLine(),
                            'is_public' => strpos($hookName, 'wp_ajax_nopriv_') === 0,
                        ];
                    }
                }
            }
        }

        return $ajaxHandlers;
    }

    /**
     * Extract REST API endpoints
     */
    private function extractRestEndpoints(array $ast): array
    {
        $endpoints = [];
        $functionCalls = $this->nodeFinder->findInstanceOf($ast, Node\Expr\FuncCall::class);

        foreach ($functionCalls as $call) {
            if ($call->name instanceof Node\Name && $call->name->toString() === 'register_rest_route') {
                $namespace = 'unknown';
                $route = 'unknown';
                $methods = ['GET'];

                // Extract namespace
                if (isset($call->args[0]) && $call->args[0]->value instanceof Node\Scalar\String_) {
                    $namespace = $call->args[0]->value->value;
                }

                // Extract route
                if (isset($call->args[1]) && $call->args[1]->value instanceof Node\Scalar\String_) {
                    $route = $call->args[1]->value->value;
                }

                $endpoints[] = [
                    'namespace' => $namespace,
                    'route' => $route,
                    'methods' => $methods,
                    'line' => $call->getStartLine(),
                ];
            }
        }

        return $endpoints;
    }

    /**
     * Extract database operations
     */
    private function extractDatabaseOperations(array $ast): array
    {
        $operations = [];
        $functionCalls = $this->nodeFinder->findInstanceOf($ast, Node\Expr\FuncCall::class);
        $dbFunctions = [
            'get_option', 'update_option', 'delete_option', 'add_option',
            'get_post_meta', 'update_post_meta', 'delete_post_meta', 'add_post_meta',
            'get_user_meta', 'update_user_meta', 'delete_user_meta', 'add_user_meta',
            'wp_insert_post', 'wp_update_post', 'wp_delete_post',
            'wp_insert_user', 'wp_update_user', 'wp_delete_user',
        ];

        foreach ($functionCalls as $call) {
            if ($call->name instanceof Node\Name) {
                $functionName = $call->name->toString();

                if (in_array($functionName, $dbFunctions)) {
                    $operations[] = [
                        'type' => $functionName,
                        'line' => $call->getStartLine(),
                        'category' => $this->categorizeDbOperation($functionName),
                    ];
                }
            }
        }

        // Also check for direct $wpdb usage
        $variables = $this->nodeFinder->findInstanceOf($ast, Node\Expr\Variable::class);
        foreach ($variables as $variable) {
            if ($variable->name === 'wpdb') {
                $operations[] = [
                    'type' => 'wpdb_direct',
                    'line' => $variable->getStartLine(),
                    'category' => 'direct_database',
                ];
            }
        }

        return $operations;
    }

    /**
     * Categorize database operation
     */
    private function categorizeDbOperation(string $functionName): string
    {
        if (strpos($functionName, 'option') !== false) {
            return 'options';
        } elseif (strpos($functionName, 'post') !== false) {
            return 'posts';
        } elseif (strpos($functionName, 'user') !== false) {
            return 'users';
        } elseif (strpos($functionName, 'meta') !== false) {
            return 'metadata';
        }

        return 'general';
    }

    /**
     * Extract security patterns
     */
    private function extractSecurityPatterns(array $ast): array
    {
        $patterns = [];
        $functionCalls = $this->nodeFinder->findInstanceOf($ast, Node\Expr\FuncCall::class);
        $securityFunctions = [
            'wp_verify_nonce', 'wp_create_nonce', 'check_admin_referer',
            'sanitize_text_field', 'sanitize_email', 'sanitize_url', 'esc_html', 'esc_attr', 'esc_url',
            'wp_kses', 'wp_kses_post', 'current_user_can', 'is_admin', 'is_user_logged_in',
        ];

        foreach ($functionCalls as $call) {
            if ($call->name instanceof Node\Name) {
                $functionName = $call->name->toString();

                if (in_array($functionName, $securityFunctions)) {
                    $patterns[] = [
                        'type' => $functionName,
                        'line' => $call->getStartLine(),
                        'category' => $this->categorizeSecurityFunction($functionName),
                    ];
                }
            }
        }

        return $patterns;
    }

    /**
     * Categorize security function
     */
    private function categorizeSecurityFunction(string $functionName): string
    {
        if (strpos($functionName, 'nonce') !== false || strpos($functionName, 'referer') !== false) {
            return 'nonce_verification';
        } elseif (strpos($functionName, 'sanitize') !== false) {
            return 'data_sanitization';
        } elseif (strpos($functionName, 'esc_') !== false || strpos($functionName, 'kses') !== false) {
            return 'output_escaping';
        } elseif (strpos($functionName, 'can') !== false || strpos($functionName, 'admin') !== false || strpos($functionName, 'logged') !== false) {
            return 'authorization';
        }

        return 'general_security';
    }

    /**
     * Generate test recommendations based on analysis
     */
    private function generateTestRecommendations(array $ast): array
    {
        $recommendations = [];

        // Basic recommendations
        $recommendations[] = [
            'type' => 'unit_tests',
            'description' => 'Create unit tests for all public functions',
            'priority' => 'high',
        ];

        $recommendations[] = [
            'type' => 'integration_tests',
            'description' => 'Create integration tests for WordPress hooks and filters',
            'priority' => 'medium',
        ];

        // Check for specific patterns and add targeted recommendations
        $functionCalls = $this->nodeFinder->findInstanceOf($ast, Node\Expr\FuncCall::class);
        $hasAjax = false;
        $hasRestApi = false;
        $hasDatabase = false;

        foreach ($functionCalls as $call) {
            if ($call->name instanceof Node\Name) {
                $functionName = $call->name->toString();

                if (strpos($functionName, 'wp_ajax') !== false) {
                    $hasAjax = true;
                } elseif ($functionName === 'register_rest_route') {
                    $hasRestApi = true;
                } elseif (in_array($functionName, ['get_option', 'update_option', 'wp_insert_post'])) {
                    $hasDatabase = true;
                }
            }
        }

        if ($hasAjax) {
            $recommendations[] = [
                'type' => 'ajax_tests',
                'description' => 'Create tests for AJAX handlers with proper nonce verification',
                'priority' => 'high',
            ];
        }

        if ($hasRestApi) {
            $recommendations[] = [
                'type' => 'rest_api_tests',
                'description' => 'Create tests for REST API endpoints including authentication',
                'priority' => 'high',
            ];
        }

        if ($hasDatabase) {
            $recommendations[] = [
                'type' => 'database_tests',
                'description' => 'Create tests for database operations with proper cleanup',
                'priority' => 'medium',
            ];
        }

        return $recommendations;
    }
}
