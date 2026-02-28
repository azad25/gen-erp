<?php

namespace App\Models;

use App\Enums\SalesOrderStatus;
use App\Models\Traits\BelongsToCompany;
use App\Models\Traits\DispatchesModelEvents;
use App\Models\Traits\HasCustomFields;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\SoftDeletes;

/**
 * Sales order document — the starting point of the sales flow.
 */
class SalesOrder extends Model
{
    use BelongsToCompany;
    use DispatchesModelEvents;
    use HasCustomFields;
    use HasFactory;
    use SoftDeletes;

    protected $fillable = [
        'company_id',
        'branch_id',
        'customer_id',
        'warehouse_id',
        'reference_number',
        'customer_reference',
        'order_date',
        'delivery_date',
        'status',
        'subtotal',
        'discount_amount',
        'tax_amount',
        'shipping_amount',
        'total_amount',
        'notes',
        'terms_conditions',
        'custom_fields',
        'created_by',
    ];

    /**
     * @return array<string, mixed>
     */
    protected function casts(): array
    {
        return [
            'status' => SalesOrderStatus::class,
            'order_date' => 'date',
            'delivery_date' => 'date',
            'subtotal' => 'integer',
            'discount_amount' => 'integer',
            'tax_amount' => 'integer',
            'shipping_amount' => 'integer',
            'total_amount' => 'integer',
            'custom_fields' => 'array',
        ];
    }

    protected static function booted(): void
    {
        static::creating(function (SalesOrder $order): void {
            if ($order->reference_number === null) {
                $date = now()->format('Ymd');
                $seq = static::withoutGlobalScopes()
                    ->where('company_id', $order->company_id)
                    ->count() + 1;
                $order->reference_number = "SO-{$date}-".str_pad((string) $seq, 4, '0', STR_PAD_LEFT);
            }
        });
    }

    public function customFieldEntityType(): string
    {
        return 'sales_order';
    }

    public function alertEntityType(): string
    {
        return 'sales_order';
    }

    public function workflowDocumentType(): string
    {
        return 'sales_order';
    }

    /**
     * @return BelongsTo<Customer, $this>
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(Customer::class);
    }

    /**
     * @return BelongsTo<Warehouse, $this>
     */
    public function warehouse(): BelongsTo
    {
        return $this->belongsTo(Warehouse::class);
    }

    /**
     * @return BelongsTo<User, $this>
     */
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by');
    }

    /**
     * @return HasMany<SalesOrderItem, $this>
     */
    public function items(): HasMany
    {
        return $this->hasMany(SalesOrderItem::class);
    }

    /**
     * @return HasMany<Invoice, $this>
     */
    public function invoices(): HasMany
    {
        return $this->hasMany(Invoice::class);
    }

    public function totalItems(): int
    {
        return $this->items()->count();
    }
}
