<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leads', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('assigned_to')->nullable()->constrained('users')->nullOnDelete();
            $table->foreignId('created_by')->constrained('users')->cascadeOnDelete();
            
            // Lead Information
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
            
            // Lead Management
            $table->string('status')->default('new'); // new, contacted, qualified, unqualified, converted
            $table->string('source')->nullable(); // website, referral, social_media, advertisement, etc.
            $table->integer('score')->default(0); // Lead scoring 0-100
            $table->decimal('estimated_value', 15, 2)->nullable();
            $table->string('currency', 3)->default('BDT');
            $table->date('expected_close_date')->nullable();
            
            // Tracking
            $table->timestamp('last_contacted_at')->nullable();
            $table->timestamp('qualified_at')->nullable();
            $table->timestamp('converted_at')->nullable();
            $table->foreignId('converted_to_customer_id')->nullable()->constrained('customers')->nullOnDelete();
            
            // Additional Data
            $table->json('custom_fields')->nullable();
            $table->text('notes')->nullable();
            
            $table->timestamps();
            $table->softDeletes();
            
            // Indexes
            $table->index(['company_id', 'status']);
            $table->index(['company_id', 'assigned_to']);
            $table->index(['company_id', 'source']);
            $table->index(['company_id', 'score']);
            $table->index(['email']);
            $table->index(['phone']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leads');
    }
};