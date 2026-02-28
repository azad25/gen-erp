<?php

namespace App\Filament\Resources;

use App\Filament\Resources\AuditLogResource\Pages;
use App\Models\AuditLog;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Tables;
use Filament\Tables\Table;

/**
 * Read-only Filament resource for viewing audit logs.
 */
class AuditLogResource extends BaseResource
{
    protected static ?string $model = AuditLog::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'Security';

    protected static ?int $navigationSort = 50;

    public static function getNavigationLabel(): string
    {
        return __('Audit Log');
    }

    public static function getModelLabel(): string
    {
        return __('Audit Log');
    }

    public static function canCreate(): bool
    {
        return false;
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery()
            ->where('company_id', activeCompany()?->id);
    }

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Audit Details'))
                    ->schema([
                        Forms\Components\TextInput::make('event')
                            ->label(__('Event'))
                            ->disabled(),

                        Forms\Components\TextInput::make('auditable_type')
                            ->label(__('Entity Type'))
                            ->disabled(),

                        Forms\Components\TextInput::make('auditable_id')
                            ->label(__('Entity ID'))
                            ->disabled(),

                        Forms\Components\TextInput::make('user.name')
                            ->label(__('User'))
                            ->disabled(),

                        Forms\Components\TextInput::make('ip_address')
                            ->label(__('IP Address'))
                            ->disabled(),

                        Forms\Components\KeyValue::make('old_values')
                            ->label(__('Old Values'))
                            ->disabled()
                            ->columnSpanFull(),

                        Forms\Components\KeyValue::make('new_values')
                            ->label(__('New Values'))
                            ->disabled()
                            ->columnSpanFull(),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('created_at')
                    ->label(__('Date'))
                    ->dateTime('d M Y H:i')
                    ->sortable(),

                Tables\Columns\TextColumn::make('event')
                    ->label(__('Event'))
                    ->badge()
                    ->color(fn (string $state) => match ($state) {
                        'created' => 'success',
                        'updated' => 'warning',
                        'deleted' => 'danger',
                        default => 'gray',
                    })
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('auditable_type')
                    ->label(__('Entity'))
                    ->formatStateUsing(fn (string $state) => class_basename($state))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('auditable_id')
                    ->label(__('ID'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('user.name')
                    ->label(__('By'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('ip_address')
                    ->label(__('IP'))
                    ->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('event')
                    ->options([
                        'created' => __('Created'),
                        'updated' => __('Updated'),
                        'deleted' => __('Deleted'),
                        'settings_updated' => __('Settings Updated'),
                    ]),
            ])
            ->actions([
                Tables\Actions\ViewAction::make()
                    ->icon('heroicon-m-eye')
                    ->color('info'),
            ])
            ->defaultSort('created_at', 'desc');
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAuditLogs::route('/'),
        ];
    }
}
