<?php

namespace App\Filament\Resources\IncomeTaxSlabResource\Pages;

use App\Filament\Resources\IncomeTaxSlabResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditIncomeTaxSlab extends EditRecord
{
    protected static string $resource = IncomeTaxSlabResource::class;

    protected function getHeaderActions(): array
    {
        return [DeleteAction::make()];
    }
}
