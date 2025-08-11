<?php

namespace Tests\Feature;

use App\Services\AI\AIProviderService;
use App\Services\TestGeneration\TestGenerationService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ChatGPT5IntegrationTest extends TestCase
{
    use RefreshDatabase;

    public function test_openai_gpt5_config_exists(): void
    {
        $config = config('thinktest_ai.ai.providers.openai-gpt5');

        $this->assertIsArray($config);
        $this->assertArrayHasKey('model', $config);
        $this->assertArrayHasKey('display_name', $config);
        $this->assertEquals('OpenAI GPT-5', $config['display_name']);
        $this->assertArrayHasKey('provider_company', $config);
        $this->assertEquals('OpenAI', $config['provider_company']);
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

    public function test_available_providers_includes_openai_gpt5(): void
    {
        $service = new AIProviderService();
        $providers = $service->getAvailableProviders();

        $this->assertIsArray($providers);
        $this->assertArrayHasKey('openai-gpt5', $providers);
        $this->assertEquals('openai-gpt5', $providers['openai-gpt5']['name']);
        $this->assertEquals('OpenAI GPT-5', $providers['openai-gpt5']['display_name']);
        $this->assertEquals('OpenAI', $providers['openai-gpt5']['provider_company']);
        $this->assertIsBool($providers['openai-gpt5']['available']);
    }

    public function test_legacy_provider_mapping_exists(): void
    {
        $mapping = config('thinktest_ai.ai.legacy_provider_mapping');

        $this->assertIsArray($mapping);
        $this->assertArrayHasKey('chatgpt-5', $mapping);
        $this->assertEquals('openai-gpt5', $mapping['chatgpt-5']);
        $this->assertArrayHasKey('anthropic', $mapping);
        $this->assertEquals('anthropic-claude', $mapping['anthropic']);
    }

    public function test_anthropic_claude_config_exists(): void
    {
        $config = config('thinktest_ai.ai.providers.anthropic-claude');

        $this->assertIsArray($config);
        $this->assertArrayHasKey('model', $config);
        $this->assertArrayHasKey('display_name', $config);
        $this->assertEquals('Anthropic Claude 3.5 Sonnet', $config['display_name']);
        $this->assertArrayHasKey('provider_company', $config);
        $this->assertEquals('Anthropic', $config['provider_company']);
        $this->assertArrayHasKey('wordpress_system_prompt', $config);
    }

    public function test_openai_provider_removed(): void
    {
        $service = new AIProviderService();
        $providers = $service->getAvailableProviders();

        // Verify openai provider is no longer available
        $this->assertArrayNotHasKey('openai', $providers);

        // Verify only chatgpt-5 and anthropic are available
        $this->assertCount(2, $providers);
        $this->assertArrayHasKey('chatgpt-5', $providers);
        $this->assertArrayHasKey('anthropic', $providers);
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
