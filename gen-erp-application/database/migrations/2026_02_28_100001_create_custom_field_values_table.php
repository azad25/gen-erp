<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('custom_field_values', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('entity_type', 100);
            $table->unsignedBigInteger('entity_id');
            $table->string('field_key', 100);
            $table->text('value_text')->nullable();
            $table->decimal('value_number', 20, 4)->nullable();
            $table->boolean('value_boolean')->nullable();
            $table->date('value_date')->nullable();
            $table->json('value_json')->nullable();
            $table->timestamps();

            $table->unique(
                ['company_id', 'entity_type', 'entity_id', 'field_key'],
                'cfv_company_entity_id_key_unique'
            );
            $table->index(
                ['company_id', 'entity_type', 'entity_id'],
                'cfv_company_entity_id_idx'
            );
            $table->index(
                ['company_id', 'field_key', 'value_text'],
                'cfv_company_key_text_idx'
            );
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('custom_field_values');
    }
};
