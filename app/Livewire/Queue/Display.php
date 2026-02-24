<?php

declare(strict_types=1);

namespace App\Livewire\Queue;

use App\Models\QueueDepartment;
use App\Models\QueueTicket;
use Illuminate\Support\Collection;
use Livewire\Attributes\Locked;
use Livewire\Component;

class Display extends Component
{
    #[Locked]
    public QueueDepartment $department;

    public function mount(QueueDepartment $department): void
    {
        $this->department = $department;
    }

    /**
     * Tickets currently being served/called (latest per counter).
     */
    public function getNowServingProperty(): Collection
    {
        $today = now()->toDateString();

        // Show the most recently called ticket for each counter.
        $called = QueueTicket::query()
            ->with(['counter'])
            ->where('queue_department_id', $this->department->getKey())
            ->whereDate('token_date', $today)
            ->whereIn('status', ['called'])
            ->orderByDesc('called_at')
            ->get();

        return $called
            ->groupBy(fn (QueueTicket $t) => $t->queue_counter_id ?: 'unassigned')
            ->map(fn (Collection $group) => $group->first())
            ->values();
    }

    public function getWaitingProperty(): Collection
    {
        $today = now()->toDateString();

        return QueueTicket::query()
            ->where('queue_department_id', $this->department->getKey())
            ->whereDate('token_date', $today)
            ->where('status', 'waiting')
            ->orderBy('token_number')
            ->limit(60)
            ->get();
    }

    public function render()
    {
        return view('livewire.queue.display');
    }
}
