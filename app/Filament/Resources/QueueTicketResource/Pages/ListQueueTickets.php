<?php

declare(strict_types=1);

namespace App\Filament\Resources\QueueTicketResource\Pages;

use App\Filament\Resources\QueueTicketResource;
use Filament\Resources\Pages\ListRecords;

class ListQueueTickets extends ListRecords
{
    protected static string $resource = QueueTicketResource::class;
}
