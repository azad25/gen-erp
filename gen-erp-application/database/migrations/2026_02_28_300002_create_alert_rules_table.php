<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('alert_rules', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('name', 255);
            $table->string('entity_type', 100);
            $table->string('trigger_field', 100);
            $table->string('operator', 20);
            $table->string('trigger_value', 500)->nullable();
            $table->json('channels');
            $table->json('target_roles');
            $table->json('target_user_ids')->nullable();
            $table->text('message_template');
            $table->string('repeat_behaviour', 20)->default('always');
            $table->unsignedSmallInteger('cooldown_minutes')->default(0);
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_triggered_at')->nullable();
            $table->timestamps();

            $table->index(['company_id', 'entity_type', 'is_active'], 'ar_company_entity_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('alert_rules');
    }
};
