<div wire:poll.2s>
    <div class="flex items-start justify-between gap-4">
        <div>
            <h1 class="text-3xl font-bold text-white">{{ __('queue.display_title') }}</h1>
            <p class="text-gray-400 mt-1">{{ __('queue.department') }}: <span class="text-gray-200 font-semibold">{{ $department->name }}</span> ({{ $department->code }})</p>
        </div>
        <div class="text-right">
            <div class="text-gray-400 text-sm">{{ now()->format('Y-m-d') }}</div>
            <div class="text-gray-200 font-semibold">{{ now()->format('h:i:s A') }}</div>
        </div>
    </div>

    <div class="grid grid-cols-1 lg:grid-cols-3 gap-4 mt-6">
        <section class="lg:col-span-1 bg-gray-900 border border-gray-800 rounded-2xl p-4">
            <h2 class="text-lg font-semibold text-white">{{ __('queue.now_serving') }}</h2>

            <div class="mt-3 space-y-3">
                @forelse ($this->nowServing as $ticket)
                    <div class="rounded-xl border border-gray-800 bg-gray-950 p-4">
                        <div class="flex items-center justify-between">
                            <div class="text-gray-400 text-sm">
                                {{ $ticket->counter?->code ?? '—' }}
                            </div>
                            <div class="text-gray-500 text-xs">
                                {{ __('queue.called') }} {{ optional($ticket->called_at)->diffForHumans() }}
                            </div>
                        </div>
                        <div class="mt-2 text-4xl font-extrabold text-sky-400 tracking-wider">
                            {{ $ticket->token_display }}
                        </div>
                    </div>
                @empty
                    <div class="text-gray-500 text-sm">{{ __('queue.no_tokens_called') }}</div>
                @endforelse
            </div>
        </section>

        <section class="lg:col-span-2 bg-gray-900 border border-gray-800 rounded-2xl p-4">
            <div class="flex items-center justify-between">
                <h2 class="text-lg font-semibold text-white">{{ __('queue.waiting') }}</h2>
                <div class="text-gray-500 text-sm">{{ __('queue.showing_next', ['count' => $this->waiting->count()]) }}</div>
            </div>

            <div class="mt-3 grid grid-cols-2 sm:grid-cols-3 md:grid-cols-4 lg:grid-cols-5 gap-3">
                @forelse ($this->waiting as $ticket)
                    <div class="rounded-xl border border-gray-800 bg-gray-950 p-3 text-center">
                        <div class="text-2xl font-bold text-gray-100">{{ $ticket->token_display }}</div>
                        <div class="text-xs text-gray-500">#{{ $ticket->token_number }}</div>
                    </div>
                @empty
                    <div class="text-gray-500 text-sm">{{ __('queue.no_waiting_tokens') }}</div>
                @endforelse
            </div>
        </section>
    </div>
</div>
