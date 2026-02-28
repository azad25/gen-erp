<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('contact_groups', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('type', 20); // customer|supplier|both
            $table->string('name', 255);
            $table->text('description')->nullable();
            $table->timestamps();

            $table->index('company_id');
            $table->unique(['company_id', 'type', 'name'], 'cg_company_type_name_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('contact_groups');
    }
};
