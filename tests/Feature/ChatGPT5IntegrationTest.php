<?php

namespace Tests\Feature;

use App\Services\AI\AIProviderService;
use App\Services\TestGeneration\TestGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatGPT5IntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_chatgpt5_config_exists(): void
    {
        $config = config('thinktest_ai.ai.providers.chatgpt-5');
        
        $this->assertIsArray($config);
        $this->assertArrayHasKey('model', $config);
        $this->assertEquals('gpt-5', $config['model']);
        $this->assertArrayHasKey('wordpress_system_prompt', $config);
        $this->assertStringContainsString('advanced reasoning capabilities', $config['wordpress_system_prompt']);
    }

    public function test_ai_provider_service_supports_chatgpt5(): void
    {
        $service = new AIProviderService();
        
        $simplePlugin = '<?php
        function test_function() {
            return "test";
        }';
        
        // Test with ChatGPT-5 provider (should use mock since no API key)
        $result = $service->generateWordPressTests($simplePlugin, ['provider' => 'chatgpt-5']);
        
        $this->assertIsArray($result);
        $this->assertTrue($result['success']);
        // Should fall back to mock provider since no API key is configured
        $this->assertEquals('mock', $result['provider']);
        $this->assertNotEmpty($result['generated_tests']);
    }

    public function test_test_generation_service_validates_chatgpt5(): void
    {
        $service = new TestGenerationService(
            app(\App\Services\AI\AIProviderService::class),
            app(\App\Services\WordPress\PluginAnalysisService::class)
        );
        
        // Test valid provider
        $errors = $service->validateOptions(['provider' => 'chatgpt-5']);
        $this->assertEmpty($errors);
        
        // Test invalid provider
        $errors = $service->validateOptions(['provider' => 'invalid-provider']);
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Unsupported AI provider', $errors[0]);
    }

    public function test_available_providers_includes_chatgpt5(): void
    {
        $service = new AIProviderService();
        $providers = $service->getAvailableProviders();
        
        $this->assertIsArray($providers);
        $this->assertArrayHasKey('chatgpt-5', $providers);
        $this->assertEquals('chatgpt-5', $providers['chatgpt-5']['name']);
        $this->assertEquals('gpt-5', $providers['chatgpt-5']['model']);
        $this->assertIsBool($providers['chatgpt-5']['available']);
    }

    public function test_chatgpt5_provider_validation_in_switch_statement(): void
    {
        $service = new AIProviderService();
        
        // Use reflection to test the private callProvider method
        $reflection = new \ReflectionClass($service);
        $method = $reflection->getMethod('callProvider');
        $method->setAccessible(true);
        
        $simplePlugin = '<?php function test() { return true; }';
        
        // This should not throw an exception for chatgpt-5
        try {
            $result = $method->invoke($service, 'chatgpt-5', $simplePlugin, []);
            // If we get here, the provider is recognized (even if it fails due to no API key)
            $this->assertTrue(true);
        } catch (\InvalidArgumentException $e) {
            // If we get InvalidArgumentException, it means the provider is not supported
            $this->fail('ChatGPT-5 provider should be supported in callProvider method');
        } catch (\RuntimeException $e) {
            // RuntimeException is expected when no API key is configured
            $this->assertStringContainsString('ChatGPT-5 API key not configured', $e->getMessage());
        }
    }
}
