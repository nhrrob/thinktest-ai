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
        Schema::create('credit_packages', function (Blueprint $table) {
            $table->id();
            $table->string('name'); // 'Starter Pack', 'Professional Pack', etc.
            $table->string('slug')->unique(); // 'starter-pack', 'professional-pack', etc.
            $table->text('description'); // Package description
            $table->decimal('credits', 10, 2); // Number of credits in this package
            $table->decimal('price', 8, 2); // Price in USD
            $table->decimal('price_per_credit', 8, 4); // Calculated price per credit
            $table->integer('bonus_credits')->default(0); // Bonus credits for this package
            $table->boolean('is_popular')->default(false); // Mark as popular package
            $table->boolean('is_active')->default(true); // Enable/disable package
            $table->integer('sort_order')->default(0); // Display order
            $table->json('features')->nullable(); // Package features/benefits
            $table->timestamps();

            $table->index(['is_active', 'sort_order']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('credit_packages');
    }
};
