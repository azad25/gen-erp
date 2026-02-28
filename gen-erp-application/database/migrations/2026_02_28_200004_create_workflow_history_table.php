<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_history', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workflow_instance_id')->constrained('workflow_instances')->cascadeOnDelete();
            $table->string('from_status_key', 100)->nullable();
            $table->string('to_status_key', 100);
            $table->foreignId('transition_id')->nullable()->constrained('workflow_transitions')->nullOnDelete();
            $table->foreignId('triggered_by')->nullable()->constrained('users')->nullOnDelete();
            $table->text('comment')->nullable();
            $table->timestamp('created_at')->useCurrent();

            $table->index(['workflow_instance_id', 'created_at'], 'wh_instance_created_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_history');
    }
};
