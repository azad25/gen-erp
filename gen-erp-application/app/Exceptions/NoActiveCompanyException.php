<?php

namespace App\Exceptions;

use RuntimeException;

/**
 * Thrown when an operation requires an active company but none is set.
 */
class NoActiveCompanyException extends RuntimeException
{
    public function __construct(string $message = 'No active company has been set for this request.')
    {
        parent::__construct($message);
    }
}
