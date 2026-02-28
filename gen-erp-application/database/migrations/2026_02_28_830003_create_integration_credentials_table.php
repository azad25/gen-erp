<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('integration_credentials', function (Blueprint $table): void {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('company_integration_id')->constrained('company_integrations')->cascadeOnDelete();
            $table->string('credential_key', 100);
            $table->text('credential_value'); // AES-256 encrypted
            $table->timestamp('expires_at')->nullable();
            $table->timestamps();

            $table->unique(['company_id', 'company_integration_id', 'credential_key'], 'integ_cred_company_ci_key_unique');
        });
    }

    public function down(): void
    {
        Schema::dropIfExists('integration_credentials');
    }
};
