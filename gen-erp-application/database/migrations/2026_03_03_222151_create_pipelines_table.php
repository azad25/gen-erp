<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('pipelines', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            
            // Pipeline Details
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('color', 7)->default('#3B82F6'); // Hex color for UI
            $table->boolean('is_default')->default(false);
            $table->boolean('is_active')->default(true);
            $table->integer('sort_order')->default(0);
            
            // Pipeline Settings
            $table->json('settings')->nullable(); // Pipeline-specific settings
            $table->boolean('auto_move_stages')->default(false);
            $table->integer('default_probability')->default(10); // Default probability for new opportunities
            
            // Metrics
            $table->integer('opportunities_count')->default(0);
            $table->decimal('total_value', 15, 2)->default(0);
            $table->decimal('won_value', 15, 2)->default(0);
            $table->decimal('lost_value', 15, 2)->default(0);
            $table->decimal('conversion_rate', 5, 2)->default(0); // Percentage
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['company_id', 'is_active']);
            $table->index(['company_id', 'is_default']);
            $table->index(['sort_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('pipelines');
    }
};