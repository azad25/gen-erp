<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_hooks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_integration_id')->constrained('company_integrations')->cascadeOnDelete();
            $table->string('hook_name', 200);
            $table->string('handler_class', 500);
            $table->integer('priority')->default(10);
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'hook_name', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_hooks');
    }
};
