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
        Schema::create('credits', function (Blueprint $table) {
            $table->id();
            $table->foreignId('user_id')->constrained()->onDelete('cascade');
            $table->decimal('balance', 10, 2)->default(0.00); // Credit balance with 2 decimal places
            $table->decimal('total_purchased', 10, 2)->default(0.00); // Total credits ever purchased
            $table->decimal('total_used', 10, 2)->default(0.00); // Total credits ever used
            $table->timestamp('last_purchase_at')->nullable();
            $table->timestamp('last_usage_at')->nullable();
            $table->timestamps();

            $table->unique('user_id');
            $table->index(['user_id', 'balance']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credits');
    }
};
