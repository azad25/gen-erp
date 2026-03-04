<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::create('tracking_events', function (Blueprint $table) {
            $table->id();
            $table->foreignId('shipment_id')->constrained('shipments')->cascadeOnDelete();
            
            $table->string('status', 50);
            $table->string('location')->nullable();
            $table->text('description');
            $table->datetime('event_time');
            
            $table->string('carrier_status', 100)->nullable(); // raw carrier status
            $table->json('carrier_data')->nullable(); // raw carrier event data
            
            $table->timestamp('created_at');
            
            $table->index(['shipment_id', 'event_time']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('tracking_events');
    }
};
