<?php

namespace App\Filament\Resources;

use App\Enums\AlertOperator;
use App\Filament\Resources\BaseResource;
use App\Filament\Support\FormStyles;
use App\Filament\Support\TableStyles;
use App\Enums\CompanyRole;
use App\Filament\Resources\AlertRuleResource\Pages;
use App\Models\AlertRule;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class AlertRuleResource extends BaseResource
{
    protected static ?string $model = AlertRule::class;

    protected static ?string $navigationIcon = 'heroicon-o-bell-alert';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Alert Rules';

    protected static ?int $navigationSort = 5;

    protected static ?string $slug = 'settings/alert-rules';

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')
                ->label(__('Rule Name'))
                ->required()
                ->maxLength(255),
            Select::make('entity_type')
                ->label(__('Entity Type'))
                ->options([
                    'customer' => __entity('customer'),
                    'product' => __entity('product'),
                    'invoice' => __entity('invoice'),
                    'purchase_order' => __('Purchase Order'),
                    'employee' => __('Employee'),
                    'expense' => __('Expense'),
                ])
                ->required()
                ->live(),
            Toggle::make('is_active')
                ->label(__('Active'))
                ->default(true),
            Select::make('trigger_field')
                ->label(__('Trigger Field'))
                ->options(fn ($get): array => self::getFieldOptions($get('entity_type')))
                ->required(),
            Select::make('operator')
                ->label(__('Operator'))
                ->options(AlertOperator::options())
                ->required()
                ->live(),
            TextInput::make('trigger_value')
                ->label(__('Trigger Value'))
                ->maxLength(500)
                ->visible(fn ($get): bool => ! in_array($get('operator'), ['is_null', 'not_null'])),
            CheckboxList::make('channels')
                ->label(__('Notification Channels'))
                ->options([
                    'in_app' => __('In-App'),
                    'email' => __('Email'),
                    'sms' => __('SMS'),
                ])
                ->columns(3)
                ->required(),
            Select::make('target_roles')
                ->label(__('Target Roles'))
                ->multiple()
                ->options(CompanyRole::options())
                ->required(),
            Textarea::make('message_template')
                ->label(__('Message Template'))
                ->helperText(__('Use {field_name} for placeholders, e.g. "Stock for {name} is below {stock_quantity}"'))
                ->required()
                ->maxLength(1000),
            Select::make('repeat_behaviour')
                ->label(__('Repeat Behaviour'))
                ->options([
                    'once' => __('Once per entity'),
                    'always' => __('Every time'),
                    'daily_max' => __('Daily maximum'),
                ])
                ->default('always'),
            TextInput::make('cooldown_minutes')
                ->label(__('Cooldown (minutes)'))
                ->numeric()
                ->default(0)
                ->minValue(0),
        ]);
    }

    public static function table(Table $table): Table
    {
        return static::modernTable($table)
            ->columns([
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('entity_type')
                    ->label(__('Entity'))
                    ->sortable(),
                TextColumn::make('trigger_field')
                    ->label(__('Field')),
                TextColumn::make('operator')
                    ->label(__('Operator'))
                    ->formatStateUsing(fn (string $state): string => AlertOperator::tryFrom($state)?->label() ?? $state),
                IconColumn::make('is_active')
                    ->label(__('Active'))
                    ->boolean(),
                TextColumn::make('last_triggered_at')
                    ->label(__('Last Triggered'))
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->actions(static::getModernTableActions());
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAlertRules::route('/'),
            'create' => Pages\CreateAlertRule::route('/create'),
            'edit' => Pages\EditAlertRule::route('/{record}/edit'),
        ];
    }

    /**
     * @return array<string, string>
     */
    private static function getFieldOptions(?string $entityType): array
    {
        if (! $entityType) {
            return [];
        }

        $service = app(\App\Services\ReportBuilderService::class);
        $fields = $service->getAvailableFields($entityType);

        return collect($fields)
            ->mapWithKeys(fn (array $f): array => [$f['key'] => $f['label']])
            ->all();
    }
}
