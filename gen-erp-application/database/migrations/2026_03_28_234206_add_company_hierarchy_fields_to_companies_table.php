<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    /**
     * Run the migrations.
     */
    public function up(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->unsignedBigInteger('parent_company_id')->nullable()->after('id');
            $table->boolean('is_master_company')->default(true)->after('parent_company_id');
            $table->enum('company_type', ['master', 'subsidiary'])->default('master')->after('is_master_company');
            $table->boolean('show_aggregated_data')->default(false)->after('company_type');
            $table->json('aggregation_settings')->nullable()->after('show_aggregated_data');
            
            $table->foreign('parent_company_id')->references('id')->on('companies')->onDelete('cascade');
            $table->index(['parent_company_id', 'is_master_company']);
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('companies', function (Blueprint $table) {
            $table->dropForeign(['parent_company_id']);
            $table->dropIndex(['parent_company_id', 'is_master_company']);
            $table->dropColumn([
                'parent_company_id',
                'is_master_company', 
                'company_type',
                'show_aggregated_data',
                'aggregation_settings'
            ]);
        });
    }
};
