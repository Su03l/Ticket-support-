@php
    use App\Models\Company;
    use App\Models\Department;
    use App\Models\SupportNotification;
    use App\Models\Ticket;
    use App\Models\User;
    use App\Enums\UserType;
    use App\Services\CustomerSatisfactionService;
    use App\Services\ReportService;

    $user = auth()->user();
    $summary = app(ReportService::class)->dashboard($user);
    $companyId = $user->company_id ?? Company::query()->value('id');
    $satisfaction = $companyId ? app(CustomerSatisfactionService::class)->dashboard((int) $companyId) : ['average_csat' => 0, 'nps_score' => 0, 'responses' => 0];
    $recentTickets = Ticket::query()
        ->with(['department:id,name', 'assignedAgent:id,name'])
        ->when($user->company_id !== null, fn ($query) => $query->where('company_id', $user->company_id))
        ->latest()
        ->limit(6)
        ->get();

    $csatPercent = min(100, max(0, (($satisfaction['average_csat'] ?? 0) / 5) * 100));
@endphp

<x-layouts::app :title="__('Dashboard')">
    <div class="flex flex-col gap-8">
        <x-page-header
            :title="__('Dashboard')"
            :description="__('Live operational overview for support, companies, and customer satisfaction.')"
        />

        {{-- Stat cards --}}
        <div class="grid gap-4 sm:grid-cols-2 xl:grid-cols-4">
            <x-stat-card
                :label="__('Open tickets')"
                :value="$summary['open_tickets'] ?? 0"
                icon="ticket"
                accent="blue"
            />
            <x-stat-card
                :label="__('SLA breaches')"
                :value="$summary['sla_breaches'] ?? 0"
                icon="bolt"
                accent="red"
            />
            <x-stat-card
                :label="__('Average CSAT')"
                :value="round((float) ($satisfaction['average_csat'] ?? 0), 1) . '/5'"
                icon="star"
                accent="amber"
            />
            <x-stat-card
                :label="__('NPS score')"
                :value="$satisfaction['nps_score'] ?? 0"
                icon="chart-bar"
                accent="emerald"
            />
        </div>

        {{-- Content grid --}}
        <div class="grid gap-6 xl:grid-cols-[1fr_22rem]">
            {{-- Recent tickets --}}
            <x-section-card :heading="__('Recent tickets')" icon="clock">
                <x-slot:headerAction>
                    <flux:button size="sm" variant="ghost" :href="route('tickets.index')" wire:navigate>
                        {{ __('View all') }}
                    </flux:button>
                </x-slot:headerAction>

                <div class="flex flex-col gap-2.5">
                    @forelse ($recentTickets as $ticket)
                        <a
                            href="{{ route('tickets.show', $ticket) }}"
                            wire:navigate
                            class="group grid gap-3 rounded-xl border border-zinc-100/80 bg-zinc-50/50 p-4 transition-all duration-150 hover:border-zinc-200/80 hover:bg-white hover:shadow-[0_2px_8px_0_rgb(0,0,0,0.05)] dark:border-zinc-800/60 dark:bg-zinc-800/30 dark:hover:border-zinc-700 dark:hover:bg-zinc-800/60 md:grid-cols-[1fr_10rem_9rem] md:items-center"
                        >
                            <div class="min-w-0">
                                <div class="flex items-center gap-2">
                                    <span class="text-sm font-semibold text-zinc-800 group-hover:text-zinc-900 dark:text-zinc-200 dark:group-hover:text-white">
                                        {{ $ticket->ticket_number }}
                                    </span>
                                    <x-status-badge :status="$ticket->status->value" />
                                </div>
                                <p class="mt-0.5 truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $ticket->title }}</p>
                            </div>
                            <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">{{ $ticket->department?->name }}</span>
                            <span class="text-xs font-medium text-zinc-400 dark:text-zinc-500">
                                {{ $ticket->assignedAgent?->name ?? __('Unassigned') }}
                            </span>
                        </a>
                    @empty
                        <x-empty-state icon="ticket" :heading="__('No tickets yet.')" class="py-10" />
                    @endforelse
                </div>
            </x-section-card>

            {{-- Right column --}}
            <div class="flex flex-col gap-4">
                {{-- Workspace health --}}
                <x-section-card :heading="__('Workspace health')" icon="heart">
                    <dl class="grid gap-0">
                        @if ($user->user_type !== UserType::Customer)
                            <div class="flex items-center justify-between gap-3 border-b border-zinc-100/80 py-3 first:pt-0 last:border-0 last:pb-0 dark:border-zinc-800/80">
                                <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Companies') }}</dt>
                                <dd class="text-sm font-bold text-zinc-900 dark:text-white">{{ Company::query()->count() }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3 border-b border-zinc-100/80 py-3 last:border-0 last:pb-0 dark:border-zinc-800/80">
                                <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Departments') }}</dt>
                                <dd class="text-sm font-bold text-zinc-900 dark:text-white">
                                    {{ Department::query()->when($user->company_id !== null, fn ($query) => $query->where('company_id', $user->company_id))->count() }}
                                </dd>
                            </div>
                            <div class="flex items-center justify-between gap-3 border-b border-zinc-100/80 py-3 last:border-0 last:pb-0 dark:border-zinc-800/80">
                                <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Users') }}</dt>
                                <dd class="text-sm font-bold text-zinc-900 dark:text-white">
                                    {{ User::query()->when($user->company_id !== null, fn ($query) => $query->where('company_id', $user->company_id))->count() }}
                                </dd>
                            </div>
                        @else
                            <div class="flex items-center justify-between gap-3 border-b border-zinc-100/80 py-3 first:pt-0 last:border-0 last:pb-0 dark:border-zinc-800/80">
                                <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('My tickets') }}</dt>
                                <dd class="text-sm font-bold text-zinc-900 dark:text-white">{{ Ticket::query()->where('customer_id', $user->id)->count() }}</dd>
                            </div>
                            <div class="flex items-center justify-between gap-3 border-b border-zinc-100/80 py-3 last:border-0 last:pb-0 dark:border-zinc-800/80">
                                <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Closed tickets') }}</dt>
                                <dd class="text-sm font-bold text-zinc-900 dark:text-white">
                                    {{ Ticket::query()->where('customer_id', $user->id)->where('status', \App\Enums\TicketStatus::Closed)->count() }}
                                </dd>
                            </div>
                        @endif
                        <div class="flex items-center justify-between gap-3 py-3 last:border-0 last:pb-0">
                            <dt class="text-sm font-medium text-zinc-500 dark:text-zinc-400">{{ __('Unread notifications') }}</dt>
                            <dd>
                                @php $unread = SupportNotification::query()->where('recipient_id', $user->id)->whereNull('read_at')->count(); @endphp
                                @if ($unread > 0)
                                    <span class="rounded-full bg-blue-50 px-2.5 py-0.5 text-xs font-bold text-blue-600 dark:bg-blue-500/10 dark:text-blue-400">{{ $unread }}</span>
                                @else
                                    <span class="text-sm font-bold text-zinc-900 dark:text-white">0</span>
                                @endif
                            </dd>
                        </div>
                    </dl>
                </x-section-card>

                {{-- Customer satisfaction --}}
                <x-section-card :heading="__('Customer satisfaction')" icon="star">
                    <div class="flex items-baseline justify-between gap-2">
                        <span class="text-2xl font-bold text-zinc-900 dark:text-white">
                            {{ round((float) ($satisfaction['average_csat'] ?? 0), 1) }}<span class="text-sm font-medium text-zinc-400">/5</span>
                        </span>
                        <span class="text-xs font-medium text-zinc-500 dark:text-zinc-400">
                            {{ $satisfaction['responses'] ?? 0 }} {{ __('responses') }}
                        </span>
                    </div>

                    <div class="mt-3 h-2 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
                        <div
                            class="h-full rounded-full transition-all duration-500 {{ $csatPercent >= 80 ? 'bg-emerald-500' : ($csatPercent >= 50 ? 'bg-amber-500' : 'bg-red-500') }}"
                            style="width: {{ $csatPercent }}%"
                        ></div>
                    </div>

                    <div class="mt-2 flex items-center justify-between text-[11px] font-medium text-zinc-400 dark:text-zinc-500">
                        <span>{{ __('Poor') }}</span>
                        <span>{{ __('Excellent') }}</span>
                    </div>
                </x-section-card>
            </div>
        </div>
    </div>
</x-layouts::app>
