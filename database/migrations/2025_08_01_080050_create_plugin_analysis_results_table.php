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
        Schema::create('plugin_analysis_results', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('filename');
            $table->string('file_hash')->unique();
            $table->json('analysis_data'); // Complete analysis results
            $table->json('wordpress_patterns')->nullable(); // WordPress-specific patterns found
            $table->json('functions')->nullable(); // Functions detected
            $table->json('classes')->nullable(); // Classes detected
            $table->json('hooks')->nullable(); // WordPress hooks
            $table->json('filters')->nullable(); // WordPress filters
            $table->json('security_patterns')->nullable(); // Security-related patterns
            $table->json('test_recommendations')->nullable(); // AI-generated test recommendations
            $table->integer('complexity_score')->nullable(); // Code complexity score
            $table->timestamp('analyzed_at');
            $table->timestamps();

            // Indexes for performance
            $table->index(['user_id', 'created_at']);
            $table->index(['file_hash']);
            $table->index(['analyzed_at']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('plugin_analysis_results');
    }
};
