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

    /**
     * Create a modern date column.
     */
    public static function date(string $name, string $label = null): TextColumn
    {
        return TextColumn::make($name)
            ->label($label ?? __(ucfirst($name)))
            ->date()
            ->sortable()
            ->icon('heroicon-m-calendar')
            ->iconColor('gray')
            ->extraAttributes([
                'class' => 'modern-date-column',
            ]);
    }

    /**
     * Create a modern datetime column with relative time.
     */
    public static function datetime(string $name, string $label = null): TextColumn
    {
        return TextColumn::make($name)
            ->label($label ?? __(ucfirst($name)))
            ->dateTime()
            ->sortable()
            ->since()
            ->icon('heroicon-m-clock')
            ->iconColor('gray')
            ->extraAttributes([
                'class' => 'modern-datetime-column',
            ]);
    }

    /**
     * Create a modern user column with avatar.
     */
    public static function user(string $name, string $label = null): TextColumn
    {
        return TextColumn::make($name)
            ->label($label ?? __(ucfirst($name)))
            ->searchable()
            ->sortable()
            ->icon('heroicon-m-user')
            ->iconColor('primary')
            ->extraAttributes([
                'class' => 'modern-user-column',
            ]);
    }

    /**
     * Create a modern boolean column with icons.
     */
    public static function boolean(string $name, string $label = null): IconColumn
    {
        return IconColumn::make($name)
            ->label($label ?? __(ucfirst($name)))
            ->boolean()
            ->sortable()
            ->extraAttributes([
                'class' => 'modern-boolean-column',
            ]);
    }

    /**
     * Create a modern enum column with colors.
     */
    public static function enum(string $name, string $label = null): TextColumn
    {
        return TextColumn::make($name)
            ->label($label ?? __(ucfirst($name)))
            ->badge()
            ->sortable()
            ->searchable()
            ->extraAttributes([
                'class' => 'modern-enum-column',
            ]);
    }

    /**
     * Get standard status filter.
     */
    public static function statusFilter(string $name = 'status'): SelectFilter
    {
        return SelectFilter::make($name)
            ->multiple()
            ->preload();
    }
}
