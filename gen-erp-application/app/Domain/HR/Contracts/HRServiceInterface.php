<?php

namespace App\Domain\HR\Contracts;

use App\Domain\Auth\Models\Company;
use App\Domain\HR\DTOs\CreateDepartmentData;
use App\Domain\HR\DTOs\UpdateDepartmentData;
use App\Domain\HR\Models\Attendance;
use App\Domain\HR\Models\Department;
use App\Domain\HR\Models\Designation;
use App\Domain\HR\Models\Employee;
use App\Domain\HR\Models\LeaveBalance;
use App\Domain\HR\Models\LeaveRequest;
use App\Domain\HR\Models\LeaveType;
use Carbon\Carbon;

/**
 * Interface for HR service operations.
 */
interface HRServiceInterface
{
    /**
     * Create a new employee.
     */
    public function createEmployee(Company $company, array $data, array $customFields = []): Employee;

    /**
     * Allocate leave for an employee.
     */
    public function allocateLeave(Employee $employee, LeaveType $leaveType, float $days, int $year): LeaveBalance;

    /**
     * Request leave for an employee.
     */
    public function requestLeave(Employee $employee, array $data): LeaveRequest;

    /**
     * Approve a leave request.
     */
    public function approveLeave(LeaveRequest $request, Employee $approver): void;

    /**
     * Reject a leave request.
     */
    public function rejectLeave(LeaveRequest $request, Employee $approver, string $reason): void;

    /**
     * Mark attendance for an employee.
     */
    public function markAttendance(Employee $employee, Carbon $date, array $data): Attendance;

    /**
     * Get monthly attendance for an employee.
     */
    public function getMonthlyAttendance(Employee $employee, int $month, int $year): \Illuminate\Database\Eloquent\Collection;

    /**
     * Terminate an employee.
     */
    public function terminateEmployee(Employee $employee, Carbon $terminationDate, string $reason = null): void;

    /**
     * Create a new department.
     */
    public function createDepartment(CreateDepartmentData $data): Department;

    /**
     * Update a department.
     */
    public function updateDepartment(Department $department, UpdateDepartmentData $data): Department;

    /**
     * Delete a department.
     */
    public function deleteDepartment(Department $department): void;

    /**
     * Get departments for a company with optional search.
     */
    public function getDepartments(Company $company, ?string $search = null): \Illuminate\Database\Eloquent\Builder;

    /**
     * Bulk mark attendance for multiple employees.
     */
    public function bulkMarkAttendance(Company $company, Carbon $date, array $records): array;

    /**
     * Get paginated leave types for a company.
     */
    public function getLeaveTypes(int $companyId, ?string $search = null, ?bool $isActive = null, int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator;

    /**
     * Get a specific leave type.
     */
    public function getLeaveType(int $companyId, int $id): LeaveType;

    /**
     * Create a leave type.
     */
    public function createLeaveType(int $companyId, array $data): LeaveType;

    /**
     * Update a leave type.
     */
    public function updateLeaveType(LeaveType $leaveType, array $data): LeaveType;

    /**
     * Delete a leave type.
     */
    public function deleteLeaveType(LeaveType $leaveType): void;

    /**
     * Get paginated designations for a company.
     */
    public function getDesignations(int $companyId, ?string $search = null, int $perPage = 15): \Illuminate\Contracts\Pagination\LengthAwarePaginator;

    /**
     * Get a specific designation.
     */
    public function getDesignation(int $companyId, int $id): Designation;

    /**
     * Create a designation.
     */
    public function createDesignation(int $companyId, array $data): Designation;

    /**
     * Update a designation.
     */
    public function updateDesignation(Designation $designation, array $data): Designation;

    /**
     * Delete a designation.
     */
    public function deleteDesignation(Designation $designation): void;
}