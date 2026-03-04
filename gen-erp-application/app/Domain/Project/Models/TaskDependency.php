<?php

namespace App\Domain\Project\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TaskDependency extends Model
{
    use HasFactory;

    protected $fillable = [
        'task_id',
        'depends_on_task_id',
        'dependency_type',
    ];

    // Dependency types
    public const TYPE_BLOCKS = 'blocks';
    public const TYPE_IS_BLOCKED_BY = 'is_blocked_by';
    public const TYPE_RELATES_TO = 'relates_to';

    /**
     * Get the task that has the dependency.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get the task that this task depends on.
     */
    public function dependsOnTask(): BelongsTo
    {
        return $this->belongsTo(Task::class, 'depends_on_task_id');
    }

    /**
     * Get available dependency types.
     */
    public static function getDependencyTypes(): array
    {
        return [
            self::TYPE_BLOCKS => 'Blocks',
            self::TYPE_IS_BLOCKED_BY => 'Is Blocked By',
            self::TYPE_RELATES_TO => 'Relates To',
        ];
    }
}