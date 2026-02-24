<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\DrugResource\Pages;
use App\Models\Drug;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class DrugResource extends Resource
{
    protected static ?string $model = Drug::class;

    protected static ?string $navigationIcon = 'heroicon-o-beaker';
    protected static ?string $navigationGroup = 'Pharmacy';
    protected static ?int $navigationSort = 30;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Drug')
                    ->schema([
                        Forms\Components\TextInput::make('generic_name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('brand_name')
                            ->maxLength(255)
                            ->nullable(),
                        Forms\Components\TextInput::make('formulation')
                            ->maxLength(50)
                            ->nullable()
                            ->helperText('tablet, capsule, syrup, injection, ...'),
                        Forms\Components\TextInput::make('strength')
                            ->maxLength(50)
                            ->nullable()
                            ->helperText('500mg, 5mg/5ml, ...'),
                        Forms\Components\TextInput::make('unit')
                            ->maxLength(30)
                            ->default('pcs')
                            ->helperText('pcs, ml, vial, ...'),
                        Forms\Components\TextInput::make('barcode')
                            ->maxLength(100)
                            ->nullable()
                            ->unique(ignoreRecord: true),
                        Forms\Components\TextInput::make('reorder_level')
                            ->numeric()
                            ->minValue(0)
                            ->default(0),
                        Forms\Components\Toggle::make('is_active')
                            ->default(true),
                        Forms\Components\Textarea::make('notes')
                            ->columnSpanFull()
                            ->nullable(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('generic_name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('brand_name')->searchable()->sortable()->toggleable(),
                Tables\Columns\TextColumn::make('strength')->toggleable(),
                Tables\Columns\TextColumn::make('formulation')->toggleable(),
                Tables\Columns\TextColumn::make('barcode')->searchable()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('total_on_hand')
                    ->label('On hand')
                    ->state(fn (Drug $record) => (int) ($record->batches_sum_quantity_on_hand ?? $record->total_on_hand)),
                Tables\Columns\TextColumn::make('reorder_level')->toggleable(),
                Tables\Columns\IconColumn::make('is_active')->boolean()->sortable(),
                Tables\Columns\TextColumn::make('created_at')->since()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\TernaryFilter::make('is_active'),
            ])
            ->modifyQueryUsing(fn ($query) => $query->withSum(
                ['batches as batches_sum_quantity_on_hand' => function ($q) {
                    $q->where('is_active', true)
                        ->where(function ($q2) {
                            $q2->whereNull('expiry_date')
                                ->orWhere('expiry_date', '>=', now()->toDateString());
                        });
                }],
                'quantity_on_hand'
            ))
            ->actions([
                Tables\Actions\EditAction::make(),
            ])
            ->bulkActions([]);
    }

    public static function getRelations(): array
    {
        return [];
    }

    public static function getPages(): array
    {
        return [
            'index' => Pages\ListDrugs::route('/'),
            'create' => Pages\CreateDrug::route('/create'),
            'edit' => Pages\EditDrug::route('/{record}/edit'),
        ];
    }
}
