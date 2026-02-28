<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('suppliers', function (Blueprint $table): void {
            $table->unsignedInteger('tds_rate_basis_points')->default(0)->after('vat_bin');
            $table->string('tds_section', 50)->nullable()->after('tds_rate_basis_points');
        });
    }

    public function down(): void
    {
        Schema::table('suppliers', function (Blueprint $table): void {
            $table->dropColumn(['tds_rate_basis_points', 'tds_section']);
        });
    }
};
