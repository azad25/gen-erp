<?php

namespace App\Domain\Project\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class BoardColumn extends Model
{
    use HasFactory;

    protected $fillable = [
        'board_id',
        'name',
        'description',
        'color',
        'position',
        'wip_limit',
        'is_done_column',
        'settings',
    ];

    protected $casts = [
        'position' => 'integer',
        'wip_limit' => 'integer',
        'is_done_column' => 'boolean',
        'settings' => 'array',
    ];

    /**
     * Get the board that owns the column.
     */
    public function board(): BelongsTo
    {
        return $this->belongsTo(Board::class);
    }

    /**
     * Get the tasks in this column.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class)->orderBy('position');
    }

    /**
     * Scope for done columns.
     */
    public function scopeDone($query)
    {
        return $query->where('is_done_column', true);
    }

    /**
     * Check if column has reached WIP limit.
     */
    public function hasReachedWipLimit(): bool
    {
        if (!$this->wip_limit) {
            return false;
        }

        return $this->tasks()->count() >= $this->wip_limit;
    }

    /**
     * Get task count in this column.
     */
    public function getTaskCount(): int
    {
        return $this->tasks()->count();
    }

    /**
     * Move column to a new position.
     */
    public function moveToPosition(int $newPosition): void
    {
        $oldPosition = $this->position;
        
        if ($newPosition > $oldPosition) {
            // Moving right, shift columns left
            $this->board->columns()
                ->where('position', '>', $oldPosition)
                ->where('position', '<=', $newPosition)
                ->decrement('position');
        } else {
            // Moving left, shift columns right
            $this->board->columns()
                ->where('position', '>=', $newPosition)
                ->where('position', '<', $oldPosition)
                ->increment('position');
        }

        $this->position = $newPosition;
        $this->save();
    }
}