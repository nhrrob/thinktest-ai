# ThinkTest AI Credit System

## Overview

The ThinkTest AI Credit System allows users to purchase credits and use any AI provider (GPT-5, Claude 4, etc.) without needing their own API keys. This system provides a seamless experience for users who want to access premium AI models without the complexity of managing API keys.

## How It Works

### Credit Types

1. **Purchased Credits**
   - Credits bought through the credit purchase system
   - Never expire
   - Can be used with any AI provider
   - Automatically deducted when generating tests

2. **Demo Credits**
   - Free credits for new users (5 credits)
   - Used as fallback when no API keys or purchased credits are available
   - Limited to encourage users to purchase credits or add API keys

### Usage Priority

The system follows this priority order:

1. **User API Keys** (Highest Priority)
   - If you have configured your own OpenAI or Anthropic API keys
   - No credits are deducted when using your own API keys
   - Direct billing to your API provider account

2. **Purchased Credits** (Medium Priority)
   - Used when no API keys are configured
   - Credits are automatically deducted based on AI provider costs
   - Provides access to all AI providers

3. **Demo Credits** (Lowest Priority)
   - Used only when no API keys or purchased credits are available
   - Limited to 5 credits for new users
   - Encourages users to upgrade to purchased credits

## Credit Costs

Different AI providers have different credit costs based on their capabilities and pricing:

| AI Provider | Credits per Use | Best For |
|-------------|----------------|----------|
| OpenAI GPT-5 Mini | 1.0 | Fast, cost-effective generation |
| Anthropic Claude 3.5 Sonnet | 1.5 | Balanced performance |
| OpenAI GPT-5 | 2.0 | Advanced reasoning |
| Anthropic Claude 4 Sonnet | 2.0 | Balanced Claude 4 performance |
| Anthropic Claude 4 Opus | 3.0 | Highest quality reasoning |

## Credit Packages

### Available Packages

1. **Starter Pack** - $9.99
   - 25 credits
   - Perfect for trying ThinkTest AI
   - No bonus credits

2. **Developer Pack** - $19.99
   - 50 credits + 5 bonus credits
   - Great for individual developers
   - 10% bonus credits

3. **Professional Pack** - $29.99 (Most Popular)
   - 100 credits + 10 bonus credits
   - Ideal for regular use and small teams
   - 10% bonus credits
   - Priority processing

4. **Enterprise Pack** - $99.99
   - 500 credits + 100 bonus credits
   - Best value for teams and heavy usage
   - 20% bonus credits
   - Priority processing
   - Usage analytics

### Package Features

All credit packages include:
- ✅ Access to all AI providers (GPT-5, Claude 4, etc.)
- ✅ Credits never expire
- ✅ Email support
- ✅ No setup required

Premium packages (Professional & Enterprise) also include:
- ✅ Priority processing
- ✅ Bonus credits
- ✅ Advanced features

## Payment Processing

### Stripe Integration

- Secure payment processing through Stripe
- Support for all major credit cards
- Automatic receipt generation
- Webhook-based credit allocation

### Payment Flow

1. **Select Package**: Choose a credit package on `/credits`
2. **Payment Intent**: System creates a Stripe payment intent
3. **Payment**: Complete payment through Stripe
4. **Webhook**: Stripe webhook confirms payment
5. **Credit Allocation**: Credits are automatically added to your account
6. **Confirmation**: Receive email receipt and account update

## Account Management

### Dashboard Integration

Your dashboard displays:
- Current credit balance
- Total credits purchased
- Total credits used
- Demo credits remaining
- Low balance warnings with purchase links

### Transaction History

Access your complete transaction history at `/credits/transactions`:
- All credit purchases
- Credit usage for AI generation
- Payment details and receipts
- Usage statistics by AI provider

### Credit Monitoring

- Real-time balance updates
- Low balance notifications
- Usage tracking by AI provider
- Monthly usage statistics

## API Integration

### Automatic Credit Deduction

When generating tests without API keys:

1. System checks for sufficient credits
2. Calls the selected AI provider
3. Automatically deducts credits on successful generation
4. Records transaction with metadata (provider, model, tokens used)

### Error Handling

- **Insufficient Credits**: Clear error message with purchase link
- **Payment Failures**: Automatic retry and fallback options
- **API Failures**: No credits deducted on failed API calls

## Migration Guide

### For Existing Users

If you're upgrading from a previous version:

1. **Existing API Keys**: Continue working as before (highest priority)
2. **Demo Credits**: Existing demo credits are preserved
3. **New Features**: Access to credit purchase system
4. **No Breaking Changes**: All existing functionality remains

### Database Migration

The credit system adds these new tables:
- `credits` - User credit balances
- `credit_transactions` - All credit-related transactions
- `credit_packages` - Available packages for purchase
- `payment_intents` - Stripe payment tracking

Run migrations:
```bash
php artisan migrate
php artisan db:seed --class=CreditPackageSeeder
```

## Configuration

### Environment Variables

Add to your `.env` file:

```env
# Stripe Configuration
STRIPE_PUBLISHABLE_KEY=pk_test_...
STRIPE_SECRET_KEY=sk_test_...
STRIPE_WEBHOOK_SECRET=whsec_...

# AI Provider Configuration (existing)
OPENAI_API_KEY=your_openai_key
ANTHROPIC_API_KEY=your_anthropic_key
```

### Stripe Webhook Setup

1. Create webhook endpoint in Stripe Dashboard
2. Point to: `https://yourdomain.com/stripe/webhook`
3. Select events: `payment_intent.succeeded`, `payment_intent.payment_failed`
4. Copy webhook secret to `STRIPE_WEBHOOK_SECRET`

## Security

### Payment Security

- All payments processed through Stripe (PCI compliant)
- No credit card data stored on ThinkTest AI servers
- Webhook signature verification for all payment events

### Credit Security

- Credits tied to user accounts with proper authentication
- Transaction logging for audit trails
- Automatic fraud detection through Stripe

## Troubleshooting

### Common Issues

1. **Payment Not Processed**
   - Check Stripe webhook configuration
   - Verify webhook secret in environment
   - Check Laravel logs for webhook errors

2. **Credits Not Added**
   - Verify payment succeeded in Stripe Dashboard
   - Check `payment_intents` table for status
   - Manually process with `CreditService::processSuccessfulPayment()`

3. **Credit Deduction Issues**
   - Verify AI provider costs in `CreditService`
   - Check user credit balance
   - Review transaction logs

### Support

For credit system issues:
1. Check Laravel logs: `storage/logs/laravel.log`
2. Verify Stripe webhook delivery in Stripe Dashboard
3. Check database tables for transaction status
4. Contact support with payment intent ID and user details
