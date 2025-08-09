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
            Log::error('PHP parsing error', [
                'filename' => $filename,
                'error' => $e->getMessage(),
            ]);
            
            throw new \RuntimeException("PHP parsing failed: " . $e->getMessage());
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
}
