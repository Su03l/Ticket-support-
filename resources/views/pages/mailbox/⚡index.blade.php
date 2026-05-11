<?php

use App\Models\MailboxMessage;
use App\Repositories\Contracts\MailboxRepositoryInterface;
use App\Services\MailboxService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Mailbox')] class extends Component
{
 use AuthorizesRequests, WithPagination;

 public string $status = 'all';

 public function mount(): void
 {
  $this->authorize('viewAny', MailboxMessage::class);
 }

 public function updatedStatus(): void
 {
  $this->resetPage();
 }

 public function markAsRead(int $messageId, MailboxService $mailbox): void
 {
  $message = MailboxMessage::query()->findOrFail($messageId);

  $this->authorize('read', $message);

  $mailbox->markAsRead(Auth::user(), $message);
 }

 public function markAsUnread(int $messageId, MailboxService $mailbox): void
 {
  $message = MailboxMessage::query()->findOrFail($messageId);

  $this->authorize('read', $message);

  $mailbox->markAsUnread(Auth::user(), $message);
 }

 public function archiveMessage(int $messageId, MailboxService $mailbox): void
 {
  $message = MailboxMessage::query()->findOrFail($messageId);

  $this->authorize('archive', $message);

  $mailbox->archive(Auth::user(), $message);
 }

 public function with(MailboxRepositoryInterface $mailbox): array
 {
  return [
   'messages' => $mailbox->paginatedForRecipient(
    Auth::user(),
    $this->status === 'all' ? null : $this->status,
    15,
   ),
  ];
 }
}; ?>

<div class="flex flex-col gap-8">
 <div class="flex flex-col gap-6 sm:flex-row sm:items-end sm:justify-between">
  <div class="space-y-1">
   <flux:heading size="xl" class="font-bold ">{{ __('Mailbox') }}</flux:heading>
   <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">{{ __('Internal communications, system notices, and team updates.') }}</flux:text>
  </div>

  <flux:radio.group wire:model.live="status" variant="segmented" size="sm" class="p-1 bg-zinc-100 dark:bg-zinc-800 rounded-lg">
   <flux:radio.indicator />
   <flux:radio value="all"label="{{ __('Inbox') }}"/>
   <flux:radio value="unread"label="{{ __('Unread') }}"/>
   <flux:radio value="read"label="{{ __('Read') }}"/>
   <flux:radio value="archived"label="{{ __('Archived') }}"/>
  </flux:radio.group>
 </div>

 <div class="relative overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900/50">
  <div class="divide-y divide-zinc-100 dark:divide-zinc-800/50">
   @forelse ($messages as $message)
    <div wire:key="mailbox-message-{{ $message->id }}" class="group relative flex flex-col gap-4 p-5 transition-all hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 sm:flex-row sm:items-center sm:gap-6">
     {{-- Avatar Column --}}
     <div class="flex shrink-0 items-center justify-center">
      <div class="flex size-12 items-center justify-center rounded-full bg-gradient-to-br from-zinc-100 to-zinc-200 text-sm font-bold text-zinc-600 ring-2 ring-white dark:from-zinc-800 dark:to-zinc-900 dark:text-zinc-300 dark:ring-zinc-900 shadow-sm">
       {{ mb_substr($message->sender?->name ?? __('System'), 0, 1) }}
      </div>
     </div>

     {{-- Content Column --}}
     <div class="min-w-0 flex-1">
      <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
       <span class="text-xs font-bold uppercase tracking-widest text-zinc-400 dark:text-zinc-500">
        {{ $message->sender?->name ?? __('System') }}
       </span>
       @if ($message->isUnread())
        <flux:badge color="blue" size="sm" variant="pill" class="font-bold uppercase tracking-wider text-[10px]">{{ __('Unread') }}</flux:badge>
       @endif
       @if ($message->isArchived())
        <flux:badge color="zinc" size="sm" variant="pill" class="font-bold uppercase tracking-wider text-[10px]">{{ __('Archived') }}</flux:badge>
       @endif
      </div>

      <a href="{{ route('mailbox.show', $message) }}" class="mt-1 block transition-colors" wire:navigate>
       <flux:heading size="sm" class="font-bold {{ $message->isUnread() ? 'text-zinc-900 dark:text-white' : 'text-zinc-500 dark:text-zinc-400' }}">
        {{ $message->subject }}
       </flux:heading>
       <flux:text size="sm" class="mt-1 line-clamp-1 leading-relaxed {{ $message->isUnread() ? 'text-zinc-600 dark:text-zinc-400' : 'text-zinc-400 dark:text-zinc-500' }}">
        {{ strip_tags($message->body) }}
       </flux:text>
      </a>

      <div class="mt-3 flex items-center gap-3 text-xs font-medium text-zinc-400 dark:text-zinc-500">
       <span class="flex items-center gap-1">
        <flux:icon name="clock" variant="micro"/>
        {{ $message->created_at->diffForHumans() }}
       </span>
      </div>
     </div>

     {{-- Actions Column --}}
     <div class="flex shrink-0 items-center gap-2 self-end sm:self-center">
      @if ($message->isUnread())
       <flux:tooltip :content="__('Mark as read')"position="top">
        <flux:button size="sm" variant="ghost"square wire:click="markAsRead({{ $message->id }})" class="hover:bg-blue-50 hover:text-blue-600 dark:hover:bg-blue-900/20">
         <flux:icon name="check" size="sm"/>
        </flux:button>
       </flux:tooltip>
      @else
       <flux:tooltip :content="__('Mark as unread')"position="top">
        <flux:button size="sm" variant="ghost"square wire:click="markAsUnread({{ $message->id }})" class="hover:bg-zinc-100 dark:hover:bg-zinc-800">
         <flux:icon name="eye-slash" size="sm"/>
        </flux:button>
       </flux:tooltip>
      @endif

      @can('archive', $message)
       @unless ($message->isArchived())
        <flux:tooltip :content="__('Archive message')"position="top">
         <flux:button size="sm" variant="ghost"square wire:click="archiveMessage({{ $message->id }})" class="hover:bg-zinc-100 dark:hover:bg-zinc-800">
          <flux:icon name="archive-box" size="sm"/>
         </flux:button>
        </flux:tooltip>
       @endunless
      @endcan
     </div>
    </div>
   @empty
    <div class="flex flex-col items-center justify-center p-20 text-center">
     <div class="mb-6 flex size-20 items-center justify-center rounded-3xl bg-zinc-50 dark:bg-zinc-800/50">
      <flux:icon name="inbox" class="size-10 text-zinc-200 dark:text-zinc-700"/>
     </div>
     <flux:heading size="lg" class="font-bold">{{ __('Your inbox is clear') }}</flux:heading>
     <flux:text class="mt-2 max-w-xs mx-auto">{{ __('When you receive internal messages or system updates, they will appear here.') }}</flux:text>
     
     @if($status !== 'all')
      <flux:button variant="ghost" class="mt-6" wire:click="$set('status', 'all')">
       {{ __('Show all messages') }}
      </flux:button>
     @endif
    </div>
   @endforelse
  </div>
 </div>

 <div class="mt-4">
  {{ $messages->links() }}
 </div>
</div>
