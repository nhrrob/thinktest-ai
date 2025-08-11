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
        Schema::create('github_repositories', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->string('owner');
            $table->string('repo');
            $table->string('full_name')->index(); // owner/repo
            $table->string('branch');
            $table->bigInteger('github_id')->nullable();
            $table->text('description')->nullable();
            $table->boolean('is_private')->default(false);
            $table->string('default_branch')->nullable();
            $table->bigInteger('size_bytes')->nullable();
            $table->string('language')->nullable();
            $table->json('languages')->nullable();
            $table->string('clone_url')->nullable();
            $table->string('html_url')->nullable();
            $table->timestamp('last_updated_at')->nullable();
            $table->json('plugin_structure')->nullable();
            $table->integer('file_count')->default(0);
            $table->string('processing_status')->default('pending'); // pending, processing, completed, failed
            $table->text('processing_error')->nullable();
            $table->timestamp('processed_at')->nullable();
            $table->timestamps();

            $table->unique(['user_id', 'full_name', 'branch']);
            $table->index(['owner', 'repo']);
            $table->index('processing_status');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('github_repositories');
    }
};
