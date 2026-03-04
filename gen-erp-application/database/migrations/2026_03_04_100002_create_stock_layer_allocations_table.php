<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Allocation records linking a stock-out movement to the layers consumed.
 *
 * This is the COGS audit trail: every unit sold is traceable back to
 * the purchase layer it came from, with its original cost.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::create('stock_layer_allocations', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('stock_layer_id')->constrained('stock_layers')->cascadeOnDelete();
            $table->foreignId('stock_movement_id')->constrained('stock_movements')->cascadeOnDelete();

            $table->float('quantity')->comment('Quantity consumed from this layer');
            $table->unsignedBigInteger('unit_cost')->comment('Cost per unit (from the layer)');
            $table->unsignedBigInteger('cost_amount')->comment('Total cost = quantity * unit_cost');

            $table->timestamps();

            $table->index(['company_id', 'stock_movement_id'], 'idx_alloc_movement');
            $table->index(['company_id', 'stock_layer_id'], 'idx_alloc_layer');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('stock_layer_allocations');
    }
};
