<x-filament-panels::page>
    @php
        $stats = $this->getStats();
        $tickets = $this->getRecentTickets();
        $requests = $this->getRecentRequests();
        $assets = $this->getMyAssetsList();
        $links = $this->getQuickLinks();
        $user = auth()->user();

        $stateColor = fn (?string $s) => match ($s) {
            'resolved', 'closed', 'fulfilled' => 'bg-green-100 text-green-800 dark:bg-green-900/40 dark:text-green-200',
            'waiting_customer' => 'bg-amber-100 text-amber-800 dark:bg-amber-900/40 dark:text-amber-200',
            'in_progress', 'assigned', 'admin_approved', 'manager_approved' => 'bg-blue-100 text-blue-800 dark:bg-blue-900/40 dark:text-blue-200',
            'cancelled', 'manager_rejected', 'admin_rejected' => 'bg-gray-200 text-gray-700 dark:bg-gray-700 dark:text-gray-300',
            default => 'bg-indigo-100 text-indigo-800 dark:bg-indigo-900/40 dark:text-indigo-200',
        };
    @endphp

    <div class="space-y-6">
        {{-- Welcome banner with quick CTAs --}}
        <div class="rounded-2xl bg-gradient-to-br from-indigo-600 via-indigo-500 to-blue-500 p-6 text-white shadow-lg">
            <div class="flex flex-wrap items-center justify-between gap-4">
                <div>
                    <div class="text-xs uppercase tracking-wider opacity-80">Self-service portal</div>
                    <h2 class="mt-1 text-2xl font-bold">Hi, {{ $user?->name ?? 'there' }}</h2>
                    <p class="mt-1 max-w-xl text-sm opacity-90">
                        Raise a support ticket, request new equipment, or check on your assigned assets — all in one place.
                    </p>
                </div>

                <div class="flex flex-wrap gap-2">
                    <a href="{{ $links['new_ticket'] }}"
                       class="inline-flex items-center gap-2 rounded-lg bg-white px-4 py-2 text-sm font-semibold text-indigo-700 shadow-sm transition hover:bg-indigo-50">
                        <x-heroicon-o-plus class="h-4 w-4" />
                        New ticket
                    </a>
                    <a href="{{ $links['new_request'] }}"
                       class="inline-flex items-center gap-2 rounded-lg bg-indigo-700/40 px-4 py-2 text-sm font-semibold text-white ring-1 ring-white/30 transition hover:bg-indigo-700/60">
                        <x-heroicon-o-inbox-arrow-down class="h-4 w-4" />
                        New request
                    </a>
                </div>
            </div>
        </div>

        {{-- KPI tiles --}}
        <div class="grid grid-cols-1 gap-4 sm:grid-cols-2 lg:grid-cols-4">
            <a href="{{ $links['all_tickets'] }}"
               class="group rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 transition hover:shadow-md dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center justify-between">
                    <div class="rounded-lg bg-indigo-100 p-2 text-indigo-700 dark:bg-indigo-900/40 dark:text-indigo-300">
                        <x-heroicon-o-ticket class="h-5 w-5" />
                    </div>
                    <span class="text-xs text-gray-500 group-hover:text-indigo-600 dark:text-gray-400">View →</span>
                </div>
                <div class="mt-3 text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['open_tickets'] }}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Open tickets</div>
            </a>

            <a href="{{ $links['all_requests'] }}"
               class="group rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 transition hover:shadow-md dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center justify-between">
                    <div class="rounded-lg bg-amber-100 p-2 text-amber-700 dark:bg-amber-900/40 dark:text-amber-300">
                        <x-heroicon-o-inbox-arrow-down class="h-5 w-5" />
                    </div>
                    <span class="text-xs text-gray-500 group-hover:text-amber-600 dark:text-gray-400">View →</span>
                </div>
                <div class="mt-3 text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['pending_requests'] }}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Pending requests</div>
            </a>

            <a href="{{ $links['all_assets'] }}"
               class="group rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 transition hover:shadow-md dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center justify-between">
                    <div class="rounded-lg bg-emerald-100 p-2 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                        <x-heroicon-o-cpu-chip class="h-5 w-5" />
                    </div>
                    <span class="text-xs text-gray-500 group-hover:text-emerald-600 dark:text-gray-400">View →</span>
                </div>
                <div class="mt-3 text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['my_assets'] }}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">My assets</div>
            </a>

            <div class="rounded-xl bg-white p-4 shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center justify-between">
                    <div class="rounded-lg bg-green-100 p-2 text-green-700 dark:bg-green-900/40 dark:text-green-300">
                        <x-heroicon-o-check-circle class="h-5 w-5" />
                    </div>
                    <span class="text-xs text-gray-500 dark:text-gray-400">{{ now()->format('M') }}</span>
                </div>
                <div class="mt-3 text-3xl font-bold text-gray-900 dark:text-white">{{ $stats['resolved_this_month'] }}</div>
                <div class="text-sm text-gray-500 dark:text-gray-400">Resolved this month</div>
            </div>
        </div>

        {{-- Two-column: Recent tickets + Recent requests --}}
        <div class="grid grid-cols-1 gap-4 lg:grid-cols-2">
            {{-- Recent tickets --}}
            <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-3 dark:border-white/10">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Recent tickets</h3>
                    <a href="{{ $links['all_tickets'] }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">View all →</a>
                </div>

                @if ($tickets->count())
                    <ul class="divide-y divide-gray-100 dark:divide-white/5">
                        @foreach ($tickets as $t)
                            @php $stateVal = $t->state?->getValue() ?? 'new'; @endphp
                            <li>
                                <a href="{{ \App\Filament\Portal\Resources\MyTicketResource::getUrl('view', ['record' => $t]) }}"
                                   class="flex items-center justify-between px-5 py-3 transition hover:bg-gray-50 dark:hover:bg-gray-800">
                                    <div class="min-w-0 flex-1">
                                        <div class="flex items-center gap-2">
                                            <span class="font-mono text-xs text-gray-500 dark:text-gray-400">{{ $t->code }}</span>
                                            <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-medium {{ $stateColor($stateVal) }}">
                                                {{ $t->state?->label() ?? '—' }}
                                            </span>
                                        </div>
                                        <div class="mt-1 truncate text-sm text-gray-800 dark:text-gray-100">{{ $t->subject }}</div>
                                    </div>
                                    <span class="ml-3 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">
                                        {{ $t->created_at?->diffForHumans(syntax: \Carbon\CarbonInterface::DIFF_ABSOLUTE, short: true) }} ago
                                    </span>
                                </a>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="px-5 py-8 text-center">
                        <x-heroicon-o-ticket class="mx-auto h-8 w-8 text-gray-400" />
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No tickets yet.</p>
                        <a href="{{ $links['new_ticket'] }}" class="mt-2 inline-block text-sm font-medium text-indigo-600 hover:text-indigo-800">Raise your first ticket</a>
                    </div>
                @endif
            </div>

            {{-- Recent requests --}}
            <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
                <div class="flex items-center justify-between border-b border-gray-200 px-5 py-3 dark:border-white/10">
                    <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">Recent requests</h3>
                    <a href="{{ $links['all_requests'] }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">View all →</a>
                </div>

                @if ($requests->count())
                    <ul class="divide-y divide-gray-100 dark:divide-white/5">
                        @foreach ($requests as $r)
                            @php $stateVal = $r->state?->getValue() ?? 'submitted'; @endphp
                            <li class="flex items-center justify-between px-5 py-3">
                                <div class="min-w-0 flex-1">
                                    <div class="flex items-center gap-2">
                                        <span class="font-mono text-xs text-gray-500 dark:text-gray-400">{{ $r->code }}</span>
                                        <span class="inline-flex items-center rounded px-1.5 py-0.5 text-[10px] font-medium {{ $stateColor($stateVal) }}">
                                            {{ $r->state?->label() ?? '—' }}
                                        </span>
                                    </div>
                                    <div class="mt-1 truncate text-sm text-gray-800 dark:text-gray-100">{{ $r->title }}</div>
                                </div>
                                <span class="ml-3 whitespace-nowrap text-xs text-gray-500 dark:text-gray-400">
                                    {{ $r->created_at?->diffForHumans(syntax: \Carbon\CarbonInterface::DIFF_ABSOLUTE, short: true) }} ago
                                </span>
                            </li>
                        @endforeach
                    </ul>
                @else
                    <div class="px-5 py-8 text-center">
                        <x-heroicon-o-inbox-arrow-down class="mx-auto h-8 w-8 text-gray-400" />
                        <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No requests yet.</p>
                        <a href="{{ $links['new_request'] }}" class="mt-2 inline-block text-sm font-medium text-indigo-600 hover:text-indigo-800">Submit a request</a>
                    </div>
                @endif
            </div>
        </div>

        {{-- My assets grid --}}
        <div class="rounded-xl bg-white shadow-sm ring-1 ring-gray-950/5 dark:bg-gray-900 dark:ring-white/10">
            <div class="flex items-center justify-between border-b border-gray-200 px-5 py-3 dark:border-white/10">
                <h3 class="text-sm font-semibold text-gray-700 dark:text-gray-200">My assigned assets</h3>
                <a href="{{ $links['all_assets'] }}" class="text-xs font-medium text-indigo-600 hover:text-indigo-800 dark:text-indigo-400">View all →</a>
            </div>

            @if ($assets->count())
                <div class="grid grid-cols-1 gap-3 p-5 sm:grid-cols-2 lg:grid-cols-3">
                    @foreach ($assets as $a)
                        <div class="rounded-lg border border-gray-200 p-3 transition hover:border-indigo-300 hover:bg-gray-50 dark:border-white/10 dark:hover:border-indigo-700 dark:hover:bg-gray-800">
                            <div class="flex items-start justify-between">
                                <div class="rounded-md bg-emerald-50 p-2 text-emerald-700 dark:bg-emerald-900/40 dark:text-emerald-300">
                                    <x-heroicon-o-cpu-chip class="h-5 w-5" />
                                </div>
                                <span class="font-mono text-xs text-gray-500 dark:text-gray-400">{{ $a->code }}</span>
                            </div>
                            <div class="mt-2 truncate text-sm font-medium text-gray-900 dark:text-white">
                                {{ $a->name ?: ($a->assetModel?->model_name ?? '—') }}
                            </div>
                            <div class="mt-0.5 truncate text-xs text-gray-500 dark:text-gray-400">
                                {{ $a->category?->name ?? '—' }}
                                @if ($a->serial_number)
                                    · S/N {{ $a->serial_number }}
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            @else
                <div class="px-5 py-8 text-center">
                    <x-heroicon-o-cpu-chip class="mx-auto h-8 w-8 text-gray-400" />
                    <p class="mt-2 text-sm text-gray-500 dark:text-gray-400">No assets assigned to you yet.</p>
                </div>
            @endif
        </div>
    </div>
</x-filament-panels::page>
