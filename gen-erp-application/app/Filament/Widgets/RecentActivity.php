<?php

namespace App\Filament\Widgets;

use App\Models\AuditLog;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;

/**
 * Modern recent activity widget.
 */
class RecentActivity extends BaseWidget
{
    protected static ?int $sort = 3;

    protected int | string | array $columnSpan = 'full';

    public function table(Table $table): Table
    {
        return $table
            ->query(
                AuditLog::query()
                    ->with('user')
                    ->latest()
                    ->limit(10)
            )
            ->columns([
                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('User'))
                    ->searchable()
                    ->sortable()
                    ->icon('heroicon-m-user')
                    ->iconColor('primary'),

                Tables\Columns\TextColumn::make('action')
                    ->label(__('Action'))
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'created' => 'success',
                        'updated' => 'info',
                        'deleted' => 'danger',
                        default => 'gray',
                    }),

                Tables\Columns\TextColumn::make('entity_type')
                    ->label(__('Entity'))
                    ->formatStateUsing(fn (string $state): string => __(class_basename($state))),

                Tables\Columns\TextColumn::make('entity_label')
                    ->label(__('Details'))
                    ->limit(50)
                    ->tooltip(function (Tables\Columns\TextColumn $column): ?string {
                        $state = $column->getState();

                        if (strlen($state) <= 50) {
                            return null;
                        }

                        return $state;
                    }),

                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Time'))
                    ->dateTime()
                    ->sortable()
                    ->since()
                    ->icon('heroicon-m-clock')
                    ->iconColor('gray'),
            ])
            ->heading(__('Recent Activity'))
            ->description(__('Latest system activities and changes'))
            ->paginated(false);
    }
}
