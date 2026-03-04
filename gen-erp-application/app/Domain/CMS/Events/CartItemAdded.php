<?php

namespace App\Domain\CMS\Events;

use App\Domain\CMS\Models\CartItem;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when an item is added to a cart.
 */
class CartItemAdded
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly CartItem $cartItem
    ) {}
}