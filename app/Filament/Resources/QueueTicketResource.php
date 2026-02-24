<?php

declare(strict_types=1);

namespace App\Filament\Resources;

use App\Filament\Resources\QueueTicketResource\Pages;
use App\Enums\QueueTicketStatus;
use App\Models\QueueCounter;
use App\Models\QueueDepartment;
use App\Models\QueueTicket;
use App\Services\Queue\QueueTokenService;
use Filament\Forms;
use Filament\Forms\Form;
use Filament\Notifications\Notification;
use Filament\Resources\Resource;
use Filament\Tables;
use Filament\Tables\Table;

class QueueTicketResource extends Resource
{
    protected static ?string $model = QueueTicket::class;

    protected static ?string $navigationIcon = 'heroicon-o-ticket';
    protected static ?string $navigationGroup = 'Queue';
    protected static ?int $navigationSort = 12;

    public static function form(Form $form): Form
    {
        return $form
            ->schema([
                Forms\Components\Section::make(__('Ticket'))
                    ->schema([
                        Forms\Components\Select::make('queue_department_id')
                            ->label(__('Department'))
                            ->required()
                            ->options(fn () => QueueDepartment::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all())
                            ->searchable()
                            ->live()
                            ->afterStateUpdated(fn (Forms\Set $set) => $set('queue_counter_id', null)),
                        Forms\Components\Select::make('queue_counter_id')
                            ->label(__('Counter'))
                            ->options(function (Forms\Get $get) {
                                $deptId = $get('queue_department_id');
                                if (! $deptId) {
                                    return [];
                                }

                                return QueueCounter::query()
                                    ->where('queue_department_id', $deptId)
                                    ->where('is_active', true)
                                    ->orderBy('code')
                                    ->pluck('code', 'id')
                                    ->all();
                            })
                            ->searchable()
                            ->nullable(),
                        Forms\Components\TextInput::make('token_display')
                            ->disabled()
                            ->dehydrated(false),
                        Forms\Components\Select::make('status')
                            ->options(QueueTicketStatus::class)
                            ->required()
                            ->disabled(fn (?QueueTicket $record) => $record !== null)
                            ->dehydrated(),
                        Forms\Components\DatePicker::make('token_date')
                            ->required()
                            ->disabled(fn (?QueueTicket $record) => $record !== null)
                            ->dehydrated(),
                        Forms\Components\TextInput::make('token_number')
                            ->numeric()
                            ->required()
                            ->disabled(fn (?QueueTicket $record) => $record !== null)
                            ->dehydrated(),
                    ])->columns(2),
            ]);
    }

    public static function table(Table $table): Table
    {
        return $table
            ->defaultSort('created_at', 'desc')
            ->columns([
                Tables\Columns\TextColumn::make('token_display')->label('Token')->searchable()->sortable(),
                Tables\Columns\TextColumn::make('department.code')->label('Dept')->sortable(),
                Tables\Columns\TextColumn::make('counter.code')->label('Counter')->toggleable(),
                Tables\Columns\TextColumn::make('status')
                    ->badge()
                    ->color(fn ($state): string => $state instanceof QueueTicketStatus ? $state->color() : 'gray')
                    ->sortable(),
                Tables\Columns\TextColumn::make('token_date')->date()->sortable(),
                Tables\Columns\TextColumn::make('token_number')->sortable(),
                Tables\Columns\TextColumn::make('called_at')->dateTime()->since()->toggleable(isToggledHiddenByDefault: true),
                Tables\Columns\TextColumn::make('served_at')->dateTime()->since()->toggleable(isToggledHiddenByDefault: true),
            ])
            ->filters([
                Tables\Filters\SelectFilter::make('queue_department_id')
                    ->label('Department')
                    ->options(fn () => QueueDepartment::query()->orderBy('name')->pluck('name', 'id')->all()),
                Tables\Filters\SelectFilter::make('status')
                    ->options(QueueTicketStatus::class),
            ])
            ->headerActions([
                Tables\Actions\Action::make('issue')
                    ->label('Issue Ticket')
                    ->icon('heroicon-o-plus')
                    ->form([
                        Forms\Components\Select::make('queue_department_id')
                            ->label('Department')
                            ->required()
                            ->options(fn () => QueueDepartment::query()->where('is_active', true)->orderBy('name')->pluck('name', 'id')->all()),
                    ])
                    ->action(function (array $data) {
                        $dept = QueueDepartment::findOrFail($data['queue_department_id']);
                        $ticket = app(QueueTokenService::class)->issueTicket(
                            department: $dept,
                            createdBy: auth()->id(),
                        );

                        Notification::make()
                            ->title(__('Ticket issued: ') . $ticket->token_display)
                            ->success()
                            ->send();
                    }),
            ])
            ->actions([
                Tables\Actions\Action::make('call')
                    ->label('Call')
                    ->requiresConfirmation()
                    ->visible(fn (QueueTicket $record) => $record->status === QueueTicketStatus::Waiting)
                    ->form([
                        Forms\Components\Select::make('queue_counter_id')
                            ->label('Counter')
                            ->options(fn (QueueTicket $record) => QueueCounter::query()
                                ->where('queue_department_id', $record->queue_department_id)
                                ->where('is_active', true)
                                ->orderBy('code')
                                ->pluck('code', 'id')
                                ->all())
                            ->nullable()
                            ->helperText(__('Optional: select a counter for this ticket.')),
                    ])
                    ->action(function (QueueTicket $record, array $data) {
                        $record->forceFill([
                            'status' => QueueTicketStatus::Called,
                            'called_at' => now(),
                            'queue_counter_id' => $data['queue_counter_id'] ?? $record->queue_counter_id,
                        ])->save();
                    }),
                Tables\Actions\Action::make('serve')
                    ->label('Serve')
                    ->requiresConfirmation()
                    ->visible(fn (QueueTicket $record) => in_array($record->status, [QueueTicketStatus::Waiting, QueueTicketStatus::Called], true))
                    ->action(function (QueueTicket $record) {
                        $record->forceFill([
                            'status' => QueueTicketStatus::Served,
                            'served_at' => now(),
                        ])->save();
                    }),
                Tables\Actions\Action::make('noShow')
                    ->label('No-show')
                    ->color('danger')
                    ->requiresConfirmation()
                    ->visible(fn (QueueTicket $record) => in_array($record->status, [QueueTicketStatus::Waiting, QueueTicketStatus::Called], true))
                    ->action(function (QueueTicket $record) {
                        $record->forceFill([
                            'status' => QueueTicketStatus::NoShow,
                            'no_show_at' => now(),
                        ])->save();
                    }),
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
            'index' => Pages\ListQueueTickets::route('/'),
            'edit' => Pages\EditQueueTicket::route('/{record}/edit'),
        ];
    }
}
