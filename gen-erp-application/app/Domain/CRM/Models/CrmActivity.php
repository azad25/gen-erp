<?php

namespace App\Domain\CRM\Models;

use App\Domain\CRM\Enums\ActivityType;
use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphTo;
use Illuminate\Database\Eloquent\SoftDeletes;
use Illuminate\Support\Str;

class CrmActivity extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'uuid',
        'company_id',
        'user_id',
        'subject_type',
        'subject_id',
        'type',
        'title',
        'description',
        'status',
        'priority',
        'scheduled_at',
        'started_at',
        'completed_at',
        'duration_minutes',
        'planned_duration_minutes',
        'direction',
        'outcome',
        'outcome_notes',
        'due_date',
        'is_reminder',
        'reminder_at',
        'reminder_sent',
        'email_subject',
        'email_body',
        'attachments',
        'meeting_location',
        'meeting_link',
        'attendees',
        'custom_fields',
        'metadata',
    ];

    protected $casts = [
        'type' => ActivityType::class,
        'scheduled_at' => 'datetime',
        'started_at' => 'datetime',
        'completed_at' => 'datetime',
        'due_date' => 'datetime',
        'reminder_at' => 'datetime',
        'is_reminder' => 'boolean',
        'reminder_sent' => 'boolean',
        'duration_minutes' => 'integer',
        'planned_duration_minutes' => 'integer',
        'attachments' => 'array',
        'attendees' => 'array',
        'custom_fields' => 'array',
        'metadata' => 'array',
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($activity) {
            if (empty($activity->uuid)) {
                $activity->uuid = Str::uuid();
            }
        });
    }

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    public function subject(): MorphTo
    {
        return $this->morphTo();
    }

    // Scopes
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByType($query, ActivityType $type)
    {
        return $query->where('type', $type);
    }

    public function scopeByStatus($query, string $status)
    {
        return $query->where('status', $status);
    }

    public function scopeScheduled($query)
    {
        return $query->where('status', 'scheduled')->whereNotNull('scheduled_at');
    }

    public function scopeCompleted($query)
    {
        return $query->where('status', 'completed');
    }

    public function scopeDueToday($query)
    {
        return $query->whereDate('due_date', today());
    }

    public function scopeOverdue($query)
    {
        return $query->where('due_date', '<', today())
            ->where('status', '!=', 'completed');
    }

    // Accessors
    public function getIsCompletedAttribute(): bool
    {
        return $this->status === 'completed';
    }

    public function getIsOverdueAttribute(): bool
    {
        return $this->due_date && $this->due_date->isPast() && !$this->is_completed;
    }

    public function getActualDurationAttribute(): ?int
    {
        if ($this->started_at && $this->completed_at) {
            return $this->started_at->diffInMinutes($this->completed_at);
        }
        return null;
    }

    // Methods
    public function start(): void
    {
        $this->update([
            'status' => 'in_progress',
            'started_at' => now(),
        ]);
    }

    public function complete(?string $outcome = null, ?string $notes = null): void
    {
        $this->update([
            'status' => 'completed',
            'completed_at' => now(),
            'outcome' => $outcome,
            'outcome_notes' => $notes,
            'duration_minutes' => $this->actual_duration,
        ]);
    }

    public function cancel(?string $reason = null): void
    {
        $this->update([
            'status' => 'cancelled',
            'outcome' => 'cancelled',
            'outcome_notes' => $reason,
        ]);
    }

    public function reschedule(\DateTime $newDateTime): void
    {
        $this->update([
            'scheduled_at' => $newDateTime,
            'status' => 'scheduled',
        ]);
    }
}