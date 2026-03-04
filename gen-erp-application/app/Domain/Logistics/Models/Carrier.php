<?php

namespace App\Domain\Logistics\Models;

use App\Domain\Auth\Models\Company;
use App\Domain\Logistics\Enums\CarrierType;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

class Carrier extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\Domain\Logistics\CarrierFactory::new();
    }

    protected $fillable = [
        'company_id',
        'name',
        'code',
        'api_endpoint',
        'api_key',
        'api_secret',
        'is_active',
        'supports_cod',
        'supports_tracking',
        'base_rate',
        'per_kg_rate',
        'cod_charge_percentage',
        'settings',
    ];

    protected $casts = [
        'is_active' => 'boolean',
        'supports_cod' => 'boolean',
        'supports_tracking' => 'boolean',
        'base_rate' => 'decimal:2',
        'per_kg_rate' => 'decimal:2',
        'cod_charge_percentage' => 'decimal:2',
        'settings' => 'array',
        'code' => CarrierType::class,
    ];

    protected $hidden = [
        'api_key',
        'api_secret',
    ];

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function shipments(): HasMany
    {
        return $this->hasMany(Shipment::class);
    }

    public function returnShipments(): HasMany
    {
        return $this->hasMany(ShipmentReturn::class, 'return_carrier_id');
    }

    // Scopes
    public function scopeActive($query)
    {
        return $query->where('is_active', true);
    }

    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeSupportsCOD($query)
    {
        return $query->where('supports_cod', true);
    }

    public function scopeSupportsTracking($query)
    {
        return $query->where('supports_tracking', true);
    }

    // Methods
    public function calculateShippingCost(float $weight, bool $isCOD = false): float
    {
        $cost = $this->base_rate + ($weight * $this->per_kg_rate);
        
        if ($isCOD && $this->supports_cod) {
            $cost += ($cost * $this->cod_charge_percentage / 100);
        }
        
        return round($cost, 2);
    }

    public function getApiInstance()
    {
        $class = $this->code->apiClass();
        return new $class($this);
    }

    public function isConfigured(): bool
    {
        return !empty($this->api_key) && !empty($this->api_endpoint);
    }
}