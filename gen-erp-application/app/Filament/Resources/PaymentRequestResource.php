<?php

namespace App\Filament\Resources;

use App\Filament\Resources\PaymentRequestResource\Pages;
use App\Models\PaymentRequest;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class PaymentRequestResource extends Resource
{
    protected static ?string $model = PaymentRequest::class;

    protected static ?string $navigationIcon = 'heroicon-o-credit-card';

    protected static ?string $navigationGroup = 'Subscription';

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Payment Details')
                    ->schema([
                        Forms\Components\Select::make('plan_id')
                            ->relationship('plan', 'name')
                            ->required()
                            ->label('Plan'),
                        Forms\Components\Select::make('billing_cycle')
                            ->options([
                                'monthly' => 'Monthly',
                                'annual' => 'Annual',
                            ])
                            ->required()
                            ->default('monthly')
                            ->label('Billing Cycle'),
                        Forms\Components\TextInput::make('amount')
                            ->numeric()
                            ->required()
                            ->label('Amount (BDT)'),
                        Forms\Components\Select::make('method')
                            ->options([
                                'bkash' => 'bKash',
                                'nagad' => 'Nagad',
                                'rocket' => 'Rocket',
                                'bank' => 'Bank Transfer',
                            ])
                            ->required()
                            ->label('Payment Method'),
                        Forms\Components\TextInput::make('transaction_id')
                            ->label('Transaction ID'),
                        Forms\Components\FileUpload::make('screenshot_path')
                            ->image()
                            ->label('Payment Screenshot'),
                    ])
                    ->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('company.name')
                    ->label('Company')
                    ->searchable(),
                Tables\Columns\TextColumn::make('plan.name')
                    ->label('Plan')
                    ->searchable(),
                Tables\Columns\TextColumn::make('amount')
                    ->label('Amount')
                    ->formatStateUsing(fn (PaymentRequest $record): string => $record->formattedAmount()),
                Tables\Columns\BadgeColumn::make('status')
                    ->colors([
                        'warning' => 'pending',
                        'success' => 'verified',
                        'danger' => 'rejected',
                    ]),
                Tables\Columns\TextColumn::make('method')
                    ->label('Method')
                    ->searchable(),
                Tables\Columns\TextColumn::make('transaction_id')
                    ->label('Transaction ID')
                    ->searchable(),
                Tables\Columns\TextColumn::make('created_at')
                    ->label('Submitted')
                    ->dateTime(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options([
                        'pending' => 'Pending',
                        'verified' => 'Verified',
                        'rejected' => 'Rejected',
                    ]),
            ])
            ->actions([
                Tables\Actions\Action::make('verify')
                    ->icon('heroicon-o-check-circle')
                    ->color('success')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('admin_note')
                            ->label('Admin Note (Optional)'),
                    ])
                    ->action(function (PaymentRequest $record, array $data) {
                        app(\App\Services\SubscriptionService::class)->verifyPayment(
                            $record,
                            auth()->id(),
                            $data['admin_note'] ?? null
                        );
                    })
                    ->visible(fn (PaymentRequest $record): bool => $record->status === 'pending'),
                Tables\Actions\Action::make('reject')
                    ->icon('heroicon-o-x-circle')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->form([
                        Forms\Components\Textarea::make('admin_note')
                            ->label('Rejection Reason')
                            ->required(),
                    ])
                    ->action(function (PaymentRequest $record, array $data) {
                        app(\App\Services\SubscriptionService::class)->rejectPayment(
                            $record,
                            auth()->id(),
                            $data['admin_note']
                        );
                    })
                    ->visible(fn (PaymentRequest $record): bool => $record->status === 'pending'),
                Tables\Actions\ViewAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListPaymentRequests::route('/'),
            'view' => Pages\ViewPaymentRequest::route('/{record}'),
        ];
    }
}
