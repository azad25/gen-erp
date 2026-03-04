<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_fields', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('form_id')->constrained()->cascadeOnDelete();
            $table->string('field_key');
            $table->string('field_type'); // text, email, select, etc.
            $table->string('label');
            $table->text('placeholder')->nullable();
            $table->text('help_text')->nullable();
            $table->boolean('is_required')->default(false);
            $table->json('validation_rules')->nullable();
            $table->json('options')->nullable(); // For select, radio, checkbox options
            $table->json('settings')->nullable(); // Field-specific settings
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->unique(['form_id', 'field_key']);
            $table->index(['form_id', 'display_order']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_fields');
    }
};