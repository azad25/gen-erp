<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_levels', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained('warehouses')->cascadeOnDelete();
            $table->foreignId('product_id')->constrained('products')->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->cascadeOnDelete();
            $table->decimal('quantity', 15, 4)->default(0);
            $table->decimal('reserved_quantity', 15, 4)->default(0);
            $table->timestamps();

            $table->unique(['warehouse_id', 'product_id', 'variant_id'], 'sl_wh_prod_var_unique');
            $table->index(['company_id', 'product_id'], 'sl_company_product_idx');
            $table->index(['company_id', 'warehouse_id'], 'sl_company_warehouse_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_levels');
    }
};
