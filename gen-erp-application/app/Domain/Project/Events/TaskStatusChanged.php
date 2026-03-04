<?php

namespace App\Domain\Project\Events;

use App\Domain\Project\Models\Task;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a task status changes
 */
class TaskStatusChanged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public Task $task,
        public string $oldStatus,
        public string $newStatus
    ) {}
}