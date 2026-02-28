<?php

namespace App\Filament\Resources;

use App\Enums\AttendanceStatus;
use App\Filament\Resources\AttendanceResource\Pages;
use App\Models\Attendance;
use App\Models\Employee;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class AttendanceResource extends Resource
{
    protected static ?string $model = Attendance::class;

    protected static ?string $navigationIcon = 'heroicon-o-clock';

    protected static ?string $navigationGroup = 'HR & Payroll';

    protected static ?int $navigationSort = 4;

    public static function getNavigationLabel(): string
    {
        return __('Attendance');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('employee_id')->label(__entity('employee'))->options(Employee::pluck('first_name', 'id'))->searchable()->required(),
            DatePicker::make('attendance_date')->label(__('Date'))->required()->default(now()),
            TextInput::make('check_in')->label(__('Check In'))->type('time'),
            TextInput::make('check_out')->label(__('Check Out'))->type('time'),
            Select::make('status')->label(__('Status'))->options(AttendanceStatus::options())->required(),
            TextInput::make('overtime_hours')->label(__('Overtime Hours'))->numeric()->default(0),
            TextInput::make('notes')->label(__('Notes'))->maxLength(255),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('employee.first_name')->label(__entity('employee'))->formatStateUsing(fn (Attendance $r) => $r->employee?->fullName())->searchable(),
            TextColumn::make('attendance_date')->label(__('Date'))->date('d M Y')->sortable(),
            TextColumn::make('check_in')->label(__('In')),
            TextColumn::make('check_out')->label(__('Out')),
            TextColumn::make('status')->label(__('Status'))->formatStateUsing(fn (AttendanceStatus $state) => $state->label())->badge()->color(fn (AttendanceStatus $state) => $state->color()),
            TextColumn::make('overtime_hours')->label(__('OT')),
        ])->defaultSort('attendance_date', 'desc')
            ->filters([SelectFilter::make('status')->options(AttendanceStatus::options())])
            ->actions([EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListAttendances::route('/'),
            'create' => Pages\CreateAttendance::route('/create'),
            'edit' => Pages\EditAttendance::route('/{record}/edit'),
        ];
    }
}
