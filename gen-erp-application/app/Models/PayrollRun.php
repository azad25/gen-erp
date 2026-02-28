<?php

namespace App\Models;

use App\Enums\PayrollRunStatus;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/**
 * Monthly payroll run — aggregates all employee payroll entries for a period.
 */
class PayrollRun extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'run_number',
        'period_month',
        'period_year',
        'status',
        'total_employees',
        'total_gross',
        'total_deductions',
        'total_net',
        'total_tax',
        'payment_date',
        'notes',
        'created_by',
        'approved_by',
    ];

    protected function casts(): array
    {
        return [
            'period_month' => 'integer',
            'period_year' => 'integer',
            'total_employees' => 'integer',
            'total_gross' => 'integer',
            'total_deductions' => 'integer',
            'total_net' => 'integer',
            'total_tax' => 'integer',
            'payment_date' => 'date',
            'status' => PayrollRunStatus::class,
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (PayrollRun $run): void {
            if ($run->run_number === null || $run->run_number === '') {
                $seq = static::withoutGlobalScopes()
                    ->where('company_id', $run->company_id)
                    ->count() + 1;
                $run->run_number = 'PAY-'.$run->period_year.'-'.str_pad((string) $run->period_month, 2, '0', STR_PAD_LEFT).'-'.str_pad((string) $seq, 3, '0', STR_PAD_LEFT);
            }
        });
    }

    public function workflowDocumentType(): string
    {
        return 'payroll_run';
    }

    /** @return BelongsTo<User, $this> */
    public function creator(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /** @return BelongsTo<User, $this> */
    public function approver(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    /** @return HasMany<PayrollEntry, $this> */
    public function entries(): HasMany
    {
        return $this->hasMany(PayrollEntry::class);
    }

    /**
     * Recalculate totals from entries.
     */
    public function recalculateTotals(): void
    {
        $entries = $this->entries()->get();

        $this->update([
            'total_employees' => $entries->count(),
            'total_gross' => $entries->sum('gross_salary'),
            'total_deductions' => $entries->sum(fn (PayrollEntry $e) => $e->totalDeductions()),
            'total_net' => $entries->sum('net_salary'),
            'total_tax' => $entries->sum('tax_deduction'),
        ]);
    }
}
