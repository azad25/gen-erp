<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Stock layers for FIFO inventory valuation.
 *
 * Each stock-in (purchase, adjustment-in, opening balance) creates a layer.
 * Stock-out operations consume layers oldest-first (FIFO) and record
 * allocations in the related `stock_layer_allocations` table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_layers', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('warehouse_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->constrained()->cascadeOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->foreignId('source_movement_id')->constrained('stock_movements')->cascadeOnDelete();

            $table->float('quantity_in')->comment('Original quantity received');
            $table->float('quantity_remaining')->comment('Remaining quantity available for consumption');
            $table->unsignedBigInteger('unit_cost')->comment('Cost per unit in smallest currency unit (paise)');
            $table->date('layer_date');
            $table->timestamps();

            // FIFO ordering: oldest layer consumed first
            $table->index(
                ['company_id', 'product_id', 'warehouse_id', 'layer_date', 'id'],
                'idx_stock_layers_fifo'
            );
            $table->index(['company_id', 'product_id', 'variant_id', 'warehouse_id'], 'idx_stock_layers_lookup');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_layers');
    }
};
