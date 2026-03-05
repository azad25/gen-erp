<?php

namespace App\Domain\POS\Exceptions;

use RuntimeException;

class InvalidPOSSaleException extends RuntimeException
{
    public function __construct(string $message = 'Invalid POS sale data.')
    {
        parent::__construct($message);
    }
}
