<?php

namespace Tests\Feature;

use App\Models\User;
use App\Services\AI\AIProviderService;
use App\Services\WordPress\PluginAnalysisService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ThinkTestBasicTest extends TestCase
{
    use RefreshDatabase;

    public function test_config_loads_properly(): void
    {
        $config = config('thinktest_ai');
        $this->assertIsArray($config);
        $this->assertArrayHasKey('ai', $config);
    }

    public function test_plugin_analysis_service_works(): void
    {
        $service = new PluginAnalysisService();
        
        $simplePlugin = '<?php
        function my_plugin_init() {
            add_action("init", "my_plugin_setup");
        }
        
        function my_plugin_setup() {
            wp_enqueue_script("my-script", "script.js");
        }';
        
        $analysis = $service->analyzePlugin($simplePlugin, 'test-plugin.php');
        
        $this->assertIsArray($analysis);
        $this->assertArrayHasKey('functions', $analysis);
        $this->assertArrayHasKey('wordpress_patterns', $analysis);
        $this->assertGreaterThan(0, count($analysis['functions']));
    }

    public function test_ai_provider_service_mock_works(): void
    {
        $service = new AIProviderService();
        
        $simplePlugin = '<?php
        function test_function() {
            return "test";
        }';
        
        // This should use the mock provider since no API keys are configured
        $result = $service->generateWordPressTests($simplePlugin);
        
        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        $this->assertEquals('mock', $result['provider']);
        $this->assertNotEmpty($result['generated_tests']);
    }

    public function test_thinktest_routes_require_auth(): void
    {
        $response = $this->get('/thinktest');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_access_thinktest(): void
    {
        $user = User::factory()->create();
        
        $response = $this->actingAs($user)->get('/thinktest');
        $response->assertStatus(200);
    }
}
