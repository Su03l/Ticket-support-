<?php

use App\Models\SupportNotification;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use App\Services\NotificationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;
use Livewire\WithPagination;

new #[Title('Notifications')] class extends Component
{
 use AuthorizesRequests, WithPagination;

 public string $status = 'all';

 public function mount(): void
 {
  $this->authorize('viewAny', SupportNotification::class);
 }

 public function updatedStatus(): void
 {
  $this->resetPage();
 }

 public function markAsRead(int $notificationId, NotificationService $notifications): void
 {
  $notification = SupportNotification::query()->findOrFail($notificationId);

  $this->authorize('markRead', $notification);

  $notifications->markAsRead(Auth::user(), $notification);
 }

 public function markAllAsRead(NotificationService $notifications): void
 {
  $this->authorize('viewAny', SupportNotification::class);

  $notifications->markAllAsRead(Auth::user());
 }

 public function deleteNotification(int $notificationId, NotificationRepositoryInterface $notifications): void
 {
  $notification = SupportNotification::query()->findOrFail($notificationId);

  $this->authorize('delete', $notification);

  $notifications->deleteForRecipient(Auth::user(), $notification);
 }

 public function with(NotificationRepositoryInterface $notifications): array
 {
  return [
   'notifications' => $notifications->paginatedForRecipient(
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
   <flux:heading size="xl" class="font-bold ">{{ __('Notifications') }}</flux:heading>
   <flux:text size="sm" class="text-zinc-500 dark:text-zinc-400">{{ __('Stay updated with the latest system alerts and account activity.') }}</flux:text>
  </div>

  <div class="flex flex-wrap items-center gap-3">
   <flux:radio.group wire:model.live="status" variant="segmented" size="sm" class="p-1 bg-zinc-100 dark:bg-zinc-800 rounded-lg">
    <flux:radio.indicator />
    <flux:radio value="all"label="{{ __('All') }}"/>
    <flux:radio value="unread"label="{{ __('Unread') }}"/>
    <flux:radio value="read"label="{{ __('Read') }}"/>
   </flux:radio.group>

   <flux:separator vertical class="hidden sm:block h-6"/>

   <flux:button variant="primary" size="sm" wire:click="markAllAsRead" icon="check" class="font-semibold shadow-sm">
    {{ __('Mark all read') }}
   </flux:button>
  </div>
 </div>

 <div class="relative overflow-hidden rounded-2xl border border-zinc-200 bg-white shadow-sm dark:border-zinc-800 dark:bg-zinc-900/50">
  <div class="divide-y divide-zinc-100 dark:divide-zinc-800/50">
   @forelse ($notifications as $notification)
    <div wire:key="notification-{{ $notification->id }}" class="group relative flex flex-col gap-4 p-5 transition-all hover:bg-zinc-50/50 dark:hover:bg-zinc-800/30 sm:flex-row sm:items-center sm:gap-6">
     <div class="flex shrink-0 items-center justify-center">
      <div class="flex size-12 items-center justify-center rounded-2xl {{ $notification['read_at'] === null ? 'bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400 ring-1 ring-blue-100 dark:ring-blue-900/20' : 'bg-zinc-50 text-zinc-400 dark:bg-zinc-800 dark:text-zinc-500' }}">
       <flux:icon name="bell" size="md"/>
      </div>
     </div>

     <div class="min-w-0 flex-1">
      <div class="flex flex-wrap items-center gap-x-3 gap-y-1">
       <a href="{{ $notification->link ?: '#' }}" class="text-base font-bold transition-colors {{ $notification->isUnread() ? 'text-zinc-900 dark:text-white' : 'text-zinc-500 dark:text-zinc-400' }}"@if($notification->link) wire:navigate @endif>
        {{ $notification->title }}
       </a>
       @if ($notification->isUnread())
        <flux:badge color="blue" size="sm" variant="pill" class="font-bold uppercase tracking-wider text-[10px]">{{ __('New') }}</flux:badge>
       @endif
      </div>

      <flux:text size="sm" class="mt-1 leading-relaxed {{ $notification->isUnread() ? 'text-zinc-600 dark:text-zinc-400' : 'text-zinc-400 dark:text-zinc-500' }}">
       {{ $notification->body }}
      </flux:text>

      <div class="mt-3 flex items-center gap-3 text-xs font-medium text-zinc-400 dark:text-zinc-500">
       <span class="flex items-center gap-1">
        <flux:icon name="clock" variant="micro"/>
        {{ $notification->created_at->diffForHumans() }}
       </span>
      </div>
     </div>

     <div class="flex shrink-0 items-center gap-2 self-end sm:self-center">
      @if ($notification->isUnread())
       <flux:tooltip :content="__('Mark as read')"position="top">
        <flux:button size="sm" variant="ghost"square wire:click="markAsRead({{ $notification->id }})" class="hover:bg-blue-50 hover:text-blue-600 dark:hover:bg-blue-900/20">
         <flux:icon name="check" size="sm"/>
        </flux:button>
       </flux:tooltip>
      @endif

      @can('delete', $notification)
       <flux:tooltip :content="__('Delete notification')"position="top">
        <flux:button size="sm" variant="ghost"square wire:click="deleteNotification({{ $notification->id }})" class="hover:bg-red-50 hover:text-red-600 dark:hover:bg-red-900/20">
         <flux:icon name="trash" size="sm"/>
        </flux:button>
       </flux:tooltip>
      @endcan
     </div>
    </div>
   @empty
    <div class="flex flex-col items-center justify-center p-20 text-center">
     <div class="mb-6 flex size-20 items-center justify-center rounded-3xl bg-zinc-50 dark:bg-zinc-800/50">
      <flux:icon name="bell-slash" class="size-10 text-zinc-200 dark:text-zinc-700"/>
     </div>
     <flux:heading size="lg" class="font-bold">{{ __('All caught up!') }}</flux:heading>
     <flux:text class="mt-2 max-w-xs mx-auto">{{ __('You have no notifications at the moment. We\'ll let you know when something happens.') }}</flux:text>
     
     @if($status !== 'all')
      <flux:button variant="ghost" class="mt-6" wire:click="$set('status', 'all')">
       {{ __('Show all notifications') }}
      </flux:button>
     @endif
    </div>
   @endforelse
  </div>
 </div>

 <div class="mt-4">
  {{ $notifications->links() }}
 </div>
</div>
