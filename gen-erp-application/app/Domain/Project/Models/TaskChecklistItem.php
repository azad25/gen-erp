<?php

namespace App\Domain\Project\Models;

use App\Domain\Auth\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TaskChecklistItem extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'checklist_id',
        'title',
        'is_completed',
        'completed_by',
        'completed_at',
        'sort_order',
    ];

    protected $casts = [
        'is_completed' => 'boolean',
        'completed_at' => 'datetime',
        'sort_order' => 'integer',
    ];

    /**
     * Get the checklist that owns the item.
     */
    public function checklist(): BelongsTo
    {
        return $this->belongsTo(TaskChecklist::class, 'checklist_id');
    }

    /**
     * Get the user who completed the item.
     */
    public function completedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'completed_by');
    }

    /**
     * Mark item as completed.
     */
    public function markAsCompleted(int $userId): void
    {
        $this->update([
            'is_completed' => true,
            'completed_by' => $userId,
            'completed_at' => now(),
        ]);
    }

    /**
     * Mark item as incomplete.
     */
    public function markAsIncomplete(): void
    {
        $this->update([
            'is_completed' => false,
            'completed_by' => null,
            'completed_at' => null,
        ]);
    }
}