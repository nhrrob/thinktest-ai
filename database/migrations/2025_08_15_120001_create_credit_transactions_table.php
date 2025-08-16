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
        Schema::create('credit_transactions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('type'); // 'purchase', 'usage', 'refund', 'bonus', 'adjustment'
            $table->decimal('amount', 10, 2); // Positive for credits added, negative for credits used
            $table->decimal('balance_before', 10, 2); // Balance before this transaction
            $table->decimal('balance_after', 10, 2); // Balance after this transaction
            $table->string('description'); // Human-readable description
            $table->json('metadata')->nullable(); // Additional data (AI provider, model, etc.)
            
            // Payment related fields
            $table->string('payment_intent_id')->nullable(); // Stripe payment intent ID
            $table->string('payment_method')->nullable(); // 'stripe', 'paypal', etc.
            $table->string('payment_status')->nullable(); // 'pending', 'completed', 'failed', 'refunded'
            
            // AI usage related fields
            $table->string('ai_provider')->nullable(); // 'openai-gpt5', 'anthropic-claude4-opus', etc.
            $table->string('ai_model')->nullable(); // 'gpt-5', 'claude-opus-4', etc.
            $table->integer('tokens_used')->nullable(); // Number of tokens used (if applicable)
            
            $table->timestamps();

            $table->index(['user_id', 'type']);
            $table->index(['user_id', 'created_at']);
            $table->index(['payment_intent_id']);
            $table->index(['ai_provider', 'created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_transactions');
    }
};
