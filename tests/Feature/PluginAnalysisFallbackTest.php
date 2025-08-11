<?php

namespace Tests\Feature;

use App\Services\WordPress\PluginAnalysisService;
use Tests\TestCase;

class PluginAnalysisFallbackTest extends TestCase
{
    private PluginAnalysisService $service;

    protected function setUp(): void
    {
        parent::setUp();
        $this->service = new PluginAnalysisService();
    }

    public function test_fallback_analysis_handles_syntax_errors(): void
    {
        // PHP code with clear syntax error
        $badCode = '<?php
        $var = ; // Clear syntax error
        add_action("init", "test_init");
        wp_enqueue_script("test-script");';

        $result = $this->service->analyzePlugin($badCode, 'broken-plugin.php');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('analysis_method', $result);
        $this->assertEquals('regex_fallback', $result['analysis_method']);
        $this->assertArrayHasKey('wordpress_patterns', $result);
        $this->assertGreaterThan(0, count($result['wordpress_patterns']));

        // Should find WordPress functions even with syntax errors
        $foundFunctions = array_column($result['wordpress_patterns'], 'function');
        $this->assertContains('add_action', $foundFunctions);
        $this->assertContains('wp_enqueue_script', $foundFunctions);
    }

    public function test_multi_file_analysis_with_mixed_valid_invalid_files(): void
    {
        // Multi-file content with one valid and one invalid file
        $multiFileContent = '

// File: valid-plugin.php
<?php
/*
Plugin Name: Valid Plugin
*/
add_action("init", "valid_init");
function valid_init() {
    wp_enqueue_style("valid-style");
}

// File: broken-plugin.php
<?php
function broken_function() {
    $var = ; // Syntax error
    add_filter("the_content", "broken_filter");
}';

        $result = $this->service->analyzePlugin($multiFileContent, 'test-repo@main');

        $this->assertIsArray($result);
        $this->assertArrayHasKey('parsed_files', $result);
        $this->assertArrayHasKey('failed_files', $result);
        $this->assertArrayHasKey('wordpress_patterns', $result);
        
        // Should have processed at least one file successfully and one with fallback
        $this->assertGreaterThanOrEqual(1, $result['parsed_files'] + $result['failed_files']);
        
        // Should still find WordPress patterns from both files
        $this->assertGreaterThan(0, count($result['wordpress_patterns']));
        
        $foundFunctions = array_column($result['wordpress_patterns'], 'function');
        $this->assertContains('add_action', $foundFunctions);
    }

    public function test_normal_analysis_still_works_for_valid_code(): void
    {
        $validCode = '<?php
        /*
        Plugin Name: Test Plugin
        */
        add_action("init", "test_init");
        
        function test_init() {
            wp_enqueue_script("test-script");
        }
        
        class TestClass {
            public function test_method() {
                add_filter("the_content", array($this, "filter_content"));
            }
        }';

        $result = $this->service->analyzePlugin($validCode, 'valid-plugin.php');

        $this->assertIsArray($result);
        $this->assertArrayNotHasKey('analysis_method', $result); // Normal analysis doesn't set this
        $this->assertArrayHasKey('wordpress_patterns', $result);
        $this->assertArrayHasKey('functions', $result);
        $this->assertArrayHasKey('classes', $result);
        
        $this->assertGreaterThan(0, count($result['wordpress_patterns']));
        $this->assertGreaterThan(0, count($result['functions']));
        $this->assertGreaterThan(0, count($result['classes']));
    }
}
