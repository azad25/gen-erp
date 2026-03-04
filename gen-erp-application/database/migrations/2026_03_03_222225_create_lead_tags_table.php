<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('lead_tags', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            
            // Tag Details
            $table->string('name');
            $table->string('slug')->nullable(); // URL-friendly version
            $table->text('description')->nullable();
            $table->string('color', 7)->default('#6B7280'); // Hex color for UI
            $table->string('icon')->nullable(); // Icon class or name
            
            // Tag Configuration
            $table->boolean('is_active')->default(true);
            $table->boolean('is_system')->default(false); // System tags cannot be deleted
            $table->string('category')->nullable(); // Group tags by category
            $table->integer('sort_order')->default(0);
            
            // Usage Statistics
            $table->integer('usage_count')->default(0); // How many leads use this tag
            $table->timestamp('last_used_at')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['company_id', 'is_active']);
            $table->index(['company_id', 'category']);
            $table->index(['slug']);
            $table->index(['usage_count']);
            
            // Unique constraints
            $table->unique(['company_id', 'name'], 'unique_company_tag_name');
            $table->unique(['company_id', 'slug'], 'unique_company_tag_slug');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('lead_tags');
    }
};