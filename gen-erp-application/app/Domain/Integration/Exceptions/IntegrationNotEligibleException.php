<?php

namespace App\Domain\Integration\Exceptions;

use Exception;

class IntegrationNotEligibleException extends Exception
{
    public function __construct(string $message = 'Your current plan does not support this integration.')
    {
        parent::__construct($message);
    }
}
