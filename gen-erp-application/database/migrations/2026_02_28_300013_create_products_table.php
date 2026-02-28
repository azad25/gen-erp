<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('products', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('category_id')
                ->nullable()
                ->constrained('product_categories')
                ->nullOnDelete();
            $table->string('name', 255);
            $table->string('slug', 255);
            $table->string('sku', 100)->nullable();
            $table->string('barcode', 100)->nullable();
            $table->text('description')->nullable();
            $table->string('product_type', 20)->default('product');
            $table->string('unit', 50)->default('pcs');
            $table->unsignedBigInteger('cost_price')->default(0);
            $table->unsignedBigInteger('selling_price')->default(0);
            $table->unsignedBigInteger('min_selling_price')->default(0);
            $table->foreignId('tax_group_id')
                ->nullable()
                ->constrained('tax_groups')
                ->nullOnDelete();
            $table->boolean('track_inventory')->default(true);
            $table->unsignedInteger('low_stock_threshold')->default(0);
            $table->boolean('has_variants')->default(false);
            $table->boolean('is_active')->default(true);
            $table->string('image_url', 500)->nullable();
            $table->json('custom_fields')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'slug'], 'products_company_slug_unique');
            $table->index(['company_id', 'category_id', 'is_active'], 'products_company_cat_active_idx');
            $table->index(['company_id', 'barcode'], 'products_company_barcode_idx');
            $table->index(['company_id', 'sku'], 'products_company_sku_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('products');
    }
};
