<?php

namespace App\Domain\Project\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaskChecklist extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'task_id',
        'title',
        'sort_order',
    ];

    protected $casts = [
        'sort_order' => 'integer',
    ];

    /**
     * Get the task that owns the checklist.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get the checklist items.
     */
    public function items(): HasMany
    {
        return $this->hasMany(TaskChecklistItem::class, 'checklist_id')->orderBy('sort_order');
    }

    /**
     * Get completion percentage.
     */
    public function getCompletionPercentage(): float
    {
        $totalItems = $this->items()->count();
        
        if ($totalItems === 0) {
            return 0;
        }

        $completedItems = $this->items()->where('is_completed', true)->count();
        return ($completedItems / $totalItems) * 100;
    }

    /**
     * Check if all items are completed.
     */
    public function isCompleted(): bool
    {
        return $this->items()->where('is_completed', false)->count() === 0;
    }
}