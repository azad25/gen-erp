<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employee_availability', function (Blueprint $table) {
            $table->id();
            $table->foreignId('employee_id')->constrained('employees')->onDelete('cascade');
            $table->date('date');
            $table->boolean('is_available')->default(true);
            $table->enum('availability_type', ['full_day', 'morning', 'afternoon', 'unavailable'])->default('full_day');
            $table->string('reason')->nullable();
            $table->timestamps();
            
            $table->unique(['employee_id', 'date'], 'unique_employee_date');
            $table->index(['date', 'is_available']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('employee_availability');
    }
};
