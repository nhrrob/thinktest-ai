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
        Schema::create('user_api_tokens', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('provider'); // 'openai', 'anthropic'
            $table->text('token'); // Encrypted API token
            $table->string('display_name')->nullable(); // User-friendly name for the token
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_used_at')->nullable();
            $table->json('usage_stats')->nullable(); // Store usage statistics
            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'provider']);
            $table->index(['user_id', 'is_active']);
            $table->unique(['user_id', 'provider']); // One token per provider per user
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('user_api_tokens');
    }
};
