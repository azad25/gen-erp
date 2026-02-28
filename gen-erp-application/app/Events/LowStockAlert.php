<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Broadcast when stock falls below threshold. */
class LowStockAlert implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly int $companyId,
        public readonly int $branchId,
        public readonly int $productId,
        public readonly string $productName,
        public readonly int $currentQty,
        public readonly int $threshold,
    ) {}

    /** @return array<Channel> */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("company.{$this->companyId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'stock.low';
    }
}
