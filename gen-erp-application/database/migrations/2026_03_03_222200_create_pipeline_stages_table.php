<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pipeline_stages', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('pipeline_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            
            // Stage Details
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('color', 7)->default('#6B7280'); // Hex color for UI
            $table->integer('sort_order');
            $table->boolean('is_active')->default(true);
            
            // Stage Configuration
            $table->integer('probability')->default(10); // Default probability for opportunities in this stage
            $table->boolean('is_closed_won')->default(false); // Mark as won stage
            $table->boolean('is_closed_lost')->default(false); // Mark as lost stage
            $table->boolean('requires_reason')->default(false); // Require reason when moving to this stage
            
            // Stage Actions
            $table->json('entry_actions')->nullable(); // Actions to perform when entering stage
            $table->json('exit_actions')->nullable(); // Actions to perform when leaving stage
            $table->integer('max_days_in_stage')->nullable(); // Alert if opportunity stays too long
            
            // Metrics
            $table->integer('opportunities_count')->default(0);
            $table->decimal('total_value', 15, 2)->default(0);
            $table->decimal('average_days', 8, 2)->default(0); // Average days opportunities spend in this stage
            $table->decimal('conversion_rate', 5, 2)->default(0); // Percentage that move to next stage
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['company_id', 'pipeline_id']);
            $table->index(['pipeline_id', 'sort_order']);
            $table->index(['pipeline_id', 'is_active']);
            
            // Unique constraint
            $table->unique(['pipeline_id', 'sort_order'], 'unique_pipeline_stage_order');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pipeline_stages');
    }
};