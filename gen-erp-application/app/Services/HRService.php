<?php

namespace App\Services;

use App\Enums\EmployeeStatus;
use App\Models\Attendance;
use App\Models\Company;
use App\Models\Employee;
use App\Models\LeaveBalance;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Carbon\Carbon;
use Illuminate\Support\Collection;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Orchestrates employee management, leave allocation/approval, and attendance tracking.
 */
class HRService
{
    // ═══════════════════════════════════════════════
    // Employee CRUD
    // ═══════════════════════════════════════════════

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $customFields
     */
    public function createEmployee(Company $company, array $data, array $customFields = []): Employee
    {
        return DB::transaction(function () use ($company, $data, $customFields): Employee {
            $employee = Employee::withoutGlobalScopes()->create(array_merge($data, [
                'company_id' => $company->id,
                'custom_fields' => $customFields ?: null,
            ]));

            return $employee;
        });
    }

    /**
     * @param  array<string, mixed>  $data
     * @param  array<string, mixed>  $customFields
     */
    public function updateEmployee(Employee $employee, array $data, array $customFields = []): Employee
    {
        return DB::transaction(function () use ($employee, $data, $customFields): Employee {
            if ($customFields !== []) {
                $data['custom_fields'] = array_merge($employee->custom_fields ?? [], $customFields);
            }
            $employee->update($data);

            return $employee->fresh();
        });
    }

    public function terminateEmployee(Employee $employee, Carbon $date, string $reason): void
    {
        $employee->update([
            'status' => EmployeeStatus::TERMINATED,
            'termination_date' => $date->toDateString(),
        ]);
    }

    // ═══════════════════════════════════════════════
    // Leave Management
    // ═══════════════════════════════════════════════

    public function allocateLeave(Employee $employee, LeaveType $type, float $days, int $year): LeaveBalance
    {
        $balance = LeaveBalance::withoutGlobalScopes()->updateOrCreate(
            [
                'employee_id' => $employee->id,
                'leave_type_id' => $type->id,
                'year' => $year,
            ],
            [
                'company_id' => $employee->company_id,
                'allocated_days' => $days,
                'used_days' => 0,
                'carried_forward' => 0,
            ],
        );

        return $balance->fresh();
    }

    /**
     * Bulk allocate leave for all active employees of a company.
     */
    public function allocateAllLeaves(Company $company, int $year): void
    {
        $employees = Employee::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('status', EmployeeStatus::ACTIVE)
            ->get();

        $leaveTypes = LeaveType::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->get();

        foreach ($employees as $employee) {
            foreach ($leaveTypes as $type) {
                $this->allocateLeave($employee, $type, $type->days_per_year, $year);
            }
        }
    }

    /**
     * @param  array<string, mixed>  $data
     */
    public function requestLeave(Employee $employee, array $data): LeaveRequest
    {
        return DB::transaction(function () use ($employee, $data): LeaveRequest {
            return LeaveRequest::withoutGlobalScopes()->create(array_merge($data, [
                'company_id' => $employee->company_id,
                'employee_id' => $employee->id,
                'status' => 'pending',
            ]));
        });
    }

    public function approveLeave(LeaveRequest $request, Employee $approver): void
    {
        DB::transaction(function () use ($request, $approver): void {
            $balance = LeaveBalance::withoutGlobalScopes()
                ->where('employee_id', $request->employee_id)
                ->where('leave_type_id', $request->leave_type_id)
                ->where('year', $request->from_date->year)
                ->first();

            if ($balance === null || $balance->balance < $request->total_days) {
                throw new InvalidArgumentException(__('Insufficient leave balance.'));
            }

            $balance->update([
                'used_days' => $balance->used_days + $request->total_days,
            ]);

            $request->update([
                'status' => 'approved',
                'approved_by' => $approver->id,
            ]);
        });
    }

    public function rejectLeave(LeaveRequest $request, Employee $approver, string $reason): void
    {
        $request->update([
            'status' => 'rejected',
            'approved_by' => $approver->id,
            'reason' => $reason,
        ]);
    }

    /**
     * @return Collection<int, LeaveBalance>
     */
    public function getLeaveBalance(Employee $employee, int $year): Collection
    {
        return LeaveBalance::withoutGlobalScopes()
            ->where('employee_id', $employee->id)
            ->where('year', $year)
            ->with('leaveType')
            ->get();
    }

    // ═══════════════════════════════════════════════
    // Attendance
    // ═══════════════════════════════════════════════

    /**
     * @param  array<string, mixed>  $data
     */
    public function markAttendance(Employee $employee, Carbon $date, array $data): Attendance
    {
        return Attendance::withoutGlobalScopes()->updateOrCreate(
            [
                'company_id' => $employee->company_id,
                'employee_id' => $employee->id,
                'attendance_date' => $date->toDateString(),
            ],
            $data,
        );
    }

    /**
     * Bulk mark attendance for multiple employees on a single date.
     *
     * @param  array<int, array{employee_id: int, status: string, check_in?: string, check_out?: string}>  $rows
     * @return array{marked: int}
     */
    public function bulkMarkAttendance(Company $company, Carbon $date, array $rows): array
    {
        $marked = 0;

        DB::transaction(function () use ($company, $date, $rows, &$marked): void {
            foreach ($rows as $row) {
                Attendance::withoutGlobalScopes()->updateOrCreate(
                    [
                        'company_id' => $company->id,
                        'employee_id' => $row['employee_id'],
                        'attendance_date' => $date->toDateString(),
                    ],
                    [
                        'status' => $row['status'],
                        'check_in' => $row['check_in'] ?? null,
                        'check_out' => $row['check_out'] ?? null,
                    ],
                );
                $marked++;
            }
        });

        return ['marked' => $marked];
    }

    /**
     * @return Collection<int, Attendance>
     */
    public function getMonthlyAttendance(Employee $employee, int $month, int $year): Collection
    {
        return Attendance::withoutGlobalScopes()
            ->where('employee_id', $employee->id)
            ->whereMonth('attendance_date', $month)
            ->whereYear('attendance_date', $year)
            ->orderBy('attendance_date')
            ->get();
    }

    /**
     * Company-wide attendance summary for a month.
     *
     * @return array{total_employees: int, avg_present_rate: float}
     */
    public function getAttendanceSummary(Company $company, Carbon $month): array
    {
        $employees = Employee::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('status', EmployeeStatus::ACTIVE)
            ->count();

        $totalPresent = Attendance::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->whereMonth('attendance_date', $month->month)
            ->whereYear('attendance_date', $month->year)
            ->where('status', 'present')
            ->count();

        $totalRecords = Attendance::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->whereMonth('attendance_date', $month->month)
            ->whereYear('attendance_date', $month->year)
            ->count();

        return [
            'total_employees' => $employees,
            'avg_present_rate' => $totalRecords > 0 ? round(($totalPresent / $totalRecords) * 100, 1) : 0,
        ];
    }
}
