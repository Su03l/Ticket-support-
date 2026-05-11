<?php

use App\Enums\ReplyVisibility;
use App\Enums\TicketPresenceAction;
use App\Enums\TicketStatus;
use App\Models\CannedResponse;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\TicketRating;
use App\Models\User;
use App\Services\CannedResponseService;
use App\Services\TicketCommentService;
use App\Services\TicketPresenceService;
use App\Services\TicketRatingService;
use App\Services\TicketReplyService;
use App\Services\TicketService;
use App\Services\TicketTimeTrackingService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Ticket detail')] class extends Component
{
 use AuthorizesRequests, WithFileUploads;

 public Ticket $ticket;

 public string $replyBody = '';

 public string $commentBody = '';

 public string $assigneeId = '';

 public string $transferDepartmentId = '';

 public string $status = '';

 public string $reason = '';

 public string $rating = '';

 public string $feedback = '';

 public string $cannedResponseId = '';

 public array $replyFiles = [];

 public array $commentFiles = [];

 public function mount(Ticket $ticket, TicketPresenceService $presence): void
 {
  $this->authorize('view', $ticket);
  $this->ticket = $ticket->load($this->relations());
  $this->assigneeId = (string) ($ticket->assigned_to_id ?? '');
  $this->transferDepartmentId = (string) $ticket->department_id;
  $this->status = $ticket->status->value;
  $presence->touch($ticket, Auth::user(), TicketPresenceAction::Viewing);
 }

 public function addReply(TicketReplyService $replies): void
 {
  $this->authorize('reply', $this->ticket);

  $validated = $this->validate([
   'replyBody' => ['required', 'string', 'min:2'],
   'replyFiles.*' => ['file', 'max:10240'],
  ]);

  $replies->addReply($this->ticket, Auth::user(), $validated['replyBody'], ReplyVisibility::Public, $this->replyFiles);

  $this->reset(['replyBody', 'replyFiles']);
  $this->refreshTicket();
 }

 public function updatePresence(string $action, TicketPresenceService $presence): void
 {
  $this->authorize('view', $this->ticket);
  $presence->touch($this->ticket, Auth::user(), TicketPresenceAction::from($action));
 }

 public function startTimer(TicketTimeTrackingService $timeTracking): void
 {
  $this->authorize('reply', $this->ticket);
  $timeTracking->start($this->ticket, Auth::user());
 }

 public function stopTimer(TicketTimeTrackingService $timeTracking): void
 {
  $this->authorize('reply', $this->ticket);
  $timeTracking->stop($this->ticket, Auth::user());
 }

 public function useCannedResponse(): void
 {
  $response = CannedResponse::query()
   ->where('company_id', $this->ticket->company_id)
   ->where('is_active', true)
   ->findOrFail($this->cannedResponseId);

  $this->replyBody = trim($this->replyBody . "\n\n". $response->body);
 }

 public function submitRating(TicketRatingService $ratings): void
 {
  $this->authorize('create', [TicketRating::class, $this->ticket]);

  $validated = $this->validate([
   'rating' => ['required', 'integer', 'min:1', 'max:5'],
   'feedback' => ['nullable', 'string', 'max:2000'],
  ]);

  $ratings->rate($this->ticket, Auth::user(), (int) $validated['rating'], $validated['feedback'] ?: null);
  $this->reset(['rating', 'feedback']);
  $this->refreshTicket();
 }

 public function addComment(TicketCommentService $comments): void
 {
  $this->authorize('comment', $this->ticket);

  $validated = $this->validate([
   'commentBody' => ['required', 'string', 'min:2'],
   'commentFiles.*' => ['file', 'max:10240'],
  ]);

  $comments->addComment($this->ticket, Auth::user(), $validated['commentBody'], $this->commentFiles);

  $this->reset(['commentBody', 'commentFiles']);
  $this->refreshTicket();
 }

 public function assign(TicketService $tickets): void
 {
  $this->authorize('assign', $this->ticket);

  $validated = $this->validate([
   'assigneeId' => ['required', Rule::exists('users', 'id')->where('company_id', $this->ticket->company_id)],
  ]);

  $assignee = User::query()->findOrFail($validated['assigneeId']);
  $tickets->assignTicket($this->ticket, Auth::user(), $assignee, $this->reason ?: null);
  $this->reset('reason');
  $this->refreshTicket();
 }

 public function transfer(TicketService $tickets): void
 {
  $this->authorize('transfer', $this->ticket);

  $validated = $this->validate([
   'transferDepartmentId' => ['required', Rule::exists('departments', 'id')->where('company_id', $this->ticket->company_id)],
   'reason'    => ['required', 'string', 'min:3'],
  ]);

  $department = Department::query()->findOrFail($validated['transferDepartmentId']);
  $tickets->transferTicket($this->ticket, Auth::user(), $department, $validated['reason']);
  $this->reset('reason');
  $this->refreshTicket();
 }

 public function changeStatus(TicketService $tickets): void
 {
  $this->authorize(in_array($this->status, [TicketStatus::Closed->value, TicketStatus::Cancelled->value], true) ? 'close' : 'reopen', $this->ticket);

  $validated = $this->validate([
   'status' => ['required', Rule::enum(TicketStatus::class)],
   'reason' => ['nullable', 'string', 'max:1000'],
  ]);

  $tickets->changeStatus($this->ticket, Auth::user(), TicketStatus::from($validated['status']), $validated['reason'] ?: null);
  $this->reset('reason');
  $this->refreshTicket();
 }

 public function with(): array
 {
  $presence = app(TicketPresenceService::class);
  $timeTracking = app(TicketTimeTrackingService::class);

  $timeline = collect();
  foreach ($this->ticket->replies as $reply) {
   if ($reply->visibility === App\Enums\ReplyVisibility::Internal && ! Auth::user()->can('tickets.comment')) {
    continue;
   }
   $timeline->push(['type' => 'reply', 'model' => $reply, 'date' => $reply->created_at]);
  }
  if (Auth::user()->can('tickets.comment')) {
   foreach ($this->ticket->comments as $comment) {
    $timeline->push(['type' => 'comment', 'model' => $comment, 'date' => $comment->created_at]);
   }
  }
  foreach ($this->ticket->statusHistories as $history) {
   $timeline->push(['type' => 'status', 'model' => $history, 'date' => $history->created_at]);
  }
  $timeline = $timeline->sortBy('date')->values();

  return [
   'agents' => User::query()
    ->where('company_id', $this->ticket->company_id)
    ->where('department_id', $this->ticket->department_id)
    ->orderBy('name')
    ->get(['id', 'name']),
   'departments' => Department::query()
    ->where('company_id', $this->ticket->company_id)
    ->orderBy('name')
    ->get(['id', 'name']),
   'statuses'   => TicketStatus::cases(),
   'cannedResponses' => app(CannedResponseService::class)->activeForUser(Auth::user()),
   'activeColleagues' => $presence->activeColleagues($this->ticket, Auth::user()),
   'runningTimer'  => $timeTracking->runningEntry($this->ticket, Auth::user()),
   'trackedSeconds' => $timeTracking->secondsForTicket($this->ticket, Auth::user()),
   'timeline'   => $timeline,
  ];
 }

 private function refreshTicket(): void
 {
  $this->ticket = $this->ticket->refresh()->load($this->relations());
  $this->assigneeId = (string) ($this->ticket->assigned_to_id ?? '');
  $this->transferDepartmentId = (string) $this->ticket->department_id;
  $this->status = $this->ticket->status->value;
 }

 private function relations(): array
 {
  return [
   'department:id,name',
   'customer:id,name,email,user_type',
   'assignedAgent:id,name,email',
   'priority:id,name,color',
   'category:id,name',
   'replies.user:id,name,email,user_type',
   'replies.attachments',
   'comments.user:id,name,email',
   'comments.attachments',
   'assignments.assignedBy:id,name,email',
   'assignments.assignedTo:id,name,email',
   'assignments.fromUser:id,name,email',
   'transfers.transferredBy:id,name,email',
   'transfers.fromDepartment:id,name',
   'transfers.toDepartment:id,name',
   'statusHistories.changedBy:id,name,email',
   'rating.customer:id,name,email',
  ];
 }
}; ?>

<div class="flex flex-col gap-6" wire:poll.60s>
 {{-- Top Navigation & Basic Info --}}
 <div class="flex items-center justify-between">
  <flux:breadcrumbs>
   <flux:breadcrumbs.item :href="route('tickets.index')" wire:navigate>{{ __('Tickets') }}</flux:breadcrumbs.item>
   <flux:breadcrumbs.item>{{ $ticket->ticket_number }}</flux:breadcrumbs.item>
  </flux:breadcrumbs>

  <div class="flex items-center gap-3">
   @can('reply', $ticket)
    @if ($runningTimer)
     <flux:button wire:click="stopTimer" icon="stop" variant="danger" size="sm" class="!px-3">
      <span class="font-mono">{{ gmdate('H:i:s', $trackedSeconds) }}</span>
     </flux:button>
    @else
     <flux:button wire:click="startTimer" icon="play" variant="ghost" size="sm" class="text-zinc-500 hover:text-blue-600">
      {{ __('Start timer') }}
     </flux:button>
    @endif
   @endcan

   <flux:button variant="ghost" icon="chevron-left":href="route('tickets.index')" wire:navigate size="sm">
    {{ __('Back') }}
   </flux:button>
  </div>
 </div>

 {{-- Header Section --}}
 <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
  <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
   <div class="flex flex-col gap-2">
    <div class="flex flex-wrap items-center gap-2">
     <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $ticket->title }}</h1>
     <x-status-badge :status="$ticket->status->value"/>
     @if ($ticket->priority)
      <x-status-badge :status="$ticket->priority->name"/>
     @endif
    </div>
    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-zinc-500 dark:text-zinc-400">
     <div class="flex items-center gap-1.5">
      <flux:icon name="folder" class="size-4"/>
      <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $ticket->department->name }}</span>
     </div>
     <div class="flex items-center gap-1.5">
      <flux:icon name="calendar" class="size-4"/>
      <span>{{ __('Opened :date', ['date' => $ticket->created_at->format('M j, Y H:i')]) }}</span>
     </div>
     @if($ticket->category)
      <div class="flex items-center gap-1.5 text-zinc-400">
       <flux:icon name="tag" class="size-4"/>
       <span>{{ $ticket->category->name }}</span>
      </div>
     @endif
    </div>
   </div>

   @if ($activeColleagues->isNotEmpty())
    <div class="flex items-center gap-2 rounded-full bg-amber-50 px-3 py-1 text-xs font-medium text-amber-700 ring-1 ring-inset ring-amber-600/20 dark:bg-amber-900/20 dark:text-amber-400">
     <flux:icon name="eye" class="size-3.5 animate-pulse"/>
     <span>{{ trans_choice(':count colleague is viewing', $activeColleagues->count()) }}</span>
    </div>
   @endif
  </div>
 </div>

 <div class="grid gap-6 lg:grid-cols-[1fr_20rem]">
  {{-- Left: Conversation & Activity --}}
  <div class="flex flex-col gap-6">
   {{-- Original Description --}}
   <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
    <div class="border-b border-zinc-100 bg-zinc-50/50 px-6 py-4 dark:border-zinc-800 dark:bg-zinc-800/20">
     <div class="flex items-center gap-3">
      <flux:avatar :name="$ticket->customer->name" size="sm"/>
      <div class="flex flex-col">
       <span class="text-sm font-bold text-zinc-900 dark:text-white">{{ $ticket->customer->name }}</span>
       <span class="text-xs text-zinc-500">{{ __('Initial Request') }}</span>
      </div>
     </div>
    </div>
    <div class="p-6">
     <div class="prose prose-sm max-w-none dark:prose-invert">
      <p class="whitespace-pre-line text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">
       {{ $ticket->description }}
      </p>
     </div>
    </div>
   </div>

   {{-- Activity Feed --}}
   <div class="relative flex flex-col gap-6 pb-6">
    {{-- Vertical Line --}}
    <div class="absolute inset-y-0 left-[1.125rem] w-0.5 bg-zinc-100 dark:bg-zinc-800"aria-hidden="true"></div>

    @foreach ($timeline as $item)
     @if ($item['type'] === 'reply')
      <div wire:key="reply-{{ $item['model']->id }}" class="relative flex gap-4">
       <div class="relative z-10 flex size-9 shrink-0 items-center justify-center rounded-full bg-white ring-8 ring-zinc-50 dark:bg-zinc-900 dark:ring-zinc-950">
        <flux:avatar :name="$item['model']->user->name" size="sm"/>
       </div>
       <div class="flex-1 rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
        <div class="flex items-center justify-between border-b border-zinc-100 px-4 py-3 dark:border-zinc-800">
         <div class="flex items-center gap-2">
          <span class="text-sm font-bold text-zinc-900 dark:text-white">{{ $item['model']->user->name }}</span>
          @if($item['model']->user->user_type !== \App\Enums\UserType::Customer)
           <flux:badge size="sm" variant="subtle" color="blue">{{ __('Staff') }}</flux:badge>
          @endif
         </div>
         <span class="text-xs text-zinc-400 dark:text-zinc-500">{{ $item['date']->diffForHumans() }}</span>
        </div>
        <div class="p-4">
         <p class="whitespace-pre-line text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">
          {{ $item['model']->body }}
         </p>
         @if ($item['model']->attachments->isNotEmpty())
          <div class="mt-4 flex flex-wrap gap-2 border-t border-zinc-100 pt-4 dark:border-zinc-800">
           @foreach ($item['model']->attachments as $attachment)
            @can('view', $attachment)
             <flux:button size="xs" variant="filled" icon="paper-clip":href="route('attachments.download', $attachment)">
              {{ $attachment->original_name }}
             </flux:button>
            @endcan
           @endforeach
          </div>
         @endif
        </div>
       </div>
      </div>

     @elseif ($item['type'] === 'comment')
      <div wire:key="comment-{{ $item['model']->id }}" class="relative flex gap-4">
       <div class="relative z-10 flex size-9 shrink-0 items-center justify-center rounded-full bg-amber-50 ring-8 ring-zinc-50 dark:bg-amber-900/20 dark:ring-zinc-950">
        <flux:icon name="lock-closed" class="size-4 text-amber-600 dark:text-amber-400"/>
       </div>
       <div class="flex-1 rounded-xl border border-amber-100 bg-amber-50/30 shadow-sm dark:border-amber-900/30 dark:bg-amber-950/10">
        <div class="flex items-center justify-between border-b border-amber-100/50 px-4 py-3 dark:border-amber-900/20">
         <div class="flex items-center gap-2">
          <span class="text-sm font-bold text-amber-900 dark:text-amber-200">{{ $item['model']->user->name }}</span>
          <flux:badge size="sm" variant="subtle" color="amber">{{ __('Internal Note') }}</flux:badge>
         </div>
         <span class="text-xs text-amber-600/60 dark:text-amber-400/50">{{ $item['date']->diffForHumans() }}</span>
        </div>
        <div class="p-4">
         <p class="whitespace-pre-line text-sm leading-relaxed text-amber-900/80 dark:text-amber-200/80">
          {{ $item['model']->body }}
         </p>
         @if ($item['model']->attachments->isNotEmpty())
          <div class="mt-4 flex flex-wrap gap-2 border-t border-amber-100/50 pt-4 dark:border-amber-900/20">
           @foreach ($item['model']->attachments as $attachment)
            @can('view', $attachment)
             <flux:button size="xs" variant="filled" color="amber" icon="paper-clip":href="route('attachments.download', $attachment)" class="bg-amber-100/50 text-amber-700 hover:bg-amber-200/50 dark:bg-amber-900/30 dark:text-amber-400">
              {{ $attachment->original_name }}
             </flux:button>
            @endcan
           @endforeach
          </div>
         @endif
        </div>
       </div>
      </div>

     @elseif ($item['type'] === 'status')
      <div wire:key="status-{{ $item['model']->id }}" class="relative flex items-center gap-4 py-2">
       <div class="relative z-10 flex size-9 shrink-0 items-center justify-center rounded-full bg-zinc-100 ring-8 ring-zinc-50 dark:bg-zinc-800 dark:ring-zinc-950">
        <flux:icon name="arrow-path" class="size-4 text-zinc-400"/>
       </div>
       <div class="flex flex-wrap items-center gap-2 text-sm">
        <span class="font-bold text-zinc-900 dark:text-white">{{ $item['model']->changedBy->name ?? __('System') }}</span>
        <span class="text-zinc-500">{{ __('updated status to') }}</span>
        <x-status-badge :status="$item['model']->new_status->value"/>
        <span class="text-xs text-zinc-400">{{ $item['date']->diffForHumans() }}</span>
       </div>
      </div>
     @endif
    @endforeach
   </div>

   {{-- Interaction Area --}}
   <div x-data="{ tab: 'reply' }" class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900 overflow-hidden">
    <div class="flex border-b border-zinc-100 bg-zinc-50/50 p-1 dark:border-zinc-800 dark:bg-zinc-800/50">
     @can('reply', $ticket)
      <button 
       type="button"
       @click="tab = 'reply'"
       :class="tab === 'reply' ? 'bg-white text-zinc-900 shadow-sm dark:bg-zinc-700 dark:text-white' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300'"
       class="flex flex-1 items-center justify-center gap-2 rounded-md py-2 text-sm font-medium transition-all"
      >
       <flux:icon name="chat-bubble-left-right" class="size-4"/>
       {{ __('Public Reply') }}
      </button>
     @endcan
     @can('comment', $ticket)
      <button 
       type="button"
       @click="tab = 'note'"
       :class="tab === 'note' ? 'bg-white text-zinc-900 shadow-sm dark:bg-zinc-700 dark:text-white' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300'"
       class="flex flex-1 items-center justify-center gap-2 rounded-md py-2 text-sm font-medium transition-all"
      >
       <flux:icon name="lock-closed" class="size-4"/>
       {{ __('Internal Note') }}
      </button>
     @endcan
    </div>

    <div class="p-6">
     @can('reply', $ticket)
      <div x-show="tab === 'reply'" class="flex flex-col gap-4">
       @if ($cannedResponses->isNotEmpty())
        <div class="flex items-end gap-2">
         <div class="flex-1">
          <flux:select wire:model="cannedResponseId":label="__('Insert canned response')">
           <flux:select.option value="">{{ __('Select a template...') }}</flux:select.option>
           @foreach ($cannedResponses as $response)
            <flux:select.option value="{{ $response->id }}">{{ $response->title }}</flux:select.option>
           @endforeach
          </flux:select>
         </div>
         <flux:button type="button" wire:click="useCannedResponse" variant="ghost">{{ __('Insert') }}</flux:button>
        </div>
       @endif

       <form wire:submit="addReply" class="flex flex-col gap-4">
        <flux:textarea
         wire:model="replyBody"
         x-on:focus="$wire.updatePresence('replying')"
         :label="__('Your Message')"
         rows="5"
         :placeholder="__('Type your message for the customer here...')"
        />
        <div class="flex flex-col gap-1.5">
         <flux:label>{{ __('Attachments') }}</flux:label>
         <flux:input type="file" wire:model="replyFiles"multiple />
        </div>
        <div class="flex justify-end pt-2">
         <flux:button type="submit" variant="primary" icon="paper-airplane">{{ __('Send Message') }}</flux:button>
        </div>
       </form>
      </div>
     @endcan

     @can('comment', $ticket)
      <div x-show="tab === 'note'"style="display: none;" class="flex flex-col gap-4">
       <div class="flex items-center gap-2 rounded-lg bg-amber-50 p-3 text-sm text-amber-700 dark:bg-amber-900/20 dark:text-amber-400">
        <flux:icon name="information-circle" class="size-4"/>
        <span>{{ __('Internal notes are only visible to staff members.') }}</span>
       </div>

       <form wire:submit="addComment" class="flex flex-col gap-4">
        <flux:textarea
         wire:model="commentBody"
         x-on:focus="$wire.updatePresence('commenting')"
         :label="__('Internal Note')"
         rows="5"
         :placeholder="__('Collaborate with other staff members...')"
        />
        <div class="flex flex-col gap-1.5">
         <flux:label>{{ __('Private Attachments') }}</flux:label>
         <flux:input type="file" wire:model="commentFiles"multiple />
        </div>
        <div class="flex justify-end pt-2">
         <flux:button type="submit" color="amber" icon="lock-closed" class="bg-amber-600 text-white hover:bg-amber-700 border-none">{{ __('Save Note') }}</flux:button>
        </div>
       </form>
      </div>
     @endcan
    </div>
   </div>
  </div>

  {{-- Right: Sidebar Details --}}
  <div class="flex flex-col gap-6">
   {{-- Customer Card --}}
   <section class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
    <flux:heading level="2" class="mb-4 flex items-center gap-2">
     <flux:icon name="user" class="size-4 text-zinc-400"/>
     {{ __('Customer Information') }}
    </flux:heading>
    <div class="flex items-center gap-4">
     <flux:avatar :name="$ticket->customer->name" size="lg"/>
     <div class="flex flex-col min-w-0">
      <span class="text-sm font-bold text-zinc-900 dark:text-white truncate">{{ $ticket->customer->name }}</span>
      <span class="text-xs text-zinc-500 truncate">{{ $ticket->customer->email }}</span>
     </div>
    </div>
   </section>

   {{-- Assignment & Management (Staff Only) --}}
   @if(Auth::user()->user_type !== \App\Enums\UserType::Customer)
    <div class="flex flex-col gap-4">
     {{-- Status Management --}}
     <section class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
      <flux:heading level="2" class="mb-4 flex items-center gap-2">
       <flux:icon name="tag" class="size-4 text-zinc-400"/>
       {{ __('Update Status') }}
      </flux:heading>
      <div class="flex flex-col gap-4">
       <flux:select wire:model="status">
        @foreach ($statuses as $ticketStatus)
         <flux:select.option value="{{ $ticketStatus->value }}">{{ __(str_replace('_', ' ', $ticketStatus->value)) }}</flux:select.option>
        @endforeach
       </flux:select>
       <flux:textarea wire:model="reason":placeholder="__('Reason for status change...')"rows="2"/>
       <flux:button wire:click="changeStatus" variant="primary" class="w-full">{{ __('Update') }}</flux:button>
      </div>
     </section>

     {{-- Assignment --}}
     @can('assign', $ticket)
      <section class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
       <flux:heading level="2" class="mb-4 flex items-center gap-2">
        <flux:icon name="user-plus" class="size-4 text-zinc-400"/>
        {{ __('Assignment') }}
       </flux:heading>
       <div class="flex flex-col gap-4">
        <flux:select wire:model="assigneeId">
         <flux:select.option value="">{{ __('Select agent...') }}</flux:select.option>
         @foreach ($agents as $agent)
          <flux:select.option value="{{ $agent->id }}">{{ $agent->name }}</flux:select.option>
         @endforeach
        </flux:select>
        <flux:button wire:click="assign" variant="primary" class="w-full">{{ __('Assign Agent') }}</flux:button>
       </div>
      </section>
     @endcan

     {{-- Transfer --}}
     @can('transfer', $ticket)
      <section class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
       <flux:heading level="2" class="mb-4 flex items-center gap-2">
        <flux:icon name="arrow-right-circle" class="size-4 text-zinc-400"/>
        {{ __('Transfer Ticket') }}
       </flux:heading>
       <div class="flex flex-col gap-4">
        <flux:select wire:model="transferDepartmentId">
         @foreach ($departments as $department)
          <flux:select.option value="{{ $department->id }}">{{ $department->name }}</flux:select.option>
         @endforeach
        </flux:select>
        <flux:textarea wire:model="reason":placeholder="__('Reason for transfer...')"rows="2"/>
        <flux:button wire:click="transfer" variant="outline" class="w-full">{{ __('Transfer') }}</flux:button>
       </div>
      </section>
     @endcan
    </div>
   @endif

   {{-- Rating --}}
   @if ($ticket->rating)
    <section class="rounded-xl border border-amber-100 bg-amber-50/30 p-5 shadow-sm dark:border-amber-900/30 dark:bg-amber-950/10">
     <flux:heading level="2" class="mb-4 flex items-center gap-2 text-amber-900 dark:text-amber-200">
      <flux:icon name="star" class="size-4"/>
      {{ __('Customer Rating') }}
     </flux:heading>
     <div class="flex flex-col gap-2">
      <div class="flex items-center gap-1">
       @for($i = 1; $i <= 5; $i++)
        <flux:icon name="star" class="size-5 {{ $i <= $ticket->rating->rating ? 'text-amber-500 fill-amber-500' : 'text-zinc-200' }}"/>
       @endfor
       <span class="ml-2 text-lg font-bold text-amber-900 dark:text-amber-200">{{ $ticket->rating->rating }}</span>
      </div>
      @if ($ticket->rating->feedback)
       <p class="text-xs italic text-amber-800/70 dark:text-amber-400/70">"{{ $ticket->rating->feedback }}"</p>
      @endif
     </div>
    </section>
   @elseif(Auth::user()->user_type === \App\Enums\UserType::Customer && $ticket->status === \App\Enums\TicketStatus::Resolved)
    @can('create', [\App\Models\TicketRating::class, $ticket])
     <section class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
      <flux:heading level="2" class="mb-4 flex items-center gap-2">
       <flux:icon name="star" class="size-4 text-zinc-400"/>
       {{ __('Rate our service') }}
      </flux:heading>
      <form wire:submit="submitRating" class="flex flex-col gap-4">
       <flux:input type="number"min="1"max="5" wire:model="rating":label="__('Rating (1-5)')"/>
       <flux:textarea wire:model="feedback":placeholder="__('How was your experience?')"rows="2"/>
       <flux:button type="submit" variant="primary" class="w-full">{{ __('Submit Rating') }}</flux:button>
      </form>
     </section>
    @endcan
   @endif

   {{-- History Trigger --}}
   <section class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
    <flux:heading level="2" class="mb-1 flex items-center gap-2">
     <flux:icon name="clock" class="size-4 text-zinc-400"/>
     {{ __('Status History') }}
    </flux:heading>
    <p class="text-xs text-zinc-500 mb-4">{{ __('Detailed log of status changes.') }}</p>
    <livewire:tickets.status-history :ticket="$ticket"/>
   </section>
  </div>
 </div>
</div>
