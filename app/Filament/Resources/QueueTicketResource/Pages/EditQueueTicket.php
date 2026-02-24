<?php

declare(strict_types=1);

namespace App\Filament\Resources\QueueTicketResource\Pages;

use App\Filament\Resources\QueueTicketResource;
use Filament\Actions;
use Filament\Resources\Pages\EditRecord;

class EditQueueTicket extends EditRecord
{
    protected static string $resource = QueueTicketResource::class;

    protected function getHeaderActions(): array
    {
        return [
            // Delete disabled — tickets are historical records tied to queue audit trail.
        ];
    }
}
