<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('workflow_instances', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('workflow_definition_id')->constrained('workflow_definitions');
            $table->string('document_type', 100);
            $table->unsignedBigInteger('document_id');
            $table->string('current_status_key', 100);
            $table->timestamp('started_at')->useCurrent();
            $table->timestamp('completed_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'document_type', 'document_id'], 'wi_company_doc_id_unique');
            $table->index(['company_id', 'document_type', 'current_status_key'], 'wi_company_doc_status_idx');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('workflow_instances');
    }
};
