<?php

namespace App\Domain\POS\Exceptions;

use RuntimeException;

class SessionAlreadyOpenException extends RuntimeException
{
    public function __construct(string $message = 'Branch already has an open POS session.')
    {
        parent::__construct($message);
    }
}
