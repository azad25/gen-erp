<?php

namespace App\Observers;

use App\Models\EntityAlias;
use Illuminate\Support\Facades\Cache;

/**
 * Invalidates the entity alias cache whenever an alias is changed.
 */
class EntityAliasObserver
{
    public function created(EntityAlias $alias): void
    {
        $this->clearCache($alias);
    }

    public function updated(EntityAlias $alias): void
    {
        $this->clearCache($alias);
    }

    public function deleted(EntityAlias $alias): void
    {
        $this->clearCache($alias);
    }

    private function clearCache(EntityAlias $alias): void
    {
        Cache::forget("entity_aliases:{$alias->company_id}");
    }
}
