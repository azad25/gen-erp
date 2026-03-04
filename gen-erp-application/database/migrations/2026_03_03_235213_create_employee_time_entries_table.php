<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('employee_time_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('task_id')->nullable();
            $table->unsignedBigInteger('project_id')->nullable();
            $table->date('entry_date');
            $table->time('start_time')->nullable();
            $table->time('end_time')->nullable();
            $table->decimal('hours', 8, 2);
            $table->text('description')->nullable();
            $table->enum('entry_type', ['task', 'project', 'general', 'break', 'meeting'])->default('task');
            $table->boolean('is_billable')->default(false);
            $table->boolean('is_approved')->default(false);
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('approved_at')->nullable();
            $table->timestamps();

            $table->index(['employee_id', 'entry_date']);
            $table->index(['task_id', 'entry_date']);
            $table->index(['project_id', 'entry_date']);
            $table->index(['entry_date', 'is_approved']);
            $table->index(['is_billable', 'is_approved']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('employee_time_entries');
    }
};