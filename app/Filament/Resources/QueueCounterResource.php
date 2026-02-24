<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\QueueCounterResource\Pages;
use App\Models\QueueCounter;
use App\Models\QueueDepartment;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class QueueCounterResource extends Resource
{
    protected static ?string $model = QueueCounter::class;

    protected static ?string $navigationIcon = 'heroicon-o-rectangle-stack';
    protected static ?string $navigationGroup = 'Queue';
    protected static ?int $navigationSort = 11;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make('Counter')
                    ->schema([
                        Forms\Components\Select::make('queue_department_id')
                            ->label('Department')
                            ->required()
                            ->options(fn () => QueueDepartment::query()->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable(),
                        Forms\Components\TextInput::make('name')->required()->maxLength(255),
                        Forms\Components\TextInput::make('code')
                            ->required()
                            ->maxLength(20)
                            ->regex('/^[A-Z0-9\-]+$/')
                            ->dehydrateStateUsing(fn ($state) => strtoupper((string) $state)),
                        Forms\Components\TextInput::make('floor')
                            ->numeric()
                            ->minValue(0)
                            ->maxValue(99)
                            ->nullable(),
                        Forms\Components\Toggle::make('is_active')->default(true),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->columns([
                Tables\Columns\TextColumn::make('department.name')->label('Department')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('name')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('code')->sortable()->searchable(),
                Tables\Columns\TextColumn::make('floor')->sortable(),
                Tables\Columns\IconColumn::make('is_active')->boolean()->sortable(),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('queue_department_id')
                    ->label('Department')
                    ->options(fn () => QueueDepartment::query()->orderBy('name')->pluck('name', 'id')->all()),
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
            'index' => Pages\ListQueueCounters::route('/'),
            'create' => Pages\CreateQueueCounter::route('/create'),
            'edit' => Pages\EditQueueCounter::route('/{record}/edit'),
        ];
    }
}
