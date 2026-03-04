<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::table('custom_field_definitions', function (Blueprint $table): void {
            $table->string('domain', 50)->after('entity_type')->nullable(); // sales, crm, hr, etc.
            $table->foreignId('created_by')->after('generated_column_name')->nullable()->constrained('users');
            $table->enum('security_level', ['public', 'internal', 'restricted'])->after('created_by')->default('internal');
            $table->text('help_text')->after('label')->nullable();
            $table->json('conditional_logic')->after('validation_rules')->nullable(); // Show/hide based on other fields
            
            $table->index(['company_id', 'domain', 'entity_type']);
            $table->index(['company_id', 'security_level']);
        });
    }

    public function down(): void
    {
        Schema::table('custom_field_definitions', function (Blueprint $table): void {
            $table->dropIndex(['company_id', 'domain', 'entity_type']);
            $table->dropIndex(['company_id', 'security_level']);
            
            $table->dropColumn([
                'domain',
                'created_by', 
                'security_level',
                'help_text',
                'conditional_logic'
            ]);
        });
    }
};