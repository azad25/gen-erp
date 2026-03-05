# HR Domain - Complete Analysis

## Overview

The HR domain manages the complete employee lifecycle from hiring to payroll, with tight integration to accounting for salary expense tracking and tax compliance.

## Backend Architecture

### 1. Core Models

#### Employee Model (`app/Domain/HR/Models/Employee.php`)

**Purpose:** Core HR entity representing an employee

**Database Schema:**
```php
$fillable = [
  'company_id',              // Multi-tenancy
  'user_id',                // User account (FK)
  'department_id',           // Department (FK)
  'designation_id',          // Designation (FK)
  'employee_code',          // Auto-generated: EMP-XXXX
  'first_name',              // First name
  'last_name',              // Last name
  'name_bangla',            // Bangla name
  'email',                  // Email
  'phone',                  // Phone
  'date_of_birth',          // Date of birth
  'gender',                 // Gender
  'nid_number',             // NID (encrypted)
  'tin_number',             // TIN (encrypted)
  'joining_date',            // Joining date
  'confirmation_date',      // Confirmation date
  'resignation_date',        // Resignation date
  'termination_date',       // Termination date
  'employment_type',        // EmploymentType enum
  'status',                 // EmployeeStatus enum
  'basic_salary',           // Basic salary in paise
  'gross_salary',           // Gross salary in paise
  'hourly_rate',            // Hourly rate
  'weekly_capacity_hours',   // Weekly capacity hours
  'is_available_for_projects', // Project availability
  'skills',                 // Skills (JSON)
  'certifications',         // Certifications (JSON)
  'bank_name',              // Bank name
  'bank_account_number',    // Bank account (encrypted)
  'bank_routing_number',    // Bank routing
  'bkash_number',           // bKash number (encrypted)
  'address',                // Address
  'emergency_contact_name', // Emergency contact
  'emergency_contact_phone', // Emergency phone
  'photo_url',              // Photo URL
  'show_on_website',        // Show on website
  'bio',                    // Bio
  'position',               // Position
  'social_links',           // Social links (JSON)
  'custom_fields',           // Custom data (JSON)
];

// Auto-generates employee_code on creation
$employee_code = 'EMP-' . str_pad($seq, 4, '0', STR_PAD_LEFT);
```

**Relationships:**
```php
department() -> Department
designation() -> Designation
user() -> User
leaveRequests() -> LeaveRequest (hasMany)
```

**Key Method:**
```php
public function fullName(): string {
  return trim("{$this->first_name} {$this->last_name}");
}
```

#### PayrollRun Model (`app/Domain/HR/Models/PayrollRun.php`)

**Purpose:** Monthly payroll run - aggregates all employee payroll entries

**Database Schema:**
```php
$fillable = [
  'company_id',              // Multi-tenancy
  'branch_id',               // Branch dimension
  'run_number',              // Auto-generated: PAY-YYYY-MM-XXX
  'period_month',            // Period month (1-12)
  'period_year',             // Period year
  'status',                  // PayrollRunStatus enum
  'total_employees',         // Total employees
  'total_gross',             // Total gross salary
  'total_deductions',       // Total deductions
  'total_net',               // Total net salary
  'total_tax',               // Total tax
  'payment_date',            // Payment date
  'notes',                  // Notes
  'created_by',              // Creator user (FK)
  'approved_by',             // Approver user (FK)
];

// Auto-generates run_number on creation
$run_number = 'PAY-' . $year . '-' . str_pad($month, 2, '0', STR_PAD_LEFT) . '-' . str_pad($seq, 3, '0', STR_PAD_LEFT);
```

**Workflow States:**
- `DRAFT` - Initial state
- `PROCESSING` - Calculating payroll
- `APPROVED` - Approved for payment
- `PAID` - Paid to employees

**Key Method:**
```php
public function recalculateTotals(): void {
  $entries = $this->entries()->get();

  $this->update([
    'total_employees' => $entries->count(),
    'total_gross' => $entries->sum('gross_salary'),
    'total_deductions' => $entries->sum(fn (PayrollEntry $e) => $e->totalDeductions()),
    'total_net' => $entries->sum('net_salary'),
    'total_tax' => $entries->sum('tax_deduction'),
  ]);
}
```

#### PayrollEntry Model

**Purpose:** Individual employee payroll entry for a period

**Database Schema:**
```php
$fillable = [
  'company_id',              // Multi-tenancy
  'payroll_run_id',         // Payroll run (FK)
  'employee_id',             // Employee (FK)
  'period_month',            // Period month
  'period_year',             // Period year
  'working_days',           // Working days in month
  'present_days',           // Present days
  'absent_days',            // Absent days
  'leave_days',             // Leave days
  'overtime_hours',         // Overtime hours
  'basic_salary',           // Basic salary
  'gross_salary',           // Gross salary
  'earnings',               // Earnings (JSON)
  'deductions',             // Deductions (JSON)
  'overtime_amount',        // Overtime amount
  'attendance_deduction',   // Attendance deduction
  'tax_deduction',          // Tax deduction
  'net_salary',             // Net salary
  'payment_status',         // PaymentStatus enum
  'payment_method',         // Payment method
  'paid_at',                // Paid at
];
```

#### LeaveRequest Model (`app/Domain/HR/Models/LeaveRequest.php`)

**Purpose:** Leave request submitted by an employee

**Database Schema:**
```php
$fillable = [
  'company_id',              // Multi-tenancy
  'employee_id',             // Employee (FK)
  'leave_type_id',           // Leave type (FK)
  'from_date',              // From date
  'to_date',                // To date
  'total_days',             // Total days
  'reason',                 // Reason
  'status',                 // Status (pending, approved, rejected)
  'approved_by',            // Approver (FK)
];
```

**Workflow States:**
- `PENDING` - Awaiting approval
- `APPROVED` - Approved
- `REJECTED` - Rejected

#### LeaveBalance Model (`app/Domain/HR/Models/LeaveBalance.php`)

**Purpose:** Per-employee leave balance for a specific type and year

**Database Schema:**
```php
$fillable = [
  'company_id',              // Multi-tenancy
  'employee_id',             // Employee (FK)
  'leave_type_id',           // Leave type (FK)
  'year',                   // Year
  'allocated_days',         // Allocated days
  'used_days',              // Used days
  'carried_forward',        // Carried forward
  'balance',                // Generated column (allocated - used + carried_forward)
];
```

#### LeaveType Model (`app/Domain/HR/Models/LeaveType.php`)

**Purpose:** Leave type configuration

**Database Schema:**
```php
$fillable = [
  'company_id',              // Multi-tenancy
  'name',                   // Name
  'days_per_year',          // Days per year
  'is_paid',                // Is paid leave
  'carry_forward',          // Allow carry forward
  'max_carry_forward_days', // Max carry forward days
  'requires_approval',      // Requires approval
];
```

#### Attendance Model (`app/Domain/HR/Models/Attendance.php`)

**Purpose:** Daily attendance record

**Database Schema:**
```php
$fillable = [
  'company_id',              // Multi-tenancy
  'branch_id',               // Branch dimension
  'employee_id',             // Employee (FK)
  'attendance_date',         // Attendance date
  'check_in',               // Check in time
  'check_out',              // Check out time
  'status',                 // AttendanceStatus enum
  'working_hours',          // Working hours
  'overtime_hours',         // Overtime hours
  'notes',                  // Notes
];
```

#### Department Model (`app/Domain/HR/Models/Department.php`)

**Purpose:** Department within a company - supports hierarchy

**Database Schema:**
```php
$fillable = [
  'company_id',              // Multi-tenancy
  'parent_id',               // Parent department (FK)
  'name',                   // Name
  'code',                   // Code
  'manager_id',             // Manager (FK)
  'is_active',              // Active status
];
```

**Relationships:**
```php
parent() -> Department (self)
children() -> Department (hasMany)
manager() -> Employee
employees() -> Employee (hasMany)
designations() -> Designation (hasMany)
```

#### PerformanceReview Model (`app/Domain/HR/Models/PerformanceReview.php`)

**Purpose:** Employee performance reviews

**Database Schema:**
```php
$fillable = [
  'employee_id',             // Employee (FK)
  'reviewer_id',             // Reviewer (FK)
  'review_period_start',     // Review period start
  'review_period_end',       // Review period end
  'overall_rating',          // Overall rating (1-5)
  'technical_skills_rating', // Technical skills (1-5)
  'communication_rating',    // Communication (1-5)
  'teamwork_rating',         // Teamwork (1-5)
  'productivity_rating',     // Productivity (1-5)
  'strengths',               // Strengths
  'areas_for_improvement',   // Areas for improvement
  'goals',                  // Goals
  'comments',               // Comments
  'status',                 // Status
  'submitted_at',           // Submitted at
  'acknowledged_at',        // Acknowledged at
];
```

**Key Methods:**
```php
public function getAverageRating(): float {
  $ratings = array_filter([
    $this->technical_skills_rating,
    $this->communication_rating,
    $this->teamwork_rating,
    $this->productivity_rating,
  ]);

  if (empty($ratings)) return 0;

  return round(array_sum($ratings) / count($ratings), 2);
}

public function getRatingGrade(): string {
  $average = $this->getAverageRating();

  return match (true) {
    $average >= 4.5 => 'A+',
    $average >= 4.0 => 'A',
    $average >= 3.5 => 'B+',
    $average >= 3.0 => 'B',
    $average >= 2.5 => 'C+',
    $average >= 2.0 => 'C',
    $average >= 1.5 => 'D',
    default => 'F',
  };
}
```

#### EmployeeTimeEntry Model

**Purpose:** Time tracking entries for employees

**Database Schema:**
```php
$fillable = [
  'employee_id',             // Employee (FK)
  'task_id',                // Task (FK)
  'project_id',             // Project (FK)
  'entry_date',             // Entry date
  'start_time',             // Start time
  'end_time',               // End time
  'hours',                  // Hours worked
  'description',            // Description
  'entry_type',             // Entry type (work, leave, etc.)
  'is_billable',            // Is billable
];
```

#### EmployeeCapacity Model

**Purpose:** Employee capacity planning

**Database Schema:**
```php
$fillable = [
  'employee_id',             // Employee (FK)
  'week_start_date',        // Week start date
  'total_capacity_hours',   // Total capacity hours
  'allocated_hours',         // Allocated hours
  'available_hours',        // Available hours
  'utilization_percentage',  // Utilization percentage
];
```

### 2. Services

#### HRService (`app/Domain/HR/Services/HRService.php`)

**Purpose:** Manages HR operations - employees, attendance, leave management

**Methods:**

```php
// Create a new employee
public function createEmployee(Company $company, array $data, array $customFields = []): Employee {
  $data['company_id'] = $company->id;
  $data['employee_code'] = $this->sequenceService->next('employee', $company->id);
  $data['status'] = EmployeeStatus::ACTIVE;

  $employee = Employee::create($data);

  if (!empty($customFields)) {
    $employee->update(['custom_fields' => $customFields]);
  }

  return $employee;
}

// Allocate leave for an employee
public function allocateLeave(Employee $employee, LeaveType $leaveType, float $days, int $year): LeaveBalance {
  $balance = LeaveBalance::updateOrCreate([
    'employee_id' => $employee->id,
    'leave_type_id' => $leaveType->id,
    'year' => $year,
  ], [
    'allocated_days' => $days,
    'used_days' => 0,
  ]);

  return $balance->fresh();
}

// Request leave for an employee
public function requestLeave(Employee $employee, array $data): LeaveRequest {
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

// Approve a leave request
public function approveLeave(LeaveRequest $request, Employee $approver): void {
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

// Reject a leave request
public function rejectLeave(LeaveRequest $request, Employee $approver, string $reason): void {
  $request->update([
    'status' => 'rejected',
    'approved_by' => $approver->id,
    'approved_at' => now(),
    'rejection_reason' => $reason,
  ]);
}

// Mark attendance for an employee
public function markAttendance(Employee $employee, Carbon $date, array $data): Attendance {
  return Attendance::updateOrCreate([
    'employee_id' => $employee->id,
    'attendance_date' => $date->toDateString(),
  ], [
    'check_in' => $data['check_in'] ?? null,
    'check_out' => $data['check_out'] ?? null,
    'status' => $data['status'] ?? AttendanceStatus::PRESENT,
    'working_hours' => $data['working_hours'] ?? 0,
    'overtime_hours' => $data['overtime_hours'] ?? 0,
    'notes' => $data['notes'] ?? null,
  ]);
}
```

#### PayrollService (`app/Domain/HR/Services/PayrollService.php`)

**Purpose:** Orchestrates monthly payroll calculation

**Methods:**

```php
// Initiate a new payroll run for a company period
public function initiateRun(Company $company, int $month, int $year): PayrollRun {
  $existing = PayrollRun::withoutGlobalScopes()
    ->where('company_id', $company->id)
    ->where('period_month', $month)
    ->where('period_year', $year)
    ->exists();

  if ($existing) {
    throw new InvalidArgumentException('Payroll run already exists.');
  }

  return PayrollRun::create([
    'company_id' => $company->id,
    'period_month' => $month,
    'period_year' => $year,
    'status' => PayrollRunStatus::DRAFT,
  ]);
}

// Calculate payroll for all active employees in a run
public function calculateRun(PayrollRun $run): void {
  DB::transaction(function () use ($run): void {
    $employees = Employee::withoutGlobalScopes()
      ->where('company_id', $run->company_id)
      ->where('status', EmployeeStatus::ACTIVE)
      ->get();

    foreach ($employees as $employee) {
      $this->calculateEntry($run, $employee);
    }

    $run->recalculateTotals();
    $run->update(['status' => PayrollRunStatus::PROCESSING]);
  });
}

// Calculate a single employee's payroll entry
public function calculateEntry(PayrollRun $run, Employee $employee): PayrollEntry {
  $month = $run->period_month;
  $year = $run->period_year;

  // 1. Attendance
  $workingDays = $this->workingDaysInMonth($month, $year);
  $attendance = $this->getAttendanceCounts($employee, $month, $year);

  // 2. Per-day rate
  $basicSalary = $employee->basic_salary;
  $grossSalary = $employee->gross_salary;
  $perDayRate = $workingDays > 0 ? (int) round($basicSalary / $workingDays) : 0;

  // 3. Attendance deduction
  $attendanceDeduction = (int) round($attendance['absent_days'] * $perDayRate);

  // 4. Earnings from salary structure
  $earnings = $this->calculateEarnings($employee);

  // 5. Deductions from salary structure
  $deductionsList = $this->calculateDeductions($employee);

  // 6. Overtime: (basic / 26 / 8) * 2 * hours (BD Labour Law double rate)
  $overtimeRate = $basicSalary > 0 ? (int) round(($basicSalary / 26 / 8) * 2) : 0;
  $overtimeAmount = (int) round($overtimeRate * $attendance['overtime_hours']);

  // 7. Income tax
  $taxDeduction = $this->calculateMonthlyTax($employee, $grossSalary);

  // 8. Net = gross + overtime - structure deductions - tax - attendance deduction
  $structureDeductions = collect($deductionsList)->sum('amount');
  $netSalary = $grossSalary + $overtimeAmount - $structureDeductions - $taxDeduction - $attendanceDeduction;

  return PayrollEntry::updateOrCreate(
    [
      'payroll_run_id' => $run->id,
      'employee_id' => $employee->id,
    ],
    [
      'company_id' => $run->company_id,
      'period_month' => $month,
      'period_year' => $year,
      'working_days' => $workingDays,
      'present_days' => $attendance['present_days'],
      'absent_days' => $attendance['absent_days'],
      'leave_days' => $attendance['leave_days'],
      'overtime_hours' => $attendance['overtime_hours'],
      'basic_salary' => $basicSalary,
      'gross_salary' => $grossSalary,
      'earnings' => $earnings,
      'deductions' => $deductionsList,
      'overtime_amount' => $overtimeAmount,
      'attendance_deduction' => $attendanceDeduction,
      'tax_deduction' => $taxDeduction,
      'net_salary' => max(0, $netSalary),
    ],
  );
}

// Calculate annual income tax for an employee based on BD tax slabs
public function calculateAnnualTax(Employee $employee, int $annualGross, string $fiscalYear): int {
  // 1. Get exemptions
  $exemptions = TaxExemption::withoutGlobalScopes()
    ->where('employee_id', $employee->id)
    ->where('fiscal_year', $fiscalYear)
    ->get();

  $totalExemptions = 0;

  foreach ($exemptions as $ex) {
    $cap = match ($ex->exemption_type) {
      'house_rent' => min($ex->amount, min((int) round($employee->basic_salary * 12 * 0.5), 30000000)),
      'medical' => min($ex->amount, 12000000),
      'transport' => min($ex->amount, 3000000),
      default => $ex->amount,
    };
    $totalExemptions += $cap;
  }

  // 2. Taxable income
  $taxableIncome = max(0, $annualGross - $totalExemptions);

  // 3. Apply slabs
  $slabs = IncomeTaxSlab::withoutGlobalScopes()
    ->where('company_id', $employee->company_id)
    ->where('fiscal_year', $fiscalYear)
    ->orderBy('display_order')
    ->get();

  if ($slabs->isEmpty()) {
    return 0;
  }

  $totalTax = 0;
  $remaining = $taxableIncome;

  foreach ($slabs as $slab) {
    if ($remaining <= 0) break;

    $slabWidth = $slab->max_income !== null
      ? $slab->max_income - $slab->min_income
      : $remaining;

    $taxableInSlab = min($remaining, $slabWidth);
    $totalTax += (int) round($taxableInSlab * $slab->tax_rate / 100);
    $remaining -= $taxableInSlab;
  }

  return $totalTax;
}

// Monthly tax = annual tax / 12
public function calculateMonthlyTax(Employee $employee, int $monthlyGross): int {
  $annualGross = $monthlyGross * 12;
  $fiscalYear = $this->currentFiscalYear();
  $annualTax = $this->calculateAnnualTax($employee, $annualGross, $fiscalYear);

  return (int) round($annualTax / 12);
}

// Approve a payroll run
public function approveRun(PayrollRun $run, User $approver): void {
  $run->update([
    'status' => PayrollRunStatus::APPROVED,
    'approved_by' => $approver->id,
  ]);
}

// Mark run as paid
public function markAsPaid(PayrollRun $run, string $paymentMethod, Carbon $paidAt): void {
  DB::transaction(function () use ($run, $paymentMethod, $paidAt): void {
    $run->entries()->update([
      'payment_status' => PaymentStatus::PAID,
      'payment_method' => $paymentMethod,
      'paid_at' => $paidAt,
    ]);

    $run->update([
      'status' => PayrollRunStatus::PAID,
      'payment_date' => $paidAt->toDateString(),
    ]);
  });
}
```

#### TimeTrackingService (`app/Domain/HR/Services/TimeTrackingService.php`)

**Purpose:** Manages time tracking for employees

**Methods:**

```php
// Log time entry
public function logTime(LogTimeData $timeData): EmployeeTimeEntry {
  $employee = Employee::findOrFail($timeData->employeeId);
  
  if ($timeData->hours <= 0 || $timeData->hours > 24) {
    throw new \InvalidArgumentException('Hours must be greater than 0 and less than or equal to 24');
  }

  $timeEntry = EmployeeTimeEntry::create([
    'employee_id' => $timeData->employeeId,
    'task_id' => $timeData->taskId,
    'project_id' => $timeData->projectId,
    'entry_date' => $timeData->entryDate,
    'start_time' => $timeData->startTime,
    'end_time' => $timeData->endTime,
    'hours' => $timeData->hours,
    'description' => $timeData->description,
    'entry_type' => $timeData->entryType,
    'is_billable' => $timeData->isBillable,
  ]);

  // Update daily worklog
  $this->updateDailyWorklog($employee, $timeData->entryDate);

  // Update task actual hours if task specified
  if ($timeEntry->task_id) {
    $this->updateTaskActualHours($timeEntry->task_id);
  }

  return $timeEntry;
}

// Get weekly timesheet
public function getWeeklyTimesheet(Employee $employee, Carbon $weekStart): array {
  $weekEnd = $weekStart->copy()->endOfWeek();
  return $this->getTimesheet($employee, $weekStart, $weekEnd);
}

// Get billable hours for a period
public function getBillableHours(Employee $employee, Carbon $startDate, Carbon $endDate): float {
  return EmployeeTimeEntry::where('employee_id', $employee->id)
    ->where('entry_date', '>=', $startDate)
    ->where('entry_date', '<=', $endDate)
    ->where('is_billable', true)
    ->sum('hours');
}
```

#### CapacityPlanningService (`app/Domain/HR/Services/CapacityPlanningService.php`)

**Purpose:** Manages employee capacity planning and resource allocation

**Methods:**

```php
// Get or create capacity record for an employee for a specific week
public function getOrCreateCapacity(Employee $employee, Carbon $weekStartDate): EmployeeCapacity {
  $weekStart = $weekStartDate->copy()->startOfWeek();

  return EmployeeCapacity::firstOrCreate([
    'employee_id' => $employee->id,
    'week_start_date' => $weekStart,
  ], [
    'total_capacity_hours' => $employee->weekly_capacity_hours ?? 40,
    'allocated_hours' => 0,
    'available_hours' => $employee->weekly_capacity_hours ?? 40,
    'utilization_percentage' => 0,
  ]);
}

// Allocate hours to an employee's capacity
public function allocateHours(Employee $employee, float $hours, $weekStartDateOrProject = null, ?Carbon $weekStartDate = null): bool {
  if ($hours <= 0) {
    throw new \InvalidArgumentException('Hours must be greater than 0');
  }

  $weekStart = $weekStartDate ?? now()->startOfWeek();
  $capacity = $this->getOrCreateCapacity($employee, $weekStart);

  return $capacity->allocateHours((int) $hours);
}

// Deallocate hours from an employee's capacity
public function deallocateHours(Employee $employee, float $hours, ?Carbon $weekStartDate = null): bool {
  $weekStart = $weekStartDate ?? now()->startOfWeek();
  $capacity = $this->getOrCreateCapacity($employee, $weekStart);

  $capacity->deallocateHours((int) $hours);
  return true;
}

// Update employee's weekly capacity
public function updateWeeklyCapacity(Employee $employee, int $newCapacityHours): void {
  $employee->update(['weekly_capacity_hours' => $newCapacityHours]);

  EmployeeCapacity::where('employee_id', $employee->id)
    ->where('week_start_date', '>=', now()->startOfWeek())
    ->update([
      'total_capacity_hours' => $newCapacityHours,
    ]);
}

// Get team capacity overview for a company
public function getTeamCapacityOverview(?Company $company = null, ?Carbon $weekStartDate = null): array {
  $company = $company ?? Company::find(session('active_company_id'));
  $weekStart = $weekStartDate ?? now()->startOfWeek();

  $employees = Employee::where('company_id', $company->id)
    ->where('status', EmployeeStatus::ACTIVE)
    ->get();

  $totalCapacity = 0;
  $totalAllocated = 0;
  $totalAvailable = 0;
  $employeeCapacities = [];

  foreach ($employees as $employee) {
    $capacity = $this->getOrCreateCapacity($employee, $weekStart);
    $totalCapacity += $capacity->total_capacity_hours;
    $totalAllocated += $capacity->allocated_hours;
    $totalAvailable += $capacity->available_hours;
    $employeeCapacities[] = [
      'employee' => $employee,
      'capacity' => $capacity,
    ];
  }

  return [
    'total_capacity' => $totalCapacity,
    'total_allocated' => $totalAllocated,
    'total_available' => $totalAvailable,
    'utilization_percentage' => $totalCapacity > 0 ? round(($totalAllocated / $totalCapacity) * 100, 2) : 0,
    'employees' => $employeeCapacities,
  ];
}
```

## Frontend Architecture

### 1. HR/Dashboard.vue

**Purpose:** HR overview dashboard

**Metrics Displayed:**
- Total Employees (with delta)
- Attendance Rate (with delta)
- Pending Leaves
- Payroll Cost (with delta)

**Charts:**
- Attendance Trend (7d, 30d, 90d)
- Department Distribution
- Salary Distribution

**Quick Actions:**
- HR Report
- Add Employee

**API Calls:**
```javascript
GET /api/v1/hr/dashboard - Dashboard metrics
GET /api/v1/hr/attendance-trend - Trend data
```

### 2. HR/Employees.vue

**Purpose:** Manage employees

**Features:**
- List employees with columns:
  - Name
  - Email
  - Department
  - Position
  - Status (Active/Inactive)
- Actions:
  - View
  - Edit
  - Delete
- Create/Edit modal with form

**Form Fields:**
- Name (required)
- Email (required)
- Phone
- Department
- Position
- Hire Date
- Salary
- Address
- Status

**API Calls:**
```javascript
GET /api/v1/employees - List employees
POST /api/v1/employees - Create employee
PUT /api/v1/employees/{id} - Update employee
DELETE /api/v1/employees/{id} - Delete employee
```

### 3. HR/Payroll.vue

**Purpose:** Manage payroll

**Features:**
- List payroll with columns:
  - Period
  - Employee
  - Gross Pay
  - Net Pay
  - Status (Badge)
- Actions:
  - View
  - Process (pending only)
- Create modal with form

**Form Fields:**
- Employee (required)
- Period (required)
- Gross Pay (required)
- Deductions

**API Calls:**
```javascript
GET /api/v1/payroll - List payroll
POST /api/v1/payroll - Create payroll
POST /api/v1/payroll/{id}/process - Process payroll
```

### 4. HR/Leave.vue

**Purpose:** Manage leave requests

**Features:**
- List leave requests with columns:
  - Employee
  - Start Date
  - End Date
  - Type (Badge)
  - Status (Badge)
- Actions:
  - View
  - Approve (pending only)
  - Reject (pending only)
- Create modal with form

**Form Fields:**
- Employee (required)
- Leave Type (required)
- Start Date (required)
- End Date (required)
- Reason

**API Calls:**
```javascript
GET /api/v1/leave-requests - List leave requests
POST /api/v1/leave-requests - Create leave request
POST /api/v1/leave-requests/{id}/approve - Approve leave
POST /api/v1/leave-requests/{id}/reject - Reject leave
```

### 5. HR/Attendance.vue

**Purpose:** Manage attendance

**Features:**
- List attendance with columns:
  - Employee
  - Date
  - Check In
  - Check Out
  - Status (Badge)
  - Working Hours
- Actions:
  - View
  - Edit
- Create modal with form

**Form Fields:**
- Employee (required)
- Date (required)
  - Check In
  - Check Out
  - Status
  - Working Hours
  - Overtime Hours

**API Calls:**
```javascript
GET /api/v1/attendance - List attendance
POST /api/v1/attendance - Create attendance
PUT /api/v1/attendance/{id} - Update attendance
```

## Complete Data Flow

### Payroll Calculation Flow

```
User initiates payroll run
    ↓
PayrollService::initiateRun()
    ├─→ Check if run exists for period
    ├─→ Create PayrollRun
    │   ├─→ Generate run_number (PAY-YYYY-MM-XXX)
    │   └─→ Set status = DRAFT
    └─→ Return PayrollRun
    ↓
User calculates payroll
    ↓
PayrollService::calculateRun()
    ├─→ For each active employee:
    │   └─→ PayrollService::calculateEntry()
    │       ├─→ Get attendance counts
    │       │   ├─→ Working days in month
    │       │   ├─→ Present days
    │       │   ├─→ Absent days
    │       │   ├─→ Leave days
    │       │   └─→ Overtime hours
    │       ├─→ Calculate per-day rate
    │       ├─→ Calculate attendance deduction
    │       ├─→ Calculate earnings from salary structure
    │       ├─→ Calculate deductions from salary structure
    │       ├─→ Calculate overtime (BD Labour Law double rate)
    │       │   └─→ overtime_rate = (basic / 26 / 8) * 2
    │       ├─→ Calculate income tax
    │       │   ├─→ Get tax exemptions
    │       │   ├─→ Calculate taxable income
    │       │   ├─→ Apply tax slabs
    │       │   └─→ Return annual tax / 12
    │       ├─→ Calculate net salary
    │       │   └─→ net = gross + overtime - deductions - tax - attendance_deduction
    │       └─→ Create/Update PayrollEntry
    └─→ Recalculate totals
        ├─→ total_employees
        ├─→ total_gross
        ├─→ total_deductions
        ├─→ total_net
        └─→ total_tax
    ↓
User approves payroll
    ↓
PayrollService::approveRun()
    └─→ Set status = APPROVED
    ↓
User marks as paid
    ↓
PayrollService::markAsPaid()
    ├─→ Update all entries: payment_status = PAID
    ├─→ Set payment method and date
    └─→ Set status = PAID
```

### Leave Request Flow

```
Employee requests leave
    ↓
HRService::requestLeave()
    ├─→ Create LeaveRequest
    │   ├─→ Set status = PENDING
    │   └─→ Set from_date, to_date, total_days
    └─→ Return LeaveRequest
    ↓
Manager approves leave
    ↓
HRService::approveLeave()
    ├─→ Check leave balance
    │   └─→ Get LeaveBalance for year
    ├─→ Verify balance >= requested_days
    ├─→ Update LeaveBalance
    │   └─→ used_days += total_days
    └─→ Update LeaveRequest
        ├─→ Set status = APPROVED
        ├─→ Set approved_by
        └─→ Set approved_at
```

### Attendance Tracking Flow

```
Employee checks in
    ↓
HRService::markAttendance()
    ├─→ Create/Update Attendance
    │   ├─→ Set check_in time
    │   ├─→ Set status = PRESENT
    │   └─→ Set working_hours
    └─→ Return Attendance
    ↓
Employee checks out
    ↓
HRService::markAttendance()
    ├─→ Update Attendance
    │   ├─→ Set check_out time
    │   ├─→ Calculate working_hours
    │   ├─→ Calculate overtime_hours
    │   └─→ Set status based on hours
    └─→ Return Attendance
```

### Time Tracking Flow

```
Employee logs time
    ↓
TimeTrackingService::logTime()
    ├─→ Validate hours (0 < hours <= 24)
    ├─→ Create EmployeeTimeEntry
    │   ├─→ Set task_id, project_id
    │   ├─→ Set start_time, end_time
    │   ├─→ Set hours
    │   ├─→ Set entry_type
    │   └─→ Set is_billable
    ├─→ Update daily worklog
    ├─→ Update task actual hours
    └─→ Return EmployeeTimeEntry
```

### Capacity Planning Flow

```
Manager allocates hours to employee
    ↓
CapacityPlanningService::allocateHours()
    ├─→ Get or create EmployeeCapacity
    │   ├─→ Set total_capacity_hours
    │   ├─→ Set allocated_hours
    │   ├─→ Set available_hours
    │   └─→ Calculate utilization_percentage
    └─→ Return EmployeeCapacity
```

## Integration with Accounting Domain

### Payroll Journal Entry

**Journal Entry Structure:**
```
DR: Salary Expense       (gross_salary)
CR: Salary Payable        (net_salary)
CR: Tax Payable          (tax_deduction)
CR: Other Deductions     (other_deductions)
```

**AccountingService::journalForPayroll()**
```php
public function journalForPayroll(PayrollRun $run): JournalEntry {
  $salaryExpense = $this->findSystemAccount($run->company_id, AccountSubType::OPERATING_EXPENSE, '5002');
  $salaryPayable = $this->findSystemAccount($run->company_id, AccountSubType::CURRENT_LIABILITY, '2004');
  $taxPayable = $this->findSystemAccount($run->company_id, AccountSubType::CURRENT_LIABILITY, '2003');

  $lines = [
    new ProposedJournalLine(
      accountId: $salaryExpense->id,
      debit: $run->total_gross,
      credit: 0,
      description: 'Salary Expense',
    ),
    new ProposedJournalLine(
      accountId: $salaryPayable->id,
      debit: 0,
      credit: $run->total_net,
      description: 'Salary Payable',
    ),
  ];

  if ($run->total_tax > 0) {
    $lines[] = new ProposedJournalLine(
      accountId: $taxPayable->id,
      debit: 0,
      credit: $run->total_tax,
      description: 'Tax Payable',
    );
  }

  $proposed = new ProposedJournalEntry(
    companyId: $run->company_id,
    idempotencyKey: "payroll_run_{$run->id}_journal",
    journalCode: JournalCode::PAYROLL,
    entryDate: $run->payment_date ?? now(),
    description: "Payroll Run {$run->run_number}",
    referenceType: 'payroll_run',
    referenceId: $run->id,
    lines: $lines,
  );

  return $this->postingService->post($proposed);
}
```

### Tax Calculation

**Bangladesh Tax Slabs:**
- Taxable income up to 3,00,000: 0%
- 3,00,001 - 4,00,000: 5%
- 4,00,001 - 7,00,000: 10%
- 7,00,001 - 11,00,000: 15%
- 11,00,001 - 16,00,000: 20%
- Above 16,00,000: 25%

**Tax Exemptions:**
- House rent: 50% of basic salary (max 30,00,000)
- Medical: 1,20,000
- Transport: 3,00,000

## Comparison with Modern ERPs

### Features Comparison

| Feature | This System | Odoo | Zoho |
|---------|-------------|------|------|
| **Employee Management** | ✅ | ✅ | ✅ |
| **Payroll Processing** | ✅ | ✅ | ✅ |
| **Leave Management** | ✅ | ✅ | ✅ |
| **Attendance Tracking** | ✅ | ✅ | ✅ |
| **Time Tracking** | ✅ | ✅ | ✅ |
| **Performance Reviews** | ✅ | ✅ | ✅ |
| **Capacity Planning** | ✅ | ⚠️ | ⚠️ |
| **Tax Calculation** | ✅ (BD) | ✅ | ✅ |
| **Salary Structures** | ✅ | ✅ | ✅ |
| **Overtime Calculation** | ✅ (BD Law) | ✅ | ✅ |
| **Biometric Integration** | ❌ | ✅ | ✅ |
| **Shift Management** | ❌ | ✅ | ✅ |
| **Expense Claims** | ⚠️ Basic | ✅ | ✅ |
| **Reimbursements** | ❌ | ✅ | ✅ |
| **Payslip Generation** | ✅ | ✅ | ✅ |
| **Tax Filing** | ⚠️ Basic | ✅ | ✅ |
| **Multi-currency** | ⚠️ Limited | ✅ | ✅ |

### Workflow Comparison

**This System:**
```
Payroll Run: DRAFT → PROCESSING → APPROVED → PAID
Leave Request: PENDING → APPROVED/REJECTED
Attendance: Check In → Check Out → Calculate Hours
```

**Odoo:**
```
Payroll Run: DRAFT → CONFIRMED → PAID
Leave Request: PENDING → APPROVED/REJECTED
Attendance: Check In → Check Out → Calculate Hours
```

**Zoho:**
```
Payroll Run: DRAFT → CONFIRMED → PAID
Leave Request: PENDING → APPROVED/REJECTED
Attendance: Check In → Check Out → Calculate Hours
```

### Unique Features

**This System:**
- Bangladesh localization (BD tax slabs, labour law)
- BDT currency as primary
- Bangla name support
- Capacity planning integration
- Time tracking with project allocation
- Simplified workflow
- Idempotency guarantee

**Odoo/Zoho:**
- Biometric integration
- Shift management
- Advanced expense claims
- Reimbursements
- Tax filing integration
- Multi-currency support
- Advanced reporting

## API Reference

### Employees

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/api/v1/employees` | List employees | Required |
| GET | `/api/v1/employees/{id}` | Get employee | Required |
| POST | `/api/v1/employees` | Create employee | Required |
| PUT | `/api/v1/employees/{id}` | Update employee | Required |
| DELETE | `/api/v1/employees/{id}` | Delete employee | Required |

### Payroll

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/api/v1/payroll` | List payroll | Required |
| POST | `/api/v1/payroll` | Create payroll | Required |
| POST | `/api/v1/payroll/{id}/initiate` | Initiate run | Required |
| POST | `/api/v1/payroll/{id}/calculate` | Calculate payroll | Required |
| POST | `/api/v1/payroll/{id}/approve` | Approve payroll | Required |
| POST | `/api/v1/payroll/{id}/pay` | Mark as paid | Required |

### Leave Requests

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/api/v1/leave-requests` | List leave requests | Required |
| POST | `/api/v1/leave-requests` | Create leave request | Required |
| POST | `/api/v1/leave-requests/{id}/approve` | Approve leave | Required |
| POST | `/api/v1/leave-requests/{id}/reject` | Reject leave | Required |

### Attendance

| Method | Endpoint | Description | Auth |
|--------|----------|-------------|------|
| GET | `/api/v1/attendance` | List attendance | Required |
| POST | `/api/v1/attendance` | Create attendance | Required |
| PUT | `/api/v1/attendance/{id}` | Update attendance | Required |

### Query Parameters (Index)

```
search -> Filter by name
status -> Filter by status
department_id -> Filter by department
per_page -> Pagination (default: 15)
page -> Page number
```

### Request Body (Create Employee)

```json
{
  "first_name": "John",
  "last_name": "Doe",
  "email": "john@example.com",
  "phone": "+8801700000000",
  "department_id": 1,
  "designation_id": 1,
  "joining_date": "2026-03-05",
  "basic_salary": 5000000,
  "gross_salary": 6000000,
  "weekly_capacity_hours": 40
}
```

### Response Format

```json
{
  "success": true,
  "data": {
    "id": 1,
    "employee_code": "EMP-0001",
    "name": "John Doe",
    "email": "john@example.com",
    "department": {
      "id": 1,
      "name": "Engineering"
    },
    "status": "active"
  },
  "message": "Employee created"
}
```

## Frontend API Integration

### HR/Employees.vue

```javascript
const fetchEmployees = async (page = 1) => {
  const response = await get('/employees', { page, per_page: 15 })
  employees.value = response.data
  pagination.value = response.meta
}

const createEmployee = async () => {
  const data = {
    first_name: form.value.first_name,
    last_name: form.value.last_name,
    email: form.value.email,
    phone: form.value.phone,
    department_id: form.value.department_id,
    joining_date: form.value.hire_date,
    basic_salary: form.value.salary,
  }
  
  await post('/employees', data)
  await fetchEmployees()
}
```

### HR/Payroll.vue

```javascript
const processPayroll = async (payroll) => {
  await post(`/payroll/${payroll.id}/process`)
  await fetchPayrolls()
}

const approvePayroll = async (payroll) => {
  await post(`/payroll/${payroll.id}/approve`)
  await fetchPayrolls()
}
```

## Summary

### Backend Coverage
- ✅ Employee model (auto-numbering, salary tracking, employment type)
- ✅ PayrollRun model (monthly runs, workflow)
- ✅ PayrollEntry model (individual payroll)
- ✅ LeaveRequest model (workflow support)
- ✅ LeaveBalance model (balance tracking)
- ✅ LeaveType model (configuration)
- ✅ Attendance model (daily tracking)
- ✅ Department model (hierarchy support)
- ✅ PerformanceReview model (ratings, grades)
- ✅ EmployeeTimeEntry model (time tracking)
- ✅ EmployeeCapacity model (capacity planning)
- ✅ HRService (employee management, leave, attendance)
- ✅ PayrollService (payroll calculation, tax calculation, overtime)
- ✅ TimeTrackingService (time logging, timesheet)
- ✅ CapacityPlanningService (capacity planning, resource allocation)
- ✅ Multi-tenancy support

### Frontend Coverage
- ✅ HR/Dashboard.vue (metrics, charts)
- ✅ HR/Employees.vue (list, create, edit, delete)
- ✅ HR/Payroll.vue (list, create, process, approve)
- ✅ HR/Leave.vue (list, create, approve, reject)
- ✅ HR/Attendance.vue (list, create, edit)
- ✅ BanglaAmount component (BDT formatting)
- ✅ Badge component (status display)
- ✅ Pagination support

### Integration
- ✅ Payroll journal entry (Salary Expense, Payable, Tax Payable)
- ✅ Tax calculation (BD tax slabs, exemptions)
- ✅ Overtime calculation (BD Labour Law double rate)
- ✅ Attendance deduction (per-day rate)
- ✅ Leave balance tracking (allocated, used, carried forward)
- ✅ Time tracking (billable hours, task allocation)
- ✅ Capacity planning (resource allocation, utilization)
- ✅ Multi-tenancy (company isolation)
- ✅ Bangladesh localization (BDT, Bangla name, tax slabs)

The HR system provides **comprehensive employee management** that follows modern ERP patterns with tight integration to accounting domain.

## Backend Architecture
- **Employee Model** - Auto-numbering (EMP-XXXX), salary tracking, employment type, weekly capacity
- **PayrollRun Model** - Monthly runs, workflow (DRAFT → PROCESSING → APPROVED → PAID)
- **PayrollEntry Model** - Individual payroll with attendance, overtime, tax calculations
- **LeaveRequest Model** - Workflow (PENDING → APPROVED/REJECTED)
- **LeaveBalance Model** - Balance tracking (allocated, used, carried_forward)
- **LeaveType Model** - Configuration (days_per_year, is_paid, carry_forward)
- **Attendance Model** - Daily tracking with working_hours, overtime_hours
- **Department Model** - Hierarchy support (parent_id, manager_id)
- **PerformanceReview Model** - Ratings (1-5), grades (A+ to F)
- **EmployeeTimeEntry Model** - Time tracking with project allocation
- **EmployeeCapacity Model** - Capacity planning (total_capacity, allocated, available)
- **HRService** - Employee management, leave management, attendance
- **PayrollService** - Payroll calculation, BD tax calculation, overtime (double rate)
- **TimeTrackingService** - Time logging, timesheet generation
- **CapacityPlanningService** - Capacity planning, resource allocation

## Data Flows
- **Payroll Calculation:** Initiate → Calculate (attendance, earnings, deductions, overtime, tax) → Approve → Mark Paid
- **Leave Request:** Request → Check Balance → Approve/Reject → Update Balance
- **Attendance Tracking:** Check In → Check Out → Calculate Hours → Update Status
- **Time Tracking:** Log Time → Update Worklog → Update Task Hours
- **Capacity Planning:** Allocate Hours → Update Capacity → Calculate Utilization

## Integration
- **Accounting:** Payroll journal entry (DR: Salary Expense, CR: Salary Payable/Tax Payable)
- **Tax Calculation:** BD tax slabs (0%, 5%, 10%, 15%, 20%, 25%), exemptions (house rent, medical, transport)
- **Overtime Calculation:** BD Labour Law double rate (basic / 26 / 8) * 2 * hours
- **Attendance Deduction:** Per-day rate calculation
- **Leave Balance:** Allocated - Used + Carried Forward
- **Time Tracking:** Billable hours, project allocation
- **Capacity Planning:** Resource allocation, utilization percentage

## Frontend Architecture
- **HR/Dashboard.vue** - Metrics (employees, attendance, leaves, payroll), charts
- **HR/Employees.vue** - List, create, edit, delete
- **HR/Payroll.vue** - List, create, process, approve
- **HR/Leave.vue** - List, create, approve, reject
- **HR/Attendance.vue** - List, create, edit

## Comparison with Modern ERPs
- **Similar:** Core HR management, payroll, leave, attendance, time tracking, performance reviews
- **Simpler:** No biometric integration, no shift management, no expense claims, no reimbursements
- **Unique:** Bangladesh localization (tax slabs, labour law), capacity planning, simplified workflow
