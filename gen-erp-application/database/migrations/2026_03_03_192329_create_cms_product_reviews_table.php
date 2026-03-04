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
        Schema::create('cms_product_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('cms_sites')->onDelete('cascade');
            $table->unsignedBigInteger('product_id'); // References products table
            $table->unsignedBigInteger('customer_id')->nullable(); // References cms_customer_accounts
            $table->unsignedBigInteger('order_id')->nullable(); // References cms_public_orders for verified purchases
            
            // Review content
            $table->tinyInteger('rating')->unsigned(); // 1-5 stars
            $table->string('title')->nullable();
            $table->text('review')->nullable();
            
            // Customer info (denormalized for guest reviews)
            $table->string('customer_name', 100);
            $table->string('customer_email');
            
            // Review status
            $table->boolean('is_verified_purchase')->default(false);
            $table->boolean('is_approved')->default(false);
            
            // Engagement
            $table->integer('helpful_count')->default(0);
            
            $table->timestamps();
            
            // Indexes
            $table->index(['product_id']);
            $table->index(['customer_id']);
            $table->index(['is_approved']);
            $table->index(['rating']);
            $table->index(['created_at']);
            
            // Foreign key constraints (will be added later when products table exists)
            // $table->foreign('product_id')->references('id')->on('products')->onDelete('cascade');
            // $table->foreign('customer_id')->references('id')->on('cms_customer_accounts')->onDelete('set null');
            // $table->foreign('order_id')->references('id')->on('cms_public_orders')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cms_product_reviews');
    }
};