<?php

namespace App\Models;

use App\Enums\AttendanceStatus;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Daily attendance record for an employee.
 */
class Attendance extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'branch_id',
        'employee_id',
        'attendance_date',
        'check_in',
        'check_out',
        'status',
        'working_hours',
        'overtime_hours',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'attendance_date' => 'date',
            'status' => AttendanceStatus::class,
            'working_hours' => 'float',
            'overtime_hours' => 'float',
        ];
    }

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }
}
