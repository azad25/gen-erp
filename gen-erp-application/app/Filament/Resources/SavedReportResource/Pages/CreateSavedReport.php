<?php

namespace App\Filament\Resources\SavedReportResource\Pages;

use App\Filament\Resources\SavedReportResource;
use Filament\Resources\Pages\CreateRecord;

class CreateSavedReport extends CreateRecord
{
    protected static string $resource = SavedReportResource::class;

    protected function mutateFormDataBeforeCreate(array $data): array
    {
        $data['created_by'] = auth()->id();

        return $data;
    }
}
