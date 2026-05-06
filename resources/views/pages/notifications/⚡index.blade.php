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

<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">{{ __('Notifications') }}</flux:heading>
            <flux:text>{{ __('Review account updates and system alerts.') }}</flux:text>
        </div>

        <div class="flex items-center gap-3">
            <flux:select wire:model.live="status" class="w-40">
                <flux:select.option value="all">{{ __('All') }}</flux:select.option>
                <flux:select.option value="unread">{{ __('Unread') }}</flux:select.option>
                <flux:select.option value="read">{{ __('Read') }}</flux:select.option>
            </flux:select>

            <flux:button variant="primary" wire:click="markAllAsRead">
                {{ __('Mark all read') }}
            </flux:button>
        </div>
    </div>

    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        @forelse ($notifications as $notification)
            <div wire:key="notification-{{ $notification->id }}" class="border-b border-zinc-200 p-4 last:border-b-0 dark:border-zinc-800">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <a href="{{ $notification->link ?: '#' }}" class="min-w-0 flex-1" @if($notification->link) wire:navigate @endif>
                        <div class="flex items-center gap-2">
                            <flux:heading size="sm">{{ $notification->title }}</flux:heading>
                            @if ($notification->isUnread())
                                <flux:badge color="blue" size="sm">{{ __('Unread') }}</flux:badge>
                            @endcan
                        </div>
                        <flux:text class="mt-1">{{ $notification->body }}</flux:text>
                        <flux:text class="mt-2 text-xs text-zinc-500">
                            {{ $notification->created_at->diffForHumans() }}
                        </flux:text>
                    </a>

                    <div class="flex shrink-0 items-center gap-2">
                        @if ($notification->isUnread())
                            <flux:button size="sm" variant="ghost" wire:click="markAsRead({{ $notification->id }})">
                                {{ __('Mark read') }}
                            </flux:button>
                        @endif

                        @can('delete', $notification)
                            <flux:button size="sm" variant="danger" wire:click="deleteNotification({{ $notification->id }})">
                                {{ __('Delete') }}
                            </flux:button>
                        @endcan
                    </div>
                </div>
            </div>
        @empty
            <div class="p-10 text-center">
                <flux:heading size="md">{{ __('No notifications found.') }}</flux:heading>
                <flux:text>{{ __('New account updates will appear here.') }}</flux:text>
            </div>
        @endforelse
    </div>

    {{ $notifications->links() }}
</div>
