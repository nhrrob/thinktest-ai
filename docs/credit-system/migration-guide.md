# Migration Guide: Upgrading to Credit System

This guide helps existing ThinkTest AI users understand and migrate to the new credit system with GPT-5 and Claude 4 support.

## What's New

### New AI Providers
- **OpenAI GPT-5** and **GPT-5 Mini**
- **Anthropic Claude 4 Opus** and **Claude 4 Sonnet**
- Improved **Claude 3.5 Sonnet** integration

### Credit System
- Purchase credits to use any AI provider without API keys
- Automatic credit deduction for AI usage
- Never-expiring credits with transparent pricing
- Demo credits for new users

### Enhanced Features
- Priority processing for credit users
- Comprehensive transaction history
- Real-time credit balance monitoring
- Improved error handling and fallbacks

## Impact on Existing Users

### ✅ No Breaking Changes

Your existing setup continues to work exactly as before:

- **API Keys**: If you have configured OpenAI or Anthropic API keys, they will continue to be used first
- **Demo Credits**: Existing demo credits are preserved
- **Settings**: All your current settings remain unchanged
- **Generated Tests**: All previous test generations remain accessible

### 🆕 New Options Available

You now have additional options:

1. **Keep Using API Keys**: Continue as before (recommended if you have existing API keys)
2. **Purchase Credits**: Buy credits to access new AI providers without managing API keys
3. **Mix Both**: Use API keys for some providers, credits for others

## Migration Scenarios

### Scenario 1: Happy with Current Setup

**If you're satisfied with your current API keys:**

✅ **No action required**
- Your API keys will continue to work
- No credits will be deducted
- Access to new AI providers if your keys support them

### Scenario 2: Want to Try New AI Providers

**If you want to access GPT-5 or Claude 4:**

**Option A: Add New API Keys**
1. Get GPT-5 access from OpenAI (when available)
2. Get Claude 4 access from Anthropic
3. Add keys in Settings → API Tokens

**Option B: Purchase Credits**
1. Visit `/credits` to see packages
2. Purchase credits for instant access
3. Use any AI provider without managing keys

### Scenario 3: Simplify API Key Management

**If you want to reduce API key complexity:**

1. **Purchase Credits**: Buy a credit package
2. **Remove API Keys**: Optionally remove existing API keys
3. **Unified Experience**: Use credits for all AI providers

### Scenario 4: Team/Organization Setup

**If you manage multiple users:**

1. **Centralized Credits**: Purchase credits for the organization
2. **User Management**: Users don't need individual API keys
3. **Cost Control**: Monitor usage through transaction history

## Step-by-Step Migration

### For Individual Users

1. **Assess Current Usage**
   ```bash
   # Check your current API tokens
   Visit: Settings → API Tokens
   
   # Review your usage patterns
   Visit: Dashboard → Recent Activity
   ```

2. **Choose Migration Path**
   - **Keep API Keys**: No changes needed
   - **Add Credits**: Purchase credits for new providers
   - **Switch to Credits**: Remove API keys, use credits only

3. **Test New Features**
   - Try new AI providers (GPT-5, Claude 4)
   - Monitor credit usage in dashboard
   - Review transaction history

### For Teams/Organizations

1. **Plan Migration Strategy**
   - Inventory current API key usage
   - Estimate credit needs based on usage
   - Choose centralized vs. distributed approach

2. **Gradual Migration**
   - Start with credit purchases for new providers
   - Gradually migrate users from API keys to credits
   - Monitor usage and costs

3. **Training and Documentation**
   - Train team on new credit system
   - Update internal documentation
   - Set up usage monitoring

## Cost Comparison

### API Keys vs. Credits

| Aspect | API Keys | Credits |
|--------|----------|---------|
| **Setup** | Complex (multiple providers) | Simple (one purchase) |
| **Cost** | Direct billing to providers | Transparent credit pricing |
| **Management** | Individual key management | Centralized credit management |
| **Access** | Limited to your API access | All providers available |
| **Billing** | Multiple provider bills | Single ThinkTest AI billing |

### Credit Cost Examples

Based on typical usage patterns:

**Light User (5 generations/month)**
- API Keys: ~$10-15/month across providers
- Credits: Starter Pack ($9.99) = 25 credits (5+ months)

**Regular User (20 generations/month)**
- API Keys: ~$30-50/month across providers  
- Credits: Professional Pack ($29.99) = 110 credits (5+ months)

**Heavy User (50+ generations/month)**
- API Keys: ~$100+/month across providers
- Credits: Enterprise Pack ($99.99) = 600 credits (12+ months)

## Database Migration

### Automatic Migration

When you update ThinkTest AI:

```bash
# Run migrations (automatic with new version)
php artisan migrate

# Seed credit packages
php artisan db:seed --class=CreditPackageSeeder
```

### Data Preservation

All existing data is preserved:
- User accounts and settings
- API token configurations
- Generated test history
- Demo credit balances

### New Tables Added

- `credits` - User credit balances
- `credit_transactions` - Transaction history
- `credit_packages` - Available packages
- `payment_intents` - Payment tracking

## Configuration Updates

### Environment Variables

Add new Stripe configuration (optional):

```env
# Only needed if you want to enable credit purchases
STRIPE_PUBLISHABLE_KEY=pk_test_...
STRIPE_SECRET_KEY=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...
```

### AI Provider Configuration

Updated configuration with new providers:

```php
// config/thinktest_ai.php - automatically updated

'providers' => [
    'openai-gpt5' => [
        'name' => 'OpenAI GPT-5',
        'model' => 'gpt-5',
        // ... configuration
    ],
    'anthropic-claude4-opus' => [
        'name' => 'Anthropic Claude 4 Opus',
        'model' => 'claude-4-opus-20240229',
        // ... configuration
    ],
    // ... other providers
],
```

## Testing Your Migration

### 1. Verify Existing Functionality

```bash
# Test with existing API keys
1. Go to ThinkTest AI page
2. Generate tests with your current setup
3. Verify everything works as before
```

### 2. Test New Features

```bash
# Test credit system (if you purchase credits)
1. Visit /credits
2. Purchase a small package
3. Try new AI providers
4. Check transaction history
```

### 3. Monitor Dashboard

```bash
# Check dashboard updates
1. Visit dashboard
2. Verify credit balance display
3. Check recent activity
4. Review usage statistics
```

## Rollback Plan

If you encounter issues:

### Immediate Rollback

1. **API Keys**: Your existing API keys continue working
2. **Demo Credits**: Preserved and functional
3. **Previous Version**: Can rollback to previous ThinkTest AI version if needed

### Gradual Rollback

1. **Stop Using Credits**: Simply don't purchase more credits
2. **Re-add API Keys**: Add your API keys back if removed
3. **Previous Workflow**: Return to previous workflow

## Support During Migration

### Self-Service Resources

1. **Documentation**: This migration guide and setup documentation
2. **Dashboard**: Monitor your usage and credits
3. **Transaction History**: Track all credit-related activities

### Getting Help

1. **Check Logs**: `storage/logs/laravel.log` for technical issues
2. **Dashboard Monitoring**: Use built-in monitoring tools
3. **Support Contact**: Email support with specific migration questions

### Common Migration Questions

**Q: Will my existing API keys stop working?**
A: No, API keys continue to work and have highest priority.

**Q: Do I need to purchase credits immediately?**
A: No, credits are optional. Your current setup continues working.

**Q: Can I use both API keys and credits?**
A: Yes, the system uses API keys first, then credits as fallback.

**Q: What happens to my demo credits?**
A: Demo credits are preserved and continue working as before.

**Q: Can I get a refund if I don't like the credit system?**
A: Contact support for refund requests within 30 days of purchase.

## Best Practices

### For Smooth Migration

1. **Start Small**: Purchase a small credit package to test
2. **Monitor Usage**: Watch your credit consumption patterns
3. **Gradual Transition**: Don't remove API keys immediately
4. **Team Communication**: Inform team members about changes

### For Optimal Experience

1. **Choose Right Package**: Match package size to your usage
2. **Monitor Balance**: Set up low balance alerts
3. **Track Costs**: Use transaction history for cost analysis
4. **Plan Ahead**: Purchase credits before running low

## Timeline Recommendations

### Week 1: Assessment
- Review current API key usage
- Understand new features
- Plan migration strategy

### Week 2: Testing
- Purchase small credit package
- Test new AI providers
- Verify existing functionality

### Week 3: Implementation
- Execute migration plan
- Train team members
- Monitor usage patterns

### Week 4: Optimization
- Adjust credit packages as needed
- Optimize AI provider selection
- Document new workflows

## Conclusion

The credit system enhances ThinkTest AI without disrupting existing workflows. You can:

- **Continue as before** with your existing API keys
- **Enhance your setup** by adding credits for new providers
- **Simplify management** by switching to credits entirely

Choose the approach that best fits your needs and migrate at your own pace.
