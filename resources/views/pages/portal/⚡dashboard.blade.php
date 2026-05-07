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

<div class="flex flex-col gap-10">
    <x-page-header :title="__('Customer portal')" :description="__('Track your requests, send updates, and find answers.')">
        <x-slot:actions>
            <flux:button variant="primary" icon="plus" :href="route('portal.tickets.create')" wire:navigate class="font-bold rounded-xl shadow-lg shadow-zinc-200/50 dark:shadow-none">{{ __('New ticket') }}</flux:button>
            <flux:button variant="ghost" icon="chat-bubble-left-right" :href="route('portal.inquiries.create')" wire:navigate class="font-bold rounded-xl">{{ __('Ask question') }}</flux:button>
        </x-slot:actions>
    </x-page-header>

    <div class="grid gap-6 md:grid-cols-3">
        <x-stat-card :label="__('Tickets')" :value="$ticketCount" icon="ticket" accent="blue" />
        <x-stat-card :label="__('Complaints')" :value="$complaintCount" icon="exclamation-triangle" accent="amber" />
        <x-stat-card :label="__('Inquiries')" :value="$inquiryCount" icon="chat-bubble-left-right" accent="emerald" />
    </div>

    <div class="grid gap-8 xl:grid-cols-2">
        <x-section-card :heading="__('Recent tickets')" icon="clock">
            <div class="flex flex-col gap-4">
                @forelse ($recentTickets as $ticket)
                    <a wire:key="portal-ticket-{{ $ticket->id }}" href="{{ route('portal.tickets.show', $ticket) }}" wire:navigate class="group rounded-2xl border border-zinc-200/60 p-4 transition-all hover:bg-zinc-50 hover:shadow-sm dark:border-zinc-800/60 dark:hover:bg-zinc-800/40">
                        <div class="flex items-center justify-between gap-4">
                            <flux:text class="font-bold tracking-tight text-zinc-900 dark:text-white uppercase text-xs">{{ $ticket->ticket_number }}</flux:text>
                            <x-status-badge :status="$ticket->status->value" />
                        </div>
                        <flux:text class="mt-2 truncate text-base font-semibold group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">{{ $ticket->title }}</flux:text>
                        <flux:text class="mt-1 text-xs font-medium text-zinc-400">{{ $ticket->created_at->diffForHumans() }}</flux:text>
                    </a>
                @empty
                    <div class="py-8 text-center">
                        <flux:text class="font-medium">{{ __('No tickets yet.') }}</flux:text>
                    </div>
                @endforelse
            </div>
            @if ($recentTickets->isNotEmpty())
                <div class="mt-6 border-t border-zinc-100/80 pt-4 dark:border-zinc-800">
                    <flux:link :href="route('portal.tickets.index')" wire:navigate class="text-sm font-bold">{{ __('View all tickets') }}</flux:link>
                </div>
            @endif
        </x-section-card>

        <x-section-card :heading="__('Knowledge base')" icon="book-open">
            <div class="flex flex-col gap-4">
                @forelse ($articles as $article)
                    <a wire:key="portal-article-{{ $article->id }}" href="{{ route('portal.knowledge-base.show', $article) }}" wire:navigate class="group rounded-2xl border border-zinc-200/60 p-4 transition-all hover:bg-zinc-50 hover:shadow-sm dark:border-zinc-800/60 dark:hover:bg-zinc-800/40">
                        <flux:text class="text-base font-bold tracking-tight text-zinc-900 dark:text-white group-hover:text-blue-600 dark:group-hover:text-blue-400 transition-colors">{{ $article->title }}</flux:text>
                        <p class="mt-1.5 line-clamp-2 text-sm leading-relaxed text-zinc-500 dark:text-zinc-400 font-medium">{{ $article->excerpt }}</p>
                    </a>
                @empty
                    <div class="py-8 text-center">
                        <flux:text class="font-medium">{{ __('No articles published yet.') }}</flux:text>
                    </div>
                @endforelse
            </div>
            @if ($articles->isNotEmpty())
                <div class="mt-6 border-t border-zinc-100/80 pt-4 dark:border-zinc-800">
                    <flux:link :href="route('knowledge-base.index')" wire:navigate class="text-sm font-bold">{{ __('Browse knowledge base') }}</flux:link>
                </div>
            @endif
        </x-section-card>
    </div>
</div>