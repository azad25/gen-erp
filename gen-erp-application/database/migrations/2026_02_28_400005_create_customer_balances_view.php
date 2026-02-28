<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Support\Facades\DB;

return new class extends Migration
{
    public function up(): void
    {
        // Customer balances view — will include transactions once invoicing is live
        // For now referencing opening_balance only; Phase 4+ adds transaction joins
        DB::statement('
            CREATE OR REPLACE VIEW customer_balances AS
            SELECT
                c.company_id,
                c.id AS customer_id,
                c.name,
                c.opening_balance,
                COALESCE(SUM(CASE WHEN t.type = \'invoice\' THEN t.amount ELSE 0 END), 0) AS total_invoiced,
                COALESCE(SUM(CASE WHEN t.type = \'payment\' THEN t.amount ELSE 0 END), 0) AS total_paid,
                c.opening_balance
                    + COALESCE(SUM(CASE WHEN t.type = \'invoice\' THEN t.amount ELSE 0 END), 0)
                    - COALESCE(SUM(CASE WHEN t.type = \'payment\' THEN t.amount ELSE 0 END), 0)
                AS current_balance
            FROM customers c
            LEFT JOIN customer_transactions t
                ON t.customer_id = c.id AND t.company_id = c.company_id
            GROUP BY c.id, c.company_id, c.name, c.opening_balance
        ');
    }

    public function down(): void
    {
        DB::statement('DROP VIEW IF EXISTS customer_balances');
    }
};
