<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('calendar_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('calendar_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();
            
            // Polymorphic relationship to any model (Activity, Task, Leave, etc.)
            $table->string('eventable_type')->nullable();
            $table->unsignedBigInteger('eventable_id')->nullable();
            
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('location')->nullable();
            $table->dateTime('start_at');
            $table->dateTime('end_at')->nullable();
            $table->boolean('all_day')->default(false);
            
            $table->enum('type', [
                'meeting', 'call', 'task', 'deadline', 'leave', 
                'availability', 'milestone', 'personal', 'company'
            ])->default('personal');
            
            $table->enum('status', ['scheduled', 'in_progress', 'completed', 'cancelled'])
                ->default('scheduled');
            
            $table->string('color', 7)->nullable();
            $table->boolean('is_recurring')->default(false);
            $table->string('recurrence_rule')->nullable();
            $table->integer('reminder_minutes')->nullable();
            $table->json('attendees')->nullable();
            $table->json('metadata')->nullable();
            
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'calendar_id']);
            $table->index(['eventable_type', 'eventable_id']);
            $table->index(['start_at', 'end_at']);
            $table->index(['type', 'status']);
            $table->index('user_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('calendar_events');
    }
};
