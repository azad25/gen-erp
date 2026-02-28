<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('purchase_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('supplier_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('warehouse_id')->constrained();
            $table->string('reference_number', 100);
            $table->string('supplier_reference', 100)->nullable();
            $table->date('order_date');
            $table->date('expected_delivery_date')->nullable();
            $table->string('status', 30)->default('draft');
            $table->bigInteger('subtotal')->default(0);
            $table->bigInteger('discount_amount')->default(0);
            $table->bigInteger('tax_amount')->default(0);
            $table->bigInteger('shipping_amount')->default(0);
            $table->bigInteger('total_amount')->default(0);
            $table->bigInteger('amount_received_value')->default(0);
            $table->text('notes')->nullable();
            $table->text('terms_conditions')->nullable();
            $table->json('custom_fields')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'supplier_id', 'order_date']);
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('purchase_orders');
    }
};
