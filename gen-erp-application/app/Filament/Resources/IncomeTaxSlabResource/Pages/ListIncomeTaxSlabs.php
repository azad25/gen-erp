<?php

namespace App\Filament\Resources\IncomeTaxSlabResource\Pages;

use App\Filament\Resources\IncomeTaxSlabResource;
use Filament\Resources\Pages\ListRecords;

class ListIncomeTaxSlabs extends ListRecords
{
    protected static string $resource = IncomeTaxSlabResource::class;

    protected function getHeaderActions(): array
    {
        return [\Filament\Actions\CreateAction::make()];
    }
}
