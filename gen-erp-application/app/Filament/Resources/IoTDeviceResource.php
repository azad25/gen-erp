<?php

namespace App\Filament\Resources;

use App\Enums\DeviceType;
use App\Models\IoTDevice;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class IoTDeviceResource extends Resource
{
    protected static ?string $model = IoTDevice::class;

    protected static ?string $navigationIcon = 'heroicon-o-cpu-chip';

    protected static ?string $navigationGroup = 'Integrations';

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('IoT Devices');
    }

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make(__('Device Information'))->schema([
                Forms\Components\TextInput::make('name')
                    ->label(__('Device Name'))
                    ->required()
                    ->maxLength(200)
                    ->placeholder('Main Gate Biometric'),

                Forms\Components\Select::make('device_type')
                    ->label(__('Device Type'))
                    ->options(DeviceType::options())
                    ->required(),

                Forms\Components\Select::make('branch_id')
                    ->label(__('Branch'))
                    ->relationship('branch', 'name')
                    ->nullable()
                    ->searchable(),

                Forms\Components\Select::make('company_integration_id')
                    ->label(__('Integration'))
                    ->relationship('companyIntegration', 'id')
                    ->required(),
            ])->columns(2),

            Forms\Components\Section::make(__('Connection Settings'))->schema([
                Forms\Components\TextInput::make('driver_class')
                    ->label(__('Driver Class'))
                    ->required()
                    ->maxLength(500),

                Forms\Components\Select::make('connection_type')
                    ->label(__('Connection Type'))
                    ->options([
                        'tcp_ip' => 'TCP/IP (Network)',
                        'usb' => 'USB',
                        'serial' => 'Serial (RS-232)',
                        'bluetooth' => 'Bluetooth',
                        'http' => 'HTTP API',
                        'mqtt' => 'MQTT',
                    ])
                    ->required(),

                Forms\Components\KeyValue::make('config')
                    ->label(__('Configuration'))
                    ->keyLabel(__('Setting'))
                    ->valueLabel(__('Value'))
                    ->addActionLabel(__('Add setting')),
            ]),

            Forms\Components\Toggle::make('is_active')
                ->label(__('Active'))
                ->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('Device'))
                    ->searchable()
                    ->sortable(),

                Tables\Columns\TextColumn::make('device_type')
                    ->label(__('Type'))
                    ->badge()
                    ->sortable(),

                Tables\Columns\TextColumn::make('branch.name')
                    ->label(__('Branch'))
                    ->sortable()
                    ->placeholder('—'),

                Tables\Columns\TextColumn::make('connection_type')
                    ->label(__('Connection'))
                    ->sortable(),

                Tables\Columns\BadgeColumn::make('status')
                    ->label(__('Status'))
                    ->colors([
                        'success' => 'online',
                        'danger' => fn (string $state) => in_array($state, ['offline', 'error']),
                        'warning' => 'syncing',
                    ]),

                Tables\Columns\TextColumn::make('last_sync_at')
                    ->label(__('Last Sync'))
                    ->dateTime('d M Y H:i')
                    ->sortable()
                    ->placeholder(__('Never')),

                Tables\Columns\IconColumn::make('is_active')
                    ->label(__('Active'))
                    ->boolean(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('device_type')
                    ->options(DeviceType::options()),
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'online' => 'Online',
                        'offline' => 'Offline',
                        'error' => 'Error',
                        'syncing' => 'Syncing',
                    ]),
            ])
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => IoTDeviceResource\Pages\ListIoTDevices::route('/'),
            'create' => IoTDeviceResource\Pages\CreateIoTDevice::route('/create'),
            'edit' => IoTDeviceResource\Pages\EditIoTDevice::route('/edit/{record}'),
        ];
    }
}
