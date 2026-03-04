<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->foreignId('phase_id')->nullable()->after('parent_task_id')
                  ->constrained('project_phases')->onDelete('set null');
            $table->index(['phase_id']);
        });
    }

    public function down(): void
    {
        Schema::table('tasks', function (Blueprint $table) {
            $table->dropForeign(['phase_id']);
            $table->dropIndex(['phase_id']);
            $table->dropColumn('phase_id');
        });
    }
};