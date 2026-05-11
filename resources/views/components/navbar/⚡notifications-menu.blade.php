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
        'id'     => $notification->id,
        'title'   => $notification->title,
        'body'    => $notification->body,
        'link'    => $notification->link,
        'read_at'  => $notification->read_at?->toISOString(),
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
    <flux:navbar.item icon="bell" href="#" :label="__('Notifications')" class="relative rounded-full transition-all">
      @if ($unreadCount > 0)
        <span class="absolute end-1.5 top-1.5 flex size-2 items-center justify-center rounded-full bg-red-500 ring-2 ring-white dark:ring-zinc-950">
        </span>
      @endif
    </flux:navbar.item>
  </flux:tooltip>

  <flux:menu class="w-80 sm:w-96 p-0 overflow-hidden rounded-2xl border-none shadow-2xl ring-1 ring-zinc-200/50 dark:ring-zinc-800/50">
    <div class="flex items-center justify-between gap-4 px-4 py-3 bg-zinc-50/50 dark:bg-zinc-800/30">
      <div>
        <flux:heading size="sm" class="font-bold tracking-tight text-zinc-900 dark:text-white">{{ __('Notifications') }}</flux:heading>
      </div>

      @if ($unreadCount > 0)
        <flux:button size="xs" variant="ghost" wire:click="markAllAsRead" class="shrink-0 font-semibold text-blue-600 hover:text-blue-700 dark:text-blue-400">
          {{ __('Mark all read') }}
        </flux:button>
      @endif
    </div>

    <flux:separator />

    <div class="max-h-[32rem] overflow-y-auto">
      @forelse ($latestNotifications as $notification)
        <div wire:key="navbar-notification-{{ $notification['id'] }}" class="group relative flex items-start gap-3 p-4 transition-all hover:bg-zinc-50/80 dark:hover:bg-zinc-800/40 border-b border-zinc-100 dark:border-zinc-800/50 last:border-0">
          <div class="mt-1 flex size-8 shrink-0 items-center justify-center rounded-lg {{ $notification['read_at'] === null ? 'bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400' : 'bg-zinc-100 text-zinc-500 dark:bg-zinc-800 dark:text-zinc-500' }}">
            <flux:icon name="bell" size="sm" variant="mini" />
          </div>

          <a href="{{ $notification['link'] ?: route('notifications.index') }}" class="min-w-0 flex-1 group" wire:navigate>
            <div class="flex items-center justify-between gap-2">
              <p class="truncate text-sm font-semibold {{ $notification['read_at'] === null ? 'text-zinc-900 dark:text-white' : 'text-zinc-500 dark:text-zinc-400' }}">
                {{ $notification['title'] }}
              </p>
              <p class="shrink-0 text-[10px] font-medium text-zinc-400 dark:text-zinc-500">{{ $notification['created_at'] }}</p>
            </div>
            <p class="mt-0.5 line-clamp-2 text-xs leading-relaxed {{ $notification['read_at'] === null ? 'text-zinc-600 dark:text-zinc-400' : 'text-zinc-400 dark:text-zinc-500' }}">
              {{ $notification['body'] }}
            </p>
          </a>

          @if ($notification['read_at'] === null)
            <div class="absolute end-4 top-1/2 -translate-y-1/2 opacity-0 group-hover:opacity-100 transition-opacity">
              <flux:button size="xs" variant="ghost" square wire:click="markAsRead({{ $notification['id'] }})" class="bg-white/80 dark:bg-zinc-900/80 shadow-sm border border-zinc-200 dark:border-zinc-700">
                <flux:icon name="check" variant="micro" />
              </flux:button>
            </div>
          @endif
        </div>
      @empty
        <div class="flex flex-col items-center justify-center px-6 py-12 text-center">
          <div class="mb-4 flex size-12 items-center justify-center rounded-xl bg-zinc-50 dark:bg-zinc-800/50">
            <flux:icon name="bell-slash" class="size-5 text-zinc-300 dark:text-zinc-600" />
          </div>
          <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('No notifications') }}</p>
          <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __("You're all caught up!") }}</p>
        </div>
      @endforelse
    </div>

    <flux:separator />

    <div class="p-2">
      <flux:button :href="route('notifications.index')" variant="ghost" wire:navigate class="w-full justify-center rounded-xl text-sm font-semibold text-zinc-600 dark:text-zinc-400">
        {{ __('View all notifications') }}
      </flux:button>
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