<?php

namespace App\Filament\Resources;

use App\Filament\Traits\HasModernStyling;
use Filament\Resources\Resource;
use Filament\Tables\Actions\Action;
use Filament\Tables\Actions\ActionGroup;
use Filament\Tables\Actions\BulkActionGroup;
use Filament\Tables\Actions\DeleteAction;
use Filament\Tables\Actions\DeleteBulkAction;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;

/**
 * Base resource with modern enterprise styling and common configurations.
 * 
 * All resources should extend this class for consistent UI/UX.
 */
abstract class BaseResource extends Resource
{
    use HasModernStyling;

    /**
     * Get modern table actions with consistent styling.
     */
    public static function getModernTableActions(): array
    {
        return [
            ActionGroup::make([
                ViewAction::make()
                    ->icon('heroicon-m-eye')
                    ->color('info')
                    ->tooltip(__('View')),
                EditAction::make()
                    ->icon('heroicon-m-pencil-square')
                    ->color('warning')
                    ->tooltip(__('Edit')),
                DeleteAction::make()
                    ->icon('heroicon-m-trash')
                    ->color('danger')
                    ->tooltip(__('Delete')),
            ])
            ->icon('heroicon-m-ellipsis-vertical')
            ->size('sm')
            ->color('gray')
            ->button()
            ->label(__('Actions'))
            ->tooltip(__('More actions')),
        ];
    }

    /**
     * Get modern bulk actions with consistent styling.
     */
    public static function getModernBulkActions(): array
    {
        return [
            BulkActionGroup::make([
                DeleteBulkAction::make()
                    ->icon('heroicon-m-trash')
                    ->requiresConfirmation(),
            ]),
        ];
    }

    /**
     * Get modern header actions for create page.
     */
    public static function getModernCreateActions(): array
    {
        return [
            Action::make('back')
                ->label(__('Back to List'))
                ->icon('heroicon-m-arrow-left')
                ->color('gray')
                ->url(static::getUrl('index')),
        ];
    }

    /**
     * Get modern header actions for edit page.
     */
    public static function getModernEditActions(): array
    {
        return [
            Action::make('back')
                ->label(__('Back to List'))
                ->icon('heroicon-m-arrow-left')
                ->color('gray')
                ->url(static::getUrl('index')),
            Action::make('view')
                ->label(__('View'))
                ->icon('heroicon-m-eye')
                ->color('info')
                ->url(fn ($record) => static::getUrl('view', ['record' => $record])),
        ];
    }

    /**
     * Get modern header actions for view page.
     */
    public static function getModernViewActions(): array
    {
        return [
            Action::make('back')
                ->label(__('Back to List'))
                ->icon('heroicon-m-arrow-left')
                ->color('gray')
                ->url(static::getUrl('index')),
            Action::make('edit')
                ->label(__('Edit'))
                ->icon('heroicon-m-pencil-square')
                ->color('warning')
                ->url(fn ($record) => static::getUrl('edit', ['record' => $record])),
        ];
    }
}
