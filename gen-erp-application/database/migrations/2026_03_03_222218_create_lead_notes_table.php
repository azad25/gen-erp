<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_notes', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete(); // Who created the note
            
            // Note Details
            $table->string('title')->nullable();
            $table->text('content');
            $table->string('type')->default('general'); // general, call_log, meeting_notes, follow_up, etc.
            $table->boolean('is_private')->default(false); // Private to the creator
            $table->boolean('is_pinned')->default(false); // Pin important notes
            
            // Attachments
            $table->json('attachments')->nullable(); // File paths/URLs
            
            // Mentions and Tags
            $table->json('mentioned_users')->nullable(); // User IDs mentioned in the note
            $table->json('tags')->nullable(); // Tags for categorization
            
            // Activity Tracking
            $table->timestamp('last_edited_at')->nullable();
            $table->foreignId('last_edited_by')->nullable()->constrained('users')->nullOnDelete();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['company_id', 'lead_id']);
            $table->index(['lead_id', 'type']);
            $table->index(['lead_id', 'is_pinned']);
            $table->index(['user_id']);
            $table->index(['created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_notes');
    }
};