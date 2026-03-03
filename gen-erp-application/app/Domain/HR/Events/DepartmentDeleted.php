<?php

namespace App\Domain\HR\Events;

use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a department is deleted.
 */
class DepartmentDeleted
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly array $departmentData
    ) {}
}