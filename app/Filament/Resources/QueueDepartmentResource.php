<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\QueueDepartmentResource\Pages;
use App\Models\QueueDepartment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class QueueDepartmentResource extends Resource
{
    protected static ?string $model = QueueDepartment::class;

    protected static ?string $navigationIcon = 'heroicon-o-building-office-2';
    protected static ?string $navigationGroup = 'Queue';
    protected static ?int $navigationSort = 10;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Department')
                    ->schema([
                        Forms\Components\TextInput::make('name')
                            ->required()
                            ->maxLength(255),
                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->maxLength(20)
                            ->unique(ignoreRecord: true)
                            ->regex('/^[A-Za-z0-9\-]+$/')
                            ->helperText('Uppercase code, e.g. OPD, LAB, PHARM')
                            ->dehydrateStateUsing(fn ($state) => strtoupper((string) $state)),
                        Forms\Components\TextInput::make('floor')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(99)
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
                Tables\Columns\TextColumn::make('name')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('code')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('floor')->sortable(),
                Tables\Columns\IconColumn::make('is_active')->boolean()->sortable(),
                Tables\Columns\TextColumn::make('created_at')->dateTime()->since()->toggleable(isToggledHiddenByDefault: true),
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
            'index' => Pages\ListQueueDepartments::route('/'),
            'create' => Pages\CreateQueueDepartment::route('/create'),
            'edit' => Pages\EditQueueDepartment::route('/{record}/edit'),
        ];
    }
}
