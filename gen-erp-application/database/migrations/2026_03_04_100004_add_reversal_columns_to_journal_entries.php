<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

/**
 * Add reversal linking columns to journal_entries.
 * reversed_by_id: points to the entry that reversed this one
 * reversal_of_id: points to the original entry this is a reversal of
 */
return new class extends Migration
{
    public function up(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->foreignId('reversed_by_id')->nullable()->after('posted_by')
                ->constrained('journal_entries')->nullOnDelete();
            $table->foreignId('reversal_of_id')->nullable()->after('reversed_by_id')
                ->constrained('journal_entries')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('journal_entries', function (Blueprint $table) {
            $table->dropConstrainedForeignId('reversed_by_id');
            $table->dropConstrainedForeignId('reversal_of_id');
        });
    }
};
