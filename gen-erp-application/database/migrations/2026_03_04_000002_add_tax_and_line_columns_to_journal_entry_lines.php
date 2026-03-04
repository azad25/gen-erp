<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Phase 1 – Adds tax tagging, line ordering, and dimension placeholders
 * to journal_entry_lines for VAT separation and future dimensional accounting.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_entry_lines', function (Blueprint $table): void {
            $table->unsignedSmallInteger('line_no')->default(0)->after('account_id');
            $table->string('tax_code', 30)->nullable()->after('credit');
            $table->unsignedSmallInteger('tax_rate')->nullable()->after('tax_code'); // basis points (e.g. 1500 = 15%)
            $table->bigInteger('tax_base_amount')->default(0)->after('tax_rate'); // amount tax was computed on, in paise
            $table->unsignedBigInteger('branch_id')->nullable()->after('tax_base_amount');
            $table->unsignedBigInteger('cost_center_id')->nullable()->after('branch_id');

            $table->index(['company_id', 'tax_code'], 'jel_company_taxcode_idx');
        });
    }

    public function down(): void
    {
        Schema::table('journal_entry_lines', function (Blueprint $table): void {
            $table->dropIndex('jel_company_taxcode_idx');
            $table->dropColumn([
                'line_no',
                'tax_code',
                'tax_rate',
                'tax_base_amount',
                'branch_id',
                'cost_center_id',
            ]);
        });
    }
};
