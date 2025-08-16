<?php

use App\Models\User;
use App\Models\CreditPackage;
use App\Models\PaymentIntent;
use App\Services\CreditService;
use App\Services\StripePaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->creditService = new CreditService();
    $this->stripeService = new StripePaymentService($this->creditService);
    
    // Seed credit packages for testing
    $this->seed(\Database\Seeders\CreditPackageSeeder::class);
});

test('stripe service creates payment intent with correct metadata', function () {
    $package = CreditPackage::where('slug', 'starter-pack')->first();
    
    // Mock Stripe API
    $this->mock(\Stripe\PaymentIntent::class, function ($mock) use ($package) {
        $mock->shouldReceive('create')
            ->once()
            ->with([
                'amount' => (int)($package->price * 100),
                'currency' => 'usd',
                'metadata' => [
                    'user_id' => $this->user->id,
                    'user_email' => $this->user->email,
                    'package_id' => $package->id,
                    'package_name' => $package->name,
                    'credits' => $package->total_credits,
                    'local_payment_intent_id' => \Mockery::any(),
                ],
                'description' => "ThinkTest AI Credits: {$package->name}",
                'receipt_email' => $this->user->email,
            ])
            ->andReturn((object)[
                'id' => 'pi_test_123',
                'client_secret' => 'pi_test_123_secret_test',
                'metadata' => (object)[
                    'user_id' => $this->user->id,
                    'package_id' => $package->id,
                ],
            ]);
    });
    
    $result = $this->stripeService->createPaymentIntent($this->user, $package);
    
    expect($result['success'])->toBeTrue();
    expect($result['payment_intent_id'])->toBe('pi_test_123');
    expect($result['client_secret'])->toBe('pi_test_123_secret_test');
    
    // Verify local payment intent was created
    $localPaymentIntent = PaymentIntent::where('stripe_payment_intent_id', 'pi_test_123')->first();
    expect($localPaymentIntent)->not->toBeNull();
    expect($localPaymentIntent->user_id)->toBe($this->user->id);
    expect($localPaymentIntent->credit_package_id)->toBe($package->id);
});

test('stripe service handles api errors gracefully', function () {
    $package = CreditPackage::where('slug', 'starter-pack')->first();
    
    // Mock Stripe API to throw exception
    $this->mock(\Stripe\PaymentIntent::class, function ($mock) {
        $mock->shouldReceive('create')
            ->once()
            ->andThrow(new \Stripe\Exception\InvalidRequestException('Test error', 'param'));
    });
    
    Log::shouldReceive('error')->once();
    
    $result = $this->stripeService->createPaymentIntent($this->user, $package);
    
    expect($result['success'])->toBeFalse();
    expect($result['error'])->toBe('Payment processing is temporarily unavailable. Please try again later.');
});

test('stripe webhook processes payment succeeded event', function () {
    $package = CreditPackage::where('slug', 'starter-pack')->first();
    
    // Create a payment intent
    $paymentIntent = PaymentIntent::create([
        'user_id' => $this->user->id,
        'credit_package_id' => $package->id,
        'stripe_payment_intent_id' => 'pi_test_123',
        'status' => 'pending',
        'amount' => $package->price,
        'credits_to_add' => $package->total_credits,
    ]);
    
    // Mock webhook event
    $webhookPayload = json_encode([
        'id' => 'evt_test_webhook',
        'type' => 'payment_intent.succeeded',
        'data' => [
            'object' => [
                'id' => 'pi_test_123',
                'status' => 'succeeded',
                'amount' => $package->price * 100,
                'currency' => 'usd',
                'metadata' => [
                    'user_id' => $this->user->id,
                    'package_id' => $package->id,
                ],
            ],
        ],
    ]);
    
    // Mock Stripe webhook verification
    $this->mock(\Stripe\Webhook::class, function ($mock) use ($webhookPayload) {
        $mock->shouldReceive('constructEvent')
            ->once()
            ->with($webhookPayload, 'test_signature', \Mockery::any())
            ->andReturn((object)[
                'id' => 'evt_test_webhook',
                'type' => 'payment_intent.succeeded',
                'data' => (object)[
                    'object' => (object)[
                        'id' => 'pi_test_123',
                        'status' => 'succeeded',
                        'amount' => 1000,
                        'currency' => 'usd',
                        'metadata' => [
                            'user_id' => $this->user->id,
                            'package_id' => 1,
                        ],
                    ],
                ],
            ]);
    });
    
    $result = $this->stripeService->handleWebhook($webhookPayload, 'test_signature');
    
    expect($result['success'])->toBeTrue();
    
    // Verify payment intent was updated
    $paymentIntent->refresh();
    expect($paymentIntent->status)->toBe('succeeded');
    expect($paymentIntent->completed_at)->not->toBeNull();
    
    // Verify credits were added
    $userBalance = $this->creditService->getUserBalance($this->user->id);
    expect($userBalance)->toBe((float)$package->total_credits);
});

test('stripe webhook processes payment failed event', function () {
    $package = CreditPackage::where('slug', 'starter-pack')->first();
    
    // Create a payment intent
    $paymentIntent = PaymentIntent::create([
        'user_id' => $this->user->id,
        'credit_package_id' => $package->id,
        'stripe_payment_intent_id' => 'pi_test_123',
        'status' => 'pending',
        'amount' => $package->price,
        'credits_to_add' => $package->total_credits,
    ]);
    
    // Mock webhook event
    $webhookPayload = json_encode([
        'id' => 'evt_test_webhook',
        'type' => 'payment_intent.payment_failed',
        'data' => [
            'object' => [
                'id' => 'pi_test_123',
                'status' => 'failed',
                'last_payment_error' => [
                    'message' => 'Your card was declined.',
                ],
            ],
        ],
    ]);
    
    // Mock Stripe webhook verification
    $this->mock(\Stripe\Webhook::class, function ($mock) use ($webhookPayload) {
        $mock->shouldReceive('constructEvent')
            ->once()
            ->andReturn((object)[
                'id' => 'evt_test_webhook',
                'type' => 'payment_intent.payment_failed',
                'data' => (object)[
                    'object' => (object)[
                        'id' => 'pi_test_123',
                        'status' => 'failed',
                        'last_payment_error' => (object)[
                            'message' => 'Your card was declined.',
                        ],
                    ],
                ],
            ]);
    });
    
    $result = $this->stripeService->handleWebhook($webhookPayload, 'test_signature');
    
    expect($result['success'])->toBeTrue();
    
    // Verify payment intent was updated
    $paymentIntent->refresh();
    expect($paymentIntent->status)->toBe('failed');
    expect($paymentIntent->failure_reason)->toBe('Your card was declined.');
    
    // Verify no credits were added
    $userBalance = $this->creditService->getUserBalance($this->user->id);
    expect($userBalance)->toBe(0.0);
});

test('stripe webhook handles invalid signature', function () {
    $webhookPayload = json_encode(['test' => 'data']);
    
    // Mock Stripe webhook verification to throw exception
    $this->mock(\Stripe\Webhook::class, function ($mock) {
        $mock->shouldReceive('constructEvent')
            ->once()
            ->andThrow(new \Exception('Invalid signature'));
    });
    
    $result = $this->stripeService->handleWebhook($webhookPayload, 'invalid_signature');
    
    expect($result['success'])->toBeFalse();
    expect($result['error'])->toBe('Webhook processing failed');
});

test('stripe webhook ignores unhandled events', function () {
    $webhookPayload = json_encode([
        'id' => 'evt_test_webhook',
        'type' => 'customer.created',
        'data' => ['object' => []],
    ]);
    
    // Mock Stripe webhook verification
    $this->mock(\Stripe\Webhook::class, function ($mock) use ($webhookPayload) {
        $mock->shouldReceive('constructEvent')
            ->once()
            ->andReturn((object)[
                'id' => 'evt_test_webhook',
                'type' => 'customer.created',
                'data' => (object)['object' => (object)[]],
            ]);
    });
    
    $result = $this->stripeService->handleWebhook($webhookPayload, 'test_signature');
    
    expect($result['success'])->toBeTrue();
    expect($result['message'])->toBe('Event not handled');
});

test('stripe service gets payment intent status correctly', function () {
    // Mock Stripe API
    $this->mock(\Stripe\PaymentIntent::class, function ($mock) {
        $mock->shouldReceive('retrieve')
            ->once()
            ->with('pi_test_123')
            ->andReturn((object)[
                'id' => 'pi_test_123',
                'status' => 'succeeded',
                'amount' => 1000,
                'currency' => 'usd',
            ]);
    });
    
    $result = $this->stripeService->getPaymentIntentStatus('pi_test_123');
    
    expect($result['success'])->toBeTrue();
    expect($result['status'])->toBe('succeeded');
    expect($result['amount'])->toBe(10.00); // Converted from cents
});

test('stripe service handles payment intent retrieval errors', function () {
    // Mock Stripe API to throw exception
    $this->mock(\Stripe\PaymentIntent::class, function ($mock) {
        $mock->shouldReceive('retrieve')
            ->once()
            ->andThrow(new \Stripe\Exception\InvalidRequestException('Payment intent not found', 'id'));
    });
    
    $result = $this->stripeService->getPaymentIntentStatus('pi_invalid');
    
    expect($result['success'])->toBeFalse();
    expect($result['error'])->toContain('Failed to retrieve payment status');
});

test('stripe service processes refunds correctly', function () {
    $package = CreditPackage::where('slug', 'starter-pack')->first();
    
    // Create a successful payment intent
    $paymentIntent = PaymentIntent::create([
        'user_id' => $this->user->id,
        'credit_package_id' => $package->id,
        'stripe_payment_intent_id' => 'pi_test_123',
        'status' => 'succeeded',
        'amount' => $package->price,
        'credits_to_add' => $package->total_credits,
        'completed_at' => now(),
    ]);
    
    // Add credits to user
    $this->creditService->addCredits($this->user->id, $package->total_credits, 'Purchase', ['payment_intent_id' => 'pi_test_123']);
    
    // Mock Stripe refund API
    $this->mock(\Stripe\Refund::class, function ($mock) use ($package) {
        $mock->shouldReceive('create')
            ->once()
            ->with([
                'payment_intent' => 'pi_test_123',
                'amount' => (int)($package->price * 100),
                'reason' => 'requested_by_customer',
                'metadata' => [
                    'refund_reason' => 'Customer request',
                    'user_id' => $this->user->id,
                ],
            ])
            ->andReturn((object)[
                'id' => 're_test_123',
                'status' => 'succeeded',
                'amount' => $package->price * 100,
            ]);
    });
    
    $result = $this->stripeService->refundPayment('pi_test_123');
    
    expect($result['success'])->toBeTrue();
    expect($result['refund_id'])->toBe('re_test_123');
    
    // Verify payment intent was updated
    $paymentIntent->refresh();
    expect($paymentIntent->status)->toBe('refunded');
    
    // Verify credits were deducted
    $userBalance = $this->creditService->getUserBalance($this->user->id);
    expect($userBalance)->toBe(0.0);
});
