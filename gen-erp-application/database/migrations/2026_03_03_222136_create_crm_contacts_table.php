<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('crm_contacts', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('lead_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('customer_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            
            // Contact Information
            $table->string('first_name');
            $table->string('last_name');
            $table->string('email')->nullable();
            $table->string('phone')->nullable();
            $table->string('company_name')->nullable();
            $table->string('job_title')->nullable();
            $table->text('address')->nullable();
            $table->string('city')->nullable();
            $table->string('state')->nullable();
            $table->string('country')->default('BD');
            $table->string('postal_code')->nullable();
            
            // Contact Type and Status
            $table->string('type')->default('lead'); // lead, customer, prospect, partner
            $table->string('status')->default('active'); // active, inactive, blocked
            $table->boolean('is_primary')->default(false);
            
            // Communication Preferences
            $table->boolean('email_opt_in')->default(true);
            $table->boolean('sms_opt_in')->default(true);
            $table->boolean('marketing_opt_in')->default(false);
            $table->string('preferred_contact_method')->default('email'); // email, phone, sms
            $table->string('preferred_language', 5)->default('en');
            
            // Social Media
            $table->string('linkedin_url')->nullable();
            $table->string('facebook_url')->nullable();
            $table->string('twitter_url')->nullable();
            $table->string('website_url')->nullable();
            
            // Additional Data
            $table->json('custom_fields')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['company_id', 'type']);
            $table->index(['company_id', 'status']);
            $table->index(['lead_id']);
            $table->index(['customer_id']);
            $table->index(['email']);
            $table->index(['phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('crm_contacts');
    }
};