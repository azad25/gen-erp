<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_field_definitions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('entity_type', 100);
            $table->string('field_key', 100);
            $table->string('label', 255);
            $table->string('field_type', 50);
            $table->boolean('is_required')->default(false);
            $table->boolean('is_filterable')->default(false);
            $table->boolean('is_searchable')->default(false);
            $table->boolean('show_in_list')->default(false);
            $table->text('default_value')->nullable();
            $table->json('options')->nullable();
            $table->json('validation_rules')->nullable();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->string('generated_column_name', 100)->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'entity_type', 'field_key'], 'cfd_company_entity_key_unique');
            $table->index(['company_id', 'entity_type', 'is_active'], 'cfd_company_entity_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_field_definitions');
    }
};
