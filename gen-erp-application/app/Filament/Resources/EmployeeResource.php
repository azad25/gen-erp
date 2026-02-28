<?php

namespace App\Filament\Resources;

use App\Enums\EmployeeStatus;
use App\Enums\EmploymentType;
use App\Filament\Resources\EmployeeResource\Pages;
use App\Models\Department;
use App\Models\Designation;
use App\Models\Employee;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\FileUpload;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Tabs;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class EmployeeResource extends Resource
{
    protected static ?string $model = Employee::class;

    protected static ?string $navigationIcon = 'heroicon-o-users';

    protected static ?string $navigationGroup = 'HR & Payroll';

    protected static ?int $navigationSort = 1;

    public static function getNavigationLabel(): string
    {
        return __entity('employee', plural: true);
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Tabs::make('Employee')->tabs([
                Tabs\Tab::make(__('Personal'))->schema([
                    TextInput::make('first_name')->label(__('First Name'))->required()->maxLength(100),
                    TextInput::make('last_name')->label(__('Last Name'))->required()->maxLength(100),
                    TextInput::make('name_bangla')->label(__('Name (Bangla)'))->maxLength(255),
                    DatePicker::make('date_of_birth')->label(__('Date of Birth')),
                    Select::make('gender')->label(__('Gender'))->options([
                        'male' => __('Male'),
                        'female' => __('Female'),
                        'other' => __('Other'),
                    ]),
                    TextInput::make('nid_number')->label(__('NID Number'))->maxLength(20),
                    TextInput::make('phone')->label(__('Phone'))->maxLength(20),
                    TextInput::make('email')->label(__('Email'))->email()->maxLength(255),
                    Textarea::make('address')->label(__('Address'))->maxLength(2000),
                    TextInput::make('emergency_contact_name')->label(__('Emergency Contact'))->maxLength(255),
                    TextInput::make('emergency_contact_phone')->label(__('Emergency Phone'))->maxLength(20),
                    FileUpload::make('photo_url')->label(__('Photo'))->image()->directory('employee-photos'),
                ]),
                Tabs\Tab::make(__('Employment'))->schema([
                    TextInput::make('employee_code')->label(__('Employee Code'))->maxLength(50)->helperText(__('Auto-generated if left blank')),
                    Select::make('department_id')->label(__('Department'))->options(Department::active()->pluck('name', 'id'))->searchable(),
                    Select::make('designation_id')->label(__('Designation'))->options(Designation::active()->pluck('name', 'id'))->searchable(),
                    DatePicker::make('joining_date')->label(__('Joining Date'))->required(),
                    DatePicker::make('confirmation_date')->label(__('Confirmation Date')),
                    Select::make('employment_type')->label(__('Employment Type'))->options(EmploymentType::options())->required(),
                    Select::make('status')->label(__('Status'))->options(EmployeeStatus::options())->required(),
                ]),
                Tabs\Tab::make(__('Salary'))->schema([
                    TextInput::make('basic_salary')->label(__('Basic Salary (৳)'))->numeric()->prefix('৳'),
                    TextInput::make('gross_salary')->label(__('Gross Salary (৳)'))->numeric()->prefix('৳'),
                    TextInput::make('bank_name')->label(__('Bank Name'))->maxLength(255),
                    TextInput::make('bank_account_number')->label(__('Account Number'))->maxLength(100),
                    TextInput::make('bank_routing_number')->label(__('Routing Number'))->maxLength(50),
                    TextInput::make('bkash_number')->label(__('bKash Number'))->maxLength(20),
                    TextInput::make('tin_number')->label(__('TIN Number'))->maxLength(20),
                ]),
            ])->columnSpanFull(),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                TextColumn::make('employee_code')->label(__('Code'))->searchable()->sortable(),
                TextColumn::make('first_name')->label(__('Name'))->formatStateUsing(fn (Employee $r) => $r->fullName())->searchable(),
                TextColumn::make('department.name')->label(__('Department'))->sortable(),
                TextColumn::make('designation.name')->label(__('Designation'))->sortable(),
                TextColumn::make('employment_type')->label(__('Type'))->formatStateUsing(fn (EmploymentType $state) => $state->label())->badge()->color(fn (EmploymentType $state) => $state->color()),
                TextColumn::make('status')->label(__('Status'))->formatStateUsing(fn (EmployeeStatus $state) => $state->label())->badge()->color(fn (EmployeeStatus $state) => $state->color()),
                TextColumn::make('joining_date')->label(__('Joined'))->date('d M Y')->sortable(),
                TextColumn::make('gross_salary')->label(__('Gross'))->formatStateUsing(fn (int $state) => '৳'.number_format($state / 100, 2))->sortable(),
            ])
            ->defaultSort('employee_code')
            ->filters([
                SelectFilter::make('department_id')->label(__('Department'))->options(Department::active()->pluck('name', 'id')),
                SelectFilter::make('status')->options(EmployeeStatus::options()),
                SelectFilter::make('employment_type')->options(EmploymentType::options()),
            ])
            ->actions([ViewAction::make(), EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListEmployees::route('/'),
            'create' => Pages\CreateEmployee::route('/create'),
            'edit' => Pages\EditEmployee::route('/{record}/edit'),
        ];
    }
}
