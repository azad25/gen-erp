<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('task_checklist_items', function (Blueprint $table) {
            $table->id();
            $table->foreignId('checklist_id')->constrained('task_checklists')->onDelete('cascade');
            $table->string('title');
            $table->boolean('is_completed')->default(false);
            $table->foreignId('completed_by')->nullable()->constrained('users');
            $table->timestamp('completed_at')->nullable();
            $table->integer('sort_order')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['checklist_id', 'sort_order']);
            $table->index(['checklist_id', 'is_completed']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('task_checklist_items');
    }
};