<?php

namespace App\Domain\HR\Events;

use App\Domain\HR\Models\EmployeeCapacity;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when employee capacity is updated
 */
class EmployeeCapacityUpdated
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly EmployeeCapacity $capacity
    ) {}
}