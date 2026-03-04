<?php

namespace App\Domain\Project\Models;

use App\Domain\Auth\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class TimeEntry extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'task_id',
        'user_id',
        'project_id',
        'description',
        'hours',
        'entry_date',
        'type',
        'is_billable',
        'hourly_rate',
        'amount',
    ];

    protected $casts = [
        'hours' => 'decimal:2',
        'entry_date' => 'date',
        'is_billable' => 'boolean',
        'hourly_rate' => 'decimal:2',
        'amount' => 'decimal:2',
    ];

    // Time entry types
    public const TYPE_DEVELOPMENT = 'development';
    public const TYPE_MEETING = 'meeting';
    public const TYPE_REVIEW = 'review';
    public const TYPE_TESTING = 'testing';
    public const TYPE_DOCUMENTATION = 'documentation';
    public const TYPE_OTHER = 'other';

    /**
     * Get the task that owns the time entry.
     */
    public function task(): BelongsTo
    {
        return $this->belongsTo(Task::class);
    }

    /**
     * Get the user who logged the time.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the project that owns the time entry.
     */
    public function project(): BelongsTo
    {
        return $this->belongsTo(Project::class);
    }

    /**
     * Calculate amount based on hours and hourly rate.
     */
    protected static function boot()
    {
        parent::boot();

        static::saving(function ($timeEntry) {
            if ($timeEntry->hours && $timeEntry->hourly_rate) {
                $timeEntry->amount = $timeEntry->hours * $timeEntry->hourly_rate;
            }
        });
    }

    /**
     * Get available time entry types.
     */
    public static function getTypes(): array
    {
        return [
            self::TYPE_DEVELOPMENT => 'Development',
            self::TYPE_MEETING => 'Meeting',
            self::TYPE_REVIEW => 'Review',
            self::TYPE_TESTING => 'Testing',
            self::TYPE_DOCUMENTATION => 'Documentation',
            self::TYPE_OTHER => 'Other',
        ];
    }
}