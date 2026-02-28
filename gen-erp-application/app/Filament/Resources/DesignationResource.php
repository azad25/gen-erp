<?php

namespace App\Filament\Resources;

use App\Filament\Resources\DesignationResource\Pages;
use App\Filament\Resources\BaseResource;
use App\Filament\Support\FormStyles;
use App\Filament\Support\TableStyles;
use App\Models\Department;
use App\Models\Designation;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class DesignationResource extends BaseResource
{
    protected static ?string $model = Designation::class;

    protected static ?string $navigationIcon = 'heroicon-o-briefcase';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $navigationParentItem = 'HR Settings';

    protected static ?int $navigationSort = 12;

    public static function getNavigationLabel(): string
    {
        return __('Designations');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')->label(__('Name'))->required()->maxLength(255),
            Select::make('department_id')->label(__('Department'))->options(Department::active()->pluck('name', 'id'))->searchable()->nullable(),
            TextInput::make('grade')->label(__('Grade'))->maxLength(50)->helperText(__('RMG wage board grade (1-7)')),
            Toggle::make('is_active')->label(__('Active'))->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label(__('Name'))->searchable()->sortable(),
            TextColumn::make('department.name')->label(__('Department'))->sortable(),
            TextColumn::make('grade')->label(__('Grade')),
            IconColumn::make('is_active')->label(__('Active'))->boolean(),
        ])->actions(static::getModernTableActions());
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDesignations::route('/'),
            'create' => Pages\CreateDesignation::route('/create'),
            'edit' => Pages\EditDesignation::route('/{record}/edit'),
        ];
    }
}
