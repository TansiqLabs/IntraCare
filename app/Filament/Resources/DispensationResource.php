<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\DispensationResource\Pages;
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
                Forms\Components\Section::make('Dispensation')
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
                            ->options([
                                'draft' => 'Draft',
                                'completed' => 'Completed',
                                'cancelled' => 'Cancelled',
                            ])
                            ->default('draft')
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

                Forms\Components\Section::make('Items')
                    ->schema([
                        Forms\Components\Repeater::make('items')
                            ->relationship()
                            ->schema([
                                Forms\Components\Select::make('drug_id')
                                    ->relationship('drug', 'generic_name')
                                    ->searchable()
                                    ->preload()
                                    ->required(),
                                Forms\Components\Select::make('drug_batch_id')
                                    ->label('Batch (optional)')
                                    ->relationship('batch', 'batch_number')
                                    ->searchable()
                                    ->nullable()
                                    ->helperText('Leave empty to auto-allocate by FEFO when completing.'),
                                Forms\Components\TextInput::make('quantity')
                                    ->numeric()
                                    ->minValue(1)
                                    ->required(),
                                Forms\Components\TextInput::make('unit_price')
                                    ->numeric()
                                    ->minValue(0)
                                    ->default(0)
                                    ->helperText('Smallest currency unit (e.g. paisa). Leave 0 to use batch price.'),
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
                    ->visible(fn (Dispensation $record) => $record->status === 'draft')
                    ->action(function (Dispensation $record): void {
                        $service = app(PharmacyInventoryService::class);
                        $service->completeDispensation($record, performedBy: auth()->id());

                        Notification::make()
                            ->title('Dispensation completed')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\Action::make('cancel')
                    ->label('Cancel')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (Dispensation $record) => $record->status === 'draft')
                    ->action(function (Dispensation $record): void {
                        $record->update(['status' => 'cancelled']);

                        Notification::make()
                            ->title('Dispensation cancelled')
                            ->success()
                            ->send();
                    }),
                Tables\Actions\EditAction::make(),
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
            'index' => Pages\ListDispensations::route('/'),
            'create' => Pages\CreateDispensation::route('/create'),
            'edit' => Pages\EditDispensation::route('/{record}/edit'),
        ];
    }
}
