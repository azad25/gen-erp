<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_transitions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('workflow_definition_id')->constrained('workflow_definitions')->cascadeOnDelete();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('from_status_key', 100);
            $table->string('to_status_key', 100);
            $table->string('label', 255);
            $table->json('allowed_roles');
            $table->boolean('requires_approval')->default(false);
            $table->string('approval_type', 20)->nullable();
            $table->json('approver_roles')->nullable();
            $table->json('auto_actions')->nullable();
            $table->text('confirmation_message')->nullable();
            $table->unsignedSmallInteger('display_order')->default(0);
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_transitions');
    }
};
