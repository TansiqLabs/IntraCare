<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\DrugBatchResource\Pages;
use App\Models\DrugBatch;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DrugBatchResource extends Resource
{
    protected static ?string $model = DrugBatch::class;

    protected static ?string $navigationIcon = 'heroicon-o-archive-box';
    protected static ?string $navigationGroup = 'Pharmacy';
    protected static ?int $navigationSort = 31;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Batch')
                    ->schema([
                        Forms\Components\Select::make('drug_id')
                            ->relationship('drug', 'generic_name')
                            ->searchable()
                            ->preload()
                            ->required(),
                        Forms\Components\TextInput::make('batch_number')
                            ->required()
                            ->maxLength(60),
                        Forms\Components\DatePicker::make('expiry_date')
                            ->nullable(),
                        Forms\Components\DateTimePicker::make('received_at')
                            ->nullable(),
                        Forms\Components\TextInput::make('quantity_received')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->disabled(fn (?DrugBatch $record) => $record !== null)
                            ->dehydrated(),
                        Forms\Components\TextInput::make('quantity_on_hand')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->disabled()
                            ->dehydrated(false)
                            ->helperText('Managed via Stock Movements. Use stock adjustment for corrections.'),
                        Forms\Components\TextInput::make('unit_cost')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->helperText('Smallest currency unit'),
                        Forms\Components\TextInput::make('sale_price')
                            ->numeric()
                            ->minValue(0)
                            ->default(0)
                            ->helperText('Smallest currency unit'),
                        Forms\Components\TextInput::make('supplier_name')
                            ->maxLength(255)
                            ->nullable(),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('drug.generic_name')
                    ->label('Drug')
                    ->searchable()
                    ->sortable(),
                Tables\Columns\TextColumn::make('batch_number')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('expiry_date')->date()->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('quantity_on_hand')->label('On hand')->sortable(),
                Tables\Columns\TextColumn::make('sale_price')->sortable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\IconColumn::make('is_active')->boolean()->sortable(),
                Tables\Columns\TextColumn::make('created_at')->since()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->actions([
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
            'index' => Pages\ListDrugBatches::route('/'),
            'create' => Pages\CreateDrugBatch::route('/create'),
            'edit' => Pages\EditDrugBatch::route('/{record}/edit'),
        ];
    }
}
