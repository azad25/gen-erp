<?php

namespace App\Events;

use Illuminate\Database\Eloquent\Model;
use Illuminate\Foundation\Events\Dispatchable;

/**
 * Fired when a model that uses DispatchesModelEvents is saved.
 */
class ModelSaved
{
    use Dispatchable;

    public function __construct(
        public readonly string $entityType,
        public readonly Model $entity,
    ) {}
}
