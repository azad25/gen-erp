<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('opportunities', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->unsignedBigInteger('pipeline_id'); // Will add constraint later
            $table->unsignedBigInteger('stage_id'); // Will add constraint later
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            
            // Opportunity Details
            $table->string('name');
            $table->text('description')->nullable();
            $table->decimal('amount', 15, 2);
            $table->string('currency', 3)->default('BDT');
            $table->integer('probability')->default(0); // 0-100%
            $table->date('expected_close_date');
            $table->date('actual_close_date')->nullable();
            
            // Status and Stage
            $table->string('status')->default('open'); // open, won, lost, cancelled
            $table->text('close_reason')->nullable();
            $table->integer('stage_order')->default(0); // For ordering within pipeline
            
            // Source and Campaign
            $table->string('source')->nullable(); // website, referral, campaign, etc.
            $table->string('campaign')->nullable();
            
            // Products/Services
            $table->json('products')->nullable(); // Array of product IDs and quantities
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2); // amount + tax - discount
            
            // Tracking
            $table->timestamp('last_activity_at')->nullable();
            $table->timestamp('won_at')->nullable();
            $table->timestamp('lost_at')->nullable();
            $table->integer('days_in_stage')->default(0);
            
            // Additional Data
            $table->json('custom_fields')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'pipeline_id']);
            $table->index(['company_id', 'stage_id']);
            $table->index(['company_id', 'assigned_to']);
            $table->index(['lead_id']);
            $table->index(['customer_id']);
            $table->index(['expected_close_date']);
            $table->index(['amount']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('opportunities');
    }
};