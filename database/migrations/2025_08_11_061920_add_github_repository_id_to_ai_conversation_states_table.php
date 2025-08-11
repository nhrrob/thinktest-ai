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
        Schema::table('ai_conversation_states', function (Blueprint $table) {
            $table->foreignId('github_repository_id')->nullable()->constrained()->onDelete('set null');
            $table->string('source_type')->default('file'); // 'file' or 'github'
            $table->index(['source_type', 'github_repository_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('ai_conversation_states', function (Blueprint $table) {
            $table->dropForeign(['github_repository_id']);
            $table->dropColumn(['github_repository_id', 'source_type']);
        });
    }
};
