<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('leave_types', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 100);
            $table->unsignedSmallInteger('days_per_year');
            $table->boolean('is_paid')->default(true);
            $table->boolean('carry_forward')->default(false);
            $table->unsignedSmallInteger('max_carry_forward_days')->default(0);
            $table->boolean('requires_approval')->default(true);
            $table->timestamps();

            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('leave_types');
    }
};
