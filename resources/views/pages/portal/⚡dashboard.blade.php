<?php

use App\Models\Complaint;
use App\Models\Inquiry;
use App\Models\KnowledgeBaseArticle;
use App\Models\Ticket;
use App\Enums\UserType;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Customer portal')] class extends Component
{
    public function mount(): void
    {
        if (! $this->canAccessPortal()) {
            $this->redirectRoute('dashboard', navigate: true);
        }
    }

    public function with(): array
    {
        $user = Auth::user();

        if (! $this->canAccessPortal()) {
            return [
                'ticketCount' => 0,
                'complaintCount' => 0,
                'inquiryCount' => 0,
                'recentTickets' => collect(),
                'articles' => collect(),
            ];
        }

        return [
            'ticketCount' => Ticket::query()->where('customer_id', $user->id)->count(),
            'complaintCount' => Complaint::query()->where('customer_id', $user->id)->count(),
            'inquiryCount' => Inquiry::query()->where('customer_id', $user->id)->count(),
            'recentTickets' => Ticket::query()
                ->where('customer_id', $user->id)
                ->latest()
                ->limit(5)
                ->get(['id', 'ticket_number', 'title', 'status', 'created_at']),
            'articles' => KnowledgeBaseArticle::query()
                ->where('company_id', $user->company_id)
                ->where('status', 'published')
                ->where('visibility', 'public')
                ->latest('published_at')
                ->limit(5)
                ->get(['id', 'title', 'slug', 'excerpt']),
        ];
    }

    private function canAccessPortal(): bool
    {
        $user = Auth::user();

        return $user->company_id !== null && $user->user_type === UserType::Customer;
    }
}; ?>

<div class="flex flex-col gap-6">
    <div class="flex flex-col gap-4 sm:flex-row sm:items-center sm:justify-between">
        <div>
            <flux:heading size="xl">{{ __('Customer portal') }}</flux:heading>
            <flux:text>{{ __('Track your requests, send updates, and find answers.') }}</flux:text>
        </div>

        <div class="flex flex-wrap gap-2">
            <flux:button variant="primary" icon="plus" :href="route('portal.tickets.create')" wire:navigate>{{ __('New ticket') }}</flux:button>
            <flux:button variant="ghost" icon="chat-bubble-left-right" :href="route('portal.inquiries.create')" wire:navigate>{{ __('Ask question') }}</flux:button>
        </div>
    </div>

    <div class="grid gap-4 md:grid-cols-3">
        <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <flux:text>{{ __('Tickets') }}</flux:text>
            <flux:heading size="xl">{{ $ticketCount }}</flux:heading>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <flux:text>{{ __('Complaints') }}</flux:text>
            <flux:heading size="xl">{{ $complaintCount }}</flux:heading>
        </div>
        <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <flux:text>{{ __('Inquiries') }}</flux:text>
            <flux:heading size="xl">{{ $inquiryCount }}</flux:heading>
        </div>
    </div>

    <div class="grid gap-6 xl:grid-cols-2">
        <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <flux:heading size="md">{{ __('Recent tickets') }}</flux:heading>
            <div class="mt-4 flex flex-col gap-3">
                @forelse ($recentTickets as $ticket)
                    <a wire:key="portal-ticket-{{ $ticket->id }}" href="{{ route('portal.tickets.show', $ticket) }}" wire:navigate class="rounded-lg border border-zinc-200 p-3 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800/60">
                        <div class="flex items-center justify-between gap-3">
                            <flux:text class="font-medium">{{ $ticket->ticket_number }}</flux:text>
                            <flux:badge size="sm">{{ __(str_replace('_', ' ', $ticket->status->value)) }}</flux:badge>
                        </div>
                        <flux:text class="mt-1 truncate text-sm">{{ $ticket->title }}</flux:text>
                    </a>
                @empty
                    <flux:text>{{ __('No tickets yet.') }}</flux:text>
                @endforelse
            </div>
        </div>

        <div class="rounded-lg border border-zinc-200 bg-white p-5 dark:border-zinc-800 dark:bg-zinc-900">
            <flux:heading size="md">{{ __('Knowledge base') }}</flux:heading>
            <div class="mt-4 flex flex-col gap-3">
                @forelse ($articles as $article)
                    <a wire:key="portal-article-{{ $article->id }}" href="{{ route('portal.knowledge-base.show', $article) }}" wire:navigate class="rounded-lg border border-zinc-200 p-3 hover:bg-zinc-50 dark:border-zinc-800 dark:hover:bg-zinc-800/60">
                        <flux:text class="font-medium">{{ $article->title }}</flux:text>
                        <flux:text class="mt-1 truncate text-sm">{{ $article->excerpt }}</flux:text>
                    </a>
                @empty
                    <flux:text>{{ __('No articles published yet.') }}</flux:text>
                @endforelse
            </div>
        </div>
    </div>
</div>
