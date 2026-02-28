<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('invoices', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('sales_order_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('warehouse_id')->constrained();
            $table->string('invoice_number', 100);
            $table->string('mushak_number', 100)->nullable();
            $table->date('invoice_date');
            $table->date('due_date');
            $table->string('status', 30)->default('draft');
            $table->bigInteger('subtotal')->default(0);
            $table->bigInteger('discount_amount')->default(0);
            $table->bigInteger('tax_amount')->default(0);
            $table->bigInteger('shipping_amount')->default(0);
            $table->bigInteger('total_amount')->default(0);
            $table->bigInteger('amount_paid')->default(0);
            $table->text('notes')->nullable();
            $table->text('terms_conditions')->nullable();
            $table->json('custom_fields')->nullable();
            $table->boolean('stock_deducted')->default(false);
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'customer_id', 'invoice_date']);
            $table->index(['company_id', 'status', 'due_date']);
        });

        // Add generated column for balance_due
        DB::statement('ALTER TABLE invoices ADD COLUMN balance_due BIGINT GENERATED ALWAYS AS (total_amount - amount_paid) STORED AFTER amount_paid');
    }

    public function down(): void
    {
        Schema::dropIfExists('invoices');
    }
};
