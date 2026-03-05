<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add dimensions column to journal_entry_lines for dimensional accounting support.
 * This allows storing custom dimensions like project_id, campaign_id, region, etc.
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_entry_lines', function (Blueprint $table): void {
            $table->json('dimensions')->nullable()->after('cost_center_id');
        });
    }

    public function down(): void
    {
        Schema::table('journal_entry_lines', function (Blueprint $table): void {
            $table->dropColumn('dimensions');
        });
    }
};
