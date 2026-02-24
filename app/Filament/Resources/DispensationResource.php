<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\DispensationResource\Pages;
use App\Enums\DispensationStatus;
use App\Models\Dispensation;
use App\Services\Pharmacy\PharmacyInventoryService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DispensationResource extends Resource
{
    protected static ?string $model = Dispensation::class;

    protected static ?string $navigationIcon = 'heroicon-o-shopping-bag';
    protected static ?string $navigationGroup = 'Pharmacy';
    protected static ?int $navigationSort = 32;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Dispensation'))
                    ->schema([
                        Forms\Components\Select::make('patient_id')
                            ->relationship('patient', 'mr_number')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        Forms\Components\Select::make('prescription_id')
                            ->relationship('prescription', 'id')
                            ->searchable()
                            ->preload()
                            ->nullable(),
                        Forms\Components\Select::make('status')
                            ->options(DispensationStatus::class)
                            ->default(DispensationStatus::Draft)
                            ->disabled(),
                        Forms\Components\DateTimePicker::make('dispensed_at')
                            ->disabled()
                            ->nullable(),
                        Forms\Components\Select::make('dispensed_by')
                            ->relationship('dispensedBy', 'name')
                            ->disabled()
                            ->nullable(),
                        Forms\Components\Textarea::make('notes')
                            ->columnSpanFull()
                            ->nullable(),
                    ])->columns(2),

                Forms\Components\Section::make(__('Items'))
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('drug_id')
                                    ->relationship('drug', 'generic_name', fn ($query) => $query->where('is_active', true))
                                    ->searchable()
                                    ->preload()
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(fn (Forms\Set $set) => $set('drug_batch_id', null)),
                                Forms\Components\Select::make('drug_batch_id')
                                    ->label('Batch (optional)')
                                    ->options(function (Forms\Get $get) {
                                        $drugId = $get('drug_id');
                                        if (! $drugId) {
                                            return [];
                                        }

                                        return \App\Models\DrugBatch::query()
                                            ->where('drug_id', $drugId)
                                            ->where('is_active', true)
                                            ->where('quantity_on_hand', '>', 0)
                                            ->orderBy('expiry_date')
                                            ->pluck('batch_number', 'id')
                                            ->all();
                                    })
                                    ->searchable()
                                    ->nullable()
                                    ->helperText(__('Leave empty to auto-allocate by FEFO when completing.')),
                                Forms\Components\TextInput::make('quantity')
                                    ->numeric()
                                    ->minValue(1)
                                    ->required()
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                        $set('line_total', ($get('quantity') ?? 0) * ($get('unit_price') ?? 0));
                                    }),
                                Forms\Components\TextInput::make('unit_price')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0)
                                    ->helperText(__('Smallest currency unit (e.g. paisa). Leave 0 to use batch price.'))
                                    ->live()
                                    ->afterStateUpdated(function (Forms\Set $set, Forms\Get $get) {
                                        $set('line_total', ($get('quantity') ?? 0) * ($get('unit_price') ?? 0));
                                    }),
                                Forms\Components\TextInput::make('line_total')
                                    ->numeric()
                                    ->default(0)
                                    ->disabled()
                                    ->dehydrated(true)
                                    ->afterStateHydrated(function (Forms\Components\TextInput $component, Forms\Get $get) {
                                        $component->state(($get('quantity') ?? 0) * ($get('unit_price') ?? 0));
                                    })
                                    ->helperText(__('Auto-calculated: quantity × unit_price')),
                                Forms\Components\Textarea::make('notes')
                                    ->columnSpanFull()
                                    ->nullable(),
                            ])
                            ->columns(2)
                            ->defaultItems(1),
                    ]),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('id')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('patient.mr_number')->label('Patient')->searchable()->toggleable(),
                Tables\Columns\TextColumn::make('status')->badge()->sortable(),
                Tables\Columns\TextColumn::make('dispensed_at')->dateTime()->since()->toggleable(),
                Tables\Columns\TextColumn::make('dispensedBy.name')->label('Dispensed by')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('created_at')->since()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([
                Tables\Actions\Action::make('complete')
                    ->label('Complete')
                    ->icon('heroicon-o-check')
                    ->requiresConfirmation()
                    ->visible(fn (Dispensation $record) => $record->status === DispensationStatus::Draft)
                    ->action(function (Dispensation $record): void {
                        $service = app(PharmacyInventoryService::class);
                        $service->completeDispensation($record, performedBy: auth()->id());

                        Notification::make()
                            ->title(__('Dispensation completed'))
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('void')
                    ->label('Void')
                    ->icon('heroicon-o-arrow-uturn-left')
                    ->color('warning')
                    ->requiresConfirmation()
                    ->visible(fn (Dispensation $record) => $record->status === DispensationStatus::Completed)
                    ->form([
                        Forms\Components\Textarea::make('reason')
                            ->label('Reason')
                            ->required()
                            ->maxLength(1000),
                    ])
                    ->action(function (Dispensation $record, array $data): void {
                        $service = app(PharmacyInventoryService::class);
                        $service->voidCompletedDispensation($record, performedBy: auth()->id(), reason: (string) ($data['reason'] ?? ''));

                        Notification::make()
                            ->title(__('Dispensation voided and stock restored'))
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('cancel')
                    ->label('Cancel')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Dispensation $record) => $record->status === DispensationStatus::Draft)
                    ->action(function (Dispensation $record): void {
                        $record->update(['status' => DispensationStatus::Cancelled]);

                        Notification::make()
                            ->title(__('Dispensation cancelled'))
                            ->success()
                            ->send();
                    }),
                Tables\Actions\EditAction::make()
                    ->visible(fn (Dispensation $record) => $record->status === DispensationStatus::Draft),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('status')
                    ->options(DispensationStatus::class),
            ])
            ->defaultSort('created_at', 'desc')
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDispensations::route('/'),
            'create' => Pages\CreateDispensation::route('/create'),
            'edit' => Pages\EditDispensation::route('/{record}/edit'),
        ];
    }
}
