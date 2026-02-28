<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('usage_counters', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->string('counter_key', 100); // products|users|branches|storage_bytes|integrations
            $table->unsignedBigInteger('current_value')->default(0);
            $table->bigInteger('max_value')->default(-1); // -1 = unlimited
            $table->timestamp('synced_at')->nullable(); // last Redis→MySQL sync
            $table->timestamps();

            $table->unique(['company_id', 'counter_key']);
            $table->index('company_id');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('usage_counters');
    }
};
