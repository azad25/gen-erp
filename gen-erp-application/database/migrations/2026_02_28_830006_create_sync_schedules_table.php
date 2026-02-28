<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('sync_schedules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_integration_id')->constrained('company_integrations')->cascadeOnDelete();
            $table->string('entity_type', 100);
            $table->string('direction', 20); // push, pull, bidirectional
            $table->string('frequency', 20); // realtime, every_5min, every_15min, hourly, daily, weekly
            $table->timestamp('last_run_at')->nullable();
            $table->timestamp('next_run_at')->nullable();
            $table->string('last_cursor', 500)->nullable(); // for incremental sync
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['is_active', 'next_run_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('sync_schedules');
    }
};
