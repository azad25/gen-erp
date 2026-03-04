<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('performance_reviews', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->foreignId('reviewer_id')->constrained('users');
            $table->date('review_period_start');
            $table->date('review_period_end');
            $table->integer('overall_rating')->nullable(); // 1-5
            $table->integer('technical_skills_rating')->nullable();
            $table->integer('communication_rating')->nullable();
            $table->integer('teamwork_rating')->nullable();
            $table->integer('productivity_rating')->nullable();
            $table->text('strengths')->nullable();
            $table->text('areas_for_improvement')->nullable();
            $table->text('goals')->nullable();
            $table->text('comments')->nullable();
            $table->enum('status', ['draft', 'submitted', 'acknowledged'])->default('draft');
            $table->timestamp('submitted_at')->nullable();
            $table->timestamp('acknowledged_at')->nullable();
            $table->timestamps();
            
            $table->index(['employee_id', 'status']);
            $table->index(['reviewer_id', 'status']);
            $table->index(['review_period_start', 'review_period_end']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('performance_reviews');
    }
};
