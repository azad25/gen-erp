<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_capacities', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->date('week_start_date');
            $table->integer('total_capacity_hours');
            $table->integer('allocated_hours')->default(0);
            $table->integer('available_hours');
            $table->decimal('utilization_percentage', 5, 2)->default(0);
            $table->timestamps();
            
            $table->unique(['employee_id', 'week_start_date'], 'unique_employee_week');
            $table->index(['week_start_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_capacities');
    }
};
