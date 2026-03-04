<?php

namespace App\Domain\Project\Models;

use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Board extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'project_id',
        'name',
        'description',
        'type',
        'is_default',
        'settings',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'settings' => 'array',
    ];

    // Board types
    public const TYPE_KANBAN = 'kanban';
    public const TYPE_SCRUM = 'scrum';
    public const TYPE_CUSTOM = 'custom';

    /**
     * Get the project that owns the board.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Get the board columns.
     */
    public function columns(): HasMany
    {
        return $this->hasMany(BoardColumn::class)->orderBy('position');
    }

    /**
     * Get the board tasks.
     */
    public function tasks(): HasMany
    {
        return $this->hasMany(Task::class);
    }

    /**
     * Scope for default boards.
     */
    public function scopeDefault($query)
    {
        return $query->where('is_default', true);
    }

    /**
     * Scope for boards by type.
     */
    public function scopeByType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Create default columns for the board.
     */
    public function createDefaultColumns(): void
    {
        $defaultColumns = [
            ['name' => 'To Do', 'color' => '#6b7280', 'position' => 1],
            ['name' => 'In Progress', 'color' => '#3b82f6', 'position' => 2],
            ['name' => 'In Review', 'color' => '#f59e0b', 'position' => 3],
            ['name' => 'Done', 'color' => '#10b981', 'position' => 4],
        ];

        foreach ($defaultColumns as $columnData) {
            $this->columns()->create($columnData);
        }
    }

    /**
     * Get available board types.
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_KANBAN => 'Kanban',
            self::TYPE_SCRUM => 'Scrum',
            self::TYPE_CUSTOM => 'Custom',
        ];
    }
}