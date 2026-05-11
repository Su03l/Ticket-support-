<?php

use App\Models\MailboxMessage;
use App\Repositories\Contracts\MailboxRepositoryInterface;
use App\Services\MailboxService;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Illuminate\Support\Facades\Auth;
use Illuminate\Support\Str;
use Livewire\Component;

new class extends Component
{
  use AuthorizesRequests;

  public int $unreadCount = 0;

  /**
   * @var array<int, array<string, mixed>>
   */
  public array $latestMessages = [];

  public function mount(MailboxRepositoryInterface $mailbox): void
  {
    $this->refreshMailbox($mailbox);
  }

  public function refreshMailbox(?MailboxRepositoryInterface $mailbox = null): void
  {
    $mailbox ??= app(MailboxRepositoryInterface::class);
    $user = Auth::user();

    $this->unreadCount = $mailbox->unreadCountForRecipient($user);
    $this->latestMessages = $mailbox->latestForRecipient($user, 5)
      ->map(fn (MailboxMessage $message): array => [
        'id'     => $message->id,
        'sender'   => $message->sender?->name ?? __('System'),
        'subject'  => $message->subject,
        'preview'  => Str::limit(strip_tags($message->body), 90),
        'read_at'  => $message->read_at?->toISOString(),
        'created_at' => $message->created_at?->diffForHumans(),
      ])
      ->all();
  }

  public function markAsRead(int $messageId, MailboxService $mailbox): void
  {
    $message = MailboxMessage::query()->findOrFail($messageId);

    $this->authorize('read', $message);

    $mailbox->markAsRead(Auth::user(), $message);
    $this->refreshMailbox();
  }
}; ?>

<flux:dropdown position="bottom" align="end">
  <flux:tooltip :content="__('Mailbox')" position="bottom">
    <flux:navbar.item icon="inbox" href="#" :label="__('Mailbox')" class="relative rounded-full transition-all">
      @if ($unreadCount > 0)
        <span class="absolute end-1.5 top-1.5 flex size-2 items-center justify-center rounded-full bg-blue-500 ring-2 ring-white dark:ring-zinc-950">
        </span>
      @endif
    </flux:navbar.item>
  </flux:tooltip>

  <flux:menu class="w-80 sm:w-[28rem] p-0 overflow-hidden rounded-2xl border-none shadow-2xl ring-1 ring-zinc-200/50 dark:ring-zinc-800/50">
    <div class="flex items-center justify-between gap-4 px-4 py-3 bg-zinc-50/50 dark:bg-zinc-800/30">
      <div>
        <flux:heading size="sm" class="font-bold tracking-tight text-zinc-900 dark:text-white">{{ __('Mailbox') }}</flux:heading>
      </div>
      @if ($unreadCount > 0)
        <span class="rounded-full bg-blue-50 px-2 py-0.5 text-[10px] font-bold text-blue-600 dark:bg-blue-500/10 dark:text-blue-400 shadow-sm ring-1 ring-blue-100 dark:ring-blue-900/30">
          {{ $unreadCount }} {{ __('unread') }}
        </span>
      @endif
    </div>

    <flux:separator />

    <div class="max-h-[32rem] overflow-y-auto">
      @forelse ($latestMessages as $message)
        <div wire:key="navbar-mailbox-message-{{ $message['id'] }}" class="group relative flex items-start gap-3 p-4 transition-all hover:bg-zinc-50/80 dark:hover:bg-zinc-800/40 border-b border-zinc-100 dark:border-zinc-800/50 last:border-0">
          {{-- Sender avatar --}}
          <div class="mt-0.5 flex size-9 shrink-0 items-center justify-center rounded-full bg-gradient-to-br from-zinc-100 to-zinc-200 text-xs font-bold text-zinc-600 ring-1 ring-zinc-200/50 dark:from-zinc-800 dark:to-zinc-900 dark:text-zinc-300 dark:ring-zinc-700/50">
            {{ mb_substr($message['sender'], 0, 1) }}
          </div>

          <a href="{{ route('mailbox.show', $message['id']) }}" class="min-w-0 flex-1 group" wire:navigate>
            <div class="flex items-center justify-between gap-2">
              <p class="truncate text-[10px] font-bold text-zinc-400 dark:text-zinc-500 uppercase tracking-widest">{{ $message['sender'] }}</p>
              <p class="shrink-0 text-[10px] font-medium text-zinc-400 dark:text-zinc-500">{{ $message['created_at'] }}</p>
            </div>
            <p class="mt-0.5 truncate text-sm font-semibold tracking-tight {{ $message['read_at'] === null ? 'text-zinc-900 dark:text-white' : 'text-zinc-500 dark:text-zinc-400' }}">
              {{ $message['subject'] }}
            </p>
            <p class="mt-0.5 line-clamp-2 text-xs leading-relaxed {{ $message['read_at'] === null ? 'text-zinc-600 dark:text-zinc-400' : 'text-zinc-400 dark:text-zinc-500' }}">
              {{ $message['preview'] }}
            </p>
          </a>

          @if ($message['read_at'] === null)
            <div class="absolute end-4 top-1/2 -translate-y-1/2 opacity-0 group-hover:opacity-100 transition-opacity">
              <flux:button size="xs" variant="ghost" square wire:click="markAsRead({{ $message['id'] }})" class="bg-white/80 dark:bg-zinc-900/80 shadow-sm border border-zinc-200 dark:border-zinc-700">
                <flux:icon name="check" variant="micro" />
              </flux:button>
            </div>
          @endif
        </div>
      @empty
        <div class="flex flex-col items-center justify-center px-6 py-12 text-center">
          <div class="mb-4 flex size-12 items-center justify-center rounded-xl bg-zinc-50 dark:bg-zinc-800/50">
            <flux:icon name="inbox" class="size-5 text-zinc-300 dark:text-zinc-600" />
          </div>
          <p class="text-sm font-semibold text-zinc-900 dark:text-white">{{ __('No messages') }}</p>
          <p class="mt-1 text-xs text-zinc-500 dark:text-zinc-400">{{ __('Your inbox is empty.') }}</p>
        </div>
      @endforelse
    </div>

    <flux:separator />

    <div class="p-2">
      <flux:button :href="route('mailbox.index')" variant="ghost" wire:navigate class="w-full justify-center rounded-xl text-sm font-semibold text-zinc-600 dark:text-zinc-400">
        {{ __('View all messages') }}
      </flux:button>
    </div>
  </flux:menu>

  @script
  <script>
    window.Echo?.private('mailbox.users.{{ auth()->id() }}')
      .listen('.mailbox.message.created', () => {
        $wire.refreshMailbox();
      });
  </script>
  @endscript
</flux:dropdown>