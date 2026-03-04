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
        Schema::table('cms_shopping_carts', function (Blueprint $table) {
            $table->foreign('customer_id')->references('id')->on('cms_customer_accounts')->onDelete('cascade');
        });
        
        Schema::table('cms_public_orders', function (Blueprint $table) {
            $table->foreign('customer_id')->references('id')->on('cms_customer_accounts')->onDelete('set null');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('cms_shopping_carts', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
        });
        
        Schema::table('cms_public_orders', function (Blueprint $table) {
            $table->dropForeign(['customer_id']);
        });
    }
};
