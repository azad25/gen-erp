<?php

namespace App\Domain\Integration\Models;

use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\Concerns\BelongsToCompany;
use Database\Factories\Domain\Integration\CompanyIntegrationFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

/** Per-company installed integration instance with config and status. */
class CompanyIntegration extends Model
{
    use BelongsToCompany, HasFactory;

    protected $fillable = [
        'company_id',
        'integration_id',
        'config',
        'field_maps',
        'status',
        'last_sync_at',
        'last_error',
        'installed_at',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'config' => 'array',
            'field_maps' => 'array',
            'last_sync_at' => 'datetime',
            'installed_at' => 'datetime',
        ];
    }

    public function integration(): BelongsTo
    {
        return $this->belongsTo(Integration::class);
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function hooks(): HasMany
    {
        return $this->hasMany(IntegrationHook::class);
    }

    public function credentials(): HasMany
    {
        return $this->hasMany(IntegrationCredential::class);
    }

    public function devices(): HasMany
    {
        return $this->hasMany(IoTDevice::class);
    }

    public function logs(): HasMany
    {
        return $this->hasMany(IntegrationLog::class);
    }

    public function syncSchedules(): HasMany
    {
        return $this->hasMany(SyncSchedule::class);
    }

    public function webhooks(): HasMany
    {
        return $this->hasMany(InboundWebhook::class);
    }

    public function isActive(): bool
    {
        return $this->status === 'active';
    }

    public function isPaused(): bool
    {
        return $this->status === 'paused';
    }

    public function hasError(): bool
    {
        return $this->status === 'error';
    }

    protected static function newFactory(): CompanyIntegrationFactory
    {
        return CompanyIntegrationFactory::new();
    }
}
