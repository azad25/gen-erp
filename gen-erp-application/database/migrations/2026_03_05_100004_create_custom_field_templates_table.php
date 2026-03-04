<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_field_templates', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name');
            $table->text('description')->nullable();
            $table->string('domain', 50); // sales, crm, hr, etc.
            $table->string('entity_type', 100); // product, customer, etc.
            $table->json('field_definitions'); // Array of field definitions
            $table->boolean('is_public')->default(false); // Can be shared across companies
            $table->boolean('is_active')->default(true);
            $table->foreignId('created_by')->constrained('users');
            $table->timestamps();

            $table->index(['company_id', 'domain', 'entity_type']);
            $table->index(['company_id', 'is_public', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_field_templates');
    }
};