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

<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">{{ __('Mailbox') }}</flux:heading>
            <flux:text>{{ __('Review internal messages, notices, and actionable updates.') }}</flux:text>
        </div>

        <flux:select wire:model.live="status" class="w-44">
            <flux:select.option value="all">{{ __('Inbox') }}</flux:select.option>
            <flux:select.option value="unread">{{ __('Unread') }}</flux:select.option>
            <flux:select.option value="read">{{ __('Read') }}</flux:select.option>
            <flux:select.option value="archived">{{ __('Archived') }}</flux:select.option>
        </flux:select>
    </div>

    <div class="overflow-hidden rounded-lg border border-zinc-200 bg-white dark:border-zinc-800 dark:bg-zinc-900">
        @forelse ($messages as $message)
            <div wire:key="mailbox-message-{{ $message->id }}" class="border-b border-zinc-200 p-4 last:border-b-0 dark:border-zinc-800">
                <div class="flex flex-col gap-3 sm:flex-row sm:items-start sm:justify-between">
                    <a href="{{ route('mailbox.show', $message) }}" class="min-w-0 flex-1" wire:navigate>
                        <div class="flex flex-wrap items-center gap-2">
                            <flux:heading size="sm">{{ $message->subject }}</flux:heading>
                            @if ($message->isUnread())
                                <flux:badge color="blue" size="sm">{{ __('Unread') }}</flux:badge>
                            @endif
                            @if ($message->isArchived())
                                <flux:badge color="zinc" size="sm">{{ __('Archived') }}</flux:badge>
                            @endif
                        </div>

                        <flux:text class="mt-1 text-sm">
                            {{ $message->sender?->name ?? __('System') }}
                            <span class="text-zinc-400">·</span>
                            {{ Str::limit(strip_tags($message->body), 140) }}
                        </flux:text>
                        <flux:text class="mt-2 text-xs text-zinc-500">
                            {{ $message->created_at->diffForHumans() }}
                        </flux:text>
                    </a>

                    <div class="flex shrink-0 flex-wrap items-center gap-2">
                        @if ($message->isUnread())
                            <flux:button size="sm" variant="ghost" wire:click="markAsRead({{ $message->id }})">
                                {{ __('Mark read') }}
                            </flux:button>
                        @else
                            <flux:button size="sm" variant="ghost" wire:click="markAsUnread({{ $message->id }})">
                                {{ __('Mark unread') }}
                            </flux:button>
                        @endif

                        @can('archive', $message)
                            @unless ($message->isArchived())
                                <flux:button size="sm" variant="ghost" wire:click="archiveMessage({{ $message->id }})">
                                    {{ __('Archive') }}
                                </flux:button>
                            @endunless
                        @endcan
                    </div>
                </div>
            </div>
        @empty
            <div class="p-10 text-center">
                <flux:heading size="md">{{ __('No mailbox messages found.') }}</flux:heading>
                <flux:text>{{ __('Internal messages and admin notices will appear here.') }}</flux:text>
            </div>
        @endforelse
    </div>

    {{ $messages->links() }}
</div>
