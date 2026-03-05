<?php

namespace App\Domain\POS\DTOs;

readonly class CloseSessionData
{
    public function __construct(
        public int $sessionId,
        public int $closedBy,
        public int $closingCash,
        public ?string $notes = null,
    ) {}
}
