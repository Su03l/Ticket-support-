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

<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-start sm:justify-between">
        <div>
            <flux:heading size="xl">{{ $message->subject }}</flux:heading>
            <flux:text>
                {{ __('From') }} {{ $message->sender?->name ?? __('System') }}
                <span class="text-zinc-400">·</span>
                {{ $message->created_at->format('M j, Y g:i A') }}
            </flux:text>
        </div>

        <div class="flex flex-wrap items-center gap-2">
            @if ($message->isUnread())
                <flux:badge color="blue">{{ __('Unread') }}</flux:badge>
            @else
                <flux:badge color="green">{{ __('Read') }}</flux:badge>
            @endif

            @if ($message->isArchived())
                <flux:badge color="zinc">{{ __('Archived') }}</flux:badge>
            @endif
        </div>
    </div>

    <div class="grid gap-4 rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900 md:grid-cols-3">
        <div>
            <flux:text class="text-xs font-medium uppercase tracking-wide text-zinc-500">{{ __('Sender') }}</flux:text>
            <flux:text>{{ $message->sender?->name ?? __('System') }}</flux:text>
            <flux:text class="text-xs">{{ $message->sender?->email }}</flux:text>
        </div>

        <div>
            <flux:text class="text-xs font-medium uppercase tracking-wide text-zinc-500">{{ __('Recipient') }}</flux:text>
            <flux:text>{{ $message->recipient->name }}</flux:text>
            <flux:text class="text-xs">{{ $message->recipient->email }}</flux:text>
        </div>

        <div>
            <flux:text class="text-xs font-medium uppercase tracking-wide text-zinc-500">{{ __('Related entity') }}</flux:text>
            @if ($message->related_type && $message->related_id)
                <flux:text>{{ $message->related_type }} #{{ $message->related_id }}</flux:text>
            @else
                <flux:text>{{ __('None') }}</flux:text>
            @endif
        </div>
    </div>

    <div class="rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
        <div class="prose max-w-none whitespace-pre-line text-zinc-800 dark:prose-invert dark:text-zinc-100">{{ $message->body }}</div>
    </div>

    <div class="flex flex-wrap items-center gap-3">
        <flux:button variant="ghost" :href="route('mailbox.index')" wire:navigate>
            {{ __('Back to mailbox') }}
        </flux:button>

        @unless ($message->isUnread())
            <flux:button variant="ghost" wire:click="markAsUnread">
                {{ __('Mark unread') }}
            </flux:button>
        @endunless

        @can('archive', $message)
            @unless ($message->isArchived())
                <flux:button variant="primary" wire:click="archiveMessage">
                    {{ __('Archive') }}
                </flux:button>
            @endunless
        @endcan
    </div>
</div>
