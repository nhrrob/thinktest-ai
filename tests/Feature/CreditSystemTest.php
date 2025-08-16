<?php

use App\Models\User;
use App\Models\Credit;
use App\Models\CreditTransaction;
use App\Models\CreditPackage;
use App\Models\PaymentIntent;
use App\Services\CreditService;
use Illuminate\Foundation\Testing\RefreshDatabase;

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

test('credit model creates and manages user credits', function () {
    $credit = Credit::getOrCreateForUser($this->user->id);
    
    expect($credit->user_id)->toBe($this->user->id);
    expect((float)$credit->balance)->toBe(0.0);
    expect((float)$credit->total_purchased)->toBe(0.0);
    expect((float)$credit->total_used)->toBe(0.0);
});

test('credit model adds credits correctly', function () {
    $credit = Credit::getOrCreateForUser($this->user->id);
    
    $transaction = $credit->addCredits(10.0, 'Test purchase');
    
    expect((float)$credit->fresh()->balance)->toBe(10.0);
    expect((float)$credit->fresh()->total_purchased)->toBe(10.0);
    expect($transaction->type)->toBe('purchase');
    expect((float)$transaction->amount)->toBe(10.0);
    expect((float)$transaction->balance_after)->toBe(10.0);
});

test('credit model deducts credits correctly', function () {
    $credit = Credit::getOrCreateForUser($this->user->id);
    $credit->addCredits(10.0, 'Initial credits');
    
    $transaction = $credit->deductCredits(3.0, 'AI usage');
    
    expect((float)$credit->fresh()->balance)->toBe(7.0);
    expect((float)$credit->fresh()->total_used)->toBe(3.0);
    expect($transaction->type)->toBe('usage');
    expect((float)$transaction->amount)->toBe(-3.0);
    expect((float)$transaction->balance_after)->toBe(7.0);
});

test('credit model prevents overdraft', function () {
    $credit = Credit::getOrCreateForUser($this->user->id);
    
    expect(fn() => $credit->deductCredits(5.0, 'Overdraft attempt'))
        ->toThrow(\RuntimeException::class, 'Insufficient credits');
});

test('credit service gets user balance', function () {
    $balance = $this->creditService->getUserBalance($this->user->id);
    expect($balance)->toBe(0.0);
    
    $this->creditService->addCredits($this->user->id, 15.0, 'Test credits');
    
    $balance = $this->creditService->getUserBalance($this->user->id);
    expect($balance)->toBe(15.0);
});

test('credit service checks provider costs', function () {
    $gpt5Cost = $this->creditService->getProviderCost('openai-gpt5');
    $gpt5MiniCost = $this->creditService->getProviderCost('openai-gpt5-mini');
    $claude4OpusCost = $this->creditService->getProviderCost('anthropic-claude4-opus');
    $mockCost = $this->creditService->getProviderCost('mock');
    
    expect($gpt5Cost)->toBe(2.0);
    expect($gpt5MiniCost)->toBe(1.0);
    expect($claude4OpusCost)->toBe(3.0);
    expect($mockCost)->toBe(0.0);
});

test('credit service checks sufficient credits for providers', function () {
    $this->creditService->addCredits($this->user->id, 2.5, 'Test credits');
    
    expect($this->creditService->hasCreditsForProvider($this->user->id, 'openai-gpt5-mini'))->toBeTrue();
    expect($this->creditService->hasCreditsForProvider($this->user->id, 'openai-gpt5'))->toBeTrue();
    expect($this->creditService->hasCreditsForProvider($this->user->id, 'anthropic-claude4-opus'))->toBeFalse();
});

test('credit service deducts credits for AI usage', function () {
    $this->creditService->addCredits($this->user->id, 10.0, 'Initial credits');
    
    $transaction = $this->creditService->deductCreditsForUsage(
        $this->user->id,
        'openai-gpt5',
        'gpt-5',
        150
    );
    
    expect($transaction->type)->toBe('usage');
    expect($transaction->ai_provider)->toBe('openai-gpt5');
    expect($transaction->ai_model)->toBe('gpt-5');
    expect($transaction->tokens_used)->toBe(150);
    expect((float)$transaction->amount)->toBe(-2.0);
    
    $balance = $this->creditService->getUserBalance($this->user->id);
    expect($balance)->toBe(8.0);
});

test('credit service gets user credit status', function () {
    $this->creditService->addCredits($this->user->id, 20.0, 'Purchase');
    $this->creditService->deductCreditsForUsage($this->user->id, 'openai-gpt5-mini', 'gpt-5-mini');
    
    $status = $this->creditService->getUserCreditStatus($this->user->id);
    
    expect((float)$status['balance'])->toBe(19.0);
    expect((float)$status['total_purchased'])->toBe(20.0);
    expect((float)$status['total_used'])->toBe(1.0);
    expect($status['recent_transactions'])->toHaveCount(2);
    expect($status['usage_stats']['total_uses'])->toBe(1);
});

test('credit packages are properly configured', function () {
    $packages = CreditPackage::active()->ordered()->get();
    
    expect($packages)->toHaveCount(4);
    
    $starterPack = $packages->where('slug', 'starter-pack')->first();
    expect($starterPack->name)->toBe('Starter Pack');
    expect((float)$starterPack->credits)->toBe(25.0);
    expect((float)$starterPack->price)->toBe(9.99);
    expect((float)$starterPack->total_credits)->toBe(25.0); // No bonus

    $professionalPack = $packages->where('slug', 'professional-pack')->first();
    expect($professionalPack->is_popular)->toBeTrue();
    expect((float)$professionalPack->total_credits)->toBe(110.0); // 100 + 10 bonus
});

test('credit service gets available packages', function () {
    $packages = $this->creditService->getAvailablePackages();
    
    expect($packages)->toHaveCount(4);
    expect($packages->first()->is_active)->toBeTrue();
});

test('credit service recommends appropriate package', function () {
    $recommended = $this->creditService->getRecommendedPackage($this->user->id);
    
    // New user should get starter pack
    expect($recommended->slug)->toBe('starter-pack');
});

test('payment intent creation works correctly', function () {
    $package = CreditPackage::where('slug', 'professional-pack')->first();
    
    $paymentIntent = $this->creditService->createPaymentIntent($this->user->id, $package->id);
    
    expect($paymentIntent->user_id)->toBe($this->user->id);
    expect($paymentIntent->credit_package_id)->toBe($package->id);
    expect((float)$paymentIntent->amount)->toBe((float)$package->price);
    expect((float)$paymentIntent->credits_to_add)->toBe((float)$package->total_credits);
    expect($paymentIntent->status)->toBe('pending');
});

test('successful payment processing adds credits', function () {
    $package = CreditPackage::where('slug', 'starter-pack')->first();
    
    $paymentIntent = PaymentIntent::create([
        'user_id' => $this->user->id,
        'credit_package_id' => $package->id,
        'stripe_payment_intent_id' => 'pi_test_123',
        'status' => 'pending',
        'amount' => $package->price,
        'credits_to_add' => $package->total_credits,
    ]);
    
    $this->creditService->processSuccessfulPayment('pi_test_123');
    
    $paymentIntent->refresh();
    expect($paymentIntent->status)->toBe('succeeded');
    expect($paymentIntent->completed_at)->not->toBeNull();
    
    $balance = $this->creditService->getUserBalance($this->user->id);
    expect($balance)->toBe((float)$package->total_credits);
    
    $transaction = CreditTransaction::where('user_id', $this->user->id)->first();
    expect($transaction->type)->toBe('purchase');
    expect($transaction->payment_intent_id)->toBe('pi_test_123');
});

test('credit transaction displays correct information', function () {
    $credit = Credit::getOrCreateForUser($this->user->id);
    $transaction = $credit->addCredits(10.0, 'Test purchase', ['price' => 9.99]);
    
    expect($transaction->formatted_amount)->toBe('+10.00');
    expect($transaction->type_display)->toBe('Credit Purchase');
    expect($transaction->cost_per_credit)->toBe(0.999);
    expect($transaction->isCreditAddition())->toBeTrue();
    expect($transaction->isCreditDeduction())->toBeFalse();
});

test('credit transaction for AI usage displays correctly', function () {
    $credit = Credit::getOrCreateForUser($this->user->id);
    $credit->addCredits(10.0, 'Initial');
    
    $transaction = $credit->deductCredits(2.0, 'AI usage');
    $transaction->update([
        'ai_provider' => 'openai-gpt5',
        'ai_model' => 'gpt-5',
    ]);
    
    expect($transaction->formatted_amount)->toBe('-2.00');
    expect($transaction->provider_display)->toBe('OpenAI GPT-5');
    expect($transaction->isCreditDeduction())->toBeTrue();
    expect($transaction->isCreditAddition())->toBeFalse();
});

test('user model credit methods work correctly', function () {
    expect($this->user->hasPurchasedCredits())->toBeFalse();
    expect($this->user->getCreditBalance())->toBe(0.0);
    
    $this->creditService->addCredits($this->user->id, 5.0, 'Test');
    
    $this->user->refresh();
    expect($this->user->hasPurchasedCredits())->toBeTrue();
    expect((float)$this->user->getCreditBalance())->toBe(5.0);
    expect($this->user->hasCreditsForProvider('openai-gpt5-mini'))->toBeTrue();
    expect($this->user->hasCreditsForProvider('anthropic-claude4-opus'))->toBeTrue(); // 5 credits >= 3 cost
});
