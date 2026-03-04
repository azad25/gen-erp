<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('time_entries', function (Blueprint $table) {
            $table->id();
            $table->foreignId('task_id')->constrained()->onDelete('cascade');
            $table->foreignId('user_id')->constrained();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->text('description')->nullable();
            $table->decimal('hours', 8, 2);
            $table->date('entry_date');
            $table->enum('type', ['development', 'meeting', 'review', 'testing', 'documentation', 'other'])->default('development');
            $table->boolean('is_billable')->default(true);
            $table->decimal('hourly_rate', 10, 2)->nullable();
            $table->decimal('amount', 15, 2)->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['task_id', 'entry_date']);
            $table->index(['user_id', 'entry_date']);
            $table->index(['project_id', 'entry_date']);
            $table->index(['entry_date', 'is_billable']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('time_entries');
    }
};