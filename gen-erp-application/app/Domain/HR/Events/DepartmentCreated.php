<?php

namespace App\Domain\HR\Events;

use App\Domain\HR\Models\Department;
use Illuminate\Foundation\Events\Dispatchable;
use Illuminate\Queue\SerializesModels;

/**
 * Event fired when a department is created.
 */
class DepartmentCreated
{
    use Dispatchable, SerializesModels;

    public function __construct(
        public readonly Department $department
    ) {}
}