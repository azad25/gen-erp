<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('units', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->string('abbreviation', 20);
            $table->timestamps();

            $table->index('company_id');
            $table->unique(['company_id', 'abbreviation'], 'units_company_abbr_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('units');
    }
};
