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
    'status'   => $this->status ?: null,
    'priority_id' => $this->priorityId ?: null,
    'department_id' => $this->departmentId ?: null,
    'assigned_to_id' => $this->assignedToId ?: null,
    'search'   => $this->search ?: null,
   ]),
   'statuses'  => TicketStatus::cases(),
   'priorities'  => TicketPriority::query()->where('company_id', $user->company_id)->orderBy('level')->get(['id', 'name']),
   'departments' => Department::query()->where('company_id', $user->company_id)->orderBy('name')->get(['id', 'name']),
   'agents'   => User::query()->where('company_id', $user->company_id)->whereNotNull('department_id')->orderBy('name')->get(['id', 'name']),
   'kanbanStatuses' => $kanbanStatuses,
   'kanbanTickets' => collect($kanbanStatuses)->mapWithKeys(fn (TicketStatus $status): array => [
    $status->value => $tickets->listTicketsForUser($user, [
     'status'   => $status->value,
     'priority_id' => $this->priorityId ?: null,
     'department_id' => $this->departmentId ?: null,
     'assigned_to_id' => $this->assignedToId ?: null,
     'search'   => $this->search ?: null,
    ], 50)->items(),
   ]),
  ];
 }
}; ?>

<div class="flex flex-col gap-8">
 <div class="flex flex-col gap-1">
  <x-page-header
   :title="__('Support Tickets')"
   :description="__('Manage and track your technical support requests and issues.')"
  >
   <x-slot:actions>
    @can('create', \App\Models\Ticket::class)
     <flux:button variant="primary" icon="plus":href="route('tickets.create')" wire:navigate>
      {{ __('New ticket') }}
     </flux:button>
    @endcan
   </x-slot:actions>
  </x-page-header>
 </div>

 <div class="flex flex-col gap-6">
  {{-- Filters & View Toggle --}}
  <div class="flex flex-col gap-4 lg:flex-row lg:items-center lg:justify-between">
   <div class="flex flex-1 flex-wrap items-center gap-3">
    <div class="w-full sm:w-80">
     <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass":placeholder="__('Search ticket # or subject...')"/>
    </div>

    <div class="flex flex-wrap items-center gap-3">
     <div class="w-44">
      <flux:select wire:model.live="status":placeholder="__('Any status')">
       <flux:select.option value="">{{ __('All statuses') }}</flux:select.option>
       @foreach ($statuses as $ticketStatus)
        <flux:select.option value="{{ $ticketStatus->value }}">{{ __(str_replace('_', ' ', $ticketStatus->value)) }}</flux:select.option>
       @endforeach
      </flux:select>
     </div>

     <div class="w-44">
      <flux:select wire:model.live="priorityId":placeholder="__('Any priority')">
       <flux:select.option value="">{{ __('All priorities') }}</flux:select.option>
       @foreach ($priorities as $priority)
        <flux:select.option value="{{ $priority->id }}">{{ $priority->name }}</flux:select.option>
       @endforeach
      </flux:select>
     </div>
    </div>
   </div>

   {{-- View mode toggle --}}
   <div class="inline-flex shrink-0 items-center rounded-lg bg-zinc-100 p-1 dark:bg-zinc-800">
    <button
     type="button"
     wire:click="$set('viewMode', 'table')"
     class="flex h-9 items-center gap-2 rounded-md px-4 text-sm font-medium transition-all {{ $viewMode === 'table' ? 'bg-white text-zinc-900 shadow-sm dark:bg-zinc-700 dark:text-white' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}"
    >
     <flux:icon name="list-bullet" class="size-4"/>
     <span>{{ __('Table') }}</span>
    </button>
    <button
     type="button"
     wire:click="$set('viewMode', 'kanban')"
     class="flex h-9 items-center gap-2 rounded-md px-4 text-sm font-medium transition-all {{ $viewMode === 'kanban' ? 'bg-white text-zinc-900 shadow-sm dark:bg-zinc-700 dark:text-white' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300' }}"
    >
     <flux:icon name="view-columns" class="size-4"/>
     <span>{{ __('Kanban') }}</span>
    </button>
   </div>
  </div>

  {{-- Kanban view --}}
  @if ($viewMode === 'kanban')
   <div class="flex gap-6 overflow-x-auto pb-6 scrollbar-hide">
    @foreach ($kanbanStatuses as $kanbanStatus)
     @php
      $columnColor = match($kanbanStatus->value) {
       'open'  => 'bg-blue-500',
       'in_progress' => 'bg-amber-500',
       'closed'  => 'bg-zinc-400',
       default  => 'bg-zinc-300',
      };
     @endphp
     <div class="flex w-[22rem] shrink-0 flex-col rounded-2xl bg-zinc-50/80 p-4 dark:bg-zinc-900/40">
      <div class="mb-5 flex items-center justify-between px-1">
       <div class="flex items-center gap-2.5">
        <div class="size-2.5 rounded-full {{ $columnColor }}"></div>
        <h3 class="text-sm font-bold text-zinc-800 dark:text-zinc-200">
         {{ __(ucwords(str_replace('_', ' ', $kanbanStatus->value))) }}
        </h3>
        <span class="rounded-full bg-zinc-200/50 px-2 py-0.5 text-[10px] font-bold text-zinc-500 dark:bg-zinc-800 dark:text-zinc-400">
         {{ count($kanbanTickets[$kanbanStatus->value] ?? []) }}
        </span>
       </div>
      </div>

      <div class="flex min-h-[40rem] flex-col gap-4">
       @forelse ($kanbanTickets[$kanbanStatus->value] ?? [] as $ticket)
        <a
         wire:key="kanban-ticket-{{ $ticket->id }}"
         href="{{ route('tickets.show', $ticket) }}"
         wire:navigate
         class="group flex flex-col gap-4 rounded-xl border border-zinc-200 bg-white p-5 shadow-sm transition-all hover:border-zinc-300 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-zinc-700"
        >
         <div class="flex items-start justify-between">
          <span class="font-mono text-[10px] font-bold tracking-wider text-zinc-400 uppercase">
           {{ $ticket->ticket_number }}
          </span>
          @if ($ticket->priority)
           <x-status-badge :status="$ticket->priority->name" class="!px-2 !py-0.5 text-[9px] uppercase tracking-wide"/>
          @endif
         </div>

         <h4 class="text-sm font-bold leading-snug text-zinc-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400">
          {{ $ticket->title }}
         </h4>

         <div class="flex items-center justify-between gap-2 border-t border-zinc-50 pt-4 dark:border-zinc-800/50">
          <div class="flex items-center gap-2 text-xs text-zinc-500">
           <flux:icon name="folder" class="size-3.5"/>
           <span class="truncate max-w-[120px]">{{ $ticket->department->name }}</span>
          </div>
          @if($ticket->assignedAgent)
           <div class="flex items-center gap-1.5"title="{{ $ticket->assignedAgent->name }}">
            <flux:avatar :name="$ticket->assignedAgent->name" size="xs"/>
           </div>
          @else
           <flux:icon name="user" class="size-3.5 text-zinc-300"/>
          @endif
         </div>
        </a>
       @empty
        <div class="flex flex-1 flex-col items-center justify-center rounded-2xl border-2 border-dashed border-zinc-200/60 p-8 dark:border-zinc-800/60">
         <flux:icon name="inbox" class="size-10 text-zinc-200 dark:text-zinc-800"/>
         <p class="mt-3 text-xs font-semibold text-zinc-400 dark:text-zinc-600 uppercase tracking-widest">{{ __('Empty') }}</p>
        </div>
       @endforelse
      </div>
     </div>
    @endforeach
   </div>

  {{-- Table view --}}
  @else
   <div class="rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900 overflow-hidden">
    <flux:table>
     <flux:table.columns>
      <flux:table.column class="!pl-6">{{ __('Ticket') }}</flux:table.column>
      <flux:table.column>{{ __('Status') }}</flux:table.column>
      <flux:table.column>{{ __('Priority') }}</flux:table.column>
      <flux:table.column>{{ __('Department') }}</flux:table.column>
      <flux:table.column>{{ __('Assignee') }}</flux:table.column>
      <flux:table.column class="!pr-6 text-right">{{ __('Created') }}</flux:table.column>
     </flux:table.columns>

     <flux:table.rows>
      @forelse ($tickets as $ticket)
       <flux:table.row :key="$ticket->id" class="cursor-pointer hover:bg-zinc-50 dark:hover:bg-zinc-800/50":href="route('tickets.show', $ticket)" wire:navigate>
        <flux:table.cell class="!pl-6">
         <div class="flex flex-col gap-1">
          <span class="font-mono text-[10px] font-bold tracking-wider text-zinc-400 uppercase">{{ $ticket->ticket_number }}</span>
          <span class="font-bold text-zinc-900 dark:text-white">{{ $ticket->title }}</span>
         </div>
        </flux:table.cell>
        <flux:table.cell>
         <x-status-badge :status="$ticket->status->value"/>
        </flux:table.cell>
        <flux:table.cell>
         @if ($ticket->priority)
          <x-status-badge :status="$ticket->priority->name"/>
         @else
          <span class="text-xs text-zinc-300">—</span>
         @endif
        </flux:table.cell>
        <flux:table.cell>
         <span class="text-sm font-semibold text-zinc-700 dark:text-zinc-300">{{ $ticket->department->name }}</span>
        </flux:table.cell>
        <flux:table.cell>
         <div class="flex items-center gap-2.5">
          @if($ticket->assignedAgent)
           <flux:avatar :name="$ticket->assignedAgent->name" size="xs"/>
           <span class="text-sm font-medium text-zinc-600 dark:text-zinc-400">{{ $ticket->assignedAgent->name }}</span>
          @else
           <flux:badge size="sm" variant="subtle" color="zinc" class="font-bold uppercase tracking-wide text-[10px]">{{ __('Unassigned') }}</flux:badge>
          @endif
         </div>
        </flux:table.cell>
        <flux:table.cell class="!pr-6 text-right whitespace-nowrap">
         <span class="text-xs font-medium text-zinc-400 dark:text-zinc-500"title="{{ $ticket->created_at->format('Y-m-d H:i') }}">
          {{ $ticket->created_at->diffForHumans() }}
         </span>
        </flux:table.cell>
       </flux:table.row>
      @empty
       <flux:table.row>
        <flux:table.cell colspan="6" class="py-20">
         <div class="flex flex-col items-center justify-center gap-4">
          <div class="flex size-16 items-center justify-center rounded-full bg-zinc-50 dark:bg-zinc-800">
           <flux:icon name="ticket" class="size-8 text-zinc-200 dark:text-zinc-700"/>
          </div>
          <div class="text-center">
           <p class="font-bold text-zinc-900 dark:text-white">{{ __('No tickets found') }}</p>
           <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('We couldn\'t find any tickets matching your current search or filters.') }}</p>
          </div>
         </div>
        </flux:table.cell>
       </flux:table.row>
      @endforelse
     </flux:table.rows>
    </flux:table>
   </div>

   <div class="mt-6">
    {{ $tickets->links() }}
   </div>
  @endif
 </div>
</div>


