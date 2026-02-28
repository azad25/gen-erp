<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaveTypeResource\Pages;
use App\Models\LeaveType;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LeaveTypeResource extends Resource
{
    protected static ?string $model = LeaveType::class;

    protected static ?string $navigationIcon = 'heroicon-o-calendar-days';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $navigationParentItem = 'HR Settings';

    protected static ?int $navigationSort = 13;

    public static function getNavigationLabel(): string
    {
        return __('Leave Types');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('name')->label(__('Name'))->required()->maxLength(100),
            TextInput::make('days_per_year')->label(__('Days Per Year'))->numeric()->required(),
            Toggle::make('is_paid')->label(__('Paid Leave'))->default(true),
            Toggle::make('carry_forward')->label(__('Carry Forward')),
            TextInput::make('max_carry_forward_days')->label(__('Max Carry Forward Days'))->numeric()->default(0),
            Toggle::make('requires_approval')->label(__('Requires Approval'))->default(true),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('name')->label(__('Name'))->searchable()->sortable(),
            TextColumn::make('days_per_year')->label(__('Days/Year'))->sortable(),
            IconColumn::make('is_paid')->label(__('Paid'))->boolean(),
            IconColumn::make('carry_forward')->label(__('Carry Forward'))->boolean(),
            IconColumn::make('requires_approval')->label(__('Approval'))->boolean(),
        ])->actions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeaveTypes::route('/'),
            'create' => Pages\CreateLeaveType::route('/create'),
            'edit' => Pages\EditLeaveType::route('/{record}/edit'),
        ];
    }
}
