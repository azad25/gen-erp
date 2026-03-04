<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_worklogs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->date('log_date');
            $table->decimal('total_hours', 8, 2)->default(0);
            $table->decimal('billable_hours', 8, 2)->default(0);
            $table->integer('tasks_completed')->default(0);
            $table->text('summary')->nullable();
            $table->enum('mood', ['excellent', 'good', 'neutral', 'tired', 'stressed'])->nullable();
            $table->timestamps();
            
            $table->unique(['employee_id', 'log_date'], 'unique_employee_date');
            $table->index(['log_date']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_worklogs');
    }
};
