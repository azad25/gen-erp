<?php

use App\Enums\AttendanceStatus;
use App\Enums\EmployeeStatus;
use App\Models\Attendance;
use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveType;
use App\Services\CompanyContext;
use App\Services\HRService;

// ═══════════════════════════════════════════════════
// HRTest — 10 tests
// ═══════════════════════════════════════════════════

test('Employee created with auto employee_code and correct company_id', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);

    $service = app(HRService::class);
    $employee = $service->createEmployee($company, [
        'first_name' => 'Karim',
        'last_name' => 'Ahmed',
        'joining_date' => now()->toDateString(),
    ]);

    expect($employee->employee_code)->toStartWith('EMP-');
    expect($employee->company_id)->toBe($company->id);
    expect($employee->fullName())->toBe('Karim Ahmed');
});

test('NID and bank account number stored encrypted, readable via model', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);

    $service = app(HRService::class);
    $employee = $service->createEmployee($company, [
        'first_name' => 'Rahim',
        'last_name' => 'Uddin',
        'joining_date' => now()->toDateString(),
        'nid_number' => '1234567890123',
        'bank_account_number' => '9876543210',
        'bkash_number' => '01712345678',
    ]);

    // Readable via model attribute
    expect($employee->nid_number)->toBe('1234567890123');
    expect($employee->bank_account_number)->toBe('9876543210');
    expect($employee->bkash_number)->toBe('01712345678');

    // Stored encrypted in DB (raw value should differ)
    $raw = DB::table('employees')->where('id', $employee->id)->first();
    expect($raw->nid_number)->not->toBe('1234567890123');
});

test('Leave allocation creates LeaveBalance with correct days', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    $employee = Employee::factory()->create(['company_id' => $company->id]);
    $leaveType = LeaveType::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'name' => 'Annual Leave',
        'days_per_year' => 18,
    ]);

    $service = app(HRService::class);
    $balance = $service->allocateLeave($employee, $leaveType, 18, now()->year);

    expect($balance->allocated_days)->toBe(18.0);
    expect($balance->used_days)->toBe(0.0);
    expect($balance->balance)->toBe(18.0);
});

test('Leave approval deducts from LeaveBalance', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    $employee = Employee::factory()->create(['company_id' => $company->id]);
    $approver = Employee::factory()->create(['company_id' => $company->id]);
    $leaveType = LeaveType::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'name' => 'Sick Leave',
        'days_per_year' => 14,
    ]);

    $service = app(HRService::class);
    $service->allocateLeave($employee, $leaveType, 14, now()->year);

    $request = $service->requestLeave($employee, [
        'leave_type_id' => $leaveType->id,
        'from_date' => now()->toDateString(),
        'to_date' => now()->addDays(2)->toDateString(),
        'total_days' => 3,
    ]);

    $service->approveLeave($request, $approver);

    $balance = LeaveBalance::withoutGlobalScopes()
        ->where('employee_id', $employee->id)
        ->where('leave_type_id', $leaveType->id)
        ->first();

    expect($balance->used_days)->toBe(3.0);
    expect($balance->fresh()->balance)->toBe(11.0);
    expect($request->fresh()->status)->toBe('approved');
});

test('Leave request rejected when insufficient balance', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    $employee = Employee::factory()->create(['company_id' => $company->id]);
    $approver = Employee::factory()->create(['company_id' => $company->id]);
    $leaveType = LeaveType::withoutGlobalScopes()->create([
        'company_id' => $company->id,
        'name' => 'Test Leave',
        'days_per_year' => 2,
    ]);

    $service = app(HRService::class);
    $service->allocateLeave($employee, $leaveType, 2, now()->year);

    $request = $service->requestLeave($employee, [
        'leave_type_id' => $leaveType->id,
        'from_date' => now()->toDateString(),
        'to_date' => now()->addDays(4)->toDateString(),
        'total_days' => 5,
    ]);

    $service->approveLeave($request, $approver);
})->throws(InvalidArgumentException::class, 'Insufficient leave balance.');

test('Attendance marked prevents duplicate for same employee + date', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    $employee = Employee::factory()->create(['company_id' => $company->id]);

    $service = app(HRService::class);
    $att1 = $service->markAttendance($employee, now(), ['status' => 'present', 'check_in' => '09:00']);
    $att2 = $service->markAttendance($employee, now(), ['status' => 'late', 'check_in' => '10:00']);

    // Should be same record (updateOrCreate)
    expect($att1->id)->toBe($att2->id);
    expect($att2->fresh()->status)->toBe(AttendanceStatus::LATE);
    expect(Attendance::withoutGlobalScopes()->where('employee_id', $employee->id)->count())->toBe(1);
});

test('getMonthlyAttendance returns correct count per status', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);
    $employee = Employee::factory()->create(['company_id' => $company->id]);

    $service = app(HRService::class);
    $now = now();
    // Mark 3 days: present, absent, late
    $service->markAttendance($employee, $now->copy()->startOfMonth(), ['status' => 'present']);
    $service->markAttendance($employee, $now->copy()->startOfMonth()->addDay(), ['status' => 'absent']);
    $service->markAttendance($employee, $now->copy()->startOfMonth()->addDays(2), ['status' => 'late']);

    $records = $service->getMonthlyAttendance($employee, $now->month, $now->year);

    expect($records)->toHaveCount(3);
    expect($records->where('status', AttendanceStatus::PRESENT)->count())->toBe(1);
    expect($records->where('status', AttendanceStatus::ABSENT)->count())->toBe(1);
});

test('Employee custom fields save/retrieve correctly', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);

    $service = app(HRService::class);
    $employee = $service->createEmployee($company, [
        'first_name' => 'Custom',
        'last_name' => 'Fields',
        'joining_date' => now()->toDateString(),
    ], [
        'blood_group' => 'O+',
        'uniform_size' => 'L',
    ]);

    expect($employee->custom_fields['blood_group'])->toBe('O+');
    expect($employee->custom_fields['uniform_size'])->toBe('L');
});

test('Company A cannot see Company B employees', function (): void {
    $companyA = Company::factory()->create();
    $companyB = Company::factory()->create();

    CompanyContext::setActive($companyA);
    $service = app(HRService::class);
    $service->createEmployee($companyA, [
        'first_name' => 'Worker',
        'last_name' => 'A',
        'joining_date' => now()->toDateString(),
    ]);

    expect(Employee::all())->toHaveCount(1);

    CompanyContext::setActive($companyB);
    expect(Employee::all())->toHaveCount(0);
});

test('Terminated employee status updated, not deleted', function (): void {
    $company = Company::factory()->create();
    CompanyContext::setActive($company);

    $service = app(HRService::class);
    $employee = $service->createEmployee($company, [
        'first_name' => 'Soon',
        'last_name' => 'Gone',
        'joining_date' => now()->subYear()->toDateString(),
    ]);

    $service->terminateEmployee($employee, now(), 'Performance issues');

    $updated = $employee->fresh();
    expect($updated->status)->toBe(EmployeeStatus::TERMINATED);
    expect($updated->termination_date)->not->toBeNull();
    expect($updated->deleted_at)->toBeNull();
});
