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
        Schema::create('shipments', function (Blueprint $table) {
            $table->id();
            $table->uuid('uuid')->unique();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('carrier_id')->constrained('carriers');
            $table->foreignId('invoice_id')->nullable()->constrained('invoices')->nullOnDelete();
            $table->foreignId('customer_id')->constrained('customers');
            
            // Tracking
            $table->string('tracking_number', 100)->unique()->nullable();
            $table->string('carrier_tracking_number', 100)->nullable();
            
            // Sender Info
            $table->string('sender_name');
            $table->string('sender_phone', 20);
            $table->text('sender_address');
            $table->string('sender_city', 100);
            $table->string('sender_area', 100)->nullable();
            $table->string('sender_postcode', 20)->nullable();
            
            // Recipient Info
            $table->string('recipient_name');
            $table->string('recipient_phone', 20);
            $table->string('recipient_email')->nullable();
            $table->text('recipient_address');
            $table->string('recipient_city', 100);
            $table->string('recipient_area', 100)->nullable();
            $table->string('recipient_postcode', 20)->nullable();
            
            // Shipment Details
            $table->string('status', 50); // pending, picked_up, in_transit, out_for_delivery, delivered, failed, returned
            $table->string('delivery_type', 50); // standard, express, same_day
            $table->string('payment_method', 50); // prepaid, cod
            
            // Pricing
            $table->decimal('cod_amount', 10, 2)->default(0);
            $table->decimal('shipping_cost', 10, 2);
            $table->decimal('cod_charge', 10, 2)->default(0);
            $table->decimal('total_cost', 10, 2);
            
            // Weight & Dimensions
            $table->decimal('weight', 8, 2)->nullable(); // in kg
            $table->decimal('length', 8, 2)->nullable(); // in cm
            $table->decimal('width', 8, 2)->nullable();
            $table->decimal('height', 8, 2)->nullable();
            
            // Dates
            $table->date('pickup_date')->nullable();
            $table->date('expected_delivery_date')->nullable();
            $table->datetime('actual_delivery_date')->nullable();
            
            // Additional Info
            $table->text('special_instructions')->nullable();
            $table->text('package_description')->nullable();
            $table->json('carrier_response')->nullable(); // raw carrier API response
            
            $table->foreignId('created_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamps();
            
            $table->index(['company_id', 'status']);
            $table->index('tracking_number');
            $table->index('carrier_tracking_number');
            $table->index('customer_id');
            $table->index(['expected_delivery_date', 'actual_delivery_date']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('shipments');
    }
};
