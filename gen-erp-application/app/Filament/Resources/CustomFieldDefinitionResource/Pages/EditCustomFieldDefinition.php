<?php

namespace App\Filament\Resources\CustomFieldDefinitionResource\Pages;

use App\Filament\Resources\CustomFieldDefinitionResource;
use App\Jobs\FilterableCustomFieldJob;
use Filament\Actions\DeleteAction;
use Filament\Resources\Pages\EditRecord;

class EditCustomFieldDefinition extends EditRecord
{
    protected static string $resource = CustomFieldDefinitionResource::class;

    protected function getHeaderActions(): array
    {
        return [
            DeleteAction::make(),
        ];
    }

    protected function afterSave(): void
    {
        $record = $this->record;

        // If is_filterable was just enabled, dispatch the job
        if ($record->is_filterable && $record->wasChanged('is_filterable')) {
            FilterableCustomFieldJob::dispatch($record);
        }
    }
}
