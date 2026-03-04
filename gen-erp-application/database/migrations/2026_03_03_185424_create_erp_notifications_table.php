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
        Schema::create('erp_notifications', function (Blueprint $table) {
            $table->uuid('id')->primary();
            $table->foreignId('tenant_id')->constrained('companies')->cascadeOnDelete();
            $table->foreignId('user_id')->constrained()->cascadeOnDelete();

            // NEVER store translated text — store keys and params only
            $table->string('type');                    // 'invoice.paid'
            $table->string('title_key');               // 'notifications.invoice.paid.title'
            $table->string('body_key');                // 'notifications.invoice.paid.body'
            $table->json('translation_params')->nullable();        // { "number": "1023", "amount": "৳5,000" }

            // UI rendering data — language agnostic
            $table->string('icon');                    // 'check-circle'
            $table->string('color');                   // 'success'|'warning'|'danger'|'info'
            $table->string('action_url')->nullable();  // '/invoices/1023'
            $table->string('action_label_key')->nullable(); // 'notifications.actions.view_invoice'
            $table->string('domain');                  // 'invoice'|'inventory'|'hr'|'crm'

            // Extra domain data for deep linking / context
            $table->json('meta')->nullable();          // { "invoice_id": 1023 }

            $table->timestamp('read_at')->nullable();
            $table->timestamps();

            $table->index(['user_id', 'read_at']);
            $table->index(['tenant_id', 'created_at']);
            $table->index('type');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::dropIfExists('erp_notifications');
    }
};
