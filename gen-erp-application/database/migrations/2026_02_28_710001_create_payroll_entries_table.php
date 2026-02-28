<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('payroll_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('payroll_run_id')->constrained('payroll_runs')->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->tinyInteger('period_month');
            $table->smallInteger('period_year');
            $table->unsignedSmallInteger('working_days');
            $table->decimal('present_days', 5, 1);
            $table->decimal('absent_days', 5, 1);
            $table->decimal('leave_days', 5, 1);
            $table->decimal('overtime_hours', 6, 2)->default(0);
            $table->bigInteger('basic_salary');
            $table->bigInteger('gross_salary');
            $table->json('earnings')->nullable();
            $table->json('deductions')->nullable();
            $table->bigInteger('overtime_amount')->default(0);
            $table->bigInteger('attendance_deduction')->default(0);
            $table->bigInteger('tax_deduction')->default(0);
            $table->bigInteger('net_salary');
            $table->string('payment_method', 20)->nullable();
            $table->string('payment_status', 20)->default('pending');
            $table->timestamp('paid_at')->nullable();
            $table->timestamps();

            $table->unique(['payroll_run_id', 'employee_id'], 'pe_run_employee_unique');
            $table->index(['company_id', 'employee_id', 'period_year', 'period_month'], 'pe_emp_period_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('payroll_entries');
    }
};
