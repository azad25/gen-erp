<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sales_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('warehouse_id')->constrained();
            $table->string('reference_number', 100);
            $table->string('customer_reference', 100)->nullable();
            $table->date('order_date');
            $table->date('delivery_date')->nullable();
            $table->string('status', 30)->default('draft');
            $table->bigInteger('subtotal')->default(0);
            $table->bigInteger('discount_amount')->default(0);
            $table->bigInteger('tax_amount')->default(0);
            $table->bigInteger('shipping_amount')->default(0);
            $table->bigInteger('total_amount')->default(0);
            $table->text('notes')->nullable();
            $table->text('terms_conditions')->nullable();
            $table->json('custom_fields')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'customer_id', 'order_date']);
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sales_orders');
    }
};
