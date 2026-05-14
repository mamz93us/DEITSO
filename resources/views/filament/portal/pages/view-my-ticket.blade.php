<x-filament-panels::page>
    @php
        $ticket = $this->record;
        $comments = $this->getComments();
        $myId = auth()->id();
        $stateLabel = $ticket->state?->label() ?? '—';
        $stateValue = $ticket->state?->getValue() ?? 'new';
        $stateColor = match (true) {
            in_array($stateValue, ['resolved', 'closed'], true) => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200',
            in_array($stateValue, ['waiting_customer'], true) => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
            in_array($stateValue, ['in_progress', 'assigned'], true) => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200',
            in_array($stateValue, ['cancelled'], true) => 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
            default => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-200',
        };
        $priorityColor = match ($ticket->priority) {
            'urgent' => 'bg-red-100 text-red-800 dark:bg-red-900/40 dark:text-red-200',
            'high' => 'bg-orange-100 text-orange-800 dark:bg-orange-900/40 dark:text-orange-200',
            'low' => 'bg-gray-100 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
            default => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200',
        };
    @endphp

    <div class="space-y-6">
        {{-- Header card --}}
        <div class="rounded-xl bg-white p-5 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex flex-wrap items-start justify-between gap-3">
                <div class="space-y-1">
                    <div class="flex items-center gap-2 text-sm text-gray-500 dark:text-gray-400">
                        <span class="font-mono">{{ $ticket->code }}</span>
                        <span>·</span>
                        <span>opened {{ $ticket->created_at?->diffForHumans() }}</span>
                    </div>
                    <h2 class="text-xl font-semibold text-gray-950 dark:text-white">{{ $ticket->subject }}</h2>
                </div>
                <div class="flex flex-wrap gap-2">
                    <span class="inline-flex items-center rounded-md px-2.5 py-1 text-xs font-medium {{ $stateColor }}">
                        {{ $stateLabel }}
                    </span>
                    <span class="inline-flex items-center rounded-md px-2.5 py-1 text-xs font-medium {{ $priorityColor }}">
                        {{ ucfirst($ticket->priority) }}
                    </span>
                </div>
            </div>

            @if ($ticket->description)
                <div class="mt-4 whitespace-pre-line rounded-lg bg-gray-50 p-3 text-sm text-gray-700 dark:bg-gray-800 dark:text-gray-300">
                    {{ $ticket->description }}
                </div>
            @endif
        </div>

        {{-- Conversation timeline --}}
        <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="border-b border-gray-200 px-5 py-3 text-sm font-medium text-gray-700 dark:border-white/10 dark:text-gray-300">
                Conversation
            </div>

            <div class="max-h-[60vh] space-y-3 overflow-y-auto p-5" id="ticket-chat-stream">
                @forelse ($comments as $c)
                    @php $mine = $c->user_id === $myId; @endphp
                    <div class="flex {{ $mine ? 'justify-end' : 'justify-start' }}">
                        <div class="max-w-[85%] sm:max-w-[70%]">
                            <div class="mb-1 flex items-center gap-2 text-xs text-gray-500 dark:text-gray-400 {{ $mine ? 'justify-end' : '' }}">
                                <span class="font-medium text-gray-700 dark:text-gray-200">
                                    {{ $mine ? 'You' : ($c->user?->name ?? 'Support') }}
                                </span>
                                @if ($c->author_type === \App\Models\TicketComment::AUTHOR_SYSTEM)
                                    <span class="rounded bg-gray-100 px-1.5 py-0.5 text-[10px] uppercase tracking-wide text-gray-600 dark:bg-gray-700 dark:text-gray-300">System</span>
                                @elseif ($c->author_type === \App\Models\TicketComment::AUTHOR_STAFF)
                                    <span class="rounded bg-indigo-100 px-1.5 py-0.5 text-[10px] uppercase tracking-wide text-indigo-700 dark:bg-indigo-900/50 dark:text-indigo-200">Support</span>
                                @endif
                                <span>·</span>
                                <span>{{ $c->created_at?->diffForHumans() }}</span>
                            </div>

                            <div class="rounded-2xl px-4 py-2 text-sm whitespace-pre-line {{ $mine ? 'bg-indigo-600 text-white' : 'bg-gray-100 text-gray-900 dark:bg-gray-800 dark:text-gray-100' }}">
                                {{ $c->body }}
                            </div>

                            @php $media = $c->getMedia('attachments'); @endphp
                            @if ($media->count())
                                <div class="mt-1 flex flex-wrap gap-1 {{ $mine ? 'justify-end' : '' }}">
                                    @foreach ($media as $m)
                                        <a href="{{ $m->getUrl() }}" target="_blank" rel="noopener"
                                           class="inline-flex items-center gap-1 rounded-md bg-white px-2 py-1 text-xs text-gray-700 ring-1 ring-gray-300 hover:bg-gray-50 dark:bg-gray-800 dark:text-gray-200 dark:ring-white/10 dark:hover:bg-gray-700">
                                            <x-heroicon-o-paper-clip class="h-3 w-3" />
                                            {{ \Illuminate\Support\Str::limit($m->file_name, 30) }}
                                        </a>
                                    @endforeach
                                </div>
                            @endif
                        </div>
                    </div>
                @empty
                    <div class="py-10 text-center text-sm text-gray-500 dark:text-gray-400">
                        No messages yet. Send the first reply below.
                    </div>
                @endforelse
            </div>

            {{-- Compose box --}}
            <div class="border-t border-gray-200 p-5 dark:border-white/10">
                <form wire:submit="send" class="space-y-3">
                    {{ $this->form }}

                    <div class="flex justify-end">
                        <x-filament::button type="submit" icon="heroicon-o-paper-airplane">
                            Send
                        </x-filament::button>
                    </div>
                </form>
            </div>
        </div>
    </div>

    <script>
        document.addEventListener('livewire:init', () => {
            const scroll = () => {
                const el = document.getElementById('ticket-chat-stream');
                if (el) el.scrollTop = el.scrollHeight;
            };
            scroll();
            Livewire.on('comment-added', () => setTimeout(scroll, 50));
        });
    </script>
</x-filament-panels::page>
