<?php

namespace App\Domain\CMS\Events;

use App\Domain\CMS\Models\Wishlist;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when an item is added to wishlist.
 */
class ItemAddedToWishlist
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Wishlist $wishlistItem
    ) {}
}