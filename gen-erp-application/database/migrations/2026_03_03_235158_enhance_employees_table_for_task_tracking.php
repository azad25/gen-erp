<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->decimal('hourly_rate', 10, 2)->nullable()->after('gross_salary');
            $table->integer('weekly_capacity_hours')->default(40)->after('hourly_rate');
            $table->boolean('is_available_for_projects')->default(true)->after('weekly_capacity_hours');
            $table->json('skills')->nullable()->after('is_available_for_projects');
            $table->json('certifications')->nullable()->after('skills');
        });
    }

    public function down(): void
    {
        Schema::table('employees', function (Blueprint $table) {
            $table->dropColumn([
                'hourly_rate',
                'weekly_capacity_hours',
                'is_available_for_projects',
                'skills',
                'certifications'
            ]);
        });
    }
};
