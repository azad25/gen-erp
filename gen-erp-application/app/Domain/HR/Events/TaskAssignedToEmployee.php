<?php

namespace App\Domain\HR\Events;

use App\Domain\HR\Models\EmployeeTask;
use Illuminate\Broadcasting\InteractsWithSockets;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a task is assigned to an employee
 */
class TaskAssignedToEmployee
{
    use Dispatchable, InteractsWithSockets, SerializesModels;

    public function __construct(
        public readonly EmployeeTask $employeeTask
    ) {}
}