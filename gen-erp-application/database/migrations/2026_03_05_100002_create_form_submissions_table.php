<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('form_submissions', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('form_id')->constrained()->cascadeOnDelete();
            $table->foreignId('submitted_by')->nullable()->constrained('users');
            $table->json('submission_data'); // The actual form data
            $table->string('ip_address')->nullable();
            $table->text('user_agent')->nullable();
            $table->enum('status', ['pending', 'processed', 'archived'])->default('pending');
            $table->timestamp('submitted_at');
            $table->timestamps();

            $table->index(['form_id', 'submitted_at']);
            $table->index(['form_id', 'status']);
            $table->index(['submitted_by', 'submitted_at']);
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('form_submissions');
    }
};