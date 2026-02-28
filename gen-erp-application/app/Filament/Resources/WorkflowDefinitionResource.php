<?php

namespace App\Filament\Resources;

use App\Enums\CompanyRole;
use App\Filament\Resources\BaseResource;
use App\Filament\Support\FormStyles;
use App\Filament\Support\TableStyles;
use App\Enums\WorkflowDocumentType;
use App\Filament\Resources\WorkflowDefinitionResource\Pages;
use App\Models\WorkflowDefinition;
use Filament\Forms\Components\Repeater;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;
use Illuminate\Support\Str;

class WorkflowDefinitionResource extends BaseResource
{
    protected static ?string $model = WorkflowDefinition::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrow-path';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Workflows';

    protected static ?int $navigationSort = 4;

    protected static ?string $slug = 'settings/workflows';

    public static function form(Form $form): Form
    {
        return $form->schema([
            Tabs::make('Workflow')
                ->tabs([
                    Tabs\Tab::make(__('Definition'))
                        ->schema([
                            TextInput::make('name')
                                ->label(__('Workflow Name'))
                                ->required()
                                ->maxLength(255),
                            Select::make('document_type')
                                ->label(__('Document Type'))
                                ->options(WorkflowDocumentType::options())
                                ->required()
                                ->disabled(fn (?WorkflowDefinition $record): bool => $record !== null),
                            Toggle::make('is_active')
                                ->label(__('Active'))
                                ->default(true),
                            Toggle::make('is_default')
                                ->label(__('Default Workflow'))
                                ->helperText(__('One default per document type per company.')),
                        ]),
                    Tabs\Tab::make(__('Statuses'))
                        ->schema([
                            Repeater::make('statuses')
                                ->relationship()
                                ->schema([
                                    TextInput::make('label')
                                        ->label(__('Label'))
                                        ->required()
                                        ->maxLength(255)
                                        ->live(onBlur: true)
                                        ->afterStateUpdated(fn ($state, $set) => $set('key', Str::snake($state))),
                                    TextInput::make('key')
                                        ->label(__('Key'))
                                        ->required()
                                        ->maxLength(100)
                                        ->dehydrated(),
                                    Select::make('color')
                                        ->label(__('Color'))
                                        ->options([
                                            'gray' => __('Gray'),
                                            'warning' => __('Warning (Yellow)'),
                                            'success' => __('Success (Green)'),
                                            'danger' => __('Danger (Red)'),
                                            'info' => __('Info (Blue)'),
                                            'primary' => __('Primary'),
                                        ])
                                        ->default('gray'),
                                    Toggle::make('is_initial')
                                        ->label(__('Initial Status')),
                                    Toggle::make('is_terminal')
                                        ->label(__('Terminal Status')),
                                    TextInput::make('display_order')
                                        ->label(__('Order'))
                                        ->numeric()
                                        ->default(0),
                                ])
                                ->columns(3)
                                ->orderColumn('display_order')
                                ->defaultItems(0)
                                ->addActionLabel(__('Add Status'))
                                ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                                    $data['company_id'] = \App\Services\CompanyContext::activeId();

                                    return $data;
                                }),
                        ]),
                    Tabs\Tab::make(__('Transitions'))
                        ->schema([
                            Repeater::make('transitions')
                                ->relationship()
                                ->schema([
                                    TextInput::make('from_status_key')
                                        ->label(__('From Status Key'))
                                        ->required()
                                        ->maxLength(100),
                                    TextInput::make('to_status_key')
                                        ->label(__('To Status Key'))
                                        ->required()
                                        ->maxLength(100),
                                    TextInput::make('label')
                                        ->label(__('Button Label'))
                                        ->required()
                                        ->maxLength(255),
                                    Select::make('allowed_roles')
                                        ->label(__('Allowed Roles'))
                                        ->multiple()
                                        ->options(CompanyRole::options())
                                        ->required(),
                                    Toggle::make('requires_approval')
                                        ->label(__('Requires Approval'))
                                        ->live(),
                                    Select::make('approval_type')
                                        ->label(__('Approval Type'))
                                        ->options([
                                            'single' => __('Single Approver'),
                                            'parallel' => __('All Must Approve'),
                                            'sequential' => __('Sequential'),
                                        ])
                                        ->visible(fn ($get): bool => (bool) $get('requires_approval')),
                                    Select::make('approver_roles')
                                        ->label(__('Approver Roles'))
                                        ->multiple()
                                        ->options(CompanyRole::options())
                                        ->visible(fn ($get): bool => (bool) $get('requires_approval')),
                                    Textarea::make('confirmation_message')
                                        ->label(__('Confirmation Message'))
                                        ->maxLength(500),
                                    TextInput::make('display_order')
                                        ->label(__('Order'))
                                        ->numeric()
                                        ->default(0),
                                ])
                                ->columns(2)
                                ->defaultItems(0)
                                ->addActionLabel(__('Add Transition'))
                                ->mutateRelationshipDataBeforeCreateUsing(function (array $data): array {
                                    $data['company_id'] = \App\Services\CompanyContext::activeId();

                                    return $data;
                                }),
                        ]),
                ])
                ->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return static::modernTable($table)
            ->columns([
                TextColumn::make('document_type')
                    ->label(__('Document Type'))
                    ->formatStateUsing(fn (WorkflowDocumentType $state): string => $state->label())
                    ->sortable(),
                TextColumn::make('name')
                    ->label(__('Name'))
                    ->searchable()
                    ->sortable(),
                TextColumn::make('statuses_count')
                    ->label(__('Statuses'))
                    ->counts('statuses')
                    ->sortable(),
                IconColumn::make('is_active')
                    ->label(__('Active'))
                    ->boolean(),
                IconColumn::make('is_default')
                    ->label(__('Default'))
                    ->boolean(),
            ])
            ->actions(static::getModernTableActions());
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListWorkflowDefinitions::route('/'),
            'create' => Pages\CreateWorkflowDefinition::route('/create'),
            'edit' => Pages\EditWorkflowDefinition::route('/{record}/edit'),
        ];
    }

    public static function getEloquentQuery(): \Illuminate\Database\Eloquent\Builder
    {
        return parent::getEloquentQuery();
    }
}
