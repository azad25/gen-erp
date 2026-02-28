<?php

namespace App\Models;

use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** Scheduled sync configuration for periodic data synchronisation. */
class SyncSchedule extends Model
{
    use BelongsToCompany;

    protected $fillable = [
        'company_id',
        'company_integration_id',
        'entity_type',
        'direction',
        'frequency',
        'last_run_at',
        'next_run_at',
        'last_cursor',
        'is_active',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'last_run_at' => 'datetime',
            'next_run_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function companyIntegration(): BelongsTo
    {
        return $this->belongsTo(CompanyIntegration::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    /** Check if this schedule is due to run. */
    public function isDue(): bool
    {
        return $this->is_active
            && $this->next_run_at !== null
            && $this->next_run_at->isPast();
    }

    /** Calculate the next run time based on frequency. */
    public function calculateNextRunAt(): \Carbon\Carbon
    {
        return match ($this->frequency) {
            'realtime' => now(),
            'every_5min' => now()->addMinutes(5),
            'every_15min' => now()->addMinutes(15),
            'hourly' => now()->addHour(),
            'daily' => now()->addDay(),
            'weekly' => now()->addWeek(),
            default => now()->addHour(),
        };
    }
}
