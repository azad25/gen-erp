<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        $tables = [
            'sales_orders',
            'invoices',
            'purchase_orders',
            'goods_receipts',
            'customer_payments',
            'supplier_payments',
            'expenses',
            'payroll_runs',
            'attendances',
            'journal_entries',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $blueprint): void {
                    $blueprint->foreignId('branch_id')->nullable()->after('company_id')->constrained('branches')->nullOnDelete();
                    $blueprint->index('branch_id');
                });
            }
        }
    }

    public function down(): void
    {
        $tables = [
            'sales_orders',
            'invoices',
            'purchase_orders',
            'goods_receipts',
            'customer_payments',
            'supplier_payments',
            'expenses',
            'payroll_runs',
            'attendances',
            'journal_entries',
        ];

        foreach ($tables as $table) {
            if (Schema::hasTable($table)) {
                Schema::table($table, function (Blueprint $blueprint): void {
                    $blueprint->dropConstrainedForeignId('branch_id');
                });
            }
        }
    }
};
