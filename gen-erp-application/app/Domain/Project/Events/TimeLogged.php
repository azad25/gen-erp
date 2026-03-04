<?php

namespace App\Domain\Project\Events;

use App\Domain\Project\Models\TimeEntry;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when time is logged for a project/task
 */
class TimeLogged
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public TimeEntry $timeEntry
    ) {}
}