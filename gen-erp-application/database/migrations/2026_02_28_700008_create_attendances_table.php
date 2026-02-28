<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('attendances', function (Blueprint $table) {
            $table->bigIncrements('id');
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->date('attendance_date');
            $table->time('check_in')->nullable();
            $table->time('check_out')->nullable();
            $table->string('status', 30)->default('present');
            $table->decimal('working_hours', 4, 2)->nullable();
            $table->decimal('overtime_hours', 4, 2)->default(0);
            $table->string('notes', 255)->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'employee_id', 'attendance_date'], 'att_emp_date_unique');
            $table->index(['company_id', 'employee_id', 'attendance_date'], 'att_emp_date_idx');
            $table->index(['company_id', 'attendance_date', 'status'], 'att_date_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('attendances');
    }
};
