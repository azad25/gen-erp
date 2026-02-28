<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Actions\DeleteAction;
use Filament\Actions\ViewAction;
use Filament\Resources\Pages\EditRecord;

/**
 * Base Edit Page with modern enterprise styling.
 * 
 * All edit pages should extend this class for consistent UI/UX.
 */
abstract class BaseEditPage extends EditRecord
{
    /**
     * Get modern header actions with consistent styling.
     */
    protected function getHeaderActions(): array
    {
        $actions = [
            Action::make('back')
                ->label(__('Back to List'))
                ->icon('heroicon-m-arrow-left')
                ->color('gray')
                ->url(static::$resource::getUrl('index'))
                ->extraAttributes([
                    'class' => 'modern-back-button',
                ]),
        ];

        // Add view action if view page exists
        if (static::$resource::hasPage('view')) {
            $actions[] = ViewAction::make()
                ->icon('heroicon-m-eye')
                ->color('info')
                ->extraAttributes([
                    'class' => 'modern-view-button',
                ]);
        }

        // Add delete action
        $actions[] = DeleteAction::make()
            ->icon('heroicon-m-trash')
            ->color('danger')
            ->extraAttributes([
                'class' => 'modern-delete-button',
            ]);

        return $actions;
    }

    /**
     * Get the view for the edit page.
     */
    public function getView(): string
    {
        return 'filament.pages.modern-edit';
    }

    /**
     * Get the page heading.
     */
    public function getHeading(): string
    {
        return __('Edit :resource', [
            'resource' => static::$resource::getModelLabel(),
        ]);
    }

    /**
     * Get the page subheading.
     */
    public function getSubheading(): ?string
    {
        return __('Update the details of this :resource', [
            'resource' => strtolower(static::$resource::getModelLabel()),
        ]);
    }

    /**
     * Get the form actions.
     */
    protected function getFormActions(): array
    {
        return [
            $this->getSaveFormAction()
                ->extraAttributes([
                    'class' => 'modern-submit-button',
                ]),
            $this->getCancelFormAction()
                ->color('gray')
                ->extraAttributes([
                    'class' => 'modern-cancel-button',
                ]),
        ];
    }
}
