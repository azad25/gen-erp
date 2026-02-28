<?php

namespace App\Filament\Resources;

use App\Filament\Resources\LeaveRequestResource\Pages;
use App\Filament\Resources\BaseResource;
use App\Filament\Support\FormStyles;
use App\Filament\Support\TableStyles;
use App\Models\Employee;
use App\Models\LeaveRequest;
use App\Models\LeaveType;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class LeaveRequestResource extends BaseResource
{
    protected static ?string $model = LeaveRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-clipboard-document-list';

    protected static ?string $navigationGroup = 'HR & Payroll';

    protected static ?int $navigationSort = 3;

    public static function getNavigationLabel(): string
    {
        return __('Leave Requests');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Select::make('employee_id')->label(__entity('employee'))->options(Employee::pluck('first_name', 'id'))->searchable()->required(),
            Select::make('leave_type_id')->label(__('Leave Type'))->options(LeaveType::pluck('name', 'id'))->searchable()->required(),
            DatePicker::make('from_date')->label(__('From'))->required(),
            DatePicker::make('to_date')->label(__('To'))->required(),
            TextInput::make('total_days')->label(__('Total Days'))->numeric()->required(),
            Textarea::make('reason')->label(__('Reason'))->maxLength(2000),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('employee.first_name')->label(__entity('employee'))->formatStateUsing(fn (LeaveRequest $r) => $r->employee?->fullName())->searchable(),
            TextColumn::make('leaveType.name')->label(__('Type'))->sortable(),
            TextColumn::make('from_date')->label(__('From'))->date('d M Y')->sortable(),
            TextColumn::make('to_date')->label(__('To'))->date('d M Y'),
            TextColumn::make('total_days')->label(__('Days')),
            TextColumn::make('status')->label(__('Status'))->badge(),
        ])->defaultSort('created_at', 'desc')->actions(static::getModernTableActions());
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListLeaveRequests::route('/'),
            'create' => Pages\CreateLeaveRequest::route('/create'),
            'edit' => Pages\EditLeaveRequest::route('/{record}/edit'),
        ];
    }
}
