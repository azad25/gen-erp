<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DepartmentResource\Pages;
use App\Models\Department;
use App\Models\Employee;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DepartmentResource extends Resource
{
    protected static ?string $model = Department::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $navigationParentItem = 'HR Settings';

    protected static ?int $navigationSort = 11;

    public static function getNavigationLabel(): string
    {
        return __('Departments');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')->label(__('Name'))->required()->maxLength(255),
            TextInput::make('code')->label(__('Code'))->maxLength(50),
            Select::make('parent_id')->label(__('Parent Department'))->options(Department::active()->pluck('name', 'id'))->searchable()->nullable(),
            Select::make('manager_id')->label(__('Manager'))->options(Employee::pluck('first_name', 'id'))->searchable()->nullable(),
            Toggle::make('is_active')->label(__('Active'))->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label(__('Name'))->searchable()->sortable(),
            TextColumn::make('code')->label(__('Code'))->sortable(),
            TextColumn::make('parent.name')->label(__('Parent')),
            TextColumn::make('employees_count')->label(__('Employees'))->counts('employees'),
            IconColumn::make('is_active')->label(__('Active'))->boolean(),
        ])->actions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDepartments::route('/'),
            'create' => Pages\CreateDepartment::route('/create'),
            'edit' => Pages\EditDepartment::route('/{record}/edit'),
        ];
    }
}
