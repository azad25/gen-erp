<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\EditAction;
use Filament\Resources\Pages\ViewRecord;

/**
 * Base View Page with modern enterprise styling.
 * 
 * All view pages should extend this class for consistent UI/UX.
 */
abstract class BaseViewPage extends ViewRecord
{
    /**
     * Get modern header actions with consistent styling.
     */
    protected function getHeaderActions(): array
    {
        return [
            Action::make('back')
                ->label(__('Back to List'))
                ->icon('heroicon-m-arrow-left')
                ->color('gray')
                ->url(static::$resource::getUrl('index'))
                ->extraAttributes([
                    'class' => 'modern-back-button',
                ]),
            EditAction::make()
                ->icon('heroicon-m-pencil-square')
                ->color('warning')
                ->extraAttributes([
                    'class' => 'modern-edit-button',
                ]),
            DeleteAction::make()
                ->icon('heroicon-m-trash')
                ->color('danger')
                ->extraAttributes([
                    'class' => 'modern-delete-button',
                ]),
        ];
    }

    /**
     * Get the view for the view page.
     */
    public function getView(): string
    {
        return 'filament.pages.modern-view';
    }

    /**
     * Get the page heading.
     */
    public function getHeading(): string
    {
        return __('View :resource', [
            'resource' => static::$resource::getModelLabel(),
        ]);
    }

    /**
     * Get the page subheading.
     */
    public function getSubheading(): ?string
    {
        return __('Detailed information about this :resource', [
            'resource' => strtolower(static::$resource::getModelLabel()),
        ]);
    }
}
