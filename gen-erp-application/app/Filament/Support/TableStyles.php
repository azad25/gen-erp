<?php

namespace App\Filament\Support;

use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

/**
 * Enterprise-level table styling utilities for consistent UI across all resources.
 */
class TableStyles
{
    /**
     * Apply modern styling to a table.
     */
    public static function apply(Table $table): Table
    {
        return $table
            ->striped()
            ->defaultPaginationPageOption(25)
            ->paginationPageOptions([10, 25, 50, 100])
            ->persistFiltersInSession()
            ->persistSearchInSession()
            ->persistSortInSession()
            ->deferLoading()
            ->extremePaginationLinks()
            ->poll('30s')
            ->extraAttributes([
                'class' => 'modern-table',
            ]);
    }

    /**
     * Create a modern status badge column.
     */
    public static function statusBadge(string $name, string $label = null): TextColumn
    {
        return TextColumn::make($name)
            ->label($label ?? __(ucfirst($name)))
            ->badge()
            ->sortable()
            ->searchable()
            ->extraAttributes([
                'class' => 'modern-badge-column',
            ]);
    }

    /**
     * Create a modern money column.
     */
    public static function money(string $name, string $label = null): TextColumn
    {
        return TextColumn::make($name)
            ->label($label ?? __(ucfirst($name)))
            ->money('BDT', divideBy: 100)
            ->sortable()
            ->alignEnd()
            ->extraAttributes([
                'class' => 'modern-money-column font-semibold',
            ]);
    }
}
