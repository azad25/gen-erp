<?php

namespace App\Domain\CMS\Models;

use App\Domain\CMS\Models\Site;
use App\Domain\CMS\Models\CustomerAccount;
use App\Domain\CMS\Models\PublicOrder;
use Database\Factories\Domain\CMS\ProductReviewFactory;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Carbon\Carbon;

/**
 * Product review model for CMS e-commerce functionality.
 * 
 * @property int $id
 * @property int $site_id
 * @property int $product_id
 * @property int|null $customer_id
 * @property int|null $order_id
 * @property int $rating
 * @property string|null $title
 * @property string|null $review
 * @property string $customer_name
 * @property string $customer_email
 * @property bool $is_verified_purchase
 * @property bool $is_approved
 * @property int $helpful_count
 * @property Carbon $created_at
 * @property Carbon $updated_at
 * 
 * @property-read Site $site
 * @property-read CustomerAccount|null $customer
 * @property-read PublicOrder|null $order
 */
class ProductReview extends Model
{
    use HasFactory;

    protected $table = 'cms_product_reviews';

    protected $fillable = [
        'site_id',
        'product_id',
        'customer_id',
        'order_id',
        'rating',
        'title',
        'review',
        'customer_name',
        'customer_email',
        'is_verified_purchase',
        'is_approved',
        'helpful_count',
    ];

    protected $casts = [
        'rating' => 'integer',
        'is_verified_purchase' => 'boolean',
        'is_approved' => 'boolean',
        'helpful_count' => 'integer',
    ];

    /**
     * Create a new factory instance for the model.
     */
    protected static function newFactory(): ProductReviewFactory
    {
        return ProductReviewFactory::new();
    }

    /**
     * Get the site that owns this review.
     */
    public function site(): BelongsTo
    {
        return $this->belongsTo(Site::class);
    }

    /**
     * Get the customer who wrote this review (if any).
     */
    public function customer(): BelongsTo
    {
        return $this->belongsTo(CustomerAccount::class);
    }

    /**
     * Get the order associated with this review (if any).
     */
    public function order(): BelongsTo
    {
        return $this->belongsTo(PublicOrder::class);
    }

    /**
     * Get the product being reviewed.
     * Note: This will reference the products table when it exists.
     */
    public function product()
    {
        // This will be implemented when products table exists
        // return $this->belongsTo(Product::class);
        return null;
    }

    /**
     * Check if this is a verified purchase review.
     */
    public function isVerifiedPurchase(): bool
    {
        return $this->is_verified_purchase && !is_null($this->order_id);
    }

    /**
     * Check if this review is approved.
     */
    public function isApproved(): bool
    {
        return $this->is_approved;
    }

    /**
     * Check if this review is pending approval.
     */
    public function isPending(): bool
    {
        return !$this->is_approved;
    }

    /**
     * Get the star rating as a formatted string.
     */
    public function getStarRating(): string
    {
        return str_repeat('★', $this->rating) . str_repeat('☆', 5 - $this->rating);
    }

    /**
     * Get the rating percentage (for progress bars).
     */
    public function getRatingPercentage(): int
    {
        return ($this->rating / 5) * 100;
    }

    /**
     * Approve this review.
     */
    public function approve(): void
    {
        $this->update(['is_approved' => true]);
    }

    /**
     * Reject/unapprove this review.
     */
    public function reject(): void
    {
        $this->update(['is_approved' => false]);
    }

    /**
     * Increment the helpful count.
     */
    public function markHelpful(): void
    {
        $this->increment('helpful_count');
    }

    /**
     * Scope to get approved reviews.
     */
    public function scopeApproved($query)
    {
        return $query->where('is_approved', true);
    }

    /**
     * Scope to get pending reviews.
     */
    public function scopePending($query)
    {
        return $query->where('is_approved', false);
    }

    /**
     * Scope to get verified purchase reviews.
     */
    public function scopeVerifiedPurchase($query)
    {
        return $query->where('is_verified_purchase', true);
    }

    /**
     * Scope to get reviews by rating.
     */
    public function scopeByRating($query, int $rating)
    {
        return $query->where('rating', $rating);
    }

    /**
     * Scope to get reviews for a specific product.
     */
    public function scopeForProduct($query, int $productId)
    {
        return $query->where('product_id', $productId);
    }

    /**
     * Scope to get reviews by customer.
     */
    public function scopeByCustomer($query, int $customerId)
    {
        return $query->where('customer_id', $customerId);
    }

    /**
     * Scope to order by most helpful.
     */
    public function scopeOrderByHelpful($query)
    {
        return $query->orderBy('helpful_count', 'desc');
    }

    /**
     * Scope to order by highest rating.
     */
    public function scopeOrderByRating($query)
    {
        return $query->orderBy('rating', 'desc');
    }

    /**
     * Scope to order by newest.
     */
    public function scopeOrderByNewest($query)
    {
        return $query->orderBy('created_at', 'desc');
    }
}