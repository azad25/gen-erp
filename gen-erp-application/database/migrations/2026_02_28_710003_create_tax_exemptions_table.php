<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('tax_exemptions', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('employee_id')->constrained()->cascadeOnDelete();
            $table->string('fiscal_year', 10);
            $table->string('exemption_type', 100);
            $table->bigInteger('amount');
            $table->timestamps();

            $table->unique(['employee_id', 'fiscal_year', 'exemption_type'], 'te_emp_year_type_unique');
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('tax_exemptions');
    }
};
