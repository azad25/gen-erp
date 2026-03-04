<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_activities', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // Who performed the activity
            
            // Related Entity (polymorphic)
            $table->morphs('subject'); // Can be lead, opportunity, contact, customer
            
            // Activity Details
            $table->string('type'); // call, email, meeting, task, note, etc.
            $table->string('title');
            $table->text('description')->nullable();
            $table->string('status')->default('completed'); // scheduled, in_progress, completed, cancelled
            $table->string('priority')->default('medium'); // low, medium, high, urgent
            
            // Timing
            $table->timestamp('scheduled_at')->nullable();
            $table->timestamp('started_at')->nullable();
            $table->timestamp('completed_at')->nullable();
            $table->integer('duration_minutes')->nullable(); // Actual duration
            $table->integer('planned_duration_minutes')->nullable(); // Planned duration
            
            // Communication Details
            $table->string('direction')->nullable(); // inbound, outbound (for calls/emails)
            $table->string('outcome')->nullable(); // successful, no_answer, busy, etc.
            $table->text('outcome_notes')->nullable();
            
            // Task/Reminder Details
            $table->timestamp('due_date')->nullable();
            $table->boolean('is_reminder')->default(false);
            $table->timestamp('reminder_at')->nullable();
            $table->boolean('reminder_sent')->default(false);
            
            // Email/Communication Details
            $table->string('email_subject')->nullable();
            $table->text('email_body')->nullable();
            $table->json('attachments')->nullable(); // File paths/URLs
            
            // Meeting Details
            $table->string('meeting_location')->nullable();
            $table->string('meeting_link')->nullable(); // Video call link
            $table->json('attendees')->nullable(); // List of attendees
            
            // Additional Data
            $table->json('custom_fields')->nullable();
            $table->json('metadata')->nullable(); // Additional activity-specific data
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['company_id', 'type']);
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'user_id']);
            $table->index(['scheduled_at']);
            $table->index(['due_date']);
            $table->index(['completed_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_activities');
    }
};