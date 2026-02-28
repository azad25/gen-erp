<?php

namespace App\Filament\Resources\SavedReportResource\Pages;

use App\Filament\Resources\SavedReportResource;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditSavedReport extends EditRecord
{
    protected static string $resource = SavedReportResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }
}
