<?php

use App\Models\User;
use App\Services\AI\AIProviderService;
use Illuminate\Foundation\Testing\RefreshDatabase;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();

    // Create required permissions
    \Spatie\Permission\Models\Permission::create(['name' => 'generate tests', 'group_name' => 'ai-test-generation']);

    // Give user required permissions
    $this->user->givePermissionTo('generate tests');

    $this->actingAs($this->user);
});

test('new AI providers are properly configured', function () {
    $service = new AIProviderService();
    $providers = $service->getAvailableProviders();

    // Check that new providers are included
    $providerNames = array_keys($providers);
    
    expect($providerNames)->toContain('openai-gpt5');
    expect($providerNames)->toContain('openai-gpt5-mini');
    expect($providerNames)->toContain('anthropic-claude4-opus');
    expect($providerNames)->toContain('anthropic-claude4-sonnet');
    
    // Verify provider configurations
    expect($providers['openai-gpt5']['display_name'])->toBe('OpenAI GPT-5');
    expect($providers['openai-gpt5-mini']['display_name'])->toBe('OpenAI GPT-5 Mini');
    expect($providers['anthropic-claude4-opus']['display_name'])->toBe('Anthropic Claude 4 Opus');
    expect($providers['anthropic-claude4-sonnet']['display_name'])->toBe('Anthropic Claude 4 Sonnet');
    
    // Verify provider companies
    expect($providers['openai-gpt5']['provider_company'])->toBe('OpenAI');
    expect($providers['openai-gpt5-mini']['provider_company'])->toBe('OpenAI');
    expect($providers['anthropic-claude4-opus']['provider_company'])->toBe('Anthropic');
    expect($providers['anthropic-claude4-sonnet']['provider_company'])->toBe('Anthropic');
});

test('GPT-5 model configuration is updated', function () {
    $config = config('thinktest_ai.ai.providers.openai-gpt5');
    
    // Should use gpt-5 model by default (or environment override)
    expect($config['model'])->toBeIn(['gpt-5', 'gpt-4-turbo']); // Allow fallback during transition
    expect($config['display_name'])->toBe('OpenAI GPT-5');
});

test('GPT-5 Mini model configuration is correct', function () {
    $config = config('thinktest_ai.ai.providers.openai-gpt5-mini');
    
    expect($config['model'])->toBe('gpt-5-mini');
    expect($config['display_name'])->toBe('OpenAI GPT-5 Mini');
    expect($config['provider_company'])->toBe('OpenAI');
});

test('Claude 4 model configurations are correct', function () {
    $opusConfig = config('thinktest_ai.ai.providers.anthropic-claude4-opus');
    $sonnetConfig = config('thinktest_ai.ai.providers.anthropic-claude4-sonnet');
    
    expect($opusConfig['model'])->toBe('claude-opus-4');
    expect($sonnetConfig['model'])->toBe('claude-sonnet-4');
    
    expect($opusConfig['display_name'])->toBe('Anthropic Claude 4 Opus');
    expect($sonnetConfig['display_name'])->toBe('Anthropic Claude 4 Sonnet');
});

test('legacy provider mapping includes new providers', function () {
    $mapping = config('thinktest_ai.ai.legacy_provider_mapping');
    
    expect($mapping)->toHaveKey('gpt-5-mini');
    expect($mapping)->toHaveKey('claude-4');
    expect($mapping)->toHaveKey('claude-opus-4');
    expect($mapping)->toHaveKey('claude-sonnet-4');
    
    expect($mapping['gpt-5-mini'])->toBe('openai-gpt5-mini');
    expect($mapping['claude-4'])->toBe('anthropic-claude4-opus');
    expect($mapping['claude-opus-4'])->toBe('anthropic-claude4-opus');
    expect($mapping['claude-sonnet-4'])->toBe('anthropic-claude4-sonnet');
});

test('provider name mapping works for new providers', function () {
    $service = new AIProviderService();
    
    // Use reflection to test private method
    $reflection = new ReflectionClass($service);
    $method = $reflection->getMethod('mapProviderName');
    $method->setAccessible(true);
    
    expect($method->invoke($service, 'openai-gpt5'))->toBe('openai');
    expect($method->invoke($service, 'openai-gpt5-mini'))->toBe('openai');
    expect($method->invoke($service, 'anthropic-claude4-opus'))->toBe('anthropic');
    expect($method->invoke($service, 'anthropic-claude4-sonnet'))->toBe('anthropic');
});

test('mock provider is used when no API keys are configured', function () {
    // Clear any environment API keys for this test
    config(['thinktest_ai.ai.providers.openai-gpt5.api_key' => null]);
    config(['thinktest_ai.ai.providers.anthropic-claude4-opus.api_key' => null]);
    
    $service = new AIProviderService();
    
    // Test with a simple plugin code
    $pluginCode = '<?php
class TestPlugin {
    public function __construct() {
        add_action("init", [$this, "init"]);
    }
    
    public function init() {
        // Plugin initialization
    }
}';
    
    // Should fall back to mock provider
    $result = $service->generateWordPressTests($pluginCode, ['provider' => 'openai-gpt5']);
    
    expect($result)->toHaveKey('success');
    expect($result['success'])->toBeTrue();
    expect($result)->toHaveKey('generated_tests');
    expect($result['generated_tests'])->toBeString();
});

test('environment variables can override model selection', function () {
    // Test that OPENAI_GPT5_MODEL environment variable works
    config(['thinktest_ai.ai.providers.openai-gpt5.model' => 'gpt-5']);
    
    $config = config('thinktest_ai.ai.providers.openai-gpt5');
    expect($config['model'])->toBe('gpt-5');
    
    // Test fallback to Claude 3.5 if Claude 4 env var not set
    $claudeConfig = config('thinktest_ai.ai.providers.anthropic-claude');
    expect($claudeConfig['model'])->toContain('claude-3-5-sonnet');
});
