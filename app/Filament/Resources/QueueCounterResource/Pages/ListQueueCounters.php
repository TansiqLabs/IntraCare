<?php

declare(strict_types=1);

namespace App\Filament\Resources\QueueCounterResource\Pages;

use App\Filament\Resources\QueueCounterResource;
use Filament\Actions;
use Filament\Resources\Pages\ListRecords;

class ListQueueCounters extends ListRecords
{
    protected static string $resource = QueueCounterResource::class;

    protected function getHeaderActions(): array
    {
        return [
            Actions\CreateAction::make(),
        ];
    }
}
