<?php

namespace App\Filament\Pages;

use Filament\Actions\Action;
use Filament\Resources\Pages\CreateRecord;

/**
 * Base Create Page with modern enterprise styling.
 * 
 * All create pages should extend this class for consistent UI/UX.
 */
abstract class BaseCreatePage extends CreateRecord
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
        ];
    }

    /**
     * Get the view for the create page.
     */
    public function getView(): string
    {
        return 'filament.pages.modern-create';
    }

    /**
     * Get the page heading.
     */
    public function getHeading(): string
    {
        return __('Create :resource', [
            'resource' => static::$resource::getModelLabel(),
        ]);
    }

    /**
     * Get the page subheading.
     */
    public function getSubheading(): ?string
    {
        return __('Fill in the details to create a new :resource', [
            'resource' => strtolower(static::$resource::getModelLabel()),
        ]);
    }

    /**
     * Get the form actions.
     */
    protected function getFormActions(): array
    {
        return [
            $this->getCreateFormAction()
                ->extraAttributes([
                    'class' => 'modern-submit-button',
                ]),
            $this->getCreateAnotherFormAction()
                ->color('gray')
                ->extraAttributes([
                    'class' => 'modern-secondary-button',
                ]),
            $this->getCancelFormAction()
                ->color('gray')
                ->extraAttributes([
                    'class' => 'modern-cancel-button',
                ]),
        ];
    }
}
