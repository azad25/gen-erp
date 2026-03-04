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
        Schema::create('shipment_returns', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('shipment_id')->constrained('shipments');
            
            $table->string('return_number', 100)->unique();
            $table->string('reason', 50); // damaged, wrong_item, not_needed, quality_issue
            $table->text('reason_details')->nullable();
            
            $table->string('status', 50); // requested, approved, rejected, picked_up, received, refunded
            
            // Return Shipment
            $table->string('return_tracking_number', 100)->nullable();
            $table->foreignId('return_carrier_id')->nullable()->constrained('carriers')->nullOnDelete();
            
            // Refund Info
            $table->decimal('refund_amount', 10, 2)->nullable();
            $table->string('refund_method', 50)->nullable(); // original_payment, store_credit, exchange
            $table->datetime('refunded_at')->nullable();
            
            // Images
            $table->json('images')->nullable(); // array of image URLs
            
            $table->foreignId('requested_by')->constrained('users');
            $table->foreignId('approved_by')->nullable()->constrained('users')->nullOnDelete();
            $table->datetime('requested_at');
            $table->datetime('approved_at')->nullable();
            
            $table->timestamps();
            
            $table->index(['company_id', 'status']);
            $table->index('return_number');
            $table->index('shipment_id');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipment_returns');
    }
};
