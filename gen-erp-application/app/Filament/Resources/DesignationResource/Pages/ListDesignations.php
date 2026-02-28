<?php

namespace App\Filament\Resources\DesignationResource\Pages;

use App\Filament\Resources\DesignationResource;
use Filament\Resources\Pages\ListRecords;

class ListDesignations extends ListRecords
{
    protected static string $resource = DesignationResource::class;

    protected function getHeaderActions(): array
    {
        return [\Filament\Actions\CreateAction::make()];
    }
}
