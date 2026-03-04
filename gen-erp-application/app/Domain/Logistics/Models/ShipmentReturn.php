<?php

namespace App\Domain\Logistics\Models;

use App\Domain\Auth\Models\Company;
use App\Domain\Auth\Models\User;
use App\Domain\Logistics\Enums\ReturnReason;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Support\Str;

class ShipmentReturn extends Model
{
    use HasFactory;

    protected static function newFactory()
    {
        return \Database\Factories\Domain\Logistics\ShipmentReturnFactory::new();
    }

    protected $fillable = [
        'uuid',
        'company_id',
        'shipment_id',
        'return_number',
        'reason',
        'reason_details',
        'status',
        'return_tracking_number',
        'return_carrier_id',
        'refund_amount',
        'refund_method',
        'refunded_at',
        'images',
        'requested_by',
        'approved_by',
        'requested_at',
        'approved_at',
    ];

    protected $casts = [
        'refund_amount' => 'decimal:2',
        'refunded_at' => 'datetime',
        'requested_at' => 'datetime',
        'approved_at' => 'datetime',
        'images' => 'array',
        'reason' => ReturnReason::class,
    ];

    protected static function boot()
    {
        parent::boot();
        
        static::creating(function ($return) {
            if (empty($return->uuid)) {
                $return->uuid = (string) Str::uuid();
            }
            if (empty($return->return_number)) {
                $return->return_number = 'RET-' . strtoupper(Str::random(8));
            }
            if (empty($return->requested_at)) {
                $return->requested_at = now();
            }
        });
    }

    // Relationships
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function shipment(): BelongsTo
    {
        return $this->belongsTo(Shipment::class);
    }

    public function returnCarrier(): BelongsTo
    {
        return $this->belongsTo(Carrier::class, 'return_carrier_id');
    }

    public function requestedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'requested_by');
    }

    public function approvedBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'approved_by');
    }

    // Scopes
    public function scopeForCompany($query, $companyId)
    {
        return $query->where('company_id', $companyId);
    }

    public function scopePending($query)
    {
        return $query->where('status', 'requested');
    }

    public function scopeApproved($query)
    {
        return $query->where('status', 'approved');
    }

    public function scopeCompleted($query)
    {
        return $query->whereIn('status', ['received', 'refunded']);
    }

    // Methods
    public function approve(User $user): void
    {
        $this->update([
            'status' => 'approved',
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);
    }

    public function reject(User $user): void
    {
        $this->update([
            'status' => 'rejected',
            'approved_by' => $user->id,
            'approved_at' => now(),
        ]);
    }

    public function processRefund(float $amount, string $method): void
    {
        $this->update([
            'status' => 'refunded',
            'refund_amount' => $amount,
            'refund_method' => $method,
            'refunded_at' => now(),
        ]);
    }

    public function isPending(): bool
    {
        return $this->status === 'requested';
    }

    public function isApproved(): bool
    {
        return $this->status === 'approved';
    }

    public function isCompleted(): bool
    {
        return in_array($this->status, ['received', 'refunded']);
    }

    public function requiresImages(): bool
    {
        return $this->reason->requiresImages();
    }

    public function shouldAutoApprove(): bool
    {
        return $this->reason->autoApprove();
    }
}