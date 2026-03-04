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
 * Employee Task Assignment - Links employees to project tasks
 */
class EmployeeTask extends Model
{
    use BelongsToCompany;
    use HasFactory;

    protected $fillable = [
        'employee_id',
        'task_id',
        'project_id',
        'assigned_by',
        'assigned_at',
        'started_at',
        'completed_at',
        'estimated_hours',
        'actual_hours',
        'status',
        'notes',
    ];

    protected function casts(): array
    {
        return [
            'assigned_at' => 'datetime',
            'started_at' => 'datetime',
            'completed_at' => 'datetime',
            'estimated_hours' => 'decimal:2',
            'actual_hours' => 'decimal:2',
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
    public function assignedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'assigned_by');
    }

    // ─── Helpers ───

    public function isOverdue(): bool
    {
        return $this->status !== 'completed' && 
               $this->task->due_date && 
               $this->task->due_date->isPast();
    }

    public function getProgressPercentage(): int
    {
        if ($this->estimated_hours <= 0) {
            return $this->status === 'completed' ? 100 : 0;
        }

        $percentage = ($this->actual_hours / $this->estimated_hours) * 100;
        return min(100, (int) round($percentage));
    }

    public function markAsStarted(): void
    {
        $this->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
    }

    public function markAsCompleted(): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
        ]);
    }
}