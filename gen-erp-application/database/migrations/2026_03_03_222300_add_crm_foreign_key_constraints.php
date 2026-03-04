<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        // Add foreign key constraints for opportunities table
        Schema::table('opportunities', function (Blueprint $table) {
            $table->foreign('pipeline_id')->references('id')->on('pipelines')->cascadeOnDelete();
            $table->foreign('stage_id')->references('id')->on('pipeline_stages')->cascadeOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('opportunities', function (Blueprint $table) {
            $table->dropForeign(['pipeline_id']);
            $table->dropForeign(['stage_id']);
        });
    }
};