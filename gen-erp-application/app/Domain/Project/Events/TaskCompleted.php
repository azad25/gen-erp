<?php

namespace App\Domain\Project\Events;

use App\Domain\Project\Models\Task;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a task is completed
 */
class TaskCompleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Task $task
    ) {}
}