<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_order_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('sales_order_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('product_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('variant_id')->nullable()->constrained('product_variants')->nullOnDelete();
            $table->string('description', 500);
            $table->decimal('quantity', 15, 4);
            $table->string('unit', 50);
            $table->bigInteger('unit_price');
            $table->decimal('discount_percent', 5, 2)->default(0);
            $table->bigInteger('discount_amount')->default(0);
            $table->foreignId('tax_group_id')->nullable()->constrained('tax_groups')->nullOnDelete();
            $table->decimal('tax_rate', 5, 2)->default(0);
            $table->bigInteger('tax_amount')->default(0);
            $table->bigInteger('line_total');
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();

            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_order_items');
    }
};
