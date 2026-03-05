<?php

namespace App\Domain\Calendar\Models;

use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\User;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

class Calendar extends Model
{
    use HasFactory, SoftDeletes;

    protected $fillable = [
        'company_id',
        'user_id',
        'team_id',
        'name',
        'description',
        'type',
        'color',
        'is_default',
        'is_public',
        'timezone',
    ];

    protected $casts = [
        'is_default' => 'boolean',
        'is_public' => 'boolean',
    ];

    /**
     * Calendar types: personal, team, company, resource
     */
    const TYPE_PERSONAL = 'personal';
    const TYPE_TEAM = 'team';
    const TYPE_COMPANY = 'company';
    const TYPE_RESOURCE = 'resource';

    /**
     * Get the company that owns the calendar.
     */
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /**
     * Get the user that owns the calendar (for personal calendars).
     */
    public function user(): BelongsTo
    {
        return $this->belongsTo(User::class);
    }

    /**
     * Get all events for this calendar.
     */
    public function events(): HasMany
    {
        return $this->hasMany(CalendarEvent::class);
    }

    /**
     * Get events for a specific date range.
     */
    public function eventsInRange($startDate, $endDate)
    {
        return $this->events()
            ->where(function ($query) use ($startDate, $endDate) {
                $query->whereBetween('start_at', [$startDate, $endDate])
                    ->orWhereBetween('end_at', [$startDate, $endDate])
                    ->orWhere(function ($q) use ($startDate, $endDate) {
                        $q->where('start_at', '<=', $startDate)
                          ->where('end_at', '>=', $endDate);
                    });
            })
            ->orderBy('start_at')
            ->get();
    }

    /**
     * Scope to get user's calendars.
     */
    public function scopeForUser($query, $userId)
    {
        return $query->where('user_id', $userId);
    }

    /**
     * Scope to get company calendars.
     */
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId)
            ->where('type', self::TYPE_COMPANY);
    }

    /**
     * Scope to get team calendars.
     */
    public function scopeForTeam($query, $teamId)
    {
        return $query->where('team_id', $teamId)
            ->where('type', self::TYPE_TEAM);
    }

    /**
     * Check if calendar is personal.
     */
    public function isPersonal(): bool
    {
        return $this->type === self::TYPE_PERSONAL;
    }

    /**
     * Check if calendar is team calendar.
     */
    public function isTeam(): bool
    {
        return $this->type === self::TYPE_TEAM;
    }

    /**
     * Check if calendar is company calendar.
     */
    public function isCompany(): bool
    {
        return $this->type === self::TYPE_COMPANY;
    }
}
