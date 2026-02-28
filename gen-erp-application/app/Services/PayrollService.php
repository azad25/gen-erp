<?php

namespace App\Services;

use App\Enums\EmployeeStatus;
use App\Enums\PaymentStatus;
use App\Enums\PayrollRunStatus;
use App\Models\Attendance;
use App\Models\Company;
use App\Models\Employee;
use App\Models\IncomeTaxSlab;
use App\Models\PayrollEntry;
use App\Models\PayrollRun;
use App\Models\SalaryComponent;
use App\Models\TaxExemption;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Support\Facades\DB;
use InvalidArgumentException;

/**
 * Orchestrates monthly payroll calculation: salary breakdown, attendance deductions, BD income tax, overtime.
 */
class PayrollService
{
    /**
     * Initiate a new payroll run for a company period. Throws if one already exists.
     */
    public function initiateRun(Company $company, int $month, int $year): PayrollRun
    {
        $existing = PayrollRun::withoutGlobalScopes()
            ->where('company_id', $company->id)
            ->where('period_month', $month)
            ->where('period_year', $year)
            ->exists();

        if ($existing) {
            throw new InvalidArgumentException(__('Payroll run already exists for :month/:year.', [
                'month' => $month,
                'year' => $year,
            ]));
        }

        return PayrollRun::withoutGlobalScopes()->create([
            'company_id' => $company->id,
            'period_month' => $month,
            'period_year' => $year,
            'status' => PayrollRunStatus::DRAFT,
        ]);
    }

    /**
     * Calculate payroll for all active employees in a run.
     */
    public function calculateRun(PayrollRun $run): void
    {
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

    /**
     * Calculate a single employee's payroll entry for the given run.
     */
    public function calculateEntry(PayrollRun $run, Employee $employee): PayrollEntry
    {
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

        return PayrollEntry::withoutGlobalScopes()->updateOrCreate(
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

    /**
     * Calculate annual income tax for an employee based on BD tax slabs.
     */
    public function calculateAnnualTax(Employee $employee, int $annualGross, string $fiscalYear): int
    {
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
            if ($remaining <= 0) {
                break;
            }

            $slabWidth = $slab->max_income !== null
                ? $slab->max_income - $slab->min_income
                : $remaining;

            $taxableInSlab = min($remaining, $slabWidth);
            $totalTax += (int) round($taxableInSlab * $slab->tax_rate / 100);
            $remaining -= $taxableInSlab;
        }

        return $totalTax;
    }

    /**
     * Monthly tax = annual tax / 12.
     */
    public function calculateMonthlyTax(Employee $employee, int $monthlyGross): int
    {
        $annualGross = $monthlyGross * 12;
        $fiscalYear = $this->currentFiscalYear();
        $annualTax = $this->calculateAnnualTax($employee, $annualGross, $fiscalYear);

        return (int) round($annualTax / 12);
    }

    /**
     * Approve a payroll run.
     */
    public function approveRun(PayrollRun $run, User $approver): void
    {
        $run->update([
            'status' => PayrollRunStatus::APPROVED,
            'approved_by' => $approver->id,
        ]);
    }

    /**
     * Mark run as paid — update all entries.
     */
    public function markAsPaid(PayrollRun $run, string $paymentMethod, Carbon $paidAt): void
    {
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

    /**
     * Recalculate a single entry (for corrections before approval).
     */
    public function recalculateEntry(PayrollEntry $entry): PayrollEntry
    {
        $run = PayrollRun::withoutGlobalScopes()->find($entry->payroll_run_id);
        $employee = Employee::withoutGlobalScopes()->find($entry->employee_id);

        return $this->calculateEntry($run, $employee);
    }

    // ─── Helpers ───

    /**
     * @return array{present_days: float, absent_days: float, leave_days: float, overtime_hours: float}
     */
    private function getAttendanceCounts(Employee $employee, int $month, int $year): array
    {
        $attendances = Attendance::withoutGlobalScopes()
            ->where('employee_id', $employee->id)
            ->whereMonth('attendance_date', $month)
            ->whereYear('attendance_date', $year)
            ->get();

        return [
            'present_days' => (float) $attendances->whereIn('status', [\App\Enums\AttendanceStatus::PRESENT, \App\Enums\AttendanceStatus::LATE])->count(),
            'absent_days' => (float) $attendances->where('status', \App\Enums\AttendanceStatus::ABSENT)->count(),
            'leave_days' => (float) $attendances->where('status', \App\Enums\AttendanceStatus::ON_LEAVE)->count(),
            'overtime_hours' => (float) $attendances->sum('overtime_hours'),
        ];
    }

    private function workingDaysInMonth(int $month, int $year): int
    {
        $start = Carbon::create($year, $month, 1);
        $end = $start->copy()->endOfMonth();
        $days = 0;

        while ($start->lte($end)) {
            if (! $start->isFriday()) {
                $days++;
            }
            $start->addDay();
        }

        return $days;
    }

    /**
     * @return array<int, array{component: string, amount: int}>
     */
    private function calculateEarnings(Employee $employee): array
    {
        $components = SalaryComponent::withoutGlobalScopes()
            ->where('company_id', $employee->company_id)
            ->where('component_type', 'earning')
            ->orderBy('display_order')
            ->get();

        $earnings = [];
        foreach ($components as $comp) {
            $amount = $this->resolveComponentAmount($comp, $employee);
            $earnings[] = ['component' => $comp->name, 'amount' => $amount];
        }

        return $earnings;
    }

    /**
     * @return array<int, array{component: string, amount: int}>
     */
    private function calculateDeductions(Employee $employee): array
    {
        $components = SalaryComponent::withoutGlobalScopes()
            ->where('company_id', $employee->company_id)
            ->where('component_type', 'deduction')
            ->orderBy('display_order')
            ->get();

        $deductions = [];
        foreach ($components as $comp) {
            $amount = $this->resolveComponentAmount($comp, $employee);
            $deductions[] = ['component' => $comp->name, 'amount' => $amount];
        }

        return $deductions;
    }

    private function resolveComponentAmount(SalaryComponent $comp, Employee $employee): int
    {
        return match ($comp->calculation_type->value) {
            'fixed' => (int) $comp->value,
            'percentage_of_basic' => (int) round($employee->basic_salary * $comp->value / 100),
            'percentage_of_gross' => (int) round($employee->gross_salary * $comp->value / 100),
            default => 0,
        };
    }

    private function currentFiscalYear(): string
    {
        $now = now();
        // BD fiscal year: July–June
        if ($now->month >= 7) {
            return $now->year.'-'.substr((string) ($now->year + 1), 2);
        }

        return ($now->year - 1).'-'.substr((string) $now->year, 2);
    }
}
