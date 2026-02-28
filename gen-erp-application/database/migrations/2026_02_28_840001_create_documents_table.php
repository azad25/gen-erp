<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('documents', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('folder_id')->nullable()->constrained('document_folders')->nullOnDelete();
            $table->nullableMorphs('documentable'); // entity_type + entity_id (product, invoice, etc.)
            $table->string('name', 500); // original filename
            $table->string('disk_path', 1000); // path on disk: {company_id}/{year}/{uuid}.ext
            $table->string('mime_type', 100);
            $table->unsignedBigInteger('size_bytes');
            $table->string('description', 1000)->nullable();
            $table->json('metadata')->nullable(); // dimensions, duration, pages, etc.
            $table->foreignId('uploaded_by')->nullable()->constrained('users')->nullOnDelete();
            $table->timestamp('uploaded_at')->useCurrent();
            $table->timestamps();
            $table->softDeletes();

            $table->index('company_id');
            $table->index(['company_id', 'folder_id']);
            $table->index(['company_id', 'mime_type']);
            $table->fullText('name');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('documents');
    }
};
