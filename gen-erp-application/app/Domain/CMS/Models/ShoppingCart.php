<?php

namespace App\Domain\CMS\Models;

use App\Domain\CMS\Models\Site;
use App\Domain\CMS\Models\CustomerAccount;
use App\Domain\CMS\Models\CartItem;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Carbon\Carbon;

/**
 * Shopping cart model for CMS e-commerce functionality.
 * 
 * @property int $id
 * @property int $site_id
 * @property string|null $session_id
 * @property int|null $customer_id
 * @property Carbon|null $expires_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * 
 * @property-read Site $site
 * @property-read CustomerAccount|null $customer
 * @property-read \Illuminate\Database\Eloquent\Collection<CartItem> $items
 */
class ShoppingCart extends Model
{
    use HasFactory;

    protected $table = 'cms_shopping_carts';

    protected $fillable = [
        'site_id',
        'session_id',
        'customer_id',
        'expires_at',
    ];

    protected $casts = [
        'expires_at' => 'datetime',
    ];

    /**
     * Get the site that owns this cart.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Get the customer that owns this cart (if any).
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerAccount::class);
    }

    /**
     * Get all items in this cart.
     */
    public function items(): HasMany
    {
        return $this->hasMany(CartItem::class, 'cart_id');
    }

    /**
     * Check if the cart is expired.
     */
    public function isExpired(): bool
    {
        return $this->expires_at && $this->expires_at->isPast();
    }

    /**
     * Check if the cart is empty.
     */
    public function isEmpty(): bool
    {
        return $this->items()->count() === 0;
    }

    /**
     * Get the total number of items in the cart.
     */
    public function getItemCount(): int
    {
        return $this->items()->sum('quantity');
    }

    /**
     * Get the cart subtotal (sum of all item totals).
     */
    public function getSubtotal(): float
    {
        return $this->items()->get()->sum(function (CartItem $item) {
            return $item->getTotal();
        });
    }

    /**
     * Get cart totals breakdown.
     */
    public function getTotals(): array
    {
        $subtotal = $this->getSubtotal();
        $taxAmount = 0; // TODO: Implement tax calculation
        $shippingCost = 0; // TODO: Implement shipping calculation
        $discountAmount = 0; // TODO: Implement discount calculation
        
        return [
            'subtotal' => $subtotal,
            'tax_amount' => $taxAmount,
            'shipping_cost' => $shippingCost,
            'discount_amount' => $discountAmount,
            'total' => $subtotal + $taxAmount + $shippingCost - $discountAmount,
        ];
    }

    /**
     * Clear all items from the cart.
     */
    public function clear(): void
    {
        $this->items()->delete();
    }

    /**
     * Set expiration time for guest carts.
     */
    public function setExpiration(int $hours = 24): void
    {
        $this->expires_at = now()->addHours($hours);
        $this->save();
    }

    /**
     * Scope to get active (non-expired) carts.
     */
    public function scopeActive($query)
    {
        return $query->where(function ($q) {
            $q->whereNull('expires_at')
              ->orWhere('expires_at', '>', now());
        });
    }

    /**
     * Scope to get expired carts.
     */
    public function scopeExpired($query)
    {
        return $query->where('expires_at', '<=', now());
    }

    /**
     * Scope to get carts by session ID.
     */
    public function scopeBySession($query, string $sessionId)
    {
        return $query->where('session_id', $sessionId);
    }

    /**
     * Scope to get carts by customer.
     */
    public function scopeByCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }
}