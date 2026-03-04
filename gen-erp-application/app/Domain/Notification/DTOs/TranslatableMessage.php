<?php

namespace App\Domain\Notification\DTOs;

class TranslatableMessage
{
    public function __construct(
        public readonly string $key,
        public readonly array  $params = [],
    ) {}

    public function translate(string $locale): string
    {
        return __($this->key, $this->params, $locale);
    }
}