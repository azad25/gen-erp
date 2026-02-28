<?php

namespace App\Filament\Widgets;

use App\Models\Invoice;
use App\Services\CompanyContext;
use Filament\Tables;
use Filament\Tables\Table;
use Filament\Widgets\TableWidget as BaseWidget;
use Illuminate\Database\Eloquent\Builder;

class RecentInvoicesWidget extends BaseWidget
{
    protected static ?int $sort = 2;
    protected int | string | array $columnSpan = [
        'default' => 'full',
        'xl' => 3,
    ];

    public function table(Table $table): Table
    {
        return $table
            ->query(
                Invoice::query()
                    ->with('customer')
                    ->where('company_id', CompanyContext::active()->id ?? null)
                    ->latest()
                    ->limit(5)
            )
            ->heading('Recent Invoices')
            ->description('Latest billing activity')
            ->columns([
                Tables\Columns\TextColumn::make('invoice_number')
                    ->label('Invoice #')
                    ->fontFamily('mono')
                    ->weight('bold')
                    ->searchable(),
                Tables\Columns\TextColumn::make('customer.name')
                    ->label('Customer')
                    ->placeholder('—'),
                Tables\Columns\TextColumn::make('total_amount')
                    ->label('Amount')
                    ->fontFamily('mono')
                    ->weight('bold')
                    ->formatStateUsing(fn ($state) => '৳' . number_format($state / 100, 2)),
                Tables\Columns\TextColumn::make('due_date')
                    ->label('Due Date')
                    ->date('d M Y')
                    ->fontFamily('mono')
                    ->color(fn ($record) => $record->due_date && $record->due_date->isPast() && in_array($record->status->value ?? 'draft', ['sent', 'partial', 'overdue']) ? 'danger' : 'gray'),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn (string $state): string => match ($state) {
                        'paid' => 'success',
                        'partial', 'sent' => 'warning',
                        'overdue' => 'danger',
                        default => 'gray',
                    })
                    ->formatStateUsing(fn ($state) => ucfirst($state)),
            ])
            ->actions([
                Tables\Actions\Action::make('view')
                    ->url(fn (Invoice $record): string => \App\Filament\Resources\InvoiceResource::getUrl('edit', ['record' => $record]))
                    ->icon('heroicon-m-arrow-right-circle')
                    ->button(),
            ])
            ->paginated(false);
    }
}
