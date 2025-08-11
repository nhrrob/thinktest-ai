<?php

namespace App\Services\WordPress;

use PhpParser\Error;
use PhpParser\Node;
use PhpParser\NodeFinder;
use PhpParser\ParserFactory;
use Illuminate\Support\Facades\Log;

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
                'hooks' => [],
                'filters' => [],
                'ajax_handlers' => [],
                'rest_endpoints' => [],
                'database_operations' => [],
                'security_patterns' => [],
                'test_recommendations' => [],
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
            if (preg_match_all('/\b' . preg_quote($function) . '\s*\(/i', $content, $matches, PREG_OFFSET_CAPTURE)) {
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

        return $analysis;
    }
}
