<?php

declare(strict_types=1);

namespace App\Filament\Resources\QueueCounterResource\Pages;

use App\Filament\Resources\QueueCounterResource;
use App\Models\QueueTicket;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQueueCounter extends EditRecord
{
    protected static string $resource = QueueCounterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\DeleteAction::make()
                ->visible(fn () => ! QueueTicket::query()
                    ->where('queue_counter_id', $this->record->getKey())
                    ->exists()
                )
                ->before(function () {
                    abort_if(
                        QueueTicket::query()
                            ->where('queue_counter_id', $this->record->getKey())
                            ->exists(),
                        403,
                        __('Cannot delete a counter that has tickets. Deactivate it instead.')
                    );
                }),
        ];
    }
}
