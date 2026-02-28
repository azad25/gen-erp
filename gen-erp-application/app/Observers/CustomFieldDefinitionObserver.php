<?php

namespace App\Observers;

use App\Models\CustomFieldDefinition;
use Illuminate\Support\Facades\Cache;

/**
 * Invalidates the custom field definition cache whenever a definition changes.
 */
class CustomFieldDefinitionObserver
{
    public function created(CustomFieldDefinition $definition): void
    {
        $this->clearCache($definition);
    }

    public function updated(CustomFieldDefinition $definition): void
    {
        $this->clearCache($definition);
    }

    public function deleted(CustomFieldDefinition $definition): void
    {
        $this->clearCache($definition);
    }

    private function clearCache(CustomFieldDefinition $definition): void
    {
        Cache::forget("custom_fields:{$definition->company_id}:{$definition->entity_type}");
    }
}
