<?php

use App\Models\MailboxMessage;
use App\Services\MailboxService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Mailbox message')] class extends Component
{
 use AuthorizesRequests;

 public MailboxMessage $message;

 public function mount(MailboxMessage $message, MailboxService $mailbox): void
 {
  $this->authorize('view', $message);

  $this->message = $message->load(['sender:id,name,email', 'recipient:id,name,email']);

  if ($this->message->isUnread() && Auth::user()->can('mailbox.read')) {
   $this->message = $mailbox->markAsRead(Auth::user(), $this->message)
    ->load(['sender:id,name,email', 'recipient:id,name,email']);
  }
 }

 public function markAsUnread(MailboxService $mailbox): void
 {
  $this->authorize('read', $this->message);

  $this->message = $mailbox->markAsUnread(Auth::user(), $this->message)
   ->load(['sender:id,name,email', 'recipient:id,name,email']);
 }

 public function archiveMessage(MailboxService $mailbox): void
 {
  $this->authorize('archive', $this->message);

  $this->message = $mailbox->archive(Auth::user(), $this->message)
   ->load(['sender:id,name,email', 'recipient:id,name,email']);
 }
}; ?>

<div class="max-w-4xl mx-auto flex flex-col gap-8">
 {{-- Header & Actions --}}
 <div class="flex flex-col gap-6 sm:flex-row sm:items-start sm:justify-between">
  <div class="flex items-start gap-4">
   <flux:button variant="ghost"square :href="route('mailbox.index')" wire:navigate class="mt-1 shadow-sm">
    <flux:icon name="arrow-left" size="sm"/>
   </flux:button>
   <div class="space-y-1">
    <flux:heading size="xl" class="font-bold ">{{ $message->subject }}</flux:heading>
    <div class="flex items-center gap-2">
     @if ($message->isUnread())
      <flux:badge color="blue" size="sm" variant="pill" class="font-bold uppercase tracking-wider text-[10px]">{{ __('Unread') }}</flux:badge>
     @else
      <flux:badge color="zinc" size="sm" variant="pill" class="font-bold uppercase tracking-wider text-[10px]">{{ __('Read') }}</flux:badge>
     @endif

     @if ($message->isArchived())
      <flux:badge color="orange" size="sm" variant="pill" class="font-bold uppercase tracking-wider text-[10px]">{{ __('Archived') }}</flux:badge>
     @endif

     <flux:separator vertical class="h-3"/>
     <flux:text size="sm" class="text-zinc-500">{{ $message->created_at->format('M j, Y \a\t g:i A') }}</flux:text>
    </div>
   </div>
  </div>

  <div class="flex items-center gap-2 ml-12 sm:ml-0">
   @unless ($message->isUnread())
    <flux:button variant="ghost" size="sm" wire:click="markAsUnread" class="font-semibold">
     {{ __('Mark unread') }}
    </flux:button>
   @endunless

   @can('archive', $message)
    @unless ($message->isArchived())
     <flux:button variant="primary" size="sm" wire:click="archiveMessage" icon="archive-box" class="font-semibold shadow-sm">
      {{ __('Archive') }}
     </flux:button>
    @endunless
   @endcan
  </div>
 </div>

 {{-- Participants & Related --}}
 <div class="grid gap-6 sm:grid-cols-3">
  <div class="p-5 rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900/50 space-y-3">
   <div class="flex items-center gap-2 text-zinc-400">
    <flux:icon name="user" variant="micro"/>
    <flux:text size="xs" class="font-bold uppercase tracking-widest">{{ __('From') }}</flux:text>
   </div>
   <div>
    <flux:text class="font-bold text-zinc-900 dark:text-white">{{ $message->sender?->name ?? __('System') }}</flux:text>
    <flux:text size="xs" class="text-zinc-500">{{ $message->sender?->email ?? __('Automated Notification') }}</flux:text>
   </div>
  </div>

  <div class="p-5 rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900/50 space-y-3">
   <div class="flex items-center gap-2 text-zinc-400">
    <flux:icon name="user-group" variant="micro"/>
    <flux:text size="xs" class="font-bold uppercase tracking-widest">{{ __('To') }}</flux:text>
   </div>
   <div>
    <flux:text class="font-bold text-zinc-900 dark:text-white">{{ $message->recipient->name }}</flux:text>
    <flux:text size="xs" class="text-zinc-500">{{ $message->recipient->email }}</flux:text>
   </div>
  </div>

  <div class="p-5 rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900/50 space-y-3">
   <div class="flex items-center gap-2 text-zinc-400">
    <flux:icon name="link" variant="micro"/>
    <flux:text size="xs" class="font-bold uppercase tracking-widest">{{ __('Reference') }}</flux:text>
   </div>
   <div>
    @if ($message->related_type && $message->related_id)
     <flux:text class="font-bold text-zinc-900 dark:text-white">{{ class_basename($message->related_type) }} #{{ $message->related_id }}</flux:text>
     <flux:text size="xs" class="text-zinc-500">{{ __('Linked record') }}</flux:text>
    @else
     <flux:text class="font-bold text-zinc-400">{{ __('No reference') }}</flux:text>
     <flux:text size="xs" class="text-zinc-500">{{ __('General message') }}</flux:text>
    @endif
   </div>
  </div>
 </div>

 {{-- Message Body --}}
 <div class="rounded-3xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900/50 overflow-hidden">
  <div class="bg-zinc-50/50 dark:bg-zinc-800/30 px-8 py-4 border-b border-zinc-100 dark:border-zinc-800/50 flex items-center justify-between">
   <flux:heading size="sm" class="font-bold text-zinc-500 uppercase tracking-widest">{{ __('Message Body') }}</flux:heading>
   <flux:icon name="document-text" variant="mini" class="text-zinc-300 dark:text-zinc-600"/>
  </div>
  <div class="p-8 sm:p-12">
   <div class="prose prose-zinc max-w-none dark:prose-invert">
    <div class="whitespace-pre-line text-base leading-relaxed text-zinc-800 dark:text-zinc-200">
     {{ $message->body }}
    </div>
   </div>
  </div>
 </div>

 {{-- Footer Actions --}}
 <div class="flex items-center justify-center gap-4 py-4">
  <flux:button variant="ghost":href="route('mailbox.index')" wire:navigate class="text-zinc-500 font-semibold">
   {{ __('Back to inbox') }}
  </flux:button>
 </div>
</div>
