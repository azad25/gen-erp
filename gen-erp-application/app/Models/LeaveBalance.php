<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Per-employee leave balance for a specific type and year (includes generated balance column).
 */
class LeaveBalance extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'employee_id',
        'leave_type_id',
        'year',
        'allocated_days',
        'used_days',
        'carried_forward',
    ];

    protected function casts(): array
    {
        return [
            'year' => 'integer',
            'allocated_days' => 'float',
            'used_days' => 'float',
            'carried_forward' => 'float',
            'balance' => 'float',
        ];
    }

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /** @return BelongsTo<LeaveType, $this> */
    public function leaveType(): BelongsTo
    {
        return $this->belongsTo(LeaveType::class);
    }
}
