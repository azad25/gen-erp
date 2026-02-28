<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('customer_transactions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('customer_id')->constrained('customers')->cascadeOnDelete();
            $table->string('type', 30); // invoice|payment|credit_note|adjustment
            $table->string('reference_type', 100)->nullable();
            $table->unsignedBigInteger('reference_id')->nullable();
            $table->bigInteger('amount'); // positive = debit, negative = credit
            $table->bigInteger('balance_after');
            $table->string('description', 500);
            $table->date('transaction_date');
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('created_at')->useCurrent();

            $table->index(
                ['company_id', 'customer_id', 'transaction_date'],
                'ct_company_cust_date_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('customer_transactions');
    }
};
