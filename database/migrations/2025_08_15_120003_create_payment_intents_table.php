<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('payment_intents', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('credit_package_id')->nullable()->constrained()->onDelete('set null');
            $table->string('stripe_payment_intent_id')->unique(); // Stripe PI ID
            $table->string('status'); // 'pending', 'processing', 'succeeded', 'failed', 'canceled'
            $table->decimal('amount', 8, 2); // Amount in USD
            $table->string('currency', 3)->default('usd'); // Currency code
            $table->decimal('credits_to_add', 10, 2); // Credits to be added upon success
            $table->json('stripe_metadata')->nullable(); // Stripe payment intent metadata
            $table->timestamp('stripe_created_at')->nullable(); // When created in Stripe
            $table->timestamp('completed_at')->nullable(); // When payment was completed
            $table->timestamp('failed_at')->nullable(); // When payment failed
            $table->text('failure_reason')->nullable(); // Reason for failure
            $table->timestamps();

            $table->index(['user_id', 'status']);
            $table->index(['stripe_payment_intent_id']);
            $table->index(['status', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('payment_intents');
    }
};
