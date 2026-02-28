<?php

namespace App\Filament\Resources\CustomFieldDefinitionResource\Pages;

use App\Filament\Resources\CustomFieldDefinitionResource;
use App\Filament\Pages\BaseEditPage;
use App\Jobs\FilterableCustomFieldJob;

class EditCustomFieldDefinition extends BaseEditPage
{
    protected static string $resource = CustomFieldDefinitionResource::class;
protected function afterSave(): void
    {
        $record = $this->record;

        // If is_filterable was just enabled, dispatch the job
        if ($record->is_filterable && $record->wasChanged('is_filterable')) {
            FilterableCustomFieldJob::dispatch($record);
        }
    }
}
