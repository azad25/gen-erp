<?php

namespace App\Domain\Calendar\Models;

use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;

class CalendarEvent extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'calendar_id',
        'user_id',
        'eventable_type',
        'eventable_id',
        'title',
        'description',
        'location',
        'start_at',
        'end_at',
        'all_day',
        'type',
        'status',
        'color',
        'is_recurring',
        'recurrence_rule',
        'reminder_minutes',
        'attendees',
        'metadata',
    ];

    protected $casts = [
        'start_at' => 'datetime',
        'end_at' => 'datetime',
        'all_day' => 'boolean',
        'is_recurring' => 'boolean',
        'attendees' => 'array',
        'metadata' => 'array',
    ];

    /**
     * Event types
     */
    const TYPE_MEETING = 'meeting';
    const TYPE_CALL = 'call';
    const TYPE_TASK = 'task';
    const TYPE_DEADLINE = 'deadline';
    const TYPE_LEAVE = 'leave';
    const TYPE_AVAILABILITY = 'availability';
    const TYPE_MILESTONE = 'milestone';
    const TYPE_PERSONAL = 'personal';
    const TYPE_COMPANY = 'company';

    /**
     * Event statuses
     */
    const STATUS_SCHEDULED = 'scheduled';
    const STATUS_IN_PROGRESS = 'in_progress';
    const STATUS_COMPLETED = 'completed';
    const STATUS_CANCELLED = 'cancelled';

    /**
     * Get the company that owns the event.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the calendar this event belongs to.
     */
    public function calendar(): BelongsTo
    {
        return $this->belongsTo(Calendar::class);
    }

    /**
     * Get the user who created the event.
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get the parent eventable model (Activity, Task, Leave, etc.).
     */
    public function eventable(): MorphTo
    {
        return $this->morphTo();
    }

    /**
     * Scope to get events for a specific date.
     */
    public function scopeOnDate($query, $date)
    {
        return $query->whereDate('start_at', $date)
            ->orWhere(function ($q) use ($date) {
                $q->where('all_day', true)
                  ->whereDate('start_at', '<=', $date)
                  ->whereDate('end_at', '>=', $date);
            });
    }

    /**
     * Scope to get events in date range.
     */
    public function scopeInRange($query, $startDate, $endDate)
    {
        return $query->where(function ($q) use ($startDate, $endDate) {
            $q->whereBetween('start_at', [$startDate, $endDate])
              ->orWhereBetween('end_at', [$startDate, $endDate])
              ->orWhere(function ($query) use ($startDate, $endDate) {
                  $query->where('start_at', '<=', $startDate)
                        ->where('end_at', '>=', $endDate);
              });
        });
    }

    /**
     * Scope to get upcoming events.
     */
    public function scopeUpcoming($query)
    {
        return $query->where('start_at', '>=', now())
            ->where('status', self::STATUS_SCHEDULED)
            ->orderBy('start_at');
    }

    /**
     * Scope to get past events.
     */
    public function scopePast($query)
    {
        return $query->where('end_at', '<', now())
            ->orderBy('start_at', 'desc');
    }

    /**
     * Scope to get events by type.
     */
    public function scopeOfType($query, $type)
    {
        return $query->where('type', $type);
    }

    /**
     * Scope to get events by status.
     */
    public function scopeWithStatus($query, $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Check if event is all day.
     */
    public function isAllDay(): bool
    {
        return $this->all_day;
    }

    /**
     * Check if event is recurring.
     */
    public function isRecurring(): bool
    {
        return $this->is_recurring;
    }

    /**
     * Check if event is in progress.
     */
    public function isInProgress(): bool
    {
        return $this->status === self::STATUS_IN_PROGRESS;
    }

    /**
     * Check if event is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === self::STATUS_COMPLETED;
    }

    /**
     * Check if event is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === self::STATUS_CANCELLED;
    }

    /**
     * Get event duration in minutes.
     */
    public function getDurationInMinutes(): ?int
    {
        if (!$this->end_at) {
            return null;
        }

        return $this->start_at->diffInMinutes($this->end_at);
    }

    /**
     * Check if event conflicts with another event.
     */
    public function conflictsWith(CalendarEvent $other): bool
    {
        if ($this->all_day || $other->all_day) {
            return $this->start_at->isSameDay($other->start_at);
        }

        return $this->start_at->between($other->start_at, $other->end_at)
            || $this->end_at->between($other->start_at, $other->end_at)
            || ($this->start_at <= $other->start_at && $this->end_at >= $other->end_at);
    }
}
