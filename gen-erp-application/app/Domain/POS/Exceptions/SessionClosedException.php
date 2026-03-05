<?php

namespace App\Domain\POS\Exceptions;

use RuntimeException;

class SessionClosedException extends RuntimeException
{
    public function __construct(string $message = 'Cannot perform operation: POS session is closed.')
    {
        parent::__construct($message);
    }
}
