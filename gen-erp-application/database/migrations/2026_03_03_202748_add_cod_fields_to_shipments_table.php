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
        Schema::table('shipments', function (Blueprint $table) {
            $table->decimal('cod_collected_amount', 10, 2)->nullable()->after('total_cost');
            $table->timestamp('cod_collected_at')->nullable()->after('cod_collected_amount');
            $table->timestamp('cod_settled_at')->nullable()->after('cod_collected_at');
            $table->string('cod_status')->nullable()->after('cod_settled_at')->comment('pending, collected, settled');
        });
    }

    /**
     * Reverse the migrations.
     */
    public function down(): void
    {
        Schema::table('shipments', function (Blueprint $table) {
            $table->dropColumn(['cod_collected_amount', 'cod_collected_at', 'cod_settled_at', 'cod_status']);
        });
    }
};
