@php
  use App\Models\Company;
  use App\Models\Department;
  use App\Models\SupportNotification;
  use App\Models\Ticket;
  use App\Models\User;
  use App\Models\MailboxMessage;
  use App\Models\SlaRecord;
  use App\Enums\UserType;
  use App\Services\CustomerSatisfactionService;
  use App\Services\ReportService;

  $user = auth()->user();
  $summary = app(ReportService::class)->dashboard($user);
  $companyId = $user->company_id ?? Company::query()->value('id');
  $satisfaction = $companyId ? app(CustomerSatisfactionService::class)->dashboard((int) $companyId) : ['average_csat' => 0, 'nps_score' => 0, 'responses' => 0];
  
  $recentTickets = Ticket::query()
    ->with(['department:id,name', 'assignedAgent:id,name', 'priority'])
    ->when($user->company_id !== null, fn ($query) => $query->where('company_id', $user->company_id))
    ->latest()
    ->limit(6)
    ->get();

  $unreadMessages = MailboxMessage::query()
    ->where('recipient_id', $user->id)
    ->whereNull('read_at')
    ->latest()
    ->limit(3)
    ->get();

  $unreadNotifications = SupportNotification::query()
    ->where('recipient_id', $user->id)
    ->whereNull('read_at')
    ->latest()
    ->limit(3)
    ->get();

  $slaRisks = SlaRecord::query()
    ->when($user->company_id !== null, fn ($query) => $query->where('company_id', $user->company_id))
    ->whereNull('resolved_at')
    ->where('resolution_due_at', '>', now())
    ->where('resolution_due_at', '<', now()->addHours(24))
    ->with('slable')
    ->latest('resolution_due_at')
    ->limit(3)
    ->get();

  $csatPercent = min(100, max(0, (($satisfaction['average_csat'] ?? 0) / 5) * 100));
  $workload = $summary['agent_workload'] ?? [];
  arsort($workload);
  $workload = array_slice($workload, 0, 5, true);
@endphp

<x-layouts::app :title="__('Dashboard')">
  @if ($user->user_type !== UserType::Customer)
    <div class="flex flex-col gap-6">
      <x-page-header
        :title="__('Dashboard')"
        :description="__('Operational intelligence and realtime workload overview.')"
      />

      {{-- KPI Grid --}}
      <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-4">
        <x-stat-card
          :label="__('Open tickets')"
          :value="$summary['open_tickets'] ?? 0"
          icon="ticket"
          accent="blue"
        />
        <x-stat-card
          :label="__('Overdue')"
          :value="$summary['overdue_tickets'] ?? 0"
          icon="exclamation-circle"
          accent="red"
        />
        <x-stat-card
          :label="__('SLA Risk')"
          :value="$summary['sla_risk_count'] ?? 0"
          icon="clock"
          accent="amber"
        />
        <x-stat-card
          :label="__('Unread Mail')"
          :value="$summary['mailbox_unread_count'] ?? 0"
          icon="envelope"
          accent="indigo"
        />
      </div>

      <div class="grid gap-5 xl:grid-cols-3">
        {{-- Main Column --}}
        <div class="flex flex-col gap-5 xl:col-span-2">
          {{-- Recent tickets --}}
          <x-section-card :heading="__('Recent activity')" icon="bolt">
            <x-slot:headerAction>
              <flux:button size="sm" variant="ghost" :href="route('tickets.index')" wire:navigate>
                {{ __('View all') }}
              </flux:button>
            </x-slot:headerAction>

            <div class="flex flex-col divide-y divide-zinc-100 dark:divide-zinc-800">
              @forelse ($recentTickets as $ticket)
                <a
                  href="{{ route('tickets.show', $ticket) }}"
                  wire:navigate
                  class="group flex flex-col gap-2 py-3 first:pt-0 last:pb-0 transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/30"
                >
                  <div class="flex items-center justify-between gap-4">
                    <div class="flex items-center gap-2.5">
                      <span class="text-xs font-semibold text-zinc-500 bg-zinc-100 px-1.5 py-0.5 rounded-md dark:bg-zinc-800 dark:text-zinc-400">
                        {{ $ticket->ticket_number }}
                      </span>
                      <x-status-badge :status="$ticket->status->value" />
                      @if($ticket->priority)
                        <span class="text-[10px] font-semibold uppercase tracking-wide text-zinc-400">
                          {{ $ticket->priority->name }}
                        </span>
                      @endif
                    </div>
                    <span class="text-xs text-zinc-400">{{ $ticket->created_at->diffForHumans() }}</span>
                  </div>
                  <div class="flex items-center justify-between gap-4">
                    <h4 class="truncate text-sm font-medium text-zinc-800 group-hover:text-blue-600 dark:text-zinc-200 dark:group-hover:text-blue-400 transition-colors">
                      {{ $ticket->title }}
                    </h4>
                    <div class="flex items-center gap-2 shrink-0">
                      <div class="flex -space-x-1">
                        @if($ticket->assignedAgent)
                          <flux:avatar size="xs" :name="$ticket->assignedAgent->name" />
                        @else
                          <div class="h-5 w-5 rounded-full border border-zinc-200 bg-zinc-100 dark:border-zinc-700 dark:bg-zinc-800 flex items-center justify-center">
                            <flux:icon name="user" size="xs" class="text-zinc-400" />
                          </div>
                        @endif
                      </div>
                      <span class="text-xs text-zinc-500">{{ $ticket->assignedAgent?->name ?? __('Unassigned') }}</span>
                    </div>
                  </div>
                </a>
              @empty
                <x-empty-state icon="ticket" :heading="__('No tickets yet.')" class="py-10" />
              @endforelse
            </div>
          </x-section-card>

          {{-- Workload & SLA Risks --}}
          <div class="grid gap-5 md:grid-cols-2">
            <x-section-card :heading="__('Top workload')" icon="users">
              <div class="space-y-4">
                @forelse($workload as $agent => $count)
                  <div class="space-y-1.5">
                    <div class="flex justify-between text-xs font-medium">
                      <span class="text-zinc-700 dark:text-zinc-300">{{ $agent }}</span>
                      <span class="text-zinc-500">{{ $count }} {{ __('tickets') }}</span>
                    </div>
                    <div class="h-1.5 w-full rounded-full bg-zinc-100 dark:bg-zinc-800">
                      <div class="h-full rounded-full bg-blue-500" style="width: {{ ($count / (max($workload) ?: 1)) * 100 }}%"></div>
                    </div>
                  </div>
                @empty
                  <p class="text-center text-sm text-zinc-500 py-4">{{ __('No assignments yet.') }}</p>
                @endforelse
              </div>
            </x-section-card>

            <x-section-card :heading="__('SLA risk')" icon="clock">
              <div class="space-y-3">
                @forelse($slaRisks as $risk)
                  <a href="{{ route('tickets.show', $risk->slable_id) }}" wire:navigate class="flex items-start gap-3 rounded-lg border border-red-100 bg-red-50/30 p-2.5 transition-colors hover:bg-red-50 dark:border-red-900/30 dark:bg-red-900/10 dark:hover:bg-red-900/20">
                    <div class="mt-0.5 rounded bg-red-500 p-1 text-white">
                      <flux:icon name="bolt" size="xs" />
                    </div>
                    <div class="min-w-0 flex-1">
                      <p class="truncate text-xs font-bold text-red-700 dark:text-red-400">{{ $risk->slable?->title ?? __('Unknown') }}</p>
                      <p class="text-[10px] font-medium text-red-600/80 dark:text-red-500/80">
                        {{ __('Breaches in') }} {{ $risk->resolution_due_at->diffForHumans(null, true) }}
                      </p>
                    </div>
                  </a>
                @empty
                  <div class="flex flex-col items-center justify-center py-6 text-center">
                    <flux:icon name="check-circle" class="text-emerald-500 mb-2" />
                    <p class="text-sm font-medium text-zinc-500">{{ __('All clear! No immediate risks.') }}</p>
                  </div>
                @endforelse
              </div>
            </x-section-card>
          </div>
        </div>

        {{-- Sidebar --}}
        <div class="flex flex-col gap-5">
          {{-- Mailbox & Notifications --}}
          <div class="flex flex-col gap-3">
            <x-section-card :heading="__('Latest messages')" icon="envelope">
              <div class="space-y-3">
                @forelse($unreadMessages as $msg)
                  <a href="{{ route('mailbox.show', $msg) }}" wire:navigate class="group block">
                    <div class="flex items-center justify-between gap-2">
                      <span class="truncate text-xs font-bold text-zinc-800 dark:text-zinc-200 group-hover:text-blue-600 transition-colors">{{ $msg->subject }}</span>
                      <span class="shrink-0 text-[10px] text-zinc-400">{{ $msg->created_at->shortRelativeDiffForHumans() }}</span>
                    </div>
                    <p class="mt-0.5 line-clamp-1 text-[11px] text-zinc-500">{{ Str::limit(strip_tags($msg->body), 50) }}</p>
                  </a>
                @empty
                  <p class="text-center text-xs text-zinc-500 py-2">{{ __('No unread messages.') }}</p>
                @endforelse
              </div>
            </x-section-card>

            <x-section-card :heading="__('Notifications')" icon="bell">
              <div class="space-y-3">
                @forelse($unreadNotifications as $notif)
                  <div class="flex gap-3">
                    <div class="mt-0.5 h-1.5 w-1.5 shrink-0 rounded-full bg-blue-500"></div>
                    <div class="min-w-0">
                      <p class="text-[11px] font-medium leading-relaxed text-zinc-700 dark:text-zinc-300">{{ $notif->content }}</p>
                      <span class="text-[10px] text-zinc-400">{{ $notif->created_at->diffForHumans() }}</span>
                    </div>
                  </div>
                @empty
                  <p class="text-center text-xs text-zinc-500 py-2">{{ __('No new notifications.') }}</p>
                @endforelse
              </div>
            </x-section-card>
          </div>

          {{-- CSAT --}}
          <x-section-card :heading="__('Satisfaction')" icon="star">
            <div class="flex items-baseline justify-between gap-2">
              <span class="text-2xl font-semibold text-zinc-900 dark:text-white">
                {{ round((float) ($satisfaction['average_csat'] ?? 0), 1) }}<span class="text-sm font-medium text-zinc-400">/5</span>
              </span>
              <div class="flex flex-col items-end">
                <span class="text-xs font-semibold text-emerald-600 dark:text-emerald-400">NPS: {{ $satisfaction['nps_score'] ?? 0 }}</span>
                <span class="text-[10px] text-zinc-400">{{ $satisfaction['responses'] ?? 0 }} {{ __('responses') }}</span>
              </div>
            </div>

            <div class="mt-3 h-1.5 overflow-hidden rounded-full bg-zinc-100 dark:bg-zinc-800">
              <div
                class="h-full rounded-full transition-all duration-700 {{ $csatPercent >= 80 ? 'bg-emerald-500' : ($csatPercent >= 50 ? 'bg-amber-500' : 'bg-red-500') }}"
                style="width: {{ $csatPercent }}%"
              ></div>
            </div>

            <div class="mt-1.5 flex items-center justify-between text-[10px] font-semibold uppercase tracking-wide text-zinc-400">
              <span>{{ __('Poor') }}</span>
              <span>{{ __('Excellent') }}</span>
            </div>
          </x-section-card>

          {{-- Workspace health --}}
          <x-section-card :heading="__('Quick stats')" icon="server">
            <div class="grid grid-cols-2 gap-3">
              <div class="rounded-lg border border-zinc-100 p-2.5 dark:border-zinc-800">
                <span class="block text-[10px] font-semibold uppercase tracking-wide text-zinc-400">{{ __('Companies') }}</span>
                <span class="text-base font-semibold text-zinc-900 dark:text-white">{{ Company::query()->count() }}</span>
              </div>
              <div class="rounded-lg border border-zinc-100 p-2.5 dark:border-zinc-800">
                <span class="block text-[10px] font-semibold uppercase tracking-wide text-zinc-400">{{ __('Users') }}</span>
                <span class="text-base font-semibold text-zinc-900 dark:text-white">
                  {{ User::query()->when($user->company_id !== null, fn ($query) => $query->where('company_id', $user->company_id))->count() }}
                </span>
              </div>
            </div>
          </x-section-card>
        </div>
      </div>
    </div>
  @else
    <div class="flex flex-col items-center justify-center py-20 text-center">
      <flux:heading size="xl" class="font-semibold">{{ __('Welcome to your Dashboard') }}</flux:heading>
      <flux:text class="mt-2 text-lg font-medium text-zinc-500">{{ __('Access your tickets, complaints, and more using the navigation menu.') }}</flux:text>
      <flux:button variant="primary" class="mt-8" :href="route('portal.dashboard')" wire:navigate>{{ __('Go to Portal') }}</flux:button>
    </div>
  @endif
</x-layouts::app>
