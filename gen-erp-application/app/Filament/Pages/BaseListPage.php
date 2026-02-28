<?php

namespace App\Filament\Pages;

use Filament\Actions\CreateAction;
use Filament\Resources\Pages\ListRecords;

/**
 * Base List Page with modern enterprise styling.
 * 
 * All list pages should extend this class for consistent UI/UX.
 */
abstract class BaseListPage extends ListRecords
{
    /**
     * Get modern header actions with consistent styling.
     */
    protected function getHeaderActions(): array
    {
        return [
            CreateAction::make()
                ->icon('heroicon-m-plus')
                ->label(__('Create New'))
                ->color('primary')
                ->extraAttributes([
                    'class' => 'modern-create-button',
                ]),
        ];
    }

    /**
     * Get the view for the list page.
     */
    public function getView(): string
    {
        return 'filament.pages.modern-list';
    }

    /**
     * Get the page heading.
     */
    public function getHeading(): string
    {
        return static::$resource::getPluralModelLabel();
    }

    /**
     * Get the page subheading.
     */
    public function getSubheading(): ?string
    {
        return __('Manage and organize your :resource', [
            'resource' => strtolower(static::$resource::getPluralModelLabel()),
        ]);
    }
}
