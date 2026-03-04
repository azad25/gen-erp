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
        Schema::create('cms_cart_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('cart_id')->constrained('cms_shopping_carts')->onDelete('cascade');
            $table->foreignId('product_id')->constrained('products');
            $table->foreignId('product_variant_id')->nullable()->constrained('product_variants');
            $table->integer('quantity')->default(1);
            $table->decimal('price', 15, 2); // Price at time of adding
            $table->timestamps();
            
            $table->unique(['cart_id', 'product_id', 'product_variant_id'], 'unique_cart_product');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cms_cart_items');
    }
};
