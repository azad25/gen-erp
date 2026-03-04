<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1 – Adds idempotency key and total_cost to stock_movements
 * for duplicate prevention and COGS tracking.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->string('idempotency_key', 100)->nullable()->unique()->after('company_id');
            $table->bigInteger('total_cost')->nullable()->after('unit_cost'); // computed COGS for outbound moves
        });
    }

    public function down(): void
    {
        Schema::table('stock_movements', function (Blueprint $table): void {
            $table->dropUnique(['idempotency_key']);
            $table->dropColumn(['idempotency_key', 'total_cost']);
        });
    }
};
