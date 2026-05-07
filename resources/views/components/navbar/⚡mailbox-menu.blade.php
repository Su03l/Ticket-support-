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
        <flux:navbar.item icon="inbox" href="#" :label="__('Mailbox')" class="relative rounded-full hover:bg-zinc-100 dark:hover:bg-zinc-800 transition-colors">
            @if ($unreadCount > 0)
                <span class="absolute end-1 top-1 flex min-w-4 items-center justify-center rounded-full bg-blue-500 px-1.5 text-[10px] font-bold leading-4 text-white ring-2 ring-white dark:ring-zinc-950">
                    {{ $unreadCount > 99 ? '99+' : $unreadCount }}
                </span>
            @endif
        </flux:navbar.item>
    </flux:tooltip>

    <flux:menu class="min-w-[28rem] rounded-2xl shadow-xl shadow-zinc-200/40 dark:shadow-zinc-900/50">
        <div class="flex items-center justify-between gap-4 px-5 py-4">
            <div>
                <flux:heading size="sm" class="font-bold tracking-tight text-zinc-900 dark:text-white">{{ __('Mailbox') }}</flux:heading>
                <flux:text class="mt-0.5 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Latest internal messages') }}</flux:text>
            </div>
            @if ($unreadCount > 0)
                <span class="rounded-full bg-blue-50 px-2.5 py-1 text-xs font-bold text-blue-600 dark:bg-blue-500/10 dark:text-blue-400 shadow-sm">
                    {{ $unreadCount }} {{ __('unread') }}
                </span>
            @endif
        </div>

        <flux:menu.separator />

        @forelse ($latestMessages as $message)
            <div wire:key="navbar-mailbox-message-{{ $message['id'] }}" class="group px-2 py-1.5">
                <div class="flex items-start gap-4 rounded-xl px-3 py-3 transition-all hover:bg-zinc-50 dark:hover:bg-zinc-800/60">
                    {{-- Sender avatar --}}
                    <div class="mt-0.5 flex size-10 shrink-0 items-center justify-center rounded-full bg-zinc-100 text-sm font-bold text-zinc-600 ring-2 ring-zinc-50 dark:bg-zinc-800 dark:text-zinc-300 dark:ring-zinc-700/50">
                        {{ mb_substr($message['sender'], 0, 1) }}
                    </div>

                    <a href="{{ route('mailbox.show', $message['id']) }}" class="min-w-0 flex-1" wire:navigate>
                        <div class="flex items-center gap-2">
                            <p class="truncate text-xs font-bold text-zinc-500 dark:text-zinc-400 uppercase tracking-wide">{{ $message['sender'] }}</p>
                            @if ($message['read_at'] === null)
                                <span class="size-2 rounded-full bg-blue-500 shrink-0 ring-4 ring-blue-50 dark:ring-blue-500/20"></span>
                            @endif
                            <p class="ml-auto shrink-0 text-xs font-medium text-zinc-400 dark:text-zinc-500">{{ $message['created_at'] }}</p>
                        </div>
                        <p class="mt-1 truncate text-sm font-bold tracking-tight {{ $message['read_at'] === null ? 'text-zinc-900 dark:text-white' : 'text-zinc-600 dark:text-zinc-400' }}">
                            {{ $message['subject'] }}
                        </p>
                        <p class="mt-1 line-clamp-2 text-sm leading-relaxed text-zinc-500 dark:text-zinc-400">{{ $message['preview'] }}</p>
                    </a>

                    @if ($message['read_at'] === null)
                        <flux:button size="xs" variant="ghost" wire:click="markAsRead({{ $message['id'] }})" class="shrink-0 opacity-0 transition-opacity group-hover:opacity-100">
                            {{ __('Read') }}
                        </flux:button>
                    @endif
                </div>
            </div>
        @empty
            <div class="flex flex-col items-center justify-center px-6 py-12 text-center">
                <div class="mb-4 flex size-14 items-center justify-center rounded-2xl bg-zinc-100 dark:bg-zinc-800/50">
                    <flux:icon name="inbox" class="size-6 text-zinc-400 dark:text-zinc-500" />
                </div>
                <p class="text-base font-semibold tracking-tight text-zinc-900 dark:text-white">{{ __('No mailbox messages yet.') }}</p>
                <p class="mt-1 text-sm text-zinc-500 dark:text-zinc-400">{{ __('Messages will appear here.') }}</p>
            </div>
        @endforelse

        <flux:menu.separator />
        <div class="px-2 py-2">
            <flux:menu.item icon="arrow-right" :href="route('mailbox.index')" wire:navigate class="rounded-xl font-semibold text-blue-600 dark:text-blue-400">
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