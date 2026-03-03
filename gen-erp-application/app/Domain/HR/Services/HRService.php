<?php

namespace App\Domain\HR\Services;

use App\Domain\Auth\Models\Company;
use App\Domain\HR\Actions\CreateDepartmentAction;
use App\Domain\HR\Actions\DeleteDepartmentAction;
use App\Domain\HR\Actions\UpdateDepartmentAction;
use App\Domain\HR\Contracts\HRServiceInterface;
use App\Domain\HR\DTOs\CreateDepartmentData;
use App\Domain\HR\DTOs\UpdateDepartmentData;
use App\Domain\HR\Models\Attendance;
use App\Domain\HR\Models\Department;
use App\Domain\HR\Models\Designation;
use App\Domain\HR\Models\Employee;
use App\Domain\HR\Models\LeaveBalance;
use App\Domain\HR\Models\LeaveRequest;
use App\Domain\HR\Models\LeaveType;
use App\Domain\System\Services\SequenceService;
use App\Support\Enums\AttendanceStatus;
use App\Support\Enums\EmployeeStatus;
use Carbon\Carbon;

/**
 * Manages HR operations - employees, attendance, leave management.
 */
class HRService implements HRServiceInterface
{
    public function __construct(
        private SequenceService $sequenceService,
        private CreateDepartmentAction $createDepartmentAction,
        private UpdateDepartmentAction $updateDepartmentAction,
        private DeleteDepartmentAction $deleteDepartmentAction,
    ) {}

    /**
     * Create a new employee.
     */
    public function createEmployee(Company $company, array $data, array $customFields = []): Employee
    {
        $data['company_id'] = $company->id;
        $data['employee_code'] = $this->sequenceService->next('employee', $company->id);
        $data['status'] = EmployeeStatus::ACTIVE;

        $employee = Employee::create($data);

        if (!empty($customFields)) {
            $employee->update(['custom_fields' => $customFields]);
        }

        return $employee;
    }

    /**
     * Allocate leave for an employee.
     */
    public function allocateLeave(Employee $employee, LeaveType $leaveType, float $days, int $year): LeaveBalance
    {
        $balance = LeaveBalance::updateOrCreate([
            'employee_id' => $employee->id,
            'leave_type_id' => $leaveType->id,
            'year' => $year,
        ], [
            'allocated_days' => $days,
            'used_days' => 0,
        ]);

        // Refresh to get the calculated balance column
        return $balance->fresh();
    }

    /**
     * Request leave for an employee.
     */
    public function requestLeave(Employee $employee, array $data): LeaveRequest
    {
        return LeaveRequest::create([
            'employee_id' => $employee->id,
            'leave_type_id' => $data['leave_type_id'],
            'from_date' => $data['from_date'],
            'to_date' => $data['to_date'],
            'total_days' => $data['total_days'],
            'reason' => $data['reason'] ?? null,
            'status' => 'pending',
        ]);
    }

    /**
     * Approve a leave request.
     */
    public function approveLeave(LeaveRequest $request, Employee $approver): void
    {
        // Check if employee has sufficient balance
        $balance = LeaveBalance::where('employee_id', $request->employee_id)
            ->where('leave_type_id', $request->leave_type_id)
            ->where('year', now()->year)
            ->first();

        if (!$balance || $balance->balance < $request->total_days) {
            throw new \InvalidArgumentException('Insufficient leave balance.');
        }

        // Update balance
        $balance->update([
            'used_days' => $balance->used_days + $request->total_days,
        ]);

        // Approve request
        $request->update([
            'status' => 'approved',
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);
    }

    /**
     * Reject a leave request.
     */
    public function rejectLeave(LeaveRequest $request, Employee $approver, string $reason): void
    {
        $request->update([
            'status' => 'rejected',
            'approved_by' => $approver->id,
            'approved_at' => now(),
            'rejection_reason' => $reason,
        ]);
    }

    /**
     * Mark attendance for an employee.
     */
    public function markAttendance(Employee $employee, Carbon $date, array $data): Attendance
    {
        return Attendance::updateOrCreate([
            'employee_id' => $employee->id,
            'attendance_date' => $date->toDateString(),
        ], [
            'status' => AttendanceStatus::from($data['status']),
            'check_in' => $data['check_in'] ?? null,
            'check_out' => $data['check_out'] ?? null,
            'notes' => $data['notes'] ?? null,
        ]);
    }

    /**
     * Get monthly attendance for an employee.
     */
    public function getMonthlyAttendance(Employee $employee, int $month, int $year): \Illuminate\Database\Eloquent\Collection
    {
        return Attendance::where('employee_id', $employee->id)
            ->whereYear('attendance_date', $year)
            ->whereMonth('attendance_date', $month)
            ->orderBy('attendance_date')
            ->get();
    }

    /**
     * Terminate an employee.
     */
    public function terminateEmployee(Employee $employee, Carbon $terminationDate, string $reason = null): void
    {
        $employee->update([
            'status' => EmployeeStatus::TERMINATED,
            'termination_date' => $terminationDate->toDateString(),
            'termination_reason' => $reason,
        ]);
    }

    /**
     * Create a new department.
     */
    public function createDepartment(CreateDepartmentData $data): Department
    {
        return $this->createDepartmentAction->execute($data);
    }

    /**
     * Update a department.
     */
    public function updateDepartment(Department $department, UpdateDepartmentData $data): Department
    {
        return $this->updateDepartmentAction->execute($department, $data);
    }

    /**
     * Delete a department.
     */
    public function deleteDepartment(Department $department): void
    {
        $this->deleteDepartmentAction->execute($department);
    }

    /**
     * Get departments for a company with optional search.
     */
    public function getDepartments(Company $company, ?string $search = null): \Illuminate\Database\Eloquent\Builder
    {
        return Department::query()
            ->where('company_id', $company->id)
            ->when($search, fn($q, $s) => $q->where('name', 'LIKE', "%{$s}%"))
            ->orderBy('name');
    }

    /**
     * Bulk mark attendance for multiple employees.
     */
    public function bulkMarkAttendance(Company $company, Carbon $date, array $records): array
    {
        $results = [];
        
        foreach ($records as $record) {
            $employee = Employee::where('company_id', $company->id)
                ->findOrFail($record['employee_id']);
            
            $data = collect($record)->except(['employee_id'])->toArray();
            
            $attendance = $this->markAttendance($employee, $date, $data);
            $results[] = $attendance->load(['employee']);
        }
        
        return $results;
    }

    // ═══════════════════════════════════════════════
    // Leave Type Management
    // ═══════════════════════════════════════════════

    /**
     * Get paginated leave types for a company.
     */
    public function getLeaveTypes(int $companyId, ?string $search = null, ?bool $isActive = null, int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return LeaveType::query()
            ->where('company_id', $companyId)
            ->when($search, fn ($q, $s) => $q->where('name', 'LIKE', "%{$s}%"))
            ->when($isActive !== null, fn ($q) => $q->where('is_active', $isActive))
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Get a specific leave type.
     */
    public function getLeaveType(int $companyId, int $id): LeaveType
    {
        return LeaveType::where('company_id', $companyId)->findOrFail($id);
    }

    /**
     * Create a leave type.
     */
    public function createLeaveType(int $companyId, array $data): LeaveType
    {
        $data['company_id'] = $companyId;
        return LeaveType::create($data);
    }

    /**
     * Update a leave type.
     */
    public function updateLeaveType(LeaveType $leaveType, array $data): LeaveType
    {
        $leaveType->update($data);
        return $leaveType->fresh();
    }

    /**
     * Delete a leave type.
     */
    public function deleteLeaveType(LeaveType $leaveType): void
    {
        $leaveType->delete();
    }

    // ═══════════════════════════════════════════════
    // Designation Management
    // ═══════════════════════════════════════════════

    /**
     * Get paginated designations for a company.
     */
    public function getDesignations(int $companyId, ?string $search = null, int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator
    {
        return Designation::query()
            ->where('company_id', $companyId)
            ->when($search, fn ($q, $s) => $q->where('name', 'LIKE', "%{$s}%"))
            ->orderBy('name')
            ->paginate($perPage);
    }

    /**
     * Get a specific designation.
     */
    public function getDesignation(int $companyId, int $id): Designation
    {
        return Designation::where('company_id', $companyId)->findOrFail($id);
    }

    /**
     * Create a designation.
     */
    public function createDesignation(int $companyId, array $data): Designation
    {
        $data['company_id'] = $companyId;
        return Designation::create($data);
    }

    /**
     * Update a designation.
     */
    public function updateDesignation(Designation $designation, array $data): Designation
    {
        $designation->update($data);
        return $designation->fresh();
    }

    /**
     * Delete a designation.
     */
    public function deleteDesignation(Designation $designation): void
    {
        $designation->delete();
    }
}