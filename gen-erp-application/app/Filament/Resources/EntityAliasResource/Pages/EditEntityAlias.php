<?php

namespace App\Filament\Resources\EntityAliasResource\Pages;

use App\Filament\Resources\EntityAliasResource;
use App\Filament\Pages\BaseEditPage;

class EditEntityAlias extends BaseEditPage
{
    protected static string $resource = EntityAliasResource::class;

    protected function afterSave(): void
    {
        EntityAliasResource::invalidateCache();
    }
}
