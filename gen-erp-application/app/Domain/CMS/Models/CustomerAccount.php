<?php

namespace App\Domain\CMS\Models;

use App\Domain\CMS\Models\Site;
use App\Domain\CMS\Models\ShoppingCart;
use App\Domain\CMS\Models\PublicOrder;
use Database\Factories\Domain\CMS\CustomerAccountFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Foundation\Auth\User as Authenticatable;
use Illuminate\Notifications\Notifiable;
use Carbon\Carbon;

/**
 * Customer account model for CMS e-commerce functionality.
 * 
 * @property int $id
 * @property int $site_id
 * @property string $email
 * @property string|null $password
 * @property string $first_name
 * @property string $last_name
 * @property string|null $phone
 * @property bool $is_guest
 * @property Carbon|null $email_verified_at
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * 
 * @property-read Site $site
 * @property-read \Illuminate\Database\Eloquent\Collection<ShoppingCart> $carts
 * @property-read \Illuminate\Database\Eloquent\Collection<PublicOrder> $orders
 */
class CustomerAccount extends Authenticatable
{
    use HasFactory, Notifiable;

    protected $table = 'cms_customer_accounts';

    protected $fillable = [
        'site_id',
        'email',
        'password',
        'first_name',
        'last_name',
        'phone',
        'is_guest',
        'email_verified_at',
    ];

    protected $hidden = [
        'password',
    ];

    protected $casts = [
        'is_guest' => 'boolean',
        'email_verified_at' => 'datetime',
        'password' => 'hashed',
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): CustomerAccountFactory
    {
        return CustomerAccountFactory::new();
    }

    /**
     * Get the site that owns this customer account.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Get all shopping carts for this customer.
     */
    public function carts(): HasMany
    {
        return $this->hasMany(ShoppingCart::class, 'customer_id');
    }

    /**
     * Get all orders for this customer.
     */
    public function orders(): HasMany
    {
        return $this->hasMany(PublicOrder::class, 'customer_id');
    }

    /**
     * Get the customer's full name.
     */
    public function getFullName(): string
    {
        return trim($this->first_name . ' ' . $this->last_name);
    }

    /**
     * Get the customer's active cart.
     */
    public function getActiveCart(): ?ShoppingCart
    {
        return $this->carts()->active()->first();
    }

    /**
     * Create or get the customer's cart.
     */
    public function getOrCreateCart(): ShoppingCart
    {
        $cart = $this->getActiveCart();
        
        if (!$cart) {
            $cart = $this->carts()->create([
                'site_id' => $this->site_id,
            ]);
        }
        
        return $cart;
    }

    /**
     * Check if the customer is a guest (no password).
     */
    public function isGuest(): bool
    {
        return $this->is_guest || empty($this->password);
    }

    /**
     * Check if the customer's email is verified.
     */
    public function hasVerifiedEmail(): bool
    {
        return !is_null($this->email_verified_at);
    }

    /**
     * Get the total number of orders for this customer.
     */
    public function getOrderCount(): int
    {
        return $this->orders()->count();
    }

    /**
     * Get the total amount spent by this customer.
     */
    public function getTotalSpent(): float
    {
        return $this->orders()
                   ->where('status', '!=', 'cancelled')
                   ->sum('total_amount');
    }

    /**
     * Scope to get registered customers (non-guests).
     */
    public function scopeRegistered($query)
    {
        return $query->where('is_guest', false)
                    ->whereNotNull('password');
    }

    /**
     * Scope to get guest customers.
     */
    public function scopeGuests($query)
    {
        return $query->where('is_guest', true)
                    ->orWhereNull('password');
    }

    /**
     * Scope to get verified customers.
     */
    public function scopeVerified($query)
    {
        return $query->whereNotNull('email_verified_at');
    }

    /**
     * Scope to get customers by email.
     */
    public function scopeByEmail($query, string $email)
    {
        return $query->where('email', $email);
    }
}