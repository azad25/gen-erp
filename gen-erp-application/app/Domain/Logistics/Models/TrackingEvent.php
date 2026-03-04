<?php

namespace App\Domain\Logistics\Models;

use App\Domain\Logistics\Enums\ShipmentStatus;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

class TrackingEvent extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\Domain\Logistics\TrackingEventFactory::new();
    }

    public $timestamps = false;

    protected $fillable = [
        'shipment_id',
        'status',
        'location',
        'description',
        'event_time',
        'carrier_status',
        'carrier_data',
        'created_at',
    ];

    protected $casts = [
        'event_time' => 'datetime',
        'carrier_data' => 'array',
        'created_at' => 'datetime',
        'status' => ShipmentStatus::class,
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($event) {
            $event->created_at = now();
        });
    }

    // Relationships
    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    // Scopes
    public function scopeLatest($query)
    {
        return $query->orderBy('event_time', 'desc');
    }

    public function scopeByStatus($query, ShipmentStatus $status)
    {
        return $query->where('status', $status);
    }

    // Methods
    public function getFormattedTime(): string
    {
        return $this->event_time->format('M d, Y h:i A');
    }

    public function getStatusColor(): string
    {
        return $this->status->color();
    }

    public function isDelivered(): bool
    {
        return $this->status === ShipmentStatus::DELIVERED;
    }

    public function isFailed(): bool
    {
        return $this->status === ShipmentStatus::FAILED;
    }
}