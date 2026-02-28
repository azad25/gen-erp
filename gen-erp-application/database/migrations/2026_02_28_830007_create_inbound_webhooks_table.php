<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('inbound_webhooks', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_integration_id')->constrained('company_integrations')->cascadeOnDelete();
            $table->string('endpoint_key', 64)->unique(); // random token in URL
            $table->string('secret', 255); // HMAC verification
            $table->string('entity_type', 100); // what entity this creates/updates
            $table->json('field_maps');
            $table->boolean('is_active')->default(true);
            $table->timestamp('last_received_at')->nullable();
            $table->unsignedInteger('received_count')->default(0);
            $table->timestamps();

            $table->index(['company_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('inbound_webhooks');
    }
};
