<?php

namespace App\Domain\HR\Models;

use App\Domain\Auth\Models\Concerns\BelongsToCompany;
use App\Domain\Auth\Models\User;
use App\Domain\Project\Models\Project;
use App\Domain\Project\Models\Task;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/**
 * Employee Time Entry - Time tracking for employees
 */
class EmployeeTimeEntry extends Model
{
    use BelongsToCompany;
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'task_id',
        'project_id',
        'entry_date',
        'start_time',
        'end_time',
        'hours',
        'description',
        'entry_type',
        'is_billable',
        'is_approved',
        'approved_by',
        'approved_at',
    ];

    protected function casts(): array
    {
        return [
            'entry_date' => 'date',
            'start_time' => 'datetime:H:i',
            'end_time' => 'datetime:H:i',
            'hours' => 'decimal:2',
            'is_billable' => 'boolean',
            'is_approved' => 'boolean',
            'approved_at' => 'datetime',
        ];
    }

    // ─── Relationships ───

    /** @return BelongsTo<Employee, $this> */
    public function employee(): BelongsTo
    {
        return $this->belongsTo(Employee::class);
    }

    /** @return BelongsTo<Task, $this> */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /** @return BelongsTo<Project, $this> */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /** @return BelongsTo<User, $this> */
    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // ─── Helpers ───

    public function calculateHours(): float
    {
        if (!$this->start_time || !$this->end_time) {
            return 0;
        }

        $start = \Carbon\Carbon::parse($this->start_time);
        $end = \Carbon\Carbon::parse($this->end_time);
        
        return $end->diffInMinutes($start) / 60;
    }

    public function approve(User $approver): void
    {
        $this->update([
            'is_approved' => true,
            'approved_by' => $approver->id,
            'approved_at' => now(),
        ]);
    }

    public function reject(): void
    {
        $this->update([
            'is_approved' => false,
            'approved_by' => null,
            'approved_at' => null,
        ]);
    }

    public function getBillableAmount(): float
    {
        if (!$this->is_billable || !$this->employee->hourly_rate) {
            return 0;
        }

        return $this->hours * $this->employee->hourly_rate;
    }
}