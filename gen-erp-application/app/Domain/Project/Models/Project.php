<?php

namespace App\Domain\Project\Models;

use App\Domain\Auth\Models\User;
use App\Domain\HR\Models\Employee;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\BelongsToMany;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Project extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'name',
        'description',
        'status',
        'priority',
        'start_date',
        'end_date',
        'budget',
        'currency',
        'client_name',
        'client_email',
        'client_phone',
        'project_manager_id',
        'progress_percentage',
        'is_billable',
        'hourly_rate',
        'estimated_hours',
        'actual_hours',
        'color',
        'settings',
    ];

    protected $casts = [
        'start_date' => 'date',
        'end_date' => 'date',
        'budget' => 'decimal:2',
        'hourly_rate' => 'decimal:2',
        'estimated_hours' => 'decimal:2',
        'actual_hours' => 'decimal:2',
        'progress_percentage' => 'integer',
        'is_billable' => 'boolean',
        'settings' => 'array',
    ];

    // Project statuses
    public const STATUS_PLANNING = 'planning';
    public const STATUS_ACTIVE = 'active';
    public const STATUS_ON_HOLD = 'on_hold';
    public const STATUS_COMPLETED = 'completed';
    public const STATUS_CANCELLED = 'cancelled';

    // Project priorities
    public const PRIORITY_LOW = 'low';
    public const PRIORITY_MEDIUM = 'medium';
    public const PRIORITY_HIGH = 'high';
    public const PRIORITY_URGENT = 'urgent';

    /**
     * Get the company that owns the project.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Auth\Models\Company::class);
    }

    /**
     * Get the project manager.
     */
    public function projectManager(): BelongsTo
    {
        return $this->belongsTo(Employee::class, 'project_manager_id');
    }

    /**
     * Get the project members.
     */
    public function members(): BelongsToMany
    {
        return $this->belongsToMany(Employee::class, 'project_members')
            ->withPivot(['role', 'hourly_rate', 'joined_at'])
            ->withTimestamps();
    }

    /**
     * Get the project phases.
     */
    public function phases(): HasMany
    {
        return $this->hasMany(ProjectPhase::class);
    }

    /**
     * Get the project boards.
     */
    public function boards(): HasMany
    {
        return $this->hasMany(Board::class);
    }

    /**
     * Get the project tasks.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Get the project time entries.
     */
    public function timeEntries(): HasMany
    {
        return $this->hasMany(TimeEntry::class);
    }

    /**
     * Get the project attachments.
     */
    public function attachments(): HasMany
    {
        return $this->hasMany(ProjectAttachment::class);
    }

    /**
     * Get the project comments.
     */
    public function comments(): HasMany
    {
        return $this->hasMany(ProjectComment::class);
    }

    /**
     * Scope for active projects.
     */
    public function scopeActive($query)
    {
        return $query->where('status', self::STATUS_ACTIVE);
    }

    /**
     * Scope for completed projects.
     */
    public function scopeCompleted($query)
    {
        return $query->where('status', self::STATUS_COMPLETED);
    }

    /**
     * Scope for projects by priority.
     */
    public function scopeByPriority($query, $priority)
    {
        return $query->where('priority', $priority);
    }

    /**
     * Scope for overdue projects.
     */
    public function scopeOverdue($query)
    {
        return $query->where('end_date', '<', now())
            ->whereNotIn('status', [self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
    }

    /**
     * Check if project is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->end_date && 
               $this->end_date->isPast() && 
               !in_array($this->status, [self::STATUS_COMPLETED, self::STATUS_CANCELLED]);
    }

    /**
     * Check if project is active.
     */
    public function isActive(): bool
    {
        return $this->status === self::STATUS_ACTIVE;
    }

    /**
     * Check if project is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Get project duration in days.
     */
    public function getDurationInDays(): ?int
    {
        if (!$this->start_date || !$this->end_date) {
            return null;
        }

        return $this->start_date->diffInDays($this->end_date);
    }

    /**
     * Get project budget utilization percentage.
     */
    public function getBudgetUtilizationPercentage(): float
    {
        if (!$this->budget || $this->budget <= 0) {
            return 0;
        }

        $totalCost = $this->actual_hours * $this->hourly_rate;
        return min(($totalCost / $this->budget) * 100, 100);
    }

    /**
     * Get project completion percentage based on tasks.
     */
    public function getTaskCompletionPercentage(): float
    {
        $totalTasks = $this->tasks()->count();
        
        if ($totalTasks === 0) {
            return 0;
        }

        $completedTasks = $this->tasks()->where('status', Task::STATUS_COMPLETED)->count();
        return ($completedTasks / $totalTasks) * 100;
    }

    /**
     * Update project progress based on tasks.
     */
    public function updateProgress(): void
    {
        $this->progress_percentage = (int) $this->getTaskCompletionPercentage();
        $this->save();
    }

    /**
     * Get available statuses.
     */
    public static function getStatuses(): array
    {
        return [
            self::STATUS_PLANNING => 'Planning',
            self::STATUS_ACTIVE => 'Active',
            self::STATUS_ON_HOLD => 'On Hold',
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
}