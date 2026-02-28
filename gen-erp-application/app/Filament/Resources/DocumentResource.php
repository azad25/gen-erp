<?php

namespace App\Filament\Resources;

use App\Models\Document;
use App\Models\DocumentFolder;
use App\Services\CompanyContext;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;
use Illuminate\Database\Eloquent\Builder;

class DocumentResource extends Resource
{
    protected static ?string $model = Document::class;

    protected static ?string $navigationIcon = 'heroicon-o-cloud-arrow-up';

    protected static ?string $navigationGroup = 'File Cloud';

    protected static ?int $navigationSort = 1;

    protected static ?string $navigationLabel = 'Documents';

    public static function getEloquentQuery(): Builder
    {
        return parent::getEloquentQuery()->withoutGlobalScopes();
    }

    public static function form(Form $form): Form
    {
        return $form->schema([
            Forms\Components\Section::make(__('File Details'))->schema([
                Forms\Components\FileUpload::make('file')
                    ->label(__('Upload File'))
                    ->required()
                    ->maxSize(10240) // 10MB
                    ->disk('local')
                    ->directory('temp-uploads')
                    ->visibility('private')
                    ->hiddenOn('edit'),

                Forms\Components\TextInput::make('name')
                    ->label(__('File Name'))
                    ->required()
                    ->maxLength(500)
                    ->visibleOn('edit'),

                Forms\Components\Select::make('folder_id')
                    ->label(__('Folder'))
                    ->options(fn () => DocumentFolder::query()->pluck('name', 'id'))
                    ->nullable()
                    ->searchable()
                    ->placeholder(__('Root (No folder)')),

                Forms\Components\Textarea::make('description')
                    ->label(__('Description'))
                    ->maxLength(1000)
                    ->rows(2),
            ])->columns(1),

            Forms\Components\Section::make(__('Storage Info'))
                ->schema([
                    Forms\Components\Placeholder::make('storage_used')
                        ->label(__('Storage Used'))
                        ->content(function () {
                            if (! CompanyContext::hasActive()) {
                                return '—';
                            }
                            $service = app(\App\Services\DocumentService::class);
                            $companyId = CompanyContext::activeId();
                            $percent = $service->storageUsagePercent($companyId);
                            $remaining = $service->storageRemaining($companyId);

                            return "{$percent}% used — {$remaining} remaining";
                        }),
                ])
                ->visibleOn('create'),
        ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('name')
                    ->label(__('File'))
                    ->searchable()
                    ->sortable()
                    ->limit(40)
                    ->tooltip(fn (Document $record) => $record->name),

                Tables\Columns\TextColumn::make('mime_type')
                    ->label(__('Type'))
                    ->badge()
                    ->formatStateUsing(fn (string $state) => strtoupper(
                        last(explode('/', $state))
                    ))
                    ->sortable(),

                Tables\Columns\TextColumn::make('size_bytes')
                    ->label(__('Size'))
                    ->formatStateUsing(fn (Document $record) => $record->formattedSize())
                    ->sortable(),

                Tables\Columns\TextColumn::make('folder.name')
                    ->label(__('Folder'))
                    ->placeholder('—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('documentable_type')
                    ->label(__('Attached To'))
                    ->formatStateUsing(fn (?string $state) => $state ? class_basename($state) : '—')
                    ->sortable(),

                Tables\Columns\TextColumn::make('uploader.name')
                    ->label(__('Uploaded By'))
                    ->sortable(),

                Tables\Columns\TextColumn::make('uploaded_at')
                    ->label(__('Date'))
                    ->dateTime('d M Y H:i')
                    ->sortable(),
            ])
            ->defaultSort('uploaded_at', 'desc')
            ->filters([
                Tables\Filters\SelectFilter::make('mime_category')
                    ->label(__('File Type'))
                    ->options([
                        'image' => __('Images'),
                        'application/pdf' => __('PDFs'),
                        'video' => __('Videos'),
                        'text' => __('Text'),
                    ])
                    ->query(function (Builder $query, array $data): Builder {
                        if (! $data['value']) {
                            return $query;
                        }

                        return $query->where('mime_type', 'like', "{$data['value']}%");
                    }),
                Tables\Filters\SelectFilter::make('folder_id')
                    ->label(__('Folder'))
                    ->options(fn () => DocumentFolder::query()->pluck('name', 'id'))
                    ->placeholder(__('All')),
            ])
            ->actions([
                Tables\Actions\Action::make('download')
                    ->label(__('Download'))
                    ->icon('heroicon-o-arrow-down-tray')
                    ->url(fn (Document $record) => $record->signedUrl())
                    ->openUrlInNewTab(),
                Tables\Actions\EditAction::make(),
                Tables\Actions\DeleteAction::make(),
            ])
            ->bulkActions([
                Tables\Actions\BulkActionGroup::make([
                    Tables\Actions\DeleteBulkAction::make(),
                ]),
            ]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => DocumentResource\Pages\ListDocuments::route('/'),
            'create' => DocumentResource\Pages\CreateDocument::route('/create'),
            'edit' => DocumentResource\Pages\EditDocument::route('/edit/{record}'),
        ];
    }
}
