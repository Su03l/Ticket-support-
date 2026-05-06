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
                'id'         => $message->id,
                'sender'     => $message->sender?->name ?? __('System'),
                'subject'    => $message->subject,
                'preview'    => Str::limit(strip_tags($message->body), 90),
                'read_at'    => $message->read_at?->toISOString(),
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
        <flux:navbar.item icon="inbox" href="#" :label="__('Mailbox')" class="relative">
            @if ($unreadCount > 0)
                <span class="absolute end-1 top-1 flex min-w-4 items-center justify-center rounded-full bg-blue-500 px-1 text-[10px] font-bold leading-4 text-white ring-2 ring-white dark:ring-zinc-950">
                    {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                </span>
            @endif
        </flux:navbar.item>
    </flux:tooltip>

    <flux:menu class="min-w-80">
        <div class="flex items-center justify-between gap-3 px-3 py-3">
            <div>
                <flux:heading size="sm" class="font-semibold">{{ __('Mailbox') }}</flux:heading>
                <flux:text class="text-xs text-zinc-500 dark:text-zinc-400">{{ __('Latest internal messages') }}</flux:text>
            </div>
            @if ($unreadCount > 0)
                <span class="rounded-full bg-blue-50 px-2 py-0.5 text-xs font-semibold text-blue-600 dark:bg-blue-500/10 dark:text-blue-400">
                    {{ $unreadCount }} {{ __('unread') }}
                </span>
            @endif
        </div>

        <flux:menu.separator />

        @forelse ($latestMessages as $message)
            <div wire:key="navbar-mailbox-message-{{ $message['id'] }}" class="group px-2 py-1">
                <div class="flex items-start gap-3 rounded-lg px-2 py-2.5 transition-colors hover:bg-zinc-50 dark:hover:bg-zinc-800/60">
                    {{-- Sender avatar --}}
                    <div class="mt-0.5 flex size-8 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-xs font-bold text-zinc-600 dark:bg-zinc-800 dark:text-zinc-300">
                        {{ mb_substr($message['sender'], 0, 1) }}
                    </div>

                    <a href="{{ route('mailbox.show', $message['id']) }}" class="min-w-0 flex-1" wire:navigate>
                        <div class="flex items-center gap-2">
                            <p class="truncate text-xs font-semibold text-zinc-500 dark:text-zinc-400">{{ $message['sender'] }}</p>
                            @if ($message['read_at'] === null)
                                <span class="size-1.5 rounded-full bg-blue-500 shrink-0"></span>
                            @endif
                            <p class="ml-auto shrink-0 text-[11px] text-zinc-400 dark:text-zinc-500">{{ $message['created_at'] }}</p>
                        </div>
                        <p class="mt-0.5 truncate text-sm font-semibold {{ $message['read_at'] === null ? 'text-zinc-900 dark:text-white' : 'text-zinc-600 dark:text-zinc-400' }}">
                            {{ $message['subject'] }}
                        </p>
                        <p class="mt-0.5 line-clamp-2 text-xs leading-relaxed text-zinc-500 dark:text-zinc-400">{{ $message['preview'] }}</p>
                    </a>

                    @if ($message['read_at'] === null)
                        <flux:button size="xs" variant="ghost" wire:click="markAsRead({{ $message['id'] }})" class="shrink-0 opacity-0 transition-opacity group-hover:opacity-100">
                            {{ __('Read') }}
                        </flux:button>
                    @endif
                </div>
            </div>
        @empty
            <div class="flex flex-col items-center justify-center px-4 py-8 text-center">
                <div class="mb-3 flex size-12 items-center justify-center rounded-xl bg-zinc-100 dark:bg-zinc-800">
                    <flux:icon name="inbox" class="size-5 text-zinc-400 dark:text-zinc-500" />
                </div>
                <p class="text-sm font-medium text-zinc-600 dark:text-zinc-400">{{ __('No mailbox messages yet.') }}</p>
                <p class="mt-0.5 text-xs text-zinc-400 dark:text-zinc-500">{{ __('Messages will appear here.') }}</p>
            </div>
        @endforelse

        <flux:menu.separator />
        <div class="px-2 py-1">
            <flux:menu.item icon="arrow-right" :href="route('mailbox.index')" wire:navigate class="font-medium">
                {{ __('View all messages') }}
            </flux:menu.item>
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
