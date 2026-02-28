<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('expenses', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('account_id')->nullable()->constrained('accounts');
            $table->foreignId('payment_account_id')->nullable()->constrained('accounts');
            $table->string('expense_number', 100);
            $table->date('expense_date');
            $table->string('category', 100)->nullable();
            $table->string('description', 500);
            $table->bigInteger('amount');
            $table->bigInteger('tax_amount')->default(0);
            $table->bigInteger('total_amount');
            $table->foreignId('payment_method_id')->nullable()->constrained('payment_methods')->nullOnDelete();
            $table->string('reference_number', 100)->nullable();
            $table->string('receipt_url', 500)->nullable();
            $table->string('status', 30)->default('draft');
            $table->json('custom_fields')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'account_id', 'expense_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('expenses');
    }
};
