<?php

use Illuminate\Database\Migrations\Migration;
use Illuminate\Database\Schema\Blueprint;
use Illuminate\Support\Facades\Schema;

return new class extends Migration
{
    public function up(): void
    {
        Schema::create('employees', function (Blueprint $table) {
            $table->id();
            $table->foreignId('company_id')->constrained()->cascadeOnDelete();
            $table->foreignId('user_id')->nullable()->constrained()->nullOnDelete();
            $table->foreignId('department_id')->nullable()->constrained('departments')->nullOnDelete();
            $table->foreignId('designation_id')->nullable()->constrained('designations')->nullOnDelete();
            $table->string('employee_code', 50);
            $table->string('first_name', 100);
            $table->string('last_name', 100);
            $table->string('name_bangla', 255)->nullable();
            $table->string('email', 255)->nullable();
            $table->string('phone', 20)->nullable();
            $table->date('date_of_birth')->nullable();
            $table->string('gender', 10)->nullable();
            $table->text('nid_number')->nullable();
            $table->text('tin_number')->nullable();
            $table->date('joining_date');
            $table->date('confirmation_date')->nullable();
            $table->date('resignation_date')->nullable();
            $table->date('termination_date')->nullable();
            $table->string('employment_type', 30)->default('permanent');
            $table->string('status', 30)->default('active');
            $table->bigInteger('basic_salary')->default(0);
            $table->bigInteger('gross_salary')->default(0);
            $table->string('bank_name', 255)->nullable();
            $table->text('bank_account_number')->nullable();
            $table->string('bank_routing_number', 50)->nullable();
            $table->text('bkash_number')->nullable();
            $table->text('address')->nullable();
            $table->string('emergency_contact_name', 255)->nullable();
            $table->string('emergency_contact_phone', 20)->nullable();
            $table->string('photo_url', 500)->nullable();
            $table->json('custom_fields')->nullable();
            $table->timestamps();
            $table->softDeletes();

            $table->unique(['company_id', 'employee_code'], 'emp_company_code_unique');
            $table->index(['company_id', 'department_id', 'status']);
            $table->index(['company_id', 'user_id']);
        });

        // Add manager FK on departments now that employees table exists
        Schema::table('departments', function (Blueprint $table) {
            $table->foreign('manager_id')->references('id')->on('employees')->nullOnDelete();
        });
    }

    public function down(): void
    {
        Schema::table('departments', function (Blueprint $table) {
            $table->dropForeign(['manager_id']);
        });
        Schema::dropIfExists('employees');
    }
};
