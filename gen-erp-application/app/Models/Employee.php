<?php

namespace App\Models;

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Models\Traits\BelongsToCompany;
use App\Models\Traits\DispatchesModelEvents;
use App\Models\Traits\HasCustomFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Employee record — core HR entity.
 */
class Employee extends Model
{
    use BelongsToCompany;
    use DispatchesModelEvents;
    use HasCustomFields;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'user_id',
        'department_id',
        'designation_id',
        'employee_code',
        'first_name',
        'last_name',
        'name_bangla',
        'email',
        'phone',
        'date_of_birth',
        'gender',
        'nid_number',
        'tin_number',
        'joining_date',
        'confirmation_date',
        'resignation_date',
        'termination_date',
        'employment_type',
        'status',
        'basic_salary',
        'gross_salary',
        'bank_name',
        'bank_account_number',
        'bank_routing_number',
        'bkash_number',
        'address',
        'emergency_contact_name',
        'emergency_contact_phone',
        'photo_url',
        'custom_fields',
    ];

    protected function casts(): array
    {
        return [
            'date_of_birth' => 'date',
            'joining_date' => 'date',
            'confirmation_date' => 'date',
            'resignation_date' => 'date',
            'termination_date' => 'date',
            'basic_salary' => 'integer',
            'gross_salary' => 'integer',
            'status' => EmployeeStatus::class,
            'employment_type' => EmploymentType::class,
            'nid_number' => 'encrypted',
            'tin_number' => 'encrypted',
            'bank_account_number' => 'encrypted',
            'bkash_number' => 'encrypted',
            'custom_fields' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (Employee $employee): void {
            if ($employee->employee_code === null || $employee->employee_code === '') {
                $seq = static::withoutGlobalScopes()
                    ->where('company_id', $employee->company_id)
                    ->count() + 1;
                $employee->employee_code = 'EMP-'.str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function customFieldEntityType(): string
    {
        return 'employee';
    }

    public function alertEntityType(): string
    {
        return 'employee';
    }

    public function fullName(): string
    {
        return trim("{$this->first_name} {$this->last_name}");
    }

    // ─── Relationships ───

    /** @return BelongsTo<Department, $this> */
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    /** @return BelongsTo<Designation, $this> */
    public function designation(): BelongsTo
    {
        return $this->belongsTo(Designation::class);
    }

    /** @return BelongsTo<User, $this> */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /** @return HasMany<LeaveRequest, $this> */
    public function leaveRequests(): HasMany
    {
        return $this->hasMany(LeaveRequest::class);
    }

    /** @return HasMany<LeaveBalance, $this> */
    public function leaveBalances(): HasMany
    {
        return $this->hasMany(LeaveBalance::class);
    }

    /** @return HasMany<Attendance, $this> */
    public function attendances(): HasMany
    {
        return $this->hasMany(Attendance::class);
    }

    // ─── Helpers ───

    public function currentLeaveBalance(int $leaveTypeId): ?LeaveBalance
    {
        return $this->leaveBalances()
            ->where('leave_type_id', $leaveTypeId)
            ->where('year', now()->year)
            ->first();
    }

    /**
     * @return array{present: int, absent: int, late: int, half_day: int, on_leave: int}
     */
    public function attendanceSummary(\Carbon\Carbon $month): array
    {
        $attendances = $this->attendances()
            ->whereMonth('attendance_date', $month->month)
            ->whereYear('attendance_date', $month->year)
            ->get();

        return [
            'present' => $attendances->where('status', 'present')->count(),
            'absent' => $attendances->where('status', 'absent')->count(),
            'late' => $attendances->where('status', 'late')->count(),
            'half_day' => $attendances->where('status', 'half_day')->count(),
            'on_leave' => $attendances->where('status', 'on_leave')->count(),
        ];
    }
}
