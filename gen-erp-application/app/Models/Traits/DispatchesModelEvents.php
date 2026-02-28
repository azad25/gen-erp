<?php

namespace App\Models\Traits;

use App\Events\ModelSaved;

/**
 * Apply to any model that should trigger alert rule evaluation on save.
 * Phase 3 will apply this to Product, Customer, Invoice, etc.
 */
trait DispatchesModelEvents
{
    /**
     * Returns the entity_type string for alert rule matching.
     */
    abstract public function alertEntityType(): string;

    public static function bootDispatchesModelEvents(): void
    {
        static::saved(function (self $model): void {
            ModelSaved::dispatch($model->alertEntityType(), $model);
        });
    }
}
