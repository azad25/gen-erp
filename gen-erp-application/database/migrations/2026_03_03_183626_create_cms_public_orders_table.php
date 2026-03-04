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
        Schema::create('cms_public_orders', function (Blueprint $table) {
            $table->id();
            $table->foreignId('site_id')->constrained('cms_sites')->onDelete('cascade');
            $table->unsignedBigInteger('customer_id')->nullable(); // Will add constraint later
            $table->string('order_number', 50)->unique();
            
            // Customer info (denormalized for guest orders)
            $table->string('customer_email');
            $table->string('customer_first_name', 100);
            $table->string('customer_last_name', 100);
            $table->string('customer_phone', 20)->nullable();
            
            // Billing address
            $table->string('billing_address_line_1');
            $table->string('billing_address_line_2')->nullable();
            $table->string('billing_city', 100);
            $table->string('billing_state', 100);
            $table->string('billing_postal_code', 20);
            $table->string('billing_country', 2)->default('BD');
            
            // Shipping address
            $table->string('shipping_address_line_1');
            $table->string('shipping_address_line_2')->nullable();
            $table->string('shipping_city', 100);
            $table->string('shipping_state', 100);
            $table->string('shipping_postal_code', 20);
            $table->string('shipping_country', 2)->default('BD');
            
            // Order details
            $table->decimal('subtotal', 15, 2);
            $table->decimal('shipping_cost', 15, 2)->default(0);
            $table->decimal('tax_amount', 15, 2)->default(0);
            $table->decimal('discount_amount', 15, 2)->default(0);
            $table->decimal('total_amount', 15, 2);
            
            // Status
            $table->enum('status', ['pending', 'processing', 'completed', 'cancelled'])->default('pending');
            $table->enum('payment_status', ['pending', 'paid', 'failed'])->default('pending');
            $table->enum('payment_method', ['cod', 'bank_transfer', 'online'])->default('cod');
            
            // Notes
            $table->text('customer_notes')->nullable();
            $table->text('admin_notes')->nullable();
            
            // Tracking
            $table->string('tracking_number', 100)->nullable();
            
            // Timestamps
            $table->timestamp('placed_at');
            $table->timestamp('completed_at')->nullable();
            $table->timestamp('cancelled_at')->nullable();
            $table->timestamps();
            
            $table->index(['order_number']);
            $table->index(['customer_email']);
            $table->index(['status']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('cms_public_orders');
    }
};
