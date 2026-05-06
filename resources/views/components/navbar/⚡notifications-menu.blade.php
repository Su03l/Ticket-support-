<?php

use App\Models\SupportNotification;
use App\Repositories\Contracts\NotificationRepositoryInterface;
use App\Services\NotificationService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Livewire\Component;

new class extends Component
{
    use AuthorizesRequests;

    public int $unreadCount = 0;

    /**
     * @var array<int, array<string, mixed>>
     */
    public array $latestNotifications = [];

    public function mount(NotificationRepositoryInterface $notifications): void
    {
        $this->refreshNotifications($notifications);
    }

    public function refreshNotifications(?NotificationRepositoryInterface $notifications = null): void
    {
        $notifications ??= app(NotificationRepositoryInterface::class);
        $user = Auth::user();

        $this->unreadCount = $notifications->unreadCountForRecipient($user);
        $this->latestNotifications = $notifications->latestForRecipient($user, 5)
            ->map(fn (SupportNotification $notification): array => [
                'id'         => $notification->id,
                'title'      => $notification->title,
                'body'       => $notification->body,
                'link'       => $notification->link,
                'read_at'    => $notification->read_at?->toISOString(),
                'created_at' => $notification->created_at?->diffForHumans(),
            ])
            ->all();
    }

    public function markAsRead(int $notificationId, NotificationService $notifications): void
    {
        $notification = SupportNotification::query()->findOrFail($notificationId);

        $this->authorize('markRead', $notification);

        $notifications->markAsRead(Auth::user(), $notification);
        $this->refreshNotifications();
    }

    public function markAllAsRead(NotificationService $notifications): void
    {
        $this->authorize('viewAny', SupportNotification::class);

        $notifications->markAllAsRead(Auth::user());
        $this->refreshNotifications();
    }
}; ?>

<flux:dropdown position="bottom" align="end">
    <flux:tooltip :content="__('Notifications')" position="bottom">
        <flux:navbar.item icon="bell" href="#" :label="__('Notifications')" class="relative">
            @if ($unreadCount > 0)
                <span class="absolute end-1 top-1 flex min-w-4 items-center justify-center rounded-full bg-red-500 px-1 text-[10px] font-bold leading-4 text-white ring-2 ring-white dark:ring-zinc-950">
                    {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                </span>
            @endif
        </flux:navbar.item>
    </flux:tooltip>

    <flux:menu class="min-w-80">
        <div class="flex items-center justify-between gap-3 px-3 py-3">
            <div>
                <flux:heading size="sm" class="font-semibold">{{ __('Notifications') }}</flux:heading>
                <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Latest updates for your account') }}</flux:text>
            </div>

            @if ($unreadCount > 0)
                <flux:button size="xs" variant="ghost" wire:click="markAllAsRead" class="shrink-0">
                    {{ __('Mark all read') }}
                </flux:button>
            @endif
        </div>

        <flux:menu.separator />

        @forelse ($latestNotifications as $notification)
            <div wire:key="navbar-notification-{{ $notification['id'] }}" class="group px-2 py-1">
                <div class="flex items-start gap-3 rounded-lg px-2 py-2.5 transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/60">
                    {{-- Unread dot indicator --}}
                    <div class="mt-1.5 flex size-5 shrink-0 items-center justify-center">
                        @if ($notification['read_at'] === null)
                            <span class="size-2 rounded-full bg-blue-500 ring-2 ring-blue-100 dark:ring-blue-500/20"></span>
                        @else
                            <span class="size-2 rounded-full bg-zinc-200 dark:bg-zinc-700"></span>
                        @endif
                    </div>

                    <a href="{{ $notification['link'] ?: route('notifications.index') }}" class="min-w-0 flex-1" wire:navigate>
                        <p class="truncate text-sm font-semibold {{ $notification['read_at'] === null ? 'text-zinc-900 dark:text-white' : 'text-zinc-600 dark:text-zinc-400' }}">
                            {{ $notification['title'] }}
                        </p>
                        <p class="mt-0.5 line-clamp-2 text-xs leading-relaxed text-zinc-500 dark:text-zinc-400">{{ $notification['body'] }}</p>
                        <p class="mt-1 text-[11px] font-medium text-zinc-400 dark:text-zinc-500">{{ $notification['created_at'] }}</p>
                    </a>

                    @if ($notification['read_at'] === null)
                        <flux:button size="xs" variant="ghost" wire:click="markAsRead({{ $notification['id'] }})" class="shrink-0 opacity-0 transition-opacity group-hover:opacity-100">
                            {{ __('Read') }}
                        </flux:button>
                    @endif
                </div>
            </div>
        @empty
            <div class="flex flex-col items-center justify-center px-4 py-8 text-center">
                <div class="mb-3 flex size-12 items-center justify-center rounded-xl bg-zinc-100 dark:bg-zinc-800">
                    <flux:icon name="bell-slash" class="size-5 text-zinc-400 dark:text-zinc-500" />
                </div>
                <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">{{ __('No notifications yet.') }}</p>
                <p class="mt-0.5 text-xs text-zinc-400 dark:text-zinc-500">{{ __("You're all caught up!") }}</p>
            </div>
        @endforelse

        <flux:menu.separator />
        <div class="px-2 py-1">
            <flux:menu.item icon="arrow-right" :href="route('notifications.index')" wire:navigate class="font-medium">
                {{ __('View all notifications') }}
            </flux:menu.item>
        </div>
    </flux:menu>

    @script
    <script>
        window.Echo?.private('users.{{ auth()->id() }}')
            .listen('.notification.created', () => {
                $wire.refreshNotifications();
            });
    </script>
    @endscript
</flux:dropdown>
