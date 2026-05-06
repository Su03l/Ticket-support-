<?php

use App\Enums\TicketStatus;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\TicketPriority;
use App\Models\User;
use App\Services\TicketService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Tickets')] class extends Component
{
    use AuthorizesRequests, WithPagination;

    public string $status = '';

    public string $priorityId = '';

    public string $departmentId = '';

    public string $assignedToId = '';

    public string $search = '';

    public string $viewMode = 'table';

    public function mount(): void
    {
        $this->authorize('viewAny', Ticket::class);
    }

    public function updated($property): void
    {
        if (in_array($property, ['status', 'priorityId', 'departmentId', 'assignedToId', 'search'], true)) {
            $this->resetPage();
        }
    }

    public function moveTicket(string $id, int $position, string $status, TicketService $tickets): void
    {
        $ticket = $tickets->viewTicket(Auth::user(), (int) $id);
        $targetStatus = TicketStatus::from($status);

        $this->authorize(in_array($targetStatus, [TicketStatus::Closed, TicketStatus::Cancelled], true) ? 'close' : 'reopen', $ticket);

        if ($ticket->status !== $targetStatus) {
            if ($ticket->status === TicketStatus::Closed && $targetStatus !== TicketStatus::Closed) {
                $targetStatus = TicketStatus::Reopened;
            }

            $tickets->changeStatus($ticket, Auth::user(), $targetStatus, 'Updated from Kanban board');
        }
    }

    public function with(TicketService $tickets): array
    {
        $user = Auth::user();
        $kanbanStatuses = [TicketStatus::Open, TicketStatus::InProgress, TicketStatus::Closed];

        return [
            'tickets' => $tickets->listTicketsForUser($user, [
                'status'         => $this->status ?: null,
                'priority_id'    => $this->priorityId ?: null,
                'department_id'  => $this->departmentId ?: null,
                'assigned_to_id' => $this->assignedToId ?: null,
                'search'         => $this->search ?: null,
            ]),
            'statuses'       => TicketStatus::cases(),
            'priorities'     => TicketPriority::query()->where('company_id', $user->company_id)->orderBy('level')->get(['id', 'name']),
            'departments'    => Department::query()->where('company_id', $user->company_id)->orderBy('name')->get(['id', 'name']),
            'agents'         => User::query()->where('company_id', $user->company_id)->whereNotNull('department_id')->orderBy('name')->get(['id', 'name']),
            'kanbanStatuses' => $kanbanStatuses,
            'kanbanTickets'  => collect($kanbanStatuses)->mapWithKeys(fn (TicketStatus $status): array => [
                $status->value => $tickets->listTicketsForUser($user, [
                    'status'         => $status->value,
                    'priority_id'    => $this->priorityId ?: null,
                    'department_id'  => $this->departmentId ?: null,
                    'assigned_to_id' => $this->assignedToId ?: null,
                    'search'         => $this->search ?: null,
                ], 50)->items(),
            ]),
        ];
    }
}; ?>

<div class="flex flex-col gap-6">
    <x-page-header
        :title="__('Tickets')"
        :description="__('Track support requests across departments and assignments.')"
    >
        <x-slot:actions>
            @can('create', \App\Models\Ticket::class)
                <flux:button variant="primary" icon="plus" :href="route('tickets.create')" wire:navigate>
                    {{ __('New ticket') }}
                </flux:button>
            @endcan
        </x-slot:actions>
    </x-page-header>

    {{-- View mode toggle --}}
    <div class="flex items-center gap-3">
        <div class="inline-flex rounded-xl border border-zinc-200/80 bg-white p-1 shadow-[0_1px_2px_0_rgb(0,0,0,0.03)] dark:border-zinc-800/80 dark:bg-zinc-900">
            <button
                type="button"
                wire:click="$set('viewMode', 'table')"
                class="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-1.5 text-sm font-medium transition-all duration-150 {{ $viewMode === 'table' ? 'bg-zinc-900 text-white shadow-sm dark:bg-white dark:text-zinc-900' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200' }}"
            >
                <flux:icon name="list-bullet" class="size-4" />
                {{ __('Table') }}
            </button>
            <button
                type="button"
                wire:click="$set('viewMode', 'kanban')"
                class="inline-flex items-center gap-1.5 rounded-lg px-3.5 py-1.5 text-sm font-medium transition-all duration-150 {{ $viewMode === 'kanban' ? 'bg-zinc-900 text-white shadow-sm dark:bg-white dark:text-zinc-900' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-200' }}"
            >
                <flux:icon name="view-columns" class="size-4" />
                {{ __('Kanban') }}
            </button>
        </div>
    </div>

    {{-- Filters --}}
    <x-filter-bar columns="md:grid-cols-5">
        <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass" :placeholder="__('Search number or title')" />
        <flux:select wire:model.live="status">
            <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
            @foreach ($statuses as $ticketStatus)
                <flux:select.option value="{{ $ticketStatus->value }}">{{ __(str_replace('_', ' ', $ticketStatus->value)) }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="priorityId">
            <flux:select.option value="">{{ __('All priorities') }}</flux:select.option>
            @foreach ($priorities as $priority)
                <flux:select.option value="{{ $priority->id }}">{{ $priority->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="departmentId">
            <flux:select.option value="">{{ __('All departments') }}</flux:select.option>
            @foreach ($departments as $department)
                <flux:select.option value="{{ $department->id }}">{{ $department->name }}</flux:select.option>
            @endforeach
        </flux:select>
        <flux:select wire:model.live="assignedToId">
            <flux:select.option value="">{{ __('All assignees') }}</flux:select.option>
            @foreach ($agents as $agent)
                <flux:select.option value="{{ $agent->id }}">{{ $agent->name }}</flux:select.option>
            @endforeach
        </flux:select>
    </x-filter-bar>

    {{-- Kanban view --}}
    @if ($viewMode === 'kanban')
        <div class="grid gap-4 lg:grid-cols-3">
            @foreach ($kanbanStatuses as $kanbanStatus)
                @php
                    $columnAccent = match($kanbanStatus->value) {
                        'open'        => 'border-t-blue-500',
                        'in_progress' => 'border-t-amber-500',
                        'closed'      => 'border-t-zinc-400',
                        default       => 'border-t-zinc-300',
                    };
                @endphp
                <div class="flex flex-col rounded-xl border border-zinc-200/60 border-t-2 {{ $columnAccent }} bg-zinc-50/60 dark:border-zinc-800/60 dark:bg-zinc-900/40">
                    <div class="flex items-center justify-between gap-2 px-4 py-3.5">
                        <span class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">
                            {{ __(ucwords(str_replace('_', ' ', $kanbanStatus->value))) }}
                        </span>
                        <flux:badge size="sm" color="zinc" class="font-bold">
                            {{ count($kanbanTickets[$kanbanStatus->value] ?? []) }}
                        </flux:badge>
                    </div>

                    <div wire:sort="moveTicket" wire:sort:group="tickets" wire:sort:group-id="{{ $kanbanStatus->value }}" class="flex min-h-80 flex-col gap-2 p-3">
                        @forelse ($kanbanTickets[$kanbanStatus->value] ?? [] as $ticket)
                            <a
                                wire:key="kanban-ticket-{{ $ticket->id }}"
                                wire:sort:item="{{ $ticket->id }}"
                                href="{{ route('tickets.show', $ticket) }}"
                                wire:navigate
                                class="group card card-hover cursor-grab p-4 active:cursor-grabbing active:shadow-[0_8px_24px_0_rgb(0,0,0,0.12)]"
                            >
                                <div class="flex items-start justify-between gap-2">
                                    <span class="text-sm font-semibold text-zinc-800 group-hover:text-zinc-900 dark:text-zinc-200 dark:group-hover:text-white">
                                        {{ $ticket->ticket_number }}
                                    </span>
                                    @if ($ticket->priority)
                                        <x-status-badge :status="$ticket->priority->name" />
                                    @endif
                                </div>
                                <p class="mt-2 line-clamp-2 text-sm leading-relaxed text-zinc-600 dark:text-zinc-400">{{ $ticket->title }}</p>
                                <div class="mt-4 flex items-center justify-between gap-2 text-xs font-medium text-zinc-400 dark:text-zinc-500">
                                    <span class="flex items-center gap-1.5">
                                        <flux:icon name="folder" class="size-3.5 shrink-0" />
                                        <span class="truncate">{{ $ticket->department->name }}</span>
                                    </span>
                                    <span class="flex items-center gap-1.5 shrink-0">
                                        <flux:icon name="user" class="size-3.5" />
                                        {{ $ticket->assignedAgent?->name ?? __('Unassigned') }}
                                    </span>
                                </div>
                            </a>
                        @empty
                            <x-empty-state icon="inbox" :heading="__('No tickets.')" class="min-h-48 py-8" />
                        @endforelse
                    </div>
                </div>
            @endforeach
        </div>

    {{-- Table view --}}
    @else
        <div class="card overflow-hidden">
            {{-- Table header row --}}
            <div class="hidden border-b border-zinc-100/80 bg-zinc-50/80 px-6 py-3 dark:border-zinc-800/80 dark:bg-zinc-800/30 lg:grid lg:grid-cols-[1fr_10rem_8rem_10rem] lg:items-center">
                <span class="text-xs font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500">{{ __('Ticket') }}</span>
                <span class="text-xs font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500">{{ __('Department') }}</span>
                <span class="text-xs font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500">{{ __('Assignee') }}</span>
                <span class="text-xs font-semibold uppercase tracking-widest text-zinc-400 dark:text-zinc-500 text-right">{{ __('Created') }}</span>
            </div>

            @forelse ($tickets as $ticket)
                <x-list-row
                    wire:key="ticket-{{ $ticket->id }}"
                    :href="route('tickets.show', $ticket)"
                    class="group px-6 lg:grid-cols-[1fr_10rem_8rem_10rem] lg:items-center"
                >
                    <div class="min-w-0">
                        <div class="flex flex-wrap items-center gap-2">
                            <span class="text-sm font-semibold text-zinc-800 group-hover:text-zinc-900 dark:text-zinc-200 dark:group-hover:text-white">
                                {{ $ticket->ticket_number }}
                            </span>
                            <x-status-badge :status="$ticket->status->value" />
                            @if ($ticket->priority)
                                <x-status-badge :status="$ticket->priority->name" />
                            @endif
                        </div>
                        <p class="mt-1 truncate text-xs text-zinc-500 dark:text-zinc-400">{{ $ticket->title }}</p>
                    </div>
                    <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ $ticket->department->name }}</span>
                    <span class="text-sm text-zinc-500 dark:text-zinc-400">{{ $ticket->assignedAgent?->name ?? __('Unassigned') }}</span>
                    <span class="text-xs text-zinc-400 dark:text-zinc-500 lg:text-right">{{ $ticket->created_at->diffForHumans() }}</span>
                </x-list-row>
            @empty
                <x-empty-state
                    icon="ticket"
                    :heading="__('No tickets found.')"
                    :description="__('Tickets matching your filters will appear here.')"
                    class="py-14"
                />
            @endforelse
        </div>

        <div class="mt-2">
            {{ $tickets->links() }}
        </div>
    @endif
</div>
