<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('outbound_webhooks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('event_name', 100);   // order.created, product.updated, etc.
            $table->string('url', 2000);
            $table->string('secret', 255);       // HMAC signing secret
            $table->boolean('is_active')->default(true);
            $table->unsignedInteger('max_retries')->default(3);
            $table->unsignedInteger('timeout_seconds')->default(10);
            $table->timestamp('last_triggered_at')->nullable();
            $table->unsignedInteger('failure_count')->default(0);
            $table->timestamps();
            $table->softDeletes();

            $table->index('company_id');
            $table->index(['company_id', 'event_name']);
        });

        Schema::create('outbound_webhook_logs', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('outbound_webhook_id')->constrained()->cascadeOnDelete();
            $table->unsignedSmallInteger('attempt')->default(1);
            $table->unsignedSmallInteger('http_status')->nullable();
            $table->text('response_body')->nullable();
            $table->string('error_message', 1000)->nullable();
            $table->unsignedInteger('duration_ms')->nullable();
            $table->timestamps();

            $table->index(['outbound_webhook_id', 'created_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('outbound_webhook_logs');
        Schema::dropIfExists('outbound_webhooks');
    }
};
