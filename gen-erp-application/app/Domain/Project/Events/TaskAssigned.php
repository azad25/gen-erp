<?php

namespace App\Domain\Project\Events;

use App\Domain\Project\Models\Task;
use App\Domain\Auth\Models\User;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a task is assigned to a user
 */
class TaskAssigned
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Task $task,
        public ?User $assignedBy = null
    ) {}
}