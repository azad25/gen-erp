<?php

namespace App\Domain\Project\Models;

use App\Domain\HR\Models\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Task extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'board_id',
        'board_column_id',
        'parent_task_id',
        'phase_id',
        'title',
        'description',
        'status',
        'priority',
        'type',
        'assignee_id',
        'reporter_id',
        'start_date',
        'due_date',
        'estimated_hours',
        'actual_hours',
        'story_points',
        'position',
        'tags',
        'settings',
    ];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
        'estimated_hours' => 'decimal:2',
        'actual_hours' => 'decimal:2',
        'story_points' => 'integer',
        'position' => 'integer',
        'tags' => 'array',
        'settings' => 'array',
    ];

    // Task statuses
    public const STATUS_TODO = 'todo';
    public const STATUS_IN_PROGRESS = 'in_progress';
    public const STATUS_IN_REVIEW = 'in_review';
    public const STATUS_TESTING = 'testing';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    // Task priorities
    public const PRIORITY_LOW = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    // Task types
    public const TYPE_TASK = 'task';
    public const TYPE_BUG = 'bug';
    public const TYPE_FEATURE = 'feature';
    public const TYPE_IMPROVEMENT = 'improvement';
    public const TYPE_EPIC = 'epic';
    public const TYPE_STORY = 'story';

    /**
     * Get the project that owns the task.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the board that contains the task.
     */
    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    /**
     * Get the board column that contains the task.
     */
    public function boardColumn(): BelongsTo
    {
        return $this->belongsTo(BoardColumn::class);
    }

    /**
     * Get the parent task.
     */
    public function parentTask(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'parent_task_id');
    }

    /**
     * Get the child tasks (subtasks).
     */
    public function subtasks(): HasMany
    {
        return $this->hasMany(Task::class, 'parent_task_id');
    }

    /**
     * Get the task assignee.
     */
    public function assignee(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'assignee_id');
    }

    /**
     * Get the task reporter.
     */
    public function reporter(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'reporter_id');
    }

    /**
     * Get the task watchers.
     */
    public function watchers(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'task_watchers')
            ->withTimestamps();
    }

    /**
     * Get the task comments.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(TaskComment::class);
    }

    /**
     * Get the task attachments.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(TaskAttachment::class);
    }

    /**
     * Get the task checklists.
     */
    public function checklists(): HasMany
    {
        return $this->hasMany(TaskChecklist::class);
    }

    /**
     * Get the task dependencies (tasks this task depends on).
     */
    public function dependencies(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_dependencies', 'task_id', 'depends_on_task_id')
            ->withPivot(['dependency_type'])
            ->withTimestamps();
    }

    /**
     * Get the tasks that depend on this task.
     */
    public function dependents(): BelongsToMany
    {
        return $this->belongsToMany(Task::class, 'task_dependencies', 'depends_on_task_id', 'task_id')
            ->withPivot(['dependency_type'])
            ->withTimestamps();
    }

    /**
     * Get the task phase.
     */
    public function phase(): BelongsTo
    {
        return $this->belongsTo(ProjectPhase::class, 'phase_id');
    }

    /**
     * Get the task time entries.
     */
    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    /**
     * Scope for tasks by status.
     */
    public function scopeByStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope for tasks by priority.
     */
    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope for tasks by assignee.
     */
    public function scopeByAssignee($query, $assigneeId)
    {
        return $query->where('assignee_id', $assigneeId);
    }

    /**
     * Scope for overdue tasks.
     */
    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', now())
            ->whereNotIn('status', [self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
    }

    /**
     * Scope for tasks due today.
     */
    public function scopeDueToday($query)
    {
        return $query->whereDate('due_date', today())
            ->whereNotIn('status', [self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
    }

    /**
     * Scope for tasks due this week.
     */
    public function scopeDueThisWeek($query)
    {
        return $query->whereBetween('due_date', [now()->startOfWeek(), now()->endOfWeek()])
            ->whereNotIn('status', [self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
    }

    /**
     * Check if task is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->due_date && 
               $this->due_date->isPast() && 
               !in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
    }

    /**
     * Check if task is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if task is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    /**
     * Get task completion percentage based on subtasks.
     */
    public function getCompletionPercentage(): float
    {
        if ($this->isCompleted()) {
            return 100;
        }

        $totalSubtasks = $this->subtasks()->count();
        
        if ($totalSubtasks === 0) {
            return $this->isInProgress() ? 50 : 0;
        }

        $completedSubtasks = $this->subtasks()->where('status', self::STATUS_COMPLETED)->count();
        return ($completedSubtasks / $totalSubtasks) * 100;
    }

    /**
     * Get total time logged on this task.
     */
    public function getTotalTimeLogged(): float
    {
        return $this->timeEntries()->sum('hours');
    }

    /**
     * Get remaining estimated hours.
     */
    public function getRemainingHours(): float
    {
        return max(0, $this->estimated_hours - $this->getTotalTimeLogged());
    }

    /**
     * Move task to a different column.
     */
    public function moveToColumn(BoardColumn $column): void
    {
        $this->board_column_id = $column->id;
        $this->position = $column->tasks()->max('position') + 1;
        $this->save();
    }

    /**
     * Assign task to an employee.
     */
    public function assignTo(Employee $employee): void
    {
        $this->assignee_id = $employee->id;
        $this->save();
    }

    /**
     * Add a watcher to the task.
     */
    public function addWatcher(Employee $employee): void
    {
        $this->watchers()->syncWithoutDetaching([$employee->id]);
    }

    /**
     * Remove a watcher from the task.
     */
    public function removeWatcher(Employee $employee): void
    {
        $this->watchers()->detach($employee->id);
    }

    /**
     * Get available statuses.
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_TODO => 'To Do',
            self::STATUS_IN_PROGRESS => 'In Progress',
            self::STATUS_IN_REVIEW => 'In Review',
            self::STATUS_TESTING => 'Testing',
            self::STATUS_COMPLETED => 'Completed',
            self::STATUS_CANCELLED => 'Cancelled',
        ];
    }

    /**
     * Get available priorities.
     */
    public static function getPriorities(): array
    {
        return [
            self::PRIORITY_LOW => 'Low',
            self::PRIORITY_MEDIUM => 'Medium',
            self::PRIORITY_HIGH => 'High',
            self::PRIORITY_URGENT => 'Urgent',
        ];
    }

    /**
     * Get available types.
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_TASK => 'Task',
            self::TYPE_BUG => 'Bug',
            self::TYPE_FEATURE => 'Feature',
            self::TYPE_IMPROVEMENT => 'Improvement',
            self::TYPE_EPIC => 'Epic',
            self::TYPE_STORY => 'Story',
        ];
    }
}