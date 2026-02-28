<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_runs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('run_number', 100);
            $table->tinyInteger('period_month');
            $table->smallInteger('period_year');
            $table->string('status', 30)->default('draft');
            $table->unsignedInteger('total_employees')->default(0);
            $table->bigInteger('total_gross')->default(0);
            $table->bigInteger('total_deductions')->default(0);
            $table->bigInteger('total_net')->default(0);
            $table->bigInteger('total_tax')->default(0);
            $table->date('payment_date')->nullable();
            $table->text('notes')->nullable();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();

            $table->unique(['company_id', 'period_month', 'period_year'], 'pr_company_period_unique');
            $table->index(['company_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_runs');
    }
};
