<?php

namespace App\Filament\Resources;

use App\Filament\Resources\SavedReportResource\Pages;
use App\Filament\Resources\BaseResource;
use App\Filament\Support\FormStyles;
use App\Filament\Support\TableStyles;
use App\Models\SavedReport;
use Filament\Forms\Components\CheckboxList;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\TagsInput;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class SavedReportResource extends BaseResource
{
    protected static ?string $model = SavedReport::class;

    protected static ?string $navigationIcon = 'heroicon-o-chart-bar';

    protected static ?string $navigationGroup = 'Reports';

    protected static ?string $navigationLabel = 'Report Builder';

    protected static ?int $navigationSort = 1;

    protected static ?string $slug = 'reports/builder';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Tabs::make('Report')
                ->tabs([
                    Tabs\Tab::make(__('Data'))
                        ->schema([
                            TextInput::make('name')
                                ->label(__('Report Name'))
                                ->required()
                                ->maxLength(255),
                            Select::make('entity_type')
                                ->label(__('Entity Type'))
                                ->options([
                                    'customer' => __entity('customer'),
                                    'product' => __entity('product'),
                                    'sales' => __entity('invoice'),
                                    'purchases' => __('Purchases'),
                                    'employees' => __('Employees'),
                                    'expenses' => __('Expenses'),
                                ])
                                ->required()
                                ->live(),
                            CheckboxList::make('selected_fields')
                                ->label(__('Fields to Include'))
                                ->options(fn ($get): array => self::getFieldOptions($get('entity_type')))
                                ->columns(3),
                            Select::make('group_by')
                                ->label(__('Group By'))
                                ->options(fn ($get): array => self::getFieldOptions($get('entity_type'))),
                            Select::make('aggregate.field')
                                ->label(__('Aggregate Field'))
                                ->options(fn ($get): array => self::getFieldOptions($get('entity_type'))),
                            Select::make('aggregate.function')
                                ->label(__('Aggregate Function'))
                                ->options([
                                    'sum' => __('Sum'),
                                    'count' => __('Count'),
                                    'avg' => __('Average'),
                                    'max' => __('Maximum'),
                                    'min' => __('Minimum'),
                                ]),
                        ]),
                    Tabs\Tab::make(__('Filters'))
                        ->schema([
                            Repeater::make('filters')
                                ->schema([
                                    Select::make('field')
                                        ->label(__('Field'))
                                        ->options(fn ($get): array => self::getFieldOptions(
                                            $get('../../entity_type')
                                        ))
                                        ->required(),
                                    Select::make('operator')
                                        ->label(__('Operator'))
                                        ->options([
                                            'eq' => __('Equals'),
                                            'neq' => __('Not Equals'),
                                            'lt' => __('Less Than'),
                                            'gt' => __('Greater Than'),
                                            'contains' => __('Contains'),
                                            'between' => __('Between'),
                                        ])
                                        ->required(),
                                    TextInput::make('value')
                                        ->label(__('Value')),
                                ])
                                ->columns(3)
                                ->defaultItems(0)
                                ->addActionLabel(__('Add Filter')),
                        ]),
                    Tabs\Tab::make(__('Display'))
                        ->schema([
                            Select::make('sort_field')
                                ->label(__('Sort By'))
                                ->options(fn ($get): array => self::getFieldOptions($get('entity_type'))),
                            Select::make('sort_direction')
                                ->label(__('Sort Direction'))
                                ->options([
                                    'asc' => __('Ascending'),
                                    'desc' => __('Descending'),
                                ])
                                ->default('asc'),
                            Select::make('visualisation')
                                ->label(__('Visualisation'))
                                ->options([
                                    'table' => __('Table'),
                                    'bar_chart' => __('Bar Chart'),
                                    'line_chart' => __('Line Chart'),
                                    'pie_chart' => __('Pie Chart'),
                                ])
                                ->default('table'),
                        ]),
                    Tabs\Tab::make(__('Schedule'))
                        ->schema([
                            Toggle::make('is_scheduled')
                                ->label(__('Enable Scheduling'))
                                ->live(),
                            Select::make('schedule_frequency')
                                ->label(__('Frequency'))
                                ->options([
                                    'daily' => __('Daily'),
                                    'weekly' => __('Weekly'),
                                    'monthly' => __('Monthly'),
                                ])
                                ->visible(fn ($get): bool => (bool) $get('is_scheduled')),
                            TagsInput::make('schedule_recipients')
                                ->label(__('Recipient Emails'))
                                ->placeholder(__('Add email'))
                                ->visible(fn ($get): bool => (bool) $get('is_scheduled')),
                        ]),
                ])
                ->columnSpanFull(),
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
                TextColumn::make('visualisation')
                    ->label(__('Type'))
                    ->badge(),
                IconColumn::make('is_scheduled')
                    ->label(__('Scheduled'))
                    ->boolean(),
                TextColumn::make('creator.name')
                    ->label(__('Created By'))
                    ->sortable(),
            ])
            ->actions(static::getModernTableActions());
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListSavedReports::route('/'),
            'create' => Pages\CreateSavedReport::route('/create'),
            'edit' => Pages\EditSavedReport::route('/{record}/edit'),
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
