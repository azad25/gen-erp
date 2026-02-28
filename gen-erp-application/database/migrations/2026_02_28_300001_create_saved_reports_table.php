<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('saved_reports', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->string('name', 255);
            $table->string('entity_type', 100);
            $table->json('selected_fields');
            $table->json('filters')->nullable();
            $table->string('group_by', 100)->nullable();
            $table->json('aggregate')->nullable();
            $table->string('sort_field', 100)->nullable();
            $table->string('sort_direction', 4)->default('asc');
            $table->string('visualisation', 20)->default('table');
            $table->boolean('is_scheduled')->default(false);
            $table->string('schedule_frequency', 20)->nullable();
            $table->json('schedule_recipients')->nullable();
            $table->timestamp('last_run_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'entity_type'], 'sr_company_entity_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('saved_reports');
    }
};
