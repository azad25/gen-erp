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
        Schema::create('tasks', function (Blueprint $table) {
            $table->id();
            $table->foreignId('project_id')->constrained()->onDelete('cascade');
            $table->foreignId('board_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('board_column_id')->nullable()->constrained()->onDelete('set null');
            $table->foreignId('parent_task_id')->nullable()->constrained('tasks')->onDelete('cascade');
            $table->string('title');
            $table->text('description')->nullable();
            $table->enum('status', ['todo', 'in_progress', 'in_review', 'testing', 'completed', 'cancelled'])
                  ->default('todo');
            $table->enum('priority', ['low', 'medium', 'high', 'urgent'])
                  ->default('medium');
            $table->enum('type', ['task', 'bug', 'feature', 'improvement', 'epic', 'story'])
                  ->default('task');
            $table->foreignId('assignee_id')->nullable()
                  ->constrained('employees')->onDelete('set null');
            $table->foreignId('reporter_id')->nullable()
                  ->constrained('employees')->onDelete('set null');
            $table->date('start_date')->nullable();
            $table->date('due_date')->nullable();
            $table->decimal('estimated_hours', 8, 2)->nullable();
            $table->decimal('actual_hours', 8, 2)->default(0);
            $table->integer('story_points')->nullable();
            $table->integer('position')->default(0);
            $table->json('tags')->nullable();
            $table->json('settings')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->index(['project_id', 'status']);
            $table->index(['project_id', 'assignee_id']);
            $table->index(['board_id', 'board_column_id']);
            $table->index(['assignee_id']);
            $table->index(['due_date']);
            $table->index(['parent_task_id']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tasks');
    }
};