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
        Schema::create('ai_conversation_states', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('conversation_id')->unique();
            $table->string('provider')->default('openai'); // openai, anthropic, etc.
            $table->string('status')->default('active'); // active, completed, failed, cancelled
            $table->json('context')->nullable(); // Conversation context and state
            $table->json('messages')->nullable(); // Message history
            $table->json('metadata')->nullable(); // Additional metadata
            $table->string('plugin_file_path')->nullable(); // Path to uploaded plugin file
            $table->string('plugin_file_hash')->nullable(); // Hash of plugin file for caching
            $table->text('generated_tests')->nullable(); // Generated test content
            $table->integer('step')->default(1); // Current conversation step
            $table->integer('total_steps')->default(5); // Total expected steps
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            // Indexes for performance
            $table->index(['user_id', 'status']);
            $table->index(['conversation_id']);
            $table->index(['provider', 'status']);
            $table->index(['created_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('ai_conversation_states');
    }
};
