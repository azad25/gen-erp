<?php

namespace App\Domain\Logistics\Models;

use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\User;
use App\Domain\Logistics\Enums\DeliveryType;
use App\Domain\Logistics\Enums\ShipmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Support\Str;

class Shipment extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\Domain\Logistics\ShipmentFactory::new();
    }

    protected $fillable = [
        'uuid',
        'company_id',
        'carrier_id',
        'invoice_id',
        'customer_id',
        'tracking_number',
        'carrier_tracking_number',
        'sender_name',
        'sender_phone',
        'sender_address',
        'sender_city',
        'sender_area',
        'sender_postcode',
        'recipient_name',
        'recipient_phone',
        'recipient_email',
        'recipient_address',
        'recipient_city',
        'recipient_area',
        'recipient_postcode',
        'status',
        'delivery_type',
        'payment_method',
        'cod_amount',
        'shipping_cost',
        'cod_charge',
        'total_cost',
        'cod_collected_amount',
        'cod_collected_at',
        'cod_settled_at',
        'cod_status',
        'weight',
        'length',
        'width',
        'height',
        'pickup_date',
        'expected_delivery_date',
        'actual_delivery_date',
        'special_instructions',
        'package_description',
        'carrier_response',
        'created_by',
    ];

    protected $casts = [
        'cod_amount' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'cod_charge' => 'decimal:2',
        'total_cost' => 'decimal:2',
        'cod_collected_amount' => 'decimal:2',
        'weight' => 'decimal:2',
        'length' => 'decimal:2',
        'width' => 'decimal:2',
        'height' => 'decimal:2',
        'pickup_date' => 'date',
        'expected_delivery_date' => 'date',
        'actual_delivery_date' => 'datetime',
        'cod_collected_at' => 'datetime',
        'cod_settled_at' => 'datetime',
        'carrier_response' => 'array',
        'status' => ShipmentStatus::class,
        'delivery_type' => DeliveryType::class,
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($shipment) {
            if (empty($shipment->uuid)) {
                $shipment->uuid = (string) Str::uuid();
            }
            if (empty($shipment->tracking_number)) {
                $shipment->tracking_number = 'SHP-' . strtoupper(Str::random(8));
            }
        });
    }

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function carrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class);
    }

    public function customer(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Customer\Models\Customer::class);
    }

    public function invoice(): BelongsTo
    {
        return $this->belongsTo(\App\Domain\Invoice\Models\Invoice::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    public function items(): HasMany
    {
        return $this->hasMany(ShipmentItem::class);
    }

    public function trackingEvents(): HasMany
    {
        return $this->hasMany(TrackingEvent::class)->orderBy('event_time', 'desc');
    }

    public function returns(): HasMany
    {
        return $this->hasMany(ShipmentReturn::class);
    }

    // Scopes
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopeByStatus($query, ShipmentStatus $status)
    {
        return $query->where('status', $status);
    }

    public function scopePending($query)
    {
        return $query->where('status', ShipmentStatus::PENDING);
    }

    public function scopeInTransit($query)
    {
        return $query->whereIn('status', [
            ShipmentStatus::PICKED_UP,
            ShipmentStatus::IN_TRANSIT,
            ShipmentStatus::OUT_FOR_DELIVERY,
        ]);
    }

    public function scopeCompleted($query)
    {
        return $query->whereIn('status', [
            ShipmentStatus::DELIVERED,
            ShipmentStatus::RETURNED,
            ShipmentStatus::CANCELLED,
        ]);
    }

    public function scopeCOD($query)
    {
        return $query->where('payment_method', 'cod');
    }

    // Methods
    public function updateStatus(ShipmentStatus $status, string $location = null, string $description = null): void
    {
        $this->update(['status' => $status]);
        
        $this->trackingEvents()->create([
            'status' => $status->value,
            'location' => $location,
            'description' => $description ?? $status->label(),
            'event_time' => now(),
        ]);
    }

    public function getTotalWeight(): float
    {
        return $this->weight ?? $this->items->sum(function ($item) {
            return $item->quantity * ($item->productVariant?->weight ?? 0.5); // default 0.5kg
        });
    }

    public function getTotalValue(): float
    {
        return $this->items->sum('total_price');
    }

    public function isCOD(): bool
    {
        return $this->payment_method === 'cod';
    }

    public function isDelivered(): bool
    {
        return $this->status === ShipmentStatus::DELIVERED;
    }

    public function canBeCancelled(): bool
    {
        return in_array($this->status, [ShipmentStatus::PENDING, ShipmentStatus::PICKED_UP]);
    }

    public function canBeReturned(): bool
    {
        return $this->status === ShipmentStatus::DELIVERED;
    }

    public function getPublicTrackingUrl(): string
    {
        return route('public.tracking', $this->tracking_number);
    }
}