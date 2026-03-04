<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_tag_pivot', function (Blueprint $table) {
            $table->id();
            $table->foreignId('lead_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_tag_id')->constrained()->cascadeOnDelete();
            $table->foreignId('tagged_by')->constrained('users')->cascadeOnDelete(); // Who applied the tag
            $table->timestamp('tagged_at')->useCurrent();
            
            // Additional pivot data
            $table->text('notes')->nullable(); // Why this tag was applied
            $table->boolean('is_auto_tagged')->default(false); // Applied by system vs manually
            
            // Indexes
            $table->index(['lead_id']);
            $table->index(['lead_tag_id']);
            $table->index(['tagged_by']);
            $table->index(['tagged_at']);
            
            // Unique constraint to prevent duplicate tags on same lead
            $table->unique(['lead_id', 'lead_tag_id'], 'unique_lead_tag');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_tag_pivot');
    }
};