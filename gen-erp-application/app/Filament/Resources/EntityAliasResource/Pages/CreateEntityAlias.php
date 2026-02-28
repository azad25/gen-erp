<?php

namespace App\Filament\Resources\EntityAliasResource\Pages;

use App\Filament\Resources\EntityAliasResource;
use App\Filament\Pages\BaseCreatePage;

class CreateEntityAlias extends BaseCreatePage
{
    protected static string $resource = EntityAliasResource::class;

    protected function afterCreate(): void
    {
        EntityAliasResource::invalidateCache();
    }
}
