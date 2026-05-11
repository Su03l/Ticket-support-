<?php

use App\Enums\ComplaintStatus;
use App\Enums\ReplyVisibility;
use App\Models\Complaint;
use App\Models\User;
use App\Services\ComplaintReplyService;
use App\Services\ComplaintService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Complaint detail')] class extends Component
{
 use AuthorizesRequests, WithFileUploads;

 public Complaint $complaint;

 public string $replyBody = '';

 public bool $internalReply = false;

 public string $assigneeId = '';

 public string $status = '';

 public string $reason = '';

 public array $replyFiles = [];

 public function mount(Complaint $complaint): void
 {
  $this->authorize('view', $complaint);
  $this->complaint = $complaint->load($this->relations());
  $this->assigneeId = (string) ($complaint->assigned_to_id ?? '');
  $this->status = $complaint->status->value;
 }

 public function addReply(ComplaintReplyService $replies): void
 {
  $this->authorize('reply', $this->complaint);

  if ($this->internalReply) {
   $this->authorize('viewInternal', $this->complaint);
  }

  $validated = $this->validate([
   'replyBody' => ['required', 'string', 'min:2'],
   'replyFiles.*' => ['file', 'max:10240'],
  ]);

  $replies->addReply(
   $this->complaint,
   Auth::user(),
   $validated['replyBody'],
   $this->internalReply ? ReplyVisibility::Internal : ReplyVisibility::Public,
   $this->replyFiles,
  );

  $this->reset(['replyBody', 'replyFiles', 'internalReply']);
  $this->refreshComplaint();
 }

 public function assign(ComplaintService $complaints): void
 {
  $this->authorize('assign', $this->complaint);

  $validated = $this->validate([
   'assigneeId' => ['required', Rule::exists('users', 'id')->where('company_id', $this->complaint->company_id)],
  ]);

  $assignee = User::query()->findOrFail($validated['assigneeId']);
  $complaints->assignComplaint($this->complaint, Auth::user(), $assignee);
  $this->refreshComplaint();
 }

 public function updateStatus(ComplaintService $complaints): void
 {
  $this->authorize('close', $this->complaint);

  $validated = $this->validate([
   'status' => ['required', Rule::enum(ComplaintStatus::class)],
   'reason' => ['nullable', 'string', 'max:1000'],
  ]);

  $status = ComplaintStatus::from($validated['status']);

  if ($status === ComplaintStatus::Escalated) {
   $complaints->escalateComplaint($this->complaint, Auth::user(), $validated['reason'] ?: null);
  } else {
   $complaints->changeStatus($this->complaint, Auth::user(), $status, $validated['reason'] ?: null);
  }

  $this->reset('reason');
  $this->refreshComplaint();
 }

 public function with(): array
 {
  return [
   'agents' => User::query()
    ->where('company_id', $this->complaint->company_id)
    ->when($this->complaint->department_id !== null, fn ($query) => $query->where('department_id', $this->complaint->department_id))
    ->orderBy('name')
    ->get(['id', 'name']),
   'statuses' => ComplaintStatus::cases(),
  ];
 }

 private function refreshComplaint(): void
 {
  $this->complaint = $this->complaint->refresh()->load($this->relations());
  $this->assigneeId = (string) ($this->complaint->assigned_to_id ?? '');
  $this->status = $this->complaint->status->value;
 }

 private function relations(): array
 {
  return [
   'department:id,name',
   'customer:id,name,email,user_type',
   'assignedAgent:id,name,email',
   'relatedTicket:id,ticket_number,title',
   'replies.user:id,name,email,user_type',
   'replies.attachments',
   'statusHistories.changedBy:id,name,email',
  ];
 }
}; ?>

<div class="flex flex-col gap-6" wire:poll.60s>
 {{-- Navigation & Quick Actions --}}
 <div class="flex items-center justify-between">
  <flux:breadcrumbs>
   <flux:breadcrumbs.item :href="route('complaints.index')" wire:navigate>{{ __('Complaints') }}</flux:breadcrumbs.item>
   <flux:breadcrumbs.item>{{ $complaint->complaint_number }}</flux:breadcrumbs.item>
  </flux:breadcrumbs>

  <flux:button variant="ghost" icon="chevron-left":href="route('complaints.index')" wire:navigate size="sm">
   {{ __('Back') }}
  </flux:button>
 </div>

 {{-- Header Section --}}
 <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
  <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
   <div class="flex flex-col gap-2">
    <div class="flex flex-wrap items-center gap-2">
     <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $complaint->title }}</h1>
     <x-status-badge :status="$complaint->status->value"/>
     <x-status-badge :status="$complaint->severity->value"/>
    </div>
    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-zinc-500 dark:text-zinc-400">
     <div class="flex items-center gap-1.5">
      <flux:icon name="folder" class="size-4"/>
      <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $complaint->department?->name ?? __('General') }}</span>
     </div>
     <div class="flex items-center gap-1.5">
      <flux:icon name="calendar" class="size-4"/>
      <span>{{ __('Created :date', ['date' => $complaint->created_at->format('M j, Y H:i')]) }}</span>
     </div>
     @if($complaint->relatedTicket)
      <div class="flex items-center gap-1.5">
       <flux:icon name="ticket" class="size-4"/>
       <a href="{{ route('tickets.show', $complaint->relatedTicket) }}" wire:navigate class="text-blue-600 hover:underline dark:text-blue-400">
        {{ $complaint->relatedTicket->ticket_number }}
       </a>
      </div>
     @endif
    </div>
   </div>
  </div>
 </div>

 <div class="grid gap-6 lg:grid-cols-[1fr_20rem]">
  {{-- Left: Conversation & Activity --}}
  <div class="flex flex-col gap-6">
   {{-- Original Description --}}
   <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
    <div class="border-b border-zinc-100 bg-zinc-50/50 px-6 py-4 dark:border-zinc-800 dark:bg-zinc-800/20">
     <div class="flex items-center gap-3">
      <flux:avatar :name="$complaint->customer->name" size="sm"/>
      <div class="flex flex-col">
       <span class="text-sm font-bold text-zinc-900 dark:text-white">{{ $complaint->customer->name }}</span>
       <span class="text-xs text-zinc-500">{{ __('Complainant') }}</span>
      </div>
     </div>
    </div>
    <div class="p-6">
     <p class="whitespace-pre-line text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">
      {{ $complaint->description }}
     </p>
    </div>
   </div>

   {{-- Activity Feed --}}
   <div class="relative flex flex-col gap-6 pb-6">
    {{-- Vertical Line --}}
    <div class="absolute inset-y-0 left-[1.125rem] w-0.5 bg-zinc-100 dark:bg-zinc-800"aria-hidden="true"></div>

    @foreach ($complaint->replies as $reply)
     @continue($reply->visibility === App\Enums\ReplyVisibility::Internal && ! Auth::user()->can('viewInternal', $complaint))
     
     <div wire:key="reply-{{ $reply->id }}" class="relative flex gap-4">
      <div class="relative z-10 flex size-9 shrink-0 items-center justify-center rounded-full bg-white ring-8 ring-zinc-50 dark:bg-zinc-900 dark:ring-zinc-950">
       <flux:avatar :name="$reply->user->name" size="sm"/>
      </div>
      <div class="flex-1 rounded-xl border {{ $reply->visibility === App\Enums\ReplyVisibility::Internal ? 'border-amber-100 bg-amber-50/30 dark:border-amber-900/30 dark:bg-amber-950/10' : 'border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900' }} shadow-sm">
       <div class="flex items-center justify-between border-b {{ $reply->visibility === App\Enums\ReplyVisibility::Internal ? 'border-amber-100/50 dark:border-amber-900/20' : 'border-zinc-100 dark:border-zinc-800' }} px-4 py-3">
        <div class="flex items-center gap-2">
         <span class="text-sm font-bold {{ $reply->visibility === App\Enums\ReplyVisibility::Internal ? 'text-amber-900 dark:text-amber-200' : 'text-zinc-900 dark:text-white' }}">{{ $reply->user->name }}</span>
         @if($reply->user->user_type !== \App\Enums\UserType::Customer)
          <flux:badge size="sm" variant="subtle" color="{{ $reply->visibility === App\Enums\ReplyVisibility::Internal ? 'amber' : 'blue' }}">
           {{ $reply->visibility === App\Enums\ReplyVisibility::Internal ? __('Internal') : __('Staff') }}
          </flux:badge>
         @endif
        </div>
        <span class="text-xs text-zinc-400 dark:text-zinc-500">{{ $reply->created_at->diffForHumans() }}</span>
       </div>
       <div class="p-4">
        <p class="whitespace-pre-line text-sm leading-relaxed {{ $reply->visibility === App\Enums\ReplyVisibility::Internal ? 'text-amber-900/80 dark:text-amber-200/80' : 'text-zinc-700 dark:text-zinc-300' }}">
         {{ $reply->body }}
        </p>
        @if ($reply->attachments->isNotEmpty())
         <div class="mt-4 flex flex-wrap gap-2 border-t {{ $reply->visibility === App\Enums\ReplyVisibility::Internal ? 'border-amber-100/50 dark:border-amber-900/20' : 'border-zinc-100 dark:border-zinc-800' }} pt-4">
          @foreach ($reply->attachments as $attachment)
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
    @endforeach
   </div>

   {{-- Interaction Area --}}
   @can('reply', $complaint)
    <div x-data="{
     tab: 'public',
     labels: {
      public: @js(__('Public Reply')),
      internal: @js(__('Internal Note')),
     },
     placeholders: {
      public: @js(__('Provide an update to the complainant...')),
      internal: @js(__('Collaborate with other investigators...')),
     },
    }" class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900 overflow-hidden">
     <div class="flex border-b border-zinc-100 bg-zinc-50/50 p-1 dark:border-zinc-800 dark:bg-zinc-800/50">
      <button 
       type="button"
       @click="tab = 'public'; $wire.set('internalReply', false)"
       :class="tab === 'public' ? 'bg-white text-zinc-900 shadow-sm dark:bg-zinc-700 dark:text-white' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300'"
       class="flex flex-1 items-center justify-center gap-2 rounded-md py-2 text-sm font-medium transition-all"
      >
       <flux:icon name="chat-bubble-left-right" class="size-4"/>
       {{ __('Public Update') }}
      </button>
      @can('viewInternal', $complaint)
       <button 
        type="button"
        @click="tab = 'internal'; $wire.set('internalReply', true)"
        :class="tab === 'internal' ? 'bg-white text-zinc-900 shadow-sm dark:bg-zinc-700 dark:text-white' : 'text-zinc-500 hover:text-zinc-700 dark:text-zinc-400 dark:hover:text-zinc-300'"
        class="flex flex-1 items-center justify-center gap-2 rounded-md py-2 text-sm font-medium transition-all"
       >
        <flux:icon name="lock-closed" class="size-4"/>
        {{ __('Internal Note') }}
       </button>
      @endcan
     </div>

     <div class="p-6">
      <form wire:submit="addReply" class="flex flex-col gap-4">
       <div x-show="tab === 'internal'"style="display: none;" class="flex items-center gap-2 rounded-lg bg-amber-50 p-3 text-sm text-amber-700 dark:bg-amber-900/20 dark:text-amber-400 mb-2">
        <flux:icon name="information-circle" class="size-4"/>
        <span>{{ __('This note will only be visible to other staff members.') }}</span>
       </div>

       <flux:field>
        <flux:label x-text="tab === 'internal' ? labels.internal : labels.public"></flux:label>
        <flux:textarea
         wire:model="replyBody"
         rows="5"
         ::placeholder="tab === 'internal' ? placeholders.internal : placeholders.public"
        />
        <flux:error name="replyBody"/>
       </flux:field>
       
       <div class="flex flex-col gap-1.5">
        <flux:label>{{ __('Attachments') }}</flux:label>
        <flux:input type="file" wire:model="replyFiles"multiple />
       </div>

       <div class="flex justify-end pt-2">
        <flux:button x-show="tab !== 'internal'" type="submit" variant="primary" icon="paper-airplane">
         {{ __('Send Public Update') }}
        </flux:button>
        <flux:button x-show="tab === 'internal'"style="display: none;" type="submit" variant="filled" color="amber" icon="paper-airplane">
         {{ __('Save Private Note') }}
        </flux:button>
       </div>
      </form>
     </div>
    </div>
   @endcan
  </div>

  {{-- Right: Sidebar Details --}}
  <div class="flex flex-col gap-6">
   {{-- Complainant Card --}}
   <section class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
    <flux:heading level="2" class="mb-4 flex items-center gap-2 text-zinc-500">
     <flux:icon name="user" class="size-4"/>
     {{ __('Complainant') }}
    </flux:heading>
    <div class="flex items-center gap-4">
     <flux:avatar :name="$complaint->customer->name" size="lg"/>
     <div class="flex flex-col min-w-0">
      <span class="text-sm font-bold text-zinc-900 dark:text-white truncate">{{ $complaint->customer->name }}</span>
      <span class="text-xs text-zinc-500 truncate">{{ $complaint->customer->email }}</span>
     </div>
    </div>
   </section>

   {{-- Management (Staff Only) --}}
   @if(Auth::user()->user_type !== \App\Enums\UserType::Customer)
    <div class="flex flex-col gap-4">
     {{-- Status Management --}}
     @can('close', $complaint)
      <section class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
       <flux:heading level="2" class="mb-4 flex items-center gap-2 text-zinc-500">
        <flux:icon name="tag" class="size-4"/>
        {{ __('Update Status') }}
       </flux:heading>
       <div class="flex flex-col gap-4">
        <flux:select wire:model="status">
         @foreach ($statuses as $complaintStatus)
          <flux:select.option value="{{ $complaintStatus->value }}">{{ __(str_replace('_', ' ', $complaintStatus->value)) }}</flux:select.option>
         @endforeach
        </flux:select>
        <flux:textarea wire:model="reason":placeholder="__('Reason for status change...')"rows="2"/>
        <flux:button wire:click="updateStatus" variant="primary" class="w-full">{{ __('Update') }}</flux:button>
       </div>
      </section>
     @endcan

     {{-- Assignment --}}
     @can('assign', $complaint)
      <section class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
       <flux:heading level="2" class="mb-4 flex items-center gap-2 text-zinc-500">
        <flux:icon name="user-plus" class="size-4"/>
        {{ __('Assignment') }}
       </flux:heading>
       <div class="flex flex-col gap-4">
        <flux:select wire:model="assigneeId">
         <flux:select.option value="">{{ __('Select agent...') }}</flux:select.option>
         @foreach ($agents as $agent)
          <flux:select.option value="{{ $agent->id }}">{{ $agent->name }}</flux:select.option>
         @endforeach
        </flux:select>
        <flux:button wire:click="assign" variant="primary" class="w-full">{{ __('Assign Investigator') }}</flux:button>
       </div>
      </section>
     @endcan
    </div>
   @endif

   {{-- History (Staff Only) --}}
   @can('viewInternal', $complaint)
    <section class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
     <flux:heading level="2" class="mb-4 flex items-center gap-2 text-zinc-500">
      <flux:icon name="clock" class="size-4"/>
      {{ __('Status Log') }}
     </flux:heading>
     <div class="flex flex-col gap-4">
      @foreach ($complaint->statusHistories as $history)
       <div wire:key="history-{{ $history->id }}" class="flex flex-col gap-1 border-b border-zinc-100 pb-3 last:border-b-0 dark:border-zinc-800">
        <div class="flex items-center justify-between gap-2 text-[10px] font-bold text-zinc-400 uppercase tracking-wider">
         <span>{{ $history->changedBy->name }}</span>
         <span>{{ $history->created_at->diffForHumans() }}</span>
        </div>
        <div class="flex items-center gap-1.5 text-xs">
         <x-status-badge :status="$history->old_status->value ?? 'None'" class="!px-1 !py-0 text-[9px]"/>
         <flux:icon name="arrow-right" class="size-2 text-zinc-300"/>
         <x-status-badge :status="$history->new_status->value" class="!px-1 !py-0 text-[9px]"/>
        </div>
       </div>
      @endforeach
     </div>
    </section>
   @endcan
  </div>
 </div>
</div>
