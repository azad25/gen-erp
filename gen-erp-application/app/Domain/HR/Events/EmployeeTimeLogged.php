<?php

namespace App\Domain\HR\Events;

use App\Domain\HR\Models\EmployeeTimeEntry;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when an employee logs time
 */
class EmployeeTimeLogged
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly EmployeeTimeEntry $timeEntry
    ) {}
}