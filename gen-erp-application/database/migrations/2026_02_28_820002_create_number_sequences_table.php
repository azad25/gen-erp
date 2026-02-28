<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('number_sequences', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('document_type', 100);
            $table->string('prefix', 20)->nullable();
            $table->string('suffix', 20)->nullable();
            $table->string('separator', 5)->default('-');
            $table->unsignedTinyInteger('padding')->default(4);
            $table->unsignedInteger('next_number')->default(1);
            $table->string('reset_frequency', 20)->default('never'); // never, yearly, monthly
            $table->date('last_reset_at')->nullable();
            $table->boolean('include_date')->default(false);
            $table->string('date_format', 20)->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'document_type']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('number_sequences');
    }
};
