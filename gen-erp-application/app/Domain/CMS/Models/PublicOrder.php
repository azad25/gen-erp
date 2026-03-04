<?php

namespace App\Domain\CMS\Models;

use App\Domain\CMS\Models\Site;
use App\Domain\CMS\Models\CustomerAccount;
use App\Domain\CMS\Models\PublicOrderItem;
use App\Domain\CMS\Enums\OrderStatus;
use App\Domain\CMS\Enums\PaymentStatus;
use App\Domain\CMS\Enums\PaymentMethod;
use Database\Factories\Domain\CMS\PublicOrderFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

/**
 * Public order model for CMS e-commerce functionality.
 * 
 * @property int $id
 * @property int $site_id
 * @property int|null $customer_id
 * @property string $order_number
 * @property string $customer_email
 * @property string $customer_first_name
 * @property string $customer_last_name
 * @property string|null $customer_phone
 * @property string $billing_address_line_1
 * @property string|null $billing_address_line_2
 * @property string $billing_city
 * @property string $billing_state
 * @property string $billing_postal_code
 * @property string $billing_country
 * @property string $shipping_address_line_1
 * @property string|null $shipping_address_line_2
 * @property string $shipping_city
 * @property string $shipping_state
 * @property string $shipping_postal_code
 * @property string $shipping_country
 * @property float $subtotal
 * @property float $shipping_cost
 * @property float $tax_amount
 * @property float $discount_amount
 * @property float $total_amount
 * @property OrderStatus $status
 * @property PaymentStatus $payment_status
 * @property PaymentMethod $payment_method
 * @property string|null $customer_notes
 * @property string|null $admin_notes
 * @property string|null $tracking_number
 * @property Carbon $placed_at
 * @property Carbon|null $completed_at
 * @property Carbon|null $cancelled_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * 
 * @property-read Site $site
 * @property-read CustomerAccount|null $customer
 * @property-read \Illuminate\Database\Eloquent\Collection<PublicOrderItem> $items
 */
class PublicOrder extends Model
{
    use HasFactory;

    protected $table = 'cms_public_orders';

    protected $fillable = [
        'site_id',
        'customer_id',
        'order_number',
        'customer_email',
        'customer_first_name',
        'customer_last_name',
        'customer_phone',
        'billing_address_line_1',
        'billing_address_line_2',
        'billing_city',
        'billing_state',
        'billing_postal_code',
        'billing_country',
        'shipping_address_line_1',
        'shipping_address_line_2',
        'shipping_city',
        'shipping_state',
        'shipping_postal_code',
        'shipping_country',
        'subtotal',
        'shipping_cost',
        'tax_amount',
        'discount_amount',
        'total_amount',
        'status',
        'payment_status',
        'payment_method',
        'customer_notes',
        'admin_notes',
        'tracking_number',
        'placed_at',
        'completed_at',
        'cancelled_at',
    ];

    protected $casts = [
        'subtotal' => 'decimal:2',
        'shipping_cost' => 'decimal:2',
        'tax_amount' => 'decimal:2',
        'discount_amount' => 'decimal:2',
        'total_amount' => 'decimal:2',
        'status' => OrderStatus::class,
        'payment_status' => PaymentStatus::class,
        'payment_method' => PaymentMethod::class,
        'placed_at' => 'datetime',
        'completed_at' => 'datetime',
        'cancelled_at' => 'datetime',
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): PublicOrderFactory
    {
        return PublicOrderFactory::new();
    }

    /**
     * Get the site that owns this order.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Get the customer that placed this order (if any).
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerAccount::class);
    }

    /**
     * Get all items in this order.
     */
    public function items(): HasMany
    {
        return $this->hasMany(PublicOrderItem::class, 'order_id');
    }

    /**
     * Get the customer's full name.
     */
    public function getCustomerFullName(): string
    {
        return trim($this->customer_first_name . ' ' . $this->customer_last_name);
    }

    /**
     * Get the billing address as a formatted string.
     */
    public function getBillingAddress(): string
    {
        $address = $this->billing_address_line_1;
        
        if ($this->billing_address_line_2) {
            $address .= ', ' . $this->billing_address_line_2;
        }
        
        $address .= ', ' . $this->billing_city;
        $address .= ', ' . $this->billing_state;
        $address .= ' ' . $this->billing_postal_code;
        $address .= ', ' . $this->billing_country;
        
        return $address;
    }

    /**
     * Get the shipping address as a formatted string.
     */
    public function getShippingAddress(): string
    {
        $address = $this->shipping_address_line_1;
        
        if ($this->shipping_address_line_2) {
            $address .= ', ' . $this->shipping_address_line_2;
        }
        
        $address .= ', ' . $this->shipping_city;
        $address .= ', ' . $this->shipping_state;
        $address .= ' ' . $this->shipping_postal_code;
        $address .= ', ' . $this->shipping_country;
        
        return $address;
    }

    /**
     * Check if the order is pending.
     */
    public function isPending(): bool
    {
        return $this->status === OrderStatus::PENDING;
    }

    /**
     * Check if the order is processing.
     */
    public function isProcessing(): bool
    {
        return $this->status === OrderStatus::PROCESSING;
    }

    /**
     * Check if the order is completed.
     */
    public function isCompleted(): bool
    {
        return $this->status === OrderStatus::COMPLETED;
    }

    /**
     * Check if the order is cancelled.
     */
    public function isCancelled(): bool
    {
        return $this->status === OrderStatus::CANCELLED;
    }

    /**
     * Check if payment is pending.
     */
    public function isPaymentPending(): bool
    {
        return $this->payment_status === PaymentStatus::PENDING;
    }

    /**
     * Check if payment is completed.
     */
    public function isPaymentCompleted(): bool
    {
        return $this->payment_status === PaymentStatus::PAID;
    }

    /**
     * Get the total number of items in the order.
     */
    public function getItemCount(): int
    {
        return $this->items()->sum('quantity');
    }

    /**
     * Mark the order as processing.
     */
    public function markAsProcessing(): void
    {
        $this->status = OrderStatus::PROCESSING;
        $this->save();
    }

    /**
     * Mark the order as completed.
     */
    public function markAsCompleted(): void
    {
        $this->status = OrderStatus::COMPLETED;
        $this->completed_at = now();
        $this->save();
    }

    /**
     * Mark the order as cancelled.
     */
    public function markAsCancelled(): void
    {
        $this->status = OrderStatus::CANCELLED;
        $this->cancelled_at = now();
        $this->save();
    }

    /**
     * Mark payment as completed.
     */
    public function markPaymentAsCompleted(): void
    {
        $this->payment_status = PaymentStatus::PAID;
        $this->save();
    }

    /**
     * Set tracking number.
     */
    public function setTrackingNumber(string $trackingNumber): void
    {
        $this->tracking_number = $trackingNumber;
        $this->save();
    }

    /**
     * Scope to get orders by status.
     */
    public function scopeByStatus($query, OrderStatus $status)
    {
        return $query->where('status', $status);
    }

    /**
     * Scope to get orders by payment status.
     */
    public function scopeByPaymentStatus($query, PaymentStatus $paymentStatus)
    {
        return $query->where('payment_status', $paymentStatus);
    }

    /**
     * Scope to get orders by customer email.
     */
    public function scopeByCustomerEmail($query, string $email)
    {
        return $query->where('customer_email', $email);
    }

    /**
     * Scope to get orders by order number.
     */
    public function scopeByOrderNumber($query, string $orderNumber)
    {
        return $query->where('order_number', $orderNumber);
    }

    /**
     * Scope to get recent orders.
     */
    public function scopeRecent($query, int $days = 30)
    {
        return $query->where('placed_at', '>=', now()->subDays($days));
    }
}