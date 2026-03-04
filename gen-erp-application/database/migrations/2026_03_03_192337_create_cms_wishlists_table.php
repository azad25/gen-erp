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
        Schema::create('cms_wishlists', function (Blueprint $table) {
            $table->id();
            $table->foreignId('customer_id')->constrained('cms_customer_accounts')->onDelete('cascade');
            $table->unsignedBigInteger('product_id'); // References products table
            $table->unsignedBigInteger('product_variant_id')->nullable(); // References product_variants table
            $table->timestamps();
            
            // Indexes
            $table->index(['customer_id']);
            $table->index(['product_id']);
            $table->index(['created_at']);
            
            // Unique constraint to prevent duplicate wishlist items
            $table->unique(['customer_id', 'product_id', 'product_variant_id'], 'unique_wishlist_item');
            
            // Foreign key constraints (will be added later when products table exists)
            // $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            // $table->foreign('product_variant_id')->references('id')->on('product_variants')->onDelete('cascade');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cms_wishlists');
    }
};