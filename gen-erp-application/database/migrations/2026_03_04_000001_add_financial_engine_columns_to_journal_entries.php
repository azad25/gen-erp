<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1 – Critical Financial Engine: Adds idempotency key, journal code,
 * posted_at timestamp, and currency to journal_entries table.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_entries', function (Blueprint $table): void {
            $table->string('idempotency_key', 100)->nullable()->unique()->after('company_id');
            $table->string('journal_code', 20)->default('general')->after('entry_number');
            $table->timestamp('posted_at')->nullable()->after('status');
            $table->char('currency', 3)->default('BDT')->after('posted_at');

            $table->index(['company_id', 'journal_code', 'entry_date'], 'je_company_code_date_idx');
            $table->index(['company_id', 'posted_at'], 'je_company_posted_idx');
        });
    }

    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table): void {
            $table->dropIndex('je_company_code_date_idx');
            $table->dropIndex('je_company_posted_idx');
            $table->dropUnique(['idempotency_key']);
            $table->dropColumn(['idempotency_key', 'journal_code', 'posted_at', 'currency']);
        });
    }
};
