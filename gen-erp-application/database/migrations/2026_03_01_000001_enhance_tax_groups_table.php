<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tax_groups', function (Blueprint $table): void {
            $table->string('type', 10)->default('vat')->after('rate');
            $table->boolean('is_compound')->default(false)->after('type');
            $table->text('description')->nullable()->after('is_compound');
            $table->unsignedInteger('rate_basis_points')->default(0)->after('rate');
            $table->boolean('is_active')->default(true)->after('is_default');
            $table->unsignedSmallInteger('sort_order')->default(0)->after('is_active');

            $table->index(['company_id', 'type']);
            $table->index(['company_id', 'is_active']);
        });
    }

    public function down(): void
    {
        Schema::table('tax_groups', function (Blueprint $table): void {
            $table->dropIndex(['company_id', 'type']);
            $table->dropIndex(['company_id', 'is_active']);
            $table->dropColumn(['type', 'is_compound', 'description', 'rate_basis_points', 'is_active', 'sort_order']);
        });
    }
};
