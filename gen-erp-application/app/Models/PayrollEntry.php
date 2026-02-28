<?php

namespace App\Models;

use App\Enums\PaymentStatus;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-employee payroll calculation for a specific run/period.
 */
class PayrollEntry extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'payroll_run_id',
        'employee_id',
        'period_month',
        'period_year',
        'working_days',
        'present_days',
        'absent_days',
        'leave_days',
        'overtime_hours',
        'basic_salary',
        'gross_salary',
        'earnings',
        'deductions',
        'overtime_amount',
        'attendance_deduction',
        'tax_deduction',
        'net_salary',
        'payment_method',
        'payment_status',
        'paid_at',
    ];

    protected function casts(): array
    {
        return [
            'period_month' => 'integer',
            'period_year' => 'integer',
            'working_days' => 'integer',
            'present_days' => 'float',
            'absent_days' => 'float',
            'leave_days' => 'float',
            'overtime_hours' => 'float',
            'basic_salary' => 'integer',
            'gross_salary' => 'integer',
            'earnings' => 'array',
            'deductions' => 'array',
            'overtime_amount' => 'integer',
            'attendance_deduction' => 'integer',
            'tax_deduction' => 'integer',
            'net_salary' => 'integer',
            'payment_status' => PaymentStatus::class,
            'paid_at' => 'datetime',
        ];
    }

    /** @return BelongsTo<PayrollRun, $this> */
    public function payrollRun(): BelongsTo
    {
        return $this->belongsTo(PayrollRun::class);
    }

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    public function totalEarnings(): int
    {
        return collect($this->earnings ?? [])->sum('amount');
    }

    public function totalDeductions(): int
    {
        return collect($this->deductions ?? [])->sum('amount')
            + $this->attendance_deduction
            + $this->tax_deduction;
    }

    public function effectiveNetSalary(): int
    {
        return $this->gross_salary + $this->overtime_amount - $this->totalDeductions();
    }
}
