<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_skills', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->string('skill_name', 100);
            $table->enum('proficiency_level', ['beginner', 'intermediate', 'advanced', 'expert']);
            $table->integer('years_of_experience')->default(0);
            $table->boolean('is_certified')->default(false);
            $table->date('last_used_date')->nullable();
            $table->timestamps();
            
            $table->unique(['employee_id', 'skill_name'], 'unique_employee_skill');
            $table->index(['skill_name', 'proficiency_level']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_skills');
    }
};
