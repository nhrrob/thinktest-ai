<?php

use App\Models\User;
use App\Models\CreditPackage;
use App\Models\PaymentIntent;
use App\Services\CreditService;
use App\Services\StripePaymentService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Http;
use Illuminate\Support\Facades\Log;

uses(RefreshDatabase::class);

beforeEach(function () {
    $this->user = User::factory()->create();
    $this->creditService = new CreditService();
    
    // Create required permissions
    \Spatie\Permission\Models\Permission::create(['name' => 'generate tests', 'group_name' => 'ai-test-generation']);
    $this->user->givePermissionTo('generate tests');
    
    // Seed credit packages for testing
    $this->seed(\Database\Seeders\CreditPackageSeeder::class);
    
    $this->actingAs($this->user);
});

test('payment controller shows credit packages page', function () {
    $response = $this->get('/credits');
    
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Credits/Index')
        ->has('packages')
        ->has('creditStatus')
        ->has('recommendedPackage')
        ->has('stripePublishableKey')
    );
});

test('payment controller creates payment intent successfully', function () {
    $package = CreditPackage::where('slug', 'starter-pack')->first();

    // Mock the StripePaymentService
    $this->mock(\App\Services\StripePaymentService::class, function ($mock) use ($package) {
        $mock->shouldReceive('createPaymentIntent')
            ->once()
            ->with(\Mockery::type(\App\Models\User::class), \Mockery::type(\App\Models\CreditPackage::class))
            ->andReturn([
                'success' => true,
                'client_secret' => 'pi_test_123_secret_test',
                'payment_intent_id' => 'pi_test_123',
                'amount' => $package->price,
                'currency' => 'usd',
                'package' => [
                    'name' => $package->name,
                    'credits' => $package->total_credits,
                    'description' => $package->description,
                ],
            ]);
    });

    $response = $this->postJson('/credits/payment-intent', [
        'package_id' => $package->id,
    ]);

    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
    ]);
    $response->assertJsonStructure([
        'success',
        'client_secret',
        'payment_intent_id',
        'package' => ['name', 'credits', 'description'],
    ]);
});

test('payment controller rejects invalid package', function () {
    $response = $this->postJson('/credits/payment-intent', [
        'package_id' => 999,
    ]);
    
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['package_id']);
});

test('payment controller rejects inactive package', function () {
    $package = CreditPackage::where('slug', 'starter-pack')->first();
    $package->update(['is_active' => false]);
    
    $response = $this->postJson('/credits/payment-intent', [
        'package_id' => $package->id,
    ]);
    
    $response->assertStatus(422);
    $response->assertJsonValidationErrors(['package_id']);
});

test('stripe webhook handles payment succeeded event', function () {
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
    
    // Mock webhook payload
    $webhookPayload = json_encode([
        'id' => 'evt_test_webhook',
        'object' => 'event',
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
    
    // Mock webhook signature verification
    $this->mock(StripePaymentService::class, function ($mock) {
        $mock->shouldReceive('handleWebhook')
            ->once()
            ->with(\Mockery::type('string'), 'test_signature')
            ->andReturn(['success' => true, 'message' => 'Payment processed successfully']);
    });
    
    $response = $this->postJson('/stripe/webhook', [], [
        'Stripe-Signature' => 'test_signature',
    ]);
    
    $response->assertStatus(200);
    $response->assertJson(['success' => true]);
});

test('stripe webhook rejects request without signature', function () {
    $response = $this->postJson('/stripe/webhook', []);
    
    $response->assertStatus(400);
    $response->assertJson(['error' => 'Missing signature']);
});

test('payment success page shows correct information', function () {
    $package = CreditPackage::where('slug', 'starter-pack')->first();
    
    // Mock Stripe API response for payment intent status
    Http::fake([
        'api.stripe.com/*' => Http::response([
            'id' => 'pi_test_123',
            'status' => 'succeeded',
            'amount' => $package->price * 100,
            'currency' => 'usd',
        ], 200)
    ]);
    
    $response = $this->get('/credits/success?payment_intent=pi_test_123');
    
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Credits/Success')
        ->has('paymentIntentId')
        ->has('amount')
        ->has('creditStatus')
    );
});

test('payment success page redirects without payment intent', function () {
    $response = $this->get('/credits/success');
    
    $response->assertRedirect('/credits');
    $response->assertSessionHas('error', 'Invalid payment confirmation.');
});

test('payment success page redirects for failed payment', function () {
    // Mock Stripe API response for failed payment
    Http::fake([
        'api.stripe.com/*' => Http::response([
            'id' => 'pi_test_123',
            'status' => 'failed',
            'amount' => 1000,
            'currency' => 'usd',
        ], 200)
    ]);
    
    $response = $this->get('/credits/success?payment_intent=pi_test_123');
    
    $response->assertRedirect('/credits');
    $response->assertSessionHas('error', 'Payment was not successful.');
});

test('transactions page shows user payment history', function () {
    // Add some credits to create transaction history
    $this->creditService->addCredits($this->user->id, 10.0, 'Test purchase');
    $this->creditService->deductCreditsForUsage($this->user->id, 'openai-gpt5', 'gpt-5', 100);
    
    $response = $this->get('/credits/transactions');
    
    $response->assertStatus(200);
    $response->assertInertia(fn ($page) => $page
        ->component('Credits/Transactions')
        ->has('transactions')
        ->has('creditStatus')
    );
});

test('payment status endpoint returns correct status', function () {
    $package = CreditPackage::where('slug', 'starter-pack')->first();

    $paymentIntent = PaymentIntent::create([
        'user_id' => $this->user->id,
        'credit_package_id' => $package->id,
        'stripe_payment_intent_id' => 'pi_test_123',
        'status' => 'succeeded',
        'amount' => $package->price,
        'credits_to_add' => $package->total_credits,
        'completed_at' => now(),
    ]);

    // Mock StripePaymentService
    $this->mock(\App\Services\StripePaymentService::class, function ($mock) {
        $mock->shouldReceive('getPaymentIntentStatus')
            ->once()
            ->with('pi_test_123')
            ->andReturn([
                'success' => true,
                'status' => 'succeeded',
                'local_status' => 'succeeded',
                'amount' => 10.00,
                'currency' => 'usd',
            ]);
    });

    $response = $this->getJson('/credits/payment-status?payment_intent_id=pi_test_123');
    
    $response->assertStatus(200);
    $response->assertJson([
        'success' => true,
        'status' => 'succeeded',
    ]);
});

test('unauthenticated user cannot access payment routes', function () {
    $this->post('/logout');

    // Test GET routes
    $getRoutes = [
        '/credits',
        '/credits/success',
        '/credits/transactions',
        '/credits/payment-status',
    ];

    foreach ($getRoutes as $route) {
        $response = $this->get($route);
        $response->assertRedirect('/login');
    }

    // Test POST routes
    $response = $this->post('/credits/payment-intent');
    $response->assertRedirect('/login');
});

test('admin can access refund functionality', function () {
    // Create admin user with refund permission
    $admin = User::factory()->create();
    \Spatie\Permission\Models\Permission::create(['name' => 'manage-payments', 'group_name' => 'admin']);
    $admin->givePermissionTo('manage-payments');
    
    $package = CreditPackage::where('slug', 'starter-pack')->first();
    $paymentIntent = PaymentIntent::create([
        'user_id' => $this->user->id,
        'credit_package_id' => $package->id,
        'stripe_payment_intent_id' => 'pi_test_123',
        'status' => 'succeeded',
        'amount' => $package->price,
        'credits_to_add' => $package->total_credits,
        'completed_at' => now(),
    ]);
    
    // Mock StripePaymentService
    $this->mock(\App\Services\StripePaymentService::class, function ($mock) {
        $mock->shouldReceive('refundPayment')
            ->once()
            ->with('pi_test_123', null)
            ->andReturn([
                'success' => true,
                'refund_id' => 're_test_123',
                'amount' => 10.00,
                'status' => 'succeeded',
            ]);
    });
    
    $response = $this->actingAs($admin)->postJson('/admin/payments/refund', [
        'payment_intent_id' => 'pi_test_123',
        'reason' => 'Customer request',
    ]);
    
    $response->assertStatus(200);
});

test('regular user cannot access refund functionality', function () {
    $response = $this->postJson('/admin/payments/refund', [
        'payment_intent_id' => 'pi_test_123',
        'reason' => 'Customer request',
    ]);
    
    $response->assertStatus(403);
});
