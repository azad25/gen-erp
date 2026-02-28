<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_integration_id')->constrained('company_integrations')->cascadeOnDelete();
            $table->string('direction', 20); // inbound, outbound, device
            $table->string('hook_name', 200)->nullable();
            $table->string('endpoint', 500)->nullable();
            $table->json('request_body')->nullable();
            $table->unsignedSmallInteger('response_status')->nullable();
            $table->json('response_body')->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->string('status', 20); // success, failed, retrying
            $table->text('error_message')->nullable();
            $table->unsignedTinyInteger('retry_count')->default(0);
            $table->timestamp('created_at')->useCurrent();

            $table->index(['company_id', 'created_at']);
            $table->index(['company_integration_id', 'status']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_logs');
    }
};
