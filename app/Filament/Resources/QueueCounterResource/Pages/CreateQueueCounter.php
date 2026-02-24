<?php

declare(strict_types=1);

namespace App\Filament\Resources\QueueCounterResource\Pages;

use App\Filament\Resources\QueueCounterResource;
use Filament\Resources\Pages\CreateRecord;

class CreateQueueCounter extends CreateRecord
{
    protected static string $resource = QueueCounterResource::class;
}
