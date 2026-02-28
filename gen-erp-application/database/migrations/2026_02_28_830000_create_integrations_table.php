<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integrations', function (Blueprint $table): void {
            $table->id();
            $table->string('slug', 100)->unique();
            $table->string('name', 200);
            $table->string('category', 50);
            $table->text('description')->nullable();
            $table->string('logo_path', 500)->nullable();
            $table->string('tier', 20); // native, connector, plugin
            $table->string('min_plan', 20)->default('pro'); // free, pro, enterprise
            $table->json('config_schema'); // schema for setup form fields
            $table->json('capabilities'); // ['push','pull','realtime','webhook','device']
            $table->boolean('is_active')->default(true);
            $table->boolean('is_official')->default(true);
            $table->string('version', 20)->default('1.0.0');
            $table->string('author', 200)->nullable();
            $table->string('author_url', 500)->nullable();
            $table->timestamps();
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integrations');
    }
};
