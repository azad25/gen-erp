<?php

namespace App\Filament\Support;

use Filament\Tables\Columns\TextColumn;

class TableHelper
{
    /**
     * Money column — formats paise as ৳X,XX,XXX.XX with mono font, right-aligned.
     */
    public static function moneyColumn(string $name, string $label): TextColumn
    {
        return TextColumn::make($name)
            ->label($label)
            ->formatStateUsing(fn ($state) => '৳' . number_format($state / 100, 2))
            ->fontFamily('mono')
            ->alignment('right')
            ->color(fn ($state) => $state < 0 ? 'danger' : null)
            ->sortable();
    }

    /**
     * Reference number column — mono, semibold, copyable.
     */
    public static function refColumn(string $name, string $label = 'Ref #'): TextColumn
    {
        return TextColumn::make($name)
            ->label($label)
            ->fontFamily('mono')
            ->weight('semibold')
            ->size('sm')
            ->searchable()
            ->copyable()
            ->copyMessage('Copied!')
            ->color('gray');
    }

    /**
     * Date column — BD format (01 Jan 2026), mono, sortable.
     */
    public static function dateColumn(string $name, string $label): TextColumn
    {
        return TextColumn::make($name)
            ->label($label)
            ->date('d M Y')
            ->fontFamily('mono')
            ->size('sm')
            ->sortable();
    }

    /**
     * Overdue-aware date column — turns red when date is past and record is unpaid.
     */
    public static function dueDateColumn(string $dateName, string $statusName): TextColumn
    {
        return TextColumn::make($dateName)
            ->label('Due Date')
            ->date('d M Y')
            ->fontFamily('mono')
            ->size('sm')
            ->color(function ($record) use ($dateName, $statusName): string {
                $isPast   = $record->$dateName && $record->$dateName->isPast();
                $isUnpaid = in_array($record->$statusName?->value, ['sent', 'partial', 'overdue']);
                return ($isPast && $isUnpaid) ? 'danger' : 'gray';
            })
            ->sortable();
    }

    /**
     * BD phone number column — formatted as 01X-XXXX-XXXX.
     */
    public static function phoneColumn(string $name = 'phone'): TextColumn
    {
        return TextColumn::make($name)
            ->label('Phone')
            ->fontFamily('mono')
            ->size('sm')
            ->formatStateUsing(fn ($state) => $state
                ? substr($state, 0, 3) . '-' . substr($state, 3, 4) . '-' . substr($state, 7)
                : '—'
            );
    }
}
