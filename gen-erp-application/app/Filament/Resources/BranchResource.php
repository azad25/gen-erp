<?php

namespace App\Filament\Resources;

use App\Filament\Resources\BranchResource\Pages;
use App\Models\Branch;
use App\Models\Employee;
use App\Models\Warehouse;
use Filament\Forms\Components\Section;
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

class BranchResource extends Resource
{
    protected static ?string $model = Branch::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-storefront';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?int $navigationSort = 20;

    public static function getNavigationLabel(): string
    {
        return __('Branches');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make(__('Branch Details'))->schema([
                TextInput::make('name')->label(__('Name'))->required()->maxLength(255),
                TextInput::make('code')->label(__('Code'))->required()->maxLength(50),
                Textarea::make('address')->label(__('Address'))->maxLength(2000),
                TextInput::make('city')->label(__('City'))->maxLength(100),
                TextInput::make('district')->label(__('District'))->maxLength(100),
                TextInput::make('phone')->label(__('Phone'))->maxLength(20),
                TextInput::make('email')->label(__('Email'))->email()->maxLength(255),
            ])->columns(2),
            Section::make(__('Settings'))->schema([
                Select::make('manager_id')->label(__('Manager'))->options(Employee::pluck('first_name', 'id'))->searchable()->nullable(),
                Select::make('warehouse_id')->label(__('Primary Warehouse'))->options(Warehouse::pluck('name', 'id'))->searchable()->nullable(),
                Toggle::make('is_headquarters')->label(__('Headquarters')),
                Toggle::make('is_active')->label(__('Active'))->default(true),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label(__('Name'))->searchable()->sortable(),
            TextColumn::make('code')->label(__('Code'))->searchable(),
            TextColumn::make('city')->label(__('City')),
            TextColumn::make('manager.first_name')->label(__('Manager')),
            TextColumn::make('warehouse.name')->label(__('Warehouse')),
            IconColumn::make('is_headquarters')->label(__('HQ'))->boolean(),
            IconColumn::make('is_active')->label(__('Active'))->boolean(),
            TextColumn::make('users_count')->label(__('Members'))->counts('users'),
        ])->actions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListBranches::route('/'),
            'create' => Pages\CreateBranch::route('/create'),
            'edit' => Pages\EditBranch::route('/{record}/edit'),
        ];
    }
}
