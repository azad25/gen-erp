<?php

namespace App\Domain\Project\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class ProjectPhase extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'name',
        'description',
        'start_date',
        'due_date',
        'completed_at',
        'sort_order',
    ];

    protected $casts = [
        'start_date' => 'date',
        'due_date' => 'date',
        'completed_at' => 'datetime',
        'sort_order' => 'integer',
    ];

    /**
     * Get the project that owns the phase.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the tasks in this phase.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class, 'phase_id');
    }

    /**
     * Check if phase is completed.
     */
    public function isCompleted(): bool
    {
        return $this->completed_at !== null;
    }

    /**
     * Check if phase is overdue.
     */
    public function isOverdue(): bool
    {
        return $this->due_date && 
               $this->due_date->isPast() && 
               !$this->isCompleted();
    }

    /**
     * Get phase completion percentage based on tasks.
     */
    public function getCompletionPercentage(): float
    {
        $totalTasks = $this->tasks()->count();
        
        if ($totalTasks === 0) {
            return $this->isCompleted() ? 100 : 0;
        }

        $completedTasks = $this->tasks()->where('status', Task::STATUS_COMPLETED)->count();
        return ($completedTasks / $totalTasks) * 100;
    }

    /**
     * Mark phase as completed.
     */
    public function markAsCompleted(): void
    {
        $this->completed_at = now();
        $this->save();
    }
}