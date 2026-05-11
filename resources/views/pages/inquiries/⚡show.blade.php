<?php

use App\Enums\InquiryStatus;
use App\Enums\ReplyVisibility;
use App\Models\Inquiry;
use App\Models\TicketPriority;
use App\Models\User;
use App\Services\InquiryReplyService;
use App\Services\InquiryService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Validation\Rule;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithFileUploads;

new #[Title('Inquiry detail')] class extends Component
{
 use AuthorizesRequests, WithFileUploads;

 public Inquiry $inquiry;
 public string $replyBody = '';
 public bool $internalReply = false;
 public string $assigneeId = '';
 public string $priorityId = '';
 public string $status = '';
 public string $reason = '';
 public array $replyFiles = [];

 public function mount(Inquiry $inquiry): void
 {
  $this->authorize('view', $inquiry);
  $this->inquiry = $inquiry->load($this->relations());
  $this->assigneeId = (string) ($inquiry->assigned_to_id ?? '');
  $this->status = $inquiry->status->value;
 }

 public function addReply(InquiryReplyService $replies): void
 {
  $this->authorize('reply', $this->inquiry);

  if ($this->internalReply) {
   $this->authorize('viewInternal', $this->inquiry);
  }

  $validated = $this->validate([
   'replyBody' => ['required', 'string', 'min:2'],
   'replyFiles.*' => ['file', 'max:10240'],
  ]);

  $replies->addReply($this->inquiry, Auth::user(), $validated['replyBody'], $this->internalReply ? ReplyVisibility::Internal : ReplyVisibility::Public, $this->replyFiles);

  $this->reset(['replyBody', 'replyFiles', 'internalReply']);
  $this->refreshInquiry();
 }

 public function assign(InquiryService $inquiries): void
 {
  $this->authorize('reply', $this->inquiry);

  $validated = $this->validate([
   'assigneeId' => ['required', Rule::exists('users', 'id')->where('company_id', $this->inquiry->company_id)],
  ]);

  $inquiries->assignInquiry($this->inquiry, Auth::user(), User::query()->findOrFail($validated['assigneeId']));
  $this->refreshInquiry();
 }

 public function updateStatus(InquiryService $inquiries): void
 {
  $this->authorize('close', $this->inquiry);

  $validated = $this->validate([
   'status' => ['required', Rule::enum(InquiryStatus::class)],
   'reason' => ['nullable', 'string', 'max:1000'],
  ]);

  $inquiries->changeStatus($this->inquiry, Auth::user(), InquiryStatus::from($validated['status']), $validated['reason'] ?: null);
  $this->reset('reason');
  $this->refreshInquiry();
 }

 public function convert(InquiryService $inquiries): void
 {
  $this->authorize('convert', $this->inquiry);

  $ticket = $inquiries->convertToTicket($this->inquiry, Auth::user(), $this->priorityId ? (int) $this->priorityId : null);

  $this->redirectRoute('tickets.show', $ticket, navigate: true);
 }

 public function with(): array
 {
  return [
   'agents' => User::query()->where('company_id', $this->inquiry->company_id)->when($this->inquiry->department_id !== null, fn ($query) => $query->where('department_id', $this->inquiry->department_id))->orderBy('name')->get(['id', 'name']),
   'statuses' => InquiryStatus::cases(),
   'priorities' => TicketPriority::query()->where('company_id', $this->inquiry->company_id)->where('is_active', true)->orderBy('level')->get(['id', 'name']),
  ];
 }

 private function refreshInquiry(): void
 {
  $this->inquiry = $this->inquiry->refresh()->load($this->relations());
  $this->assigneeId = (string) ($this->inquiry->assigned_to_id ?? '');
  $this->status = $this->inquiry->status->value;
 }

 private function relations(): array
 {
  return ['department:id,name', 'customer:id,name,email,user_type', 'assignedAgent:id,name,email', 'convertedTicket:id,ticket_number,title', 'replies.user:id,name,email,user_type', 'replies.attachments', 'statusHistories.changedBy:id,name,email', 'slaRecord'];
 }
}; ?>

<div class="flex flex-col gap-6" wire:poll.60s>
 {{-- Navigation & Quick Actions --}}
 <div class="flex items-center justify-between">
  <flux:breadcrumbs>
   <flux:breadcrumbs.item :href="route('inquiries.index')" wire:navigate>{{ __('Inquiries') }}</flux:breadcrumbs.item>
   <flux:breadcrumbs.item>{{ $inquiry->inquiry_number }}</flux:breadcrumbs.item>
  </flux:breadcrumbs>

  <flux:button variant="ghost" icon="chevron-left":href="route('inquiries.index')" wire:navigate size="sm">
   {{ __('Back') }}
  </flux:button>
 </div>

 {{-- Header Section --}}
 <div class="rounded-xl border border-zinc-200 bg-white p-6 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
  <div class="flex flex-col gap-4 md:flex-row md:items-start md:justify-between">
   <div class="flex flex-col gap-2">
    <div class="flex flex-wrap items-center gap-2">
     <h1 class="text-2xl font-bold text-zinc-900 dark:text-white">{{ $inquiry->subject }}</h1>
     <x-status-badge :status="$inquiry->status->value"/>
     @if ($inquiry->slaRecord?->status?->value === 'breached')
      <flux:badge color="red" variant="subtle" size="sm" class="uppercase tracking-widest font-bold">{{ __('SLA Breached') }}</flux:badge>
     @endif
    </div>
    <div class="flex flex-wrap items-center gap-x-4 gap-y-1 text-sm text-zinc-500 dark:text-zinc-400">
     <div class="flex items-center gap-1.5">
      <flux:icon name="folder" class="size-4"/>
      <span class="font-medium text-zinc-700 dark:text-zinc-300">{{ $inquiry->department?->name ?? __('General') }}</span>
     </div>
     <div class="flex items-center gap-1.5">
      <flux:icon name="calendar" class="size-4"/>
      <span>{{ __('Created :date', ['date' => $inquiry->created_at->format('M j, Y H:i')]) }}</span>
     </div>
     @if($inquiry->convertedTicket)
      <div class="flex items-center gap-1.5">
       <flux:icon name="ticket" class="size-4"/>
       <a href="{{ route('tickets.show', $inquiry->convertedTicket) }}" wire:navigate class="text-blue-600 hover:underline dark:text-blue-400">
        {{ $inquiry->convertedTicket->ticket_number }}
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
   {{-- Original Message --}}
   <div class="rounded-xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
    <div class="border-b border-zinc-100 bg-zinc-50/50 px-6 py-4 dark:border-zinc-800 dark:bg-zinc-800/20">
     <div class="flex items-center gap-3">
      <flux:avatar :name="$inquiry->customer->name" size="sm"/>
      <div class="flex flex-col">
       <span class="text-sm font-bold text-zinc-900 dark:text-white">{{ $inquiry->customer->name }}</span>
       <span class="text-xs text-zinc-500">{{ __('Customer Inquiry') }}</span>
      </div>
     </div>
    </div>
    <div class="p-6">
     <p class="whitespace-pre-line text-sm leading-relaxed text-zinc-700 dark:text-zinc-300">
      {{ $inquiry->body }}
     </p>
    </div>
   </div>

   {{-- Activity Feed --}}
   <div class="relative flex flex-col gap-6 pb-6">
    {{-- Vertical Line --}}
    <div class="absolute inset-y-0 left-[1.125rem] w-0.5 bg-zinc-100 dark:bg-zinc-800"aria-hidden="true"></div>

    @foreach ($inquiry->replies as $reply)
     @continue($reply->visibility === App\Enums\ReplyVisibility::Internal && ! Auth::user()->can('viewInternal', $inquiry))
     
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
   @can('reply', $inquiry)
    <div x-data="{
     tab: 'public',
     labels: {
      public: @js(__('Public Reply')),
      internal: @js(__('Internal Note')),
     },
     placeholders: {
      public: @js(__('Provide an answer to the customer...')),
      internal: @js(__('Collaborate with other staff...')),
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
      @can('viewInternal', $inquiry)
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
         {{ __('Send Public Answer') }}
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
   {{-- Customer Card --}}
   <section class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
    <flux:heading level="2" class="mb-4 flex items-center gap-2 text-zinc-500">
     <flux:icon name="user" class="size-4"/>
     {{ __('Customer') }}
    </flux:heading>
    <div class="flex items-center gap-4">
     <flux:avatar :name="$inquiry->customer->name" size="lg"/>
     <div class="flex flex-col min-w-0">
      <span class="text-sm font-bold text-zinc-900 dark:text-white truncate">{{ $inquiry->customer->name }}</span>
      <span class="text-xs text-zinc-500 truncate">{{ $inquiry->customer->email }}</span>
     </div>
    </div>
   </section>

   {{-- Management (Staff Only) --}}
   @if(Auth::user()->user_type !== \App\Enums\UserType::Customer)
    <div class="flex flex-col gap-4">
     {{-- Convert to Ticket --}}
     @can('convert', $inquiry)
      <section class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
       <flux:heading level="2" class="mb-4 flex items-center gap-2 text-zinc-500">
        <flux:icon name="ticket" class="size-4"/>
        {{ __('Convert to Ticket') }}
       </flux:heading>
       <div class="flex flex-col gap-4">
        <flux:select wire:model="priorityId">
         <flux:select.option value="">{{ __('Select priority (Optional)') }}</flux:select.option>
         @foreach ($priorities as $priority)
          <flux:select.option value="{{ $priority->id }}">{{ $priority->name }}</flux:select.option>
         @endforeach
        </flux:select>
        <flux:button wire:click="convert" variant="primary" class="w-full">{{ __('Convert Now') }}</flux:button>
       </div>
      </section>
     @endcan

     {{-- Status Management --}}
     @can('close', $inquiry)
      <section class="rounded-xl border border-zinc-200 bg-white p-5 shadow-sm dark:border-zinc-800 dark:bg-zinc-900">
       <flux:heading level="2" class="mb-4 flex items-center gap-2 text-zinc-500">
        <flux:icon name="tag" class="size-4"/>
        {{ __('Update Status') }}
       </flux:heading>
       <div class="flex flex-col gap-4">
        <flux:select wire:model="status">
         @foreach ($statuses as $inquiryStatus)
          <flux:select.option value="{{ $inquiryStatus->value }}">{{ __(str_replace('_', ' ', $inquiryStatus->value)) }}</flux:select.option>
         @endforeach
        </flux:select>
        <flux:textarea wire:model="reason":placeholder="__('Reason for change...')"rows="2"/>
        <flux:button wire:click="updateStatus" variant="outline" class="w-full">{{ __('Update') }}</flux:button>
       </div>
      </section>
     @endcan

     {{-- Assignment --}}
     @can('reply', $inquiry)
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
        <flux:button wire:click="assign" variant="primary" class="w-full">{{ __('Assign Agent') }}</flux:button>
       </div>
      </section>
     @endcan
    </div>
   @endif
  </div>
 </div>
</div>
