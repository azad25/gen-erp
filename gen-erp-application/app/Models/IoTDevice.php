<?php

namespace App\Models;

use App\Enums\DeviceType;
use App\Models\Traits\BelongsToCompany;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

/** IoT device registry — hardware devices connected per branch. */
class IoTDevice extends Model
{
    use BelongsToCompany;

    protected $table = 'iot_devices';

    protected $fillable = [
        'company_id',
        'branch_id',
        'company_integration_id',
        'name',
        'device_type',
        'driver_class',
        'connection_type',
        'config',
        'last_sync_at',
        'last_ping_at',
        'status',
        'is_active',
    ];

    /** @return array<string, string> */
    protected function casts(): array
    {
        return [
            'device_type' => DeviceType::class,
            'config' => 'array',
            'last_sync_at' => 'datetime',
            'last_ping_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function branch(): BelongsTo
    {
        return $this->belongsTo(Branch::class);
    }

    public function companyIntegration(): BelongsTo
    {
        return $this->belongsTo(CompanyIntegration::class);
    }

    public function isOnline(): bool
    {
        return $this->status === 'online';
    }

    public function isOffline(): bool
    {
        return $this->status === 'offline';
    }

    public function markOnline(): void
    {
        $this->update(['status' => 'online', 'last_ping_at' => now()]);
    }

    public function markOffline(): void
    {
        $this->update(['status' => 'offline']);
    }

    public function markError(): void
    {
        $this->update(['status' => 'error']);
    }

    public function markSyncing(): void
    {
        $this->update(['status' => 'syncing']);
    }
}
