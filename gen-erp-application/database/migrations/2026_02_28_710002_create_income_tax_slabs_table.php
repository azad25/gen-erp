<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('income_tax_slabs', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('fiscal_year', 10);
            $table->bigInteger('min_income');
            $table->bigInteger('max_income')->nullable();
            $table->decimal('tax_rate', 5, 2);
            $table->string('description', 255)->nullable();
            $table->unsignedSmallInteger('display_order');
            $table->timestamps();

            $table->index(['company_id', 'fiscal_year']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('income_tax_slabs');
    }
};
