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
        Schema::create('github_file_test_generations', function (Blueprint $table) {
            $table->id();

            // Foreign keys
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->foreignId('github_repository_id')->constrained()->onDelete('cascade');
            $table->foreignId('ai_conversation_state_id')->nullable()->constrained()->onDelete('set null');

            // File information
            $table->string('file_path', 500);
            $table->string('file_name', 255);
            $table->string('file_sha', 40);
            $table->bigInteger('file_size')->unsigned();
            $table->string('branch', 100);

            // Generation settings
            $table->string('provider', 50);
            $table->string('framework', 50);

            // Generated content
            $table->longText('generated_tests')->nullable();
            $table->json('test_suite')->nullable();
            $table->json('analysis_data')->nullable();

            // Tracking
            $table->string('file_content_hash', 64);
            $table->enum('generation_status', ['pending', 'processing', 'completed', 'failed'])->default('pending');
            $table->text('generation_error')->nullable();
            $table->timestamp('generated_at')->nullable();

            $table->timestamps();

            // Indexes
            $table->index(['user_id', 'github_repository_id'], 'gftg_user_repo_idx');
            $table->index(['file_path', 'github_repository_id'], 'gftg_file_repo_idx');
            $table->index('generation_status', 'gftg_status_idx');
            $table->index('generated_at', 'gftg_generated_at_idx');

            // Unique constraint to prevent duplicate generations for same file
            $table->unique(['github_repository_id', 'file_path', 'branch', 'file_content_hash'], 'gftg_unique_file');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('github_file_test_generations');
    }
};
