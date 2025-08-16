# GPT-5 and Claude 4 Integration Guide

## Overview

ThinkTest AI now supports the latest AI models from OpenAI and Anthropic:

- **OpenAI GPT-5** - The latest flagship model from OpenAI
- **OpenAI GPT-5 Mini** - A faster, more cost-effective version of GPT-5
- **Anthropic Claude 4 Opus** - The most powerful model in the Claude 4 family
- **Anthropic Claude 4 Sonnet** - A balanced model offering great performance and speed

## New AI Providers Available

### OpenAI Providers

1. **OpenAI GPT-5** (`openai-gpt5`)
   - Model: `gpt-5`
   - Best for: Complex test generation requiring advanced reasoning
   - API: Uses OpenAI Chat Completions API

2. **OpenAI GPT-5 Mini** (`openai-gpt5-mini`)
   - Model: `gpt-5-mini`
   - Best for: Fast test generation with good quality at lower cost
   - API: Uses OpenAI Chat Completions API

### Anthropic Providers

3. **Anthropic Claude 4 Opus** (`anthropic-claude4-opus`)
   - Model: `claude-opus-4`
   - Best for: Highest quality test generation with advanced reasoning
   - API: Uses Anthropic Messages API

4. **Anthropic Claude 4 Sonnet** (`anthropic-claude4-sonnet`)
   - Model: `claude-sonnet-4`
   - Best for: Balanced performance and speed for most use cases
   - API: Uses Anthropic Messages API

## Configuration

### Environment Variables

Add these environment variables to your `.env` file:

```env
# OpenAI Configuration
OPENAI_API_KEY=your_openai_api_key_here
OPENAI_ORGANIZATION=your_org_id_here  # Optional
OPENAI_GPT5_MODEL=gpt-5  # Can be 'gpt-5' or 'gpt-4-turbo' for fallback

# Anthropic Configuration
ANTHROPIC_API_KEY=your_anthropic_api_key_here
ANTHROPIC_CLAUDE_MODEL=claude-3-5-sonnet-20241022  # Fallback for existing Claude provider

# AI Service Configuration
AI_TIMEOUT=60
AI_RATE_LIMIT_RPM=60
AI_RATE_LIMIT_TPM=100000
```

### Provider Selection

You can specify which provider to use when generating tests:

```php
// In your application
$aiService = new AIProviderService();

// Use GPT-5
$result = $aiService->generateWordPressTests($pluginCode, [
    'provider' => 'openai-gpt5'
]);

// Use GPT-5 Mini for faster generation
$result = $aiService->generateWordPressTests($pluginCode, [
    'provider' => 'openai-gpt5-mini'
]);

// Use Claude 4 Opus for highest quality
$result = $aiService->generateWordPressTests($pluginCode, [
    'provider' => 'anthropic-claude4-opus'
]);

// Use Claude 4 Sonnet for balanced performance
$result = $aiService->generateWordPressTests($pluginCode, [
    'provider' => 'anthropic-claude4-sonnet'
]);
```

## Migration Guide

### From Existing Installations

If you're upgrading from a previous version of ThinkTest AI:

1. **Update your `.env` file** with the new environment variables shown above
2. **Clear configuration cache**: `php artisan config:clear`
3. **Test the new providers** using the ThinkTest AI interface

### Backward Compatibility

All existing configurations will continue to work:

- `openai-gpt5` provider now uses actual GPT-5 instead of GPT-4 Turbo
- `anthropic-claude` provider continues to use Claude 3.5 Sonnet
- Legacy provider names are still supported through automatic mapping

### Legacy Provider Mapping

The following legacy names are automatically mapped to new providers:

```php
'chatgpt-5' => 'openai-gpt5',
'gpt-5-mini' => 'openai-gpt5-mini',
'claude-4' => 'anthropic-claude4-opus',
'claude-opus-4' => 'anthropic-claude4-opus',
'claude-sonnet-4' => 'anthropic-claude4-sonnet',
```

## API Key Management

### User-Level API Keys

Users can configure their own API keys in the ThinkTest AI settings:

1. Go to **Settings** → **API Tokens**
2. Add your OpenAI or Anthropic API key
3. The system will automatically use your personal API key when available

### System-Level API Keys

Administrators can configure system-wide API keys via environment variables. User-level keys take precedence over system-level keys.

## Cost Considerations

### Model Pricing (Approximate)

- **GPT-5**: Higher cost, best quality
- **GPT-5 Mini**: Lower cost, good quality
- **Claude 4 Opus**: Highest cost, best reasoning
- **Claude 4 Sonnet**: Moderate cost, balanced performance

### Recommendations

- Use **GPT-5 Mini** or **Claude 4 Sonnet** for most test generation tasks
- Use **GPT-5** or **Claude 4 Opus** for complex plugins requiring advanced reasoning
- Monitor usage through the ThinkTest AI dashboard

## Troubleshooting

### Common Issues

1. **"Model not found" errors**
   - Ensure your API key has access to the requested model
   - Check if the model is available in your region
   - Verify the model name in your configuration

2. **Rate limiting**
   - The system automatically handles rate limits with exponential backoff
   - Consider upgrading your API plan for higher limits

3. **API key issues**
   - Verify your API keys are correctly set in the environment or user settings
   - Check that the API keys have the necessary permissions

### Fallback Behavior

If a requested provider fails:

1. The system attempts to use the configured fallback provider
2. If all providers fail, the system uses the mock provider for testing
3. Error details are logged for debugging

## Testing

Run the test suite to verify your configuration:

```bash
php artisan test tests/Feature/NewAIProvidersTest.php
```

This will verify:
- All new providers are properly configured
- Model configurations are correct
- Legacy provider mapping works
- Fallback mechanisms function properly

## Credit System Integration

The new AI providers are fully integrated with ThinkTest AI's credit system:

### Credit Costs
- **OpenAI GPT-5**: 2.0 credits per use
- **OpenAI GPT-5 Mini**: 1.0 credit per use
- **Anthropic Claude 4 Opus**: 3.0 credits per use
- **Anthropic Claude 4 Sonnet**: 2.0 credits per use
- **Anthropic Claude 3.5 Sonnet**: 1.5 credits per use

### Usage Priority
1. **User API Keys**: If you have configured your own API keys, they will be used first (no credits deducted)
2. **Purchased Credits**: If no API keys are available, purchased credits will be used
3. **Demo Credits**: Free demo credits are used as a last resort for new users

### Credit Purchase
Visit `/credits` to purchase credit packages and use any AI provider without needing your own API keys.

## Support

For issues with the new AI providers:

1. Check the Laravel logs: `storage/logs/laravel.log`
2. Verify your API key permissions with the provider
3. Test with the mock provider to isolate configuration issues
4. Check your credit balance if using the credit system
5. Contact support with specific error messages and logs
