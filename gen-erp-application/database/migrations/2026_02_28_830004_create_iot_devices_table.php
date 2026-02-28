<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('iot_devices', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('branch_id')->nullable()->constrained('branches')->nullOnDelete();
            $table->foreignId('company_integration_id')->constrained('company_integrations')->cascadeOnDelete();
            $table->string('name', 200);
            $table->string('device_type', 50); // biometric, pos_printer, barcode_scanner, etc.
            $table->string('driver_class', 500);
            $table->string('connection_type', 20); // tcp_ip, usb, serial, bluetooth, http, mqtt
            $table->json('config'); // ip, port, baud_rate, etc.
            $table->timestamp('last_sync_at')->nullable();
            $table->timestamp('last_ping_at')->nullable();
            $table->string('status', 20)->default('offline'); // online, offline, error, syncing
            $table->boolean('is_active')->default(true);
            $table->timestamps();

            $table->index(['company_id', 'is_active']);
            $table->index(['company_id', 'branch_id']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('iot_devices');
    }
};
