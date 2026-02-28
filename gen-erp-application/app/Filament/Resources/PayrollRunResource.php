<?php

namespace App\Filament\Resources;

use App\Enums\PayrollRunStatus;
use App\Filament\Resources\BaseResource;
use App\Filament\Support\FormStyles;
use App\Filament\Support\TableStyles;
use App\Filament\Resources\PayrollRunResource\Pages;
use App\Models\PayrollRun;
use Filament\Forms\Components\DatePicker;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\Textarea;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Actions\ViewAction;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Filters\SelectFilter;
use Filament\Tables\Table;

class PayrollRunResource extends BaseResource
{
    protected static ?string $model = PayrollRun::class;

    protected static ?string $navigationIcon = 'heroicon-o-banknotes';

    protected static ?string $navigationGroup = 'HR & Payroll';

    protected static ?int $navigationSort = 5;

    public static function getNavigationLabel(): string
    {
        return __('Payroll Runs');
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            TextInput::make('run_number')->label(__('Run Number'))->disabled(),
            Select::make('period_month')->label(__('Month'))->options(collect(range(1, 12))->mapWithKeys(fn (int $m) => [$m => Carbon\Carbon::create(null, $m)->format('F')]))->required(),
            TextInput::make('period_year')->label(__('Year'))->numeric()->required()->default(now()->year),
            DatePicker::make('payment_date')->label(__('Payment Date')),
            Textarea::make('notes')->label(__('Notes'))->maxLength(2000),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('run_number')->label(__('Run #'))->searchable()->sortable(),
            TextColumn::make('period_month')->label(__('Period'))->formatStateUsing(fn (PayrollRun $r) => Carbon\Carbon::create(null, $r->period_month)->format('M').' '.$r->period_year),
            TextColumn::make('total_employees')->label(__('Employees'))->sortable(),
            TextColumn::make('total_gross')->label(__('Gross'))->formatStateUsing(fn (int $state) => '৳'.number_format($state / 100, 2))->sortable(),
            TextColumn::make('total_net')->label(__('Net'))->formatStateUsing(fn (int $state) => '৳'.number_format($state / 100, 2))->sortable(),
            TextColumn::make('total_tax')->label(__('Tax'))->formatStateUsing(fn (int $state) => '৳'.number_format($state / 100, 2)),
            TextColumn::make('status')->label(__('Status'))->formatStateUsing(fn (PayrollRunStatus $state) => $state->label())->badge()->color(fn (PayrollRunStatus $state) => $state->color()),
        ])->defaultSort('created_at', 'desc')
            ->filters([SelectFilter::make('status')->options(PayrollRunStatus::options())])
            ->actions([ViewAction::make(), EditAction::make()]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPayrollRuns::route('/'),
            'create' => Pages\CreatePayrollRun::route('/create'),
            'edit' => Pages\EditPayrollRun::route('/{record}/edit'),
        ];
    }
}
