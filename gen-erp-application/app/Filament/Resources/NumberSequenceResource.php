<?php

namespace App\Filament\Resources;

use App\Filament\Resources\NumberSequenceResource\Pages;
use App\Filament\Resources\BaseResource;
use App\Filament\Support\FormStyles;
use App\Filament\Support\TableStyles;
use App\Models\NumberSequence;
use Filament\Forms\Components\Section;
use Filament\Forms\Components\Select;
use Filament\Forms\Components\TextInput;
use Filament\Forms\Components\Toggle;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables\Actions\EditAction;
use Filament\Tables\Columns\IconColumn;
use Filament\Tables\Columns\TextColumn;
use Filament\Tables\Table;

class NumberSequenceResource extends BaseResource
{
    protected static ?string $model = NumberSequence::class;

    protected static ?string $navigationIcon = 'heroicon-o-hashtag';

    protected static ?string $navigationGroup = 'Settings';

    protected static ?string $navigationLabel = 'Document Numbering';

    protected static ?int $navigationSort = 25;

    public static function form(Form $form): Form
    {
        return $form->schema([
            Section::make(__('Sequence Configuration'))->schema([
                TextInput::make('document_type')->label(__('Document Type'))->required()->maxLength(100)->disabled(),
                TextInput::make('prefix')->label(__('Prefix'))->maxLength(20),
                TextInput::make('suffix')->label(__('Suffix'))->maxLength(20),
                TextInput::make('separator')->label(__('Separator'))->maxLength(5)->default('-'),
                TextInput::make('padding')->label(__('Padding'))->numeric()->minValue(1)->maxValue(10)->default(4),
                TextInput::make('next_number')->label(__('Next Number'))->numeric()->minValue(1)->default(1),
                Select::make('reset_frequency')->label(__('Reset Frequency'))
                    ->options(['never' => __('Never'), 'yearly' => __('Yearly'), 'monthly' => __('Monthly')])->default('never'),
                Toggle::make('include_date')->label(__('Include Date')),
                TextInput::make('date_format')->label(__('Date Format'))->maxLength(20)->placeholder('Ymd'),
            ])->columns(2),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table->columns([
            TextColumn::make('document_type')->label(__('Document Type'))->searchable()->sortable(),
            TextColumn::make('prefix')->label(__('Prefix')),
            TextColumn::make('next_number')->label(__('Next #'))->sortable(),
            TextColumn::make('reset_frequency')->label(__('Reset'))->badge(),
            IconColumn::make('include_date')->label(__('Date'))->boolean(),
        ])->actions(static::getModernTableActions());
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListNumberSequences::route('/'),
            'edit' => Pages\EditNumberSequence::route('/{record}/edit'),
        ];
    }
}
