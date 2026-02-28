<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_balances', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->foreignId('leave_type_id')->constrained()->cascadeOnDelete();
            $table->year('year');
            $table->decimal('allocated_days', 5, 1);
            $table->decimal('used_days', 5, 1)->default(0);
            $table->decimal('carried_forward', 5, 1)->default(0);
            $table->timestamps();

            $table->unique(['employee_id', 'leave_type_id', 'year'], 'lb_emp_type_year_unique');
            $table->index('company_id');
        });

        DB::statement('ALTER TABLE leave_balances ADD COLUMN balance DECIMAL(5,1) GENERATED ALWAYS AS (allocated_days + carried_forward - used_days) STORED AFTER carried_forward');
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_balances');
    }
};
