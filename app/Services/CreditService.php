<?php

namespace App\Services;

use App\Models\Credit;
use App\Models\CreditPackage;
use App\Models\CreditTransaction;
use App\Models\PaymentIntent;
use App\Models\User;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Log;

class CreditService
{
    /**
     * AI provider credit costs (credits per usage)
     */
    private const PROVIDER_COSTS = [
        'openai-gpt5' => 2.0,
        'openai-gpt5-mini' => 1.0,
        'anthropic-claude4-opus' => 3.0,
        'anthropic-claude4-sonnet' => 2.0,
        'anthropic-claude' => 1.5,
        'mock' => 0.0, // Mock provider is free
    ];

    /**
     * Get user's credit balance.
     */
    public function getUserBalance(int $userId): float
    {
        $credit = Credit::where('user_id', $userId)->first();
        return $credit ? $credit->balance : 0.00;
    }

    /**
     * Check if user has sufficient credits for AI provider.
     */
    public function hasCreditsForProvider(int $userId, string $provider): bool
    {
        $cost = $this->getProviderCost($provider);
        $balance = $this->getUserBalance($userId);
        
        return $balance >= $cost;
    }

    /**
     * Get credit cost for AI provider.
     */
    public function getProviderCost(string $provider): float
    {
        return self::PROVIDER_COSTS[$provider] ?? 1.0;
    }

    /**
     * Deduct credits for AI provider usage.
     */
    public function deductCreditsForUsage(int $userId, string $provider, string $model = null, int $tokensUsed = null): CreditTransaction
    {
        $cost = $this->getProviderCost($provider);
        $credit = Credit::getOrCreateForUser($userId);

        if (!$credit->hasCredits($cost)) {
            throw new \RuntimeException(
                "Insufficient credits. Required: {$cost}, Available: {$credit->balance}. Please purchase more credits to continue."
            );
        }

        $description = "AI test generation using " . $this->getProviderDisplayName($provider);
        
        $metadata = [
            'provider' => $provider,
            'model' => $model,
            'tokens_used' => $tokensUsed,
            'cost_per_usage' => $cost,
        ];

        $transaction = $credit->deductCredits($cost, $description, $metadata);

        // Update the transaction with AI-specific fields
        $transaction->update([
            'ai_provider' => $provider,
            'ai_model' => $model,
            'tokens_used' => $tokensUsed,
        ]);

        return $transaction;
    }

    /**
     * Add credits to user's account.
     */
    public function addCredits(int $userId, float $amount, string $description, array $metadata = []): CreditTransaction
    {
        $credit = Credit::getOrCreateForUser($userId);
        return $credit->addCredits($amount, $description, $metadata);
    }

    /**
     * Get user's credit status and statistics.
     */
    public function getUserCreditStatus(int $userId): array
    {
        $credit = Credit::where('user_id', $userId)->first();
        
        if (!$credit) {
            return [
                'balance' => 0.00,
                'total_purchased' => 0.00,
                'total_used' => 0.00,
                'recent_transactions' => [],
                'usage_stats' => [
                    'total_uses' => 0,
                    'total_credits_used' => 0.00,
                    'provider_breakdown' => [],
                ],
            ];
        }

        return [
            'balance' => $credit->balance,
            'total_purchased' => $credit->total_purchased,
            'total_used' => $credit->total_used,
            'recent_transactions' => $credit->getRecentTransactions(5),
            'usage_stats' => $credit->getUsageStats(),
        ];
    }

    /**
     * Get available credit packages.
     */
    public function getAvailablePackages(): \Illuminate\Database\Eloquent\Collection
    {
        return CreditPackage::active()->ordered()->get();
    }

    /**
     * Get recommended package for user based on usage.
     */
    public function getRecommendedPackage(int $userId): ?CreditPackage
    {
        $credit = Credit::where('user_id', $userId)->first();
        
        if (!$credit) {
            return CreditPackage::where('slug', 'starter-pack')->first();
        }

        // Estimate monthly usage based on recent activity
        $monthlyUsage = $credit->transactions()
            ->where('type', 'usage')
            ->where('created_at', '>=', now()->subMonth())
            ->count();

        return CreditPackage::getRecommendedForUsage($monthlyUsage);
    }

    /**
     * Create payment intent for credit purchase.
     */
    public function createPaymentIntent(int $userId, int $packageId): PaymentIntent
    {
        $package = CreditPackage::findOrFail($packageId);
        
        if (!$package->is_active) {
            throw new \RuntimeException('This credit package is no longer available.');
        }

        return PaymentIntent::create([
            'user_id' => $userId,
            'credit_package_id' => $packageId,
            'stripe_payment_intent_id' => 'temp_' . uniqid(), // Will be updated with actual Stripe ID
            'status' => 'pending',
            'amount' => $package->price,
            'currency' => 'usd',
            'credits_to_add' => $package->total_credits,
        ]);
    }

    /**
     * Process successful payment and add credits.
     */
    public function processSuccessfulPayment(string $stripePaymentIntentId): void
    {
        DB::transaction(function () use ($stripePaymentIntentId) {
            $paymentIntent = PaymentIntent::where('stripe_payment_intent_id', $stripePaymentIntentId)->firstOrFail();
            
            if ($paymentIntent->isCompleted()) {
                Log::warning('Payment intent already processed', ['payment_intent_id' => $stripePaymentIntentId]);
                return;
            }

            // Mark payment as succeeded
            $paymentIntent->markAsSucceeded();

            // Add credits to user's account
            $description = "Credit purchase: " . ($paymentIntent->creditPackage->name ?? 'Credit Package');
            $metadata = [
                'payment_intent_id' => $stripePaymentIntentId,
                'package_id' => $paymentIntent->credit_package_id,
                'price' => $paymentIntent->amount,
            ];

            $transaction = $this->addCredits(
                $paymentIntent->user_id,
                $paymentIntent->credits_to_add,
                $description,
                $metadata
            );

            // Update transaction with payment details
            $transaction->update([
                'payment_intent_id' => $stripePaymentIntentId,
                'payment_method' => 'stripe',
                'payment_status' => 'completed',
            ]);

            Log::info('Credits added successfully', [
                'user_id' => $paymentIntent->user_id,
                'credits_added' => $paymentIntent->credits_to_add,
                'payment_intent_id' => $stripePaymentIntentId,
            ]);
        });
    }

    /**
     * Get provider display name.
     */
    private function getProviderDisplayName(string $provider): string
    {
        return match ($provider) {
            'openai-gpt5' => 'OpenAI GPT-5',
            'openai-gpt5-mini' => 'OpenAI GPT-5 Mini',
            'anthropic-claude4-opus' => 'Anthropic Claude 4 Opus',
            'anthropic-claude4-sonnet' => 'Anthropic Claude 4 Sonnet',
            'anthropic-claude' => 'Anthropic Claude 3.5 Sonnet',
            default => ucfirst(str_replace('-', ' ', $provider)),
        };
    }

    /**
     * Get all provider costs for display.
     */
    public function getProviderCosts(): array
    {
        $costs = [];
        foreach (self::PROVIDER_COSTS as $provider => $cost) {
            $costs[] = [
                'provider' => $provider,
                'display_name' => $this->getProviderDisplayName($provider),
                'cost' => $cost,
                'formatted_cost' => $cost == 0 ? 'Free' : number_format($cost, 1) . ' credits',
            ];
        }
        return $costs;
    }
}
