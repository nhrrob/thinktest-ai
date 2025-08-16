# Credit System Setup Guide

This guide walks you through setting up the ThinkTest AI Credit System with Stripe payment integration.

## Prerequisites

- ThinkTest AI application installed and running
- Stripe account (test or live)
- SSL certificate for production (required by Stripe)

## Step 1: Install Dependencies

The Stripe PHP SDK should already be installed. If not:

```bash
composer require stripe/stripe-php
```

## Step 2: Database Setup

Run the credit system migrations:

```bash
# Run migrations to create credit tables
php artisan migrate

# Seed credit packages
php artisan db:seed --class=CreditPackageSeeder
```

This creates the following tables:
- `credits` - User credit balances
- `credit_transactions` - Transaction history
- `credit_packages` - Available packages
- `payment_intents` - Payment tracking

## Step 3: Stripe Configuration

### 3.1 Get Stripe Keys

1. Log into your [Stripe Dashboard](https://dashboard.stripe.com)
2. Go to **Developers > API keys**
3. Copy your **Publishable key** and **Secret key**

For testing, use test keys (starting with `pk_test_` and `sk_test_`)

### 3.2 Configure Environment

Add to your `.env` file:

```env
# Stripe Configuration
STRIPE_PUBLISHABLE_KEY=pk_test_51234567890abcdef...
STRIPE_SECRET_KEY=sk_test_51234567890abcdef...
STRIPE_WEBHOOK_SECRET=whsec_1234567890abcdef...
```

### 3.3 Set Up Webhooks

1. In Stripe Dashboard, go to **Developers > Webhooks**
2. Click **Add endpoint**
3. Set endpoint URL: `https://yourdomain.com/stripe/webhook`
4. Select events to send:
   - `payment_intent.succeeded`
   - `payment_intent.payment_failed`
   - `payment_intent.canceled`
5. Click **Add endpoint**
6. Copy the **Signing secret** and add to `STRIPE_WEBHOOK_SECRET`

## Step 4: Test the Integration

### 4.1 Test Credit Packages

Visit `/credits` to see available packages:

```bash
# Check if packages are seeded
php artisan tinker
>>> App\Models\CreditPackage::count()
=> 4
```

### 4.2 Test Payment Flow

1. Go to `/credits` in your browser
2. Select a package
3. Use Stripe test card: `4242 4242 4242 4242`
4. Complete the payment flow
5. Verify credits are added to your account

### 4.3 Test Webhook

Use Stripe CLI to test webhooks locally:

```bash
# Install Stripe CLI
# https://stripe.com/docs/stripe-cli

# Login to Stripe
stripe login

# Forward webhooks to local server
stripe listen --forward-to localhost:8000/stripe/webhook

# Trigger test payment
stripe trigger payment_intent.succeeded
```

## Step 5: Production Setup

### 5.1 Switch to Live Keys

1. Get live keys from Stripe Dashboard
2. Update `.env` with live keys:

```env
STRIPE_PUBLISHABLE_KEY=pk_live_...
STRIPE_SECRET_KEY=sk_live_...
STRIPE_WEBHOOK_SECRET=whsec_live_...
```

### 5.2 Update Webhook Endpoint

1. Create new webhook in Stripe Dashboard
2. Point to production URL: `https://yourdomain.com/stripe/webhook`
3. Update `STRIPE_WEBHOOK_SECRET` with new signing secret

### 5.3 SSL Certificate

Ensure your production site has a valid SSL certificate. Stripe requires HTTPS for webhooks.

## Step 6: Customize Credit Packages

### 6.1 Modify Existing Packages

Edit the seeder file:

```php
// database/seeders/CreditPackageSeeder.php

CreditPackage::create([
    'name' => 'Custom Pack',
    'slug' => 'custom-pack',
    'description' => 'Custom package for your needs',
    'credits' => 75,
    'price' => 24.99,
    'bonus_credits' => 5,
    'is_popular' => false,
    'is_active' => true,
    'features' => [
        'Access to all AI providers',
        'Credits never expire',
        'Email support',
        'Custom feature'
    ],
]);
```

### 6.2 Update Credit Costs

Modify AI provider costs in `CreditService`:

```php
// app/Services/CreditService.php

private function getProviderCost(string $provider): float
{
    return match ($provider) {
        'openai-gpt5-mini' => 1.0,
        'openai-gpt5' => 2.0,
        'anthropic-claude35-sonnet' => 1.5,
        'anthropic-claude4-sonnet' => 2.0,
        'anthropic-claude4-opus' => 3.0,
        'your-custom-provider' => 2.5, // Add custom costs
        default => 1.0,
    };
}
```

## Step 7: Monitoring and Maintenance

### 7.1 Monitor Payments

- Check Stripe Dashboard regularly
- Set up Stripe email notifications
- Monitor webhook delivery status

### 7.2 Database Maintenance

```bash
# Check credit balances
php artisan tinker
>>> App\Models\Credit::sum('balance')

# Check transaction volume
>>> App\Models\CreditTransaction::count()

# Check payment success rate
>>> App\Models\PaymentIntent::where('status', 'completed')->count()
```

### 7.3 Logs and Debugging

Monitor these log files:
- `storage/logs/laravel.log` - Application logs
- Stripe Dashboard - Payment and webhook logs

## Step 8: Security Considerations

### 8.1 Webhook Security

- Always verify webhook signatures
- Use HTTPS for webhook endpoints
- Monitor for suspicious webhook activity

### 8.2 Credit Security

- Implement rate limiting for credit purchases
- Monitor for unusual credit usage patterns
- Set up alerts for large transactions

### 8.3 Data Protection

- Regularly backup credit transaction data
- Implement proper access controls
- Follow GDPR/privacy regulations

## Troubleshooting

### Common Issues

1. **Webhook Not Receiving Events**
   - Check webhook URL is accessible
   - Verify SSL certificate
   - Check Stripe webhook logs

2. **Payment Succeeded but Credits Not Added**
   - Check webhook signature verification
   - Verify `STRIPE_WEBHOOK_SECRET`
   - Check Laravel logs for errors

3. **Credits Not Deducted During AI Usage**
   - Verify `CreditService` integration
   - Check AI provider cost configuration
   - Review transaction logs

### Debug Commands

```bash
# Check webhook configuration
php artisan route:list | grep webhook

# Test credit service
php artisan tinker
>>> $service = new App\Services\CreditService();
>>> $service->getUserBalance(1);

# Check payment intents
>>> App\Models\PaymentIntent::latest()->first();
```

## Support

For setup issues:

1. Check this documentation
2. Review Laravel logs
3. Check Stripe Dashboard for payment/webhook issues
4. Contact support with specific error messages

## Next Steps

After setup:
1. Test the complete payment flow
2. Configure monitoring and alerts
3. Train your team on the credit system
4. Consider implementing usage analytics
5. Plan for scaling and optimization
