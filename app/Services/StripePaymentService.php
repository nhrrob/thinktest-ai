<?php

namespace App\Services;

use App\Models\CreditPackage;
use App\Models\PaymentIntent;
use App\Models\User;
use Illuminate\Support\Facades\Log;
use Stripe\Exception\ApiErrorException;
use Stripe\PaymentIntent as StripePaymentIntent;
use Stripe\Stripe;
use Stripe\Webhook;

class StripePaymentService
{
    private CreditService $creditService;

    public function __construct(CreditService $creditService)
    {
        $this->creditService = $creditService;
        
        // Set Stripe API key
        Stripe::setApiKey(config('services.stripe.secret'));
    }

    /**
     * Create a payment intent for credit purchase.
     */
    public function createPaymentIntent(User $user, CreditPackage $package): array
    {
        try {
            // Create local payment intent record first
            $localPaymentIntent = PaymentIntent::create([
                'user_id' => $user->id,
                'credit_package_id' => $package->id,
                'stripe_payment_intent_id' => 'temp_' . uniqid(),
                'status' => 'pending',
                'amount' => $package->price,
                'currency' => 'usd',
                'credits_to_add' => $package->total_credits,
            ]);

            // Create Stripe payment intent
            $stripePaymentIntent = StripePaymentIntent::create([
                'amount' => (int)($package->price * 100), // Convert to cents
                'currency' => 'usd',
                'metadata' => [
                    'user_id' => $user->id,
                    'user_email' => $user->email,
                    'package_id' => $package->id,
                    'package_name' => $package->name,
                    'credits' => $package->total_credits,
                    'local_payment_intent_id' => $localPaymentIntent->id,
                ],
                'description' => "ThinkTest AI Credits: {$package->name}",
                'receipt_email' => $user->email,
            ]);

            // Update local record with Stripe payment intent ID
            $localPaymentIntent->update([
                'stripe_payment_intent_id' => $stripePaymentIntent->id,
                'stripe_metadata' => $stripePaymentIntent->metadata->toArray(),
                'stripe_created_at' => now(),
            ]);

            return [
                'success' => true,
                'client_secret' => $stripePaymentIntent->client_secret,
                'payment_intent_id' => $stripePaymentIntent->id,
                'amount' => $package->price,
                'currency' => 'usd',
                'package' => [
                    'name' => $package->name,
                    'credits' => $package->total_credits,
                    'description' => $package->description,
                ],
            ];

        } catch (ApiErrorException $e) {
            Log::error('Stripe payment intent creation failed', [
                'user_id' => $user->id,
                'package_id' => $package->id,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Payment processing is temporarily unavailable. Please try again later.',
            ];
        }
    }

    /**
     * Handle Stripe webhook events.
     */
    public function handleWebhook(string $payload, string $signature): array
    {
        try {
            $event = Webhook::constructEvent(
                $payload,
                $signature,
                config('services.stripe.webhook_secret')
            );

            Log::info('Stripe webhook received', [
                'event_type' => $event->type,
                'event_id' => $event->id,
            ]);

            switch ($event->type) {
                case 'payment_intent.succeeded':
                    return $this->handlePaymentSucceeded($event->data->object);

                case 'payment_intent.payment_failed':
                    return $this->handlePaymentFailed($event->data->object);

                case 'payment_intent.canceled':
                    return $this->handlePaymentCanceled($event->data->object);

                default:
                    Log::info('Unhandled Stripe webhook event', ['event_type' => $event->type]);
                    return ['success' => true, 'message' => 'Event not handled'];
            }

        } catch (\Exception $e) {
            Log::error('Stripe webhook processing failed', [
                'error' => $e->getMessage(),
                'payload' => $payload,
            ]);

            return [
                'success' => false,
                'error' => 'Webhook processing failed',
            ];
        }
    }

    /**
     * Handle successful payment.
     */
    private function handlePaymentSucceeded(StripePaymentIntent $stripePaymentIntent): array
    {
        try {
            $paymentIntent = PaymentIntent::where('stripe_payment_intent_id', $stripePaymentIntent->id)->first();

            if (!$paymentIntent) {
                Log::error('Payment intent not found for successful payment', [
                    'stripe_payment_intent_id' => $stripePaymentIntent->id,
                ]);
                return ['success' => false, 'error' => 'Payment intent not found'];
            }

            if ($paymentIntent->isCompleted()) {
                Log::warning('Payment already processed', [
                    'payment_intent_id' => $stripePaymentIntent->id,
                ]);
                return ['success' => true, 'message' => 'Payment already processed'];
            }

            // Process the successful payment
            $this->creditService->processSuccessfulPayment($stripePaymentIntent->id);

            Log::info('Payment processed successfully', [
                'user_id' => $paymentIntent->user_id,
                'payment_intent_id' => $stripePaymentIntent->id,
                'credits_added' => $paymentIntent->credits_to_add,
            ]);

            return ['success' => true, 'message' => 'Payment processed successfully'];

        } catch (\Exception $e) {
            Log::error('Failed to process successful payment', [
                'payment_intent_id' => $stripePaymentIntent->id,
                'error' => $e->getMessage(),
            ]);

            return ['success' => false, 'error' => 'Failed to process payment'];
        }
    }

    /**
     * Handle failed payment.
     */
    private function handlePaymentFailed(StripePaymentIntent $stripePaymentIntent): array
    {
        $paymentIntent = PaymentIntent::where('stripe_payment_intent_id', $stripePaymentIntent->id)->first();

        if ($paymentIntent) {
            $failureReason = $stripePaymentIntent->last_payment_error->message ?? 'Payment failed';
            $paymentIntent->markAsFailed($failureReason);

            Log::info('Payment marked as failed', [
                'payment_intent_id' => $stripePaymentIntent->id,
                'failure_reason' => $failureReason,
            ]);
        }

        return ['success' => true, 'message' => 'Payment failure recorded'];
    }

    /**
     * Handle canceled payment.
     */
    private function handlePaymentCanceled(StripePaymentIntent $stripePaymentIntent): array
    {
        $paymentIntent = PaymentIntent::where('stripe_payment_intent_id', $stripePaymentIntent->id)->first();

        if ($paymentIntent) {
            $paymentIntent->update(['status' => 'canceled']);

            Log::info('Payment marked as canceled', [
                'payment_intent_id' => $stripePaymentIntent->id,
            ]);
        }

        return ['success' => true, 'message' => 'Payment cancellation recorded'];
    }

    /**
     * Get payment intent status.
     */
    public function getPaymentIntentStatus(string $paymentIntentId): array
    {
        try {
            $stripePaymentIntent = StripePaymentIntent::retrieve($paymentIntentId);
            $localPaymentIntent = PaymentIntent::where('stripe_payment_intent_id', $paymentIntentId)->first();

            return [
                'success' => true,
                'status' => $stripePaymentIntent->status,
                'local_status' => $localPaymentIntent?->status,
                'amount' => $stripePaymentIntent->amount / 100,
                'currency' => $stripePaymentIntent->currency,
            ];

        } catch (ApiErrorException $e) {
            Log::error('Failed to retrieve payment intent status', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Failed to retrieve payment status',
            ];
        }
    }

    /**
     * Refund a payment.
     */
    public function refundPayment(string $paymentIntentId, ?float $amount = null): array
    {
        try {
            $stripePaymentIntent = StripePaymentIntent::retrieve($paymentIntentId);
            
            $refundData = ['payment_intent' => $paymentIntentId];
            if ($amount) {
                $refundData['amount'] = (int)($amount * 100); // Convert to cents
            }

            $refund = \Stripe\Refund::create($refundData);

            Log::info('Refund created', [
                'payment_intent_id' => $paymentIntentId,
                'refund_id' => $refund->id,
                'amount' => $refund->amount / 100,
            ]);

            return [
                'success' => true,
                'refund_id' => $refund->id,
                'amount' => $refund->amount / 100,
                'status' => $refund->status,
            ];

        } catch (ApiErrorException $e) {
            Log::error('Refund failed', [
                'payment_intent_id' => $paymentIntentId,
                'error' => $e->getMessage(),
            ]);

            return [
                'success' => false,
                'error' => 'Refund processing failed',
            ];
        }
    }
}
