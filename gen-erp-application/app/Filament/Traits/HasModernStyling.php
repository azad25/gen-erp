<?php

namespace App\Filament\Traits;

use Filament\Tables\Table;

/**
 * Trait to apply modern enterprise styling to all Filament resources.
 * 
 * Usage: Add `use HasModernStyling;` to any Resource class.
 */
trait HasModernStyling
{
    /**
     * Apply modern table styling and advanced datatable features.
     */
    public static function modernTable(Table $table): Table
    {
        return $table
            // Removed striped() for cleaner SaaS aesthetic
            ->defaultPaginationPageOption(25)
            ->paginationPageOptions([10, 25, 50, 100])
            ->persistFiltersInSession()
            ->persistSearchInSession()
            ->persistSortInSession()
            // Enable advanced column toggling layout
            ->columnToggleFormColumns(2)
            ->deferLoading()
            ->extremePaginationLinks()
            ->poll('30s');
    }

    /**
     * Get modern form column configuration.
     */
    public static function modernFormColumns(): array
    {
        return [
            'sm' => 1,
            'md' => 2,
            'lg' => 3,
        ];
    }

    /**
     * Get compact form column configuration.
     */
    public static function compactFormColumns(): array
    {
        return [
            'sm' => 2,
            'md' => 3,
            'lg' => 4,
        ];
    }
}
