<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\StockMovementResource\Pages;
use App\Models\StockMovement;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class StockMovementResource extends Resource
{
    protected static ?string $model = StockMovement::class;

    protected static ?string $navigationIcon = 'heroicon-o-arrows-right-left';
    protected static ?string $navigationGroup = 'Pharmacy';
    protected static ?int $navigationSort = 33;

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('occurred_at')->dateTime()->sortable(),
                Tables\Columns\TextColumn::make('type')->badge()->sortable(),
                Tables\Columns\TextColumn::make('drug.generic_name')->label('Drug')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('batch.batch_number')->label('Batch')->toggleable(),
                Tables\Columns\TextColumn::make('quantity')->sortable(),
                Tables\Columns\TextColumn::make('performedBy.name')->label('By')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('reference_type')->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('reference_id')->toggleable(isToggledHiddenByDefault: true),
            ])
            ->actions([])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListStockMovements::route('/'),
        ];
    }
}
