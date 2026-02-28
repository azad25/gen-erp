<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('suppliers', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('group_id')->nullable()->constrained('contact_groups')->nullOnDelete();
            $table->string('supplier_code', 50)->nullable();
            $table->string('name', 255);
            $table->string('contact_person', 255)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('phone', 20)->nullable();
            $table->string('mobile', 20)->nullable();
            $table->string('address_line1', 255)->nullable();
            $table->string('address_line2', 255)->nullable();
            $table->string('city', 100)->nullable();
            $table->string('district', 100)->nullable();
            $table->string('postal_code', 20)->nullable();
            $table->string('vat_bin', 20)->nullable();
            $table->decimal('tds_rate', 5, 2)->default(0.00);
            $table->decimal('vds_rate', 5, 2)->default(0.00);
            $table->unsignedSmallInteger('credit_days')->default(0);
            $table->bigInteger('opening_balance')->default(0);
            $table->date('opening_balance_date')->nullable();
            $table->string('bank_name', 255)->nullable();
            $table->string('bank_account_number', 100)->nullable();
            $table->string('bank_routing_number', 50)->nullable();
            $table->text('notes')->nullable();
            $table->json('custom_fields')->nullable();
            $table->boolean('is_active')->default(true);
            $table->timestamps();
            $table->softDeletes();

            $table->index(['company_id', 'supplier_code'], 'suppliers_company_code_idx');
            $table->index(['company_id', 'is_active'], 'suppliers_company_active_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('suppliers');
    }
};
