<?php

namespace App\Events;

use Illuminate\Broadcasting\Channel;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Broadcasting\PrivateChannel;
use Illuminate\Contracts\Broadcasting\ShouldBroadcast;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/** Broadcast when a POS sale is completed. */
class POSSaleCompleted implements ShouldBroadcast
{
    use Dispatchable;
    use InteractsWithSockets;
    use SerializesModels;

    public function __construct(
        public readonly int $companyId,
        public readonly int $branchId,
        public readonly int $saleId,
        public readonly string $saleNumber,
        public readonly int $totalAmount,
        public readonly string $paymentMethod,
    ) {}

    /** @return array<Channel> */
    public function broadcastOn(): array
    {
        return [
            new PrivateChannel("company.{$this->companyId}"),
            new PrivateChannel("branch.{$this->branchId}"),
        ];
    }

    public function broadcastAs(): string
    {
        return 'pos.sale.completed';
    }
}
