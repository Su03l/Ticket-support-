<?php

use App\Models\Complaint;
use App\Models\Inquiry;
use App\Models\KnowledgeBaseArticle;
use App\Models\Ticket;
use App\Enums\UserType;
use App\Enums\TicketStatus;
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
    'waitingCount' => 0,
    'resolvedCount' => 0,
    'recentTickets' => collect(),
    'articles' => collect(),
   ];
  }

  return [
   'ticketCount' => Ticket::query()->where('customer_id', $user->id)->count(),
   'waitingCount' => Ticket::query()->where('customer_id', $user->id)->where('status', TicketStatus::WaitingCustomer)->count(),
   'resolvedCount' => Ticket::query()->where('customer_id', $user->id)->where('status', TicketStatus::Resolved)->count(),
   'recentTickets' => Ticket::query()
    ->where('customer_id', $user->id)
    ->with(['priority', 'department'])
    ->latest()
    ->limit(4)
    ->get(),
   'articles' => KnowledgeBaseArticle::query()
    ->where('company_id', $user->company_id)
    ->where('status', 'published')
    ->where('visibility', 'public')
    ->latest('published_at')
    ->limit(3)
    ->get(),
  ];
 }

 private function canAccessPortal(): bool
 {
  $user = Auth::user();

  return $user && $user->company_id !== null && $user->user_type === UserType::Customer;
 }
}; ?>

<div class="flex flex-col gap-8">
 <div class="flex flex-col gap-1.5">
  <flux:heading size="xl" class="font-semibold text-zinc-900 dark:text-white">{{ __('Welcome back, :name', ['name' => auth()->user()->name]) }}</flux:heading>
  <flux:text class="text-sm font-medium text-zinc-500">{{ __('How can we help you today?') }}</flux:text>
 </div>

 {{-- Quick Actions --}}
 <div class="grid gap-4 sm:grid-cols-2 lg:grid-cols-3">
  <a href="{{ route('portal.tickets.create') }}" wire:navigate class="group relative flex flex-col gap-3 overflow-hidden rounded-2xl border border-zinc-200 bg-white p-5 transition-all hover:border-blue-500 hover:shadow-lg hover:shadow-blue-500/10 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-blue-500">
   <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-blue-50 text-blue-600 dark:bg-blue-500/10 dark:text-blue-400">
    <flux:icon name="plus" variant="mini"/>
   </div>
   <div>
    <flux:heading size="md" class="font-semibold">{{ __('New Support Ticket') }}</flux:heading>
    <flux:text class="mt-1 text-sm">{{ __('Open a new request for our support team.') }}</flux:text>
   </div>
   <div class="absolute -right-4 -bottom-4 opacity-[0.03] transition-opacity group-hover:opacity-[0.07]">
    <flux:icon name="ticket" size="xl" class="h-24 w-24"/>
   </div>
  </a>

  <a href="{{ route('knowledge-base.index') }}" wire:navigate class="group relative flex flex-col gap-3 overflow-hidden rounded-2xl border border-zinc-200 bg-white p-5 transition-all hover:border-emerald-500 hover:shadow-lg hover:shadow-emerald-500/10 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-emerald-500">
   <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-emerald-50 text-emerald-600 dark:bg-emerald-500/10 dark:text-emerald-400">
    <flux:icon name="book-open" variant="mini"/>
   </div>
   <div>
    <flux:heading size="md" class="font-semibold">{{ __('Knowledge Base') }}</flux:heading>
    <flux:text class="mt-1 text-sm">{{ __('Browse guides and frequently asked questions.') }}</flux:text>
   </div>
   <div class="absolute -right-4 -bottom-4 opacity-[0.03] transition-opacity group-hover:opacity-[0.07]">
    <flux:icon name="book-open" size="xl" class="h-24 w-24"/>
   </div>
  </a>

  <a href="{{ route('portal.inquiries.create') }}" wire:navigate class="group relative flex flex-col gap-3 overflow-hidden rounded-2xl border border-zinc-200 bg-white p-5 transition-all hover:border-amber-500 hover:shadow-lg hover:shadow-amber-500/10 dark:border-zinc-800 dark:bg-zinc-900 dark:hover:border-amber-500">
   <div class="flex h-12 w-12 items-center justify-center rounded-2xl bg-amber-50 text-amber-600 dark:bg-amber-500/10 dark:text-amber-400">
    <flux:icon name="chat-bubble-left-right" variant="mini"/>
   </div>
   <div>
    <flux:heading size="md" class="font-semibold">{{ __('Ask a Question') }}</flux:heading>
    <flux:text class="mt-1 text-sm">{{ __('Send us a quick inquiry about our services.') }}</flux:text>
   </div>
   <div class="absolute -right-4 -bottom-4 opacity-[0.03] transition-opacity group-hover:opacity-[0.07]">
    <flux:icon name="chat-bubble-left-right" size="xl" class="h-24 w-24"/>
   </div>
  </a>
 </div>

 {{-- Stats Row --}}
 <div class="grid gap-4 md:grid-cols-3">
  <div class="flex items-center gap-4 rounded-2xl border border-zinc-100 bg-zinc-50/50 p-5 dark:border-zinc-800 dark:bg-zinc-800/30">
   <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white shadow-sm dark:bg-zinc-800">
    <flux:icon name="ticket" size="sm" class="text-zinc-500"/>
   </div>
   <div>
    <span class="text-2xl font-semibold text-zinc-900 dark:text-white">{{ $ticketCount }}</span>
    <span class="ml-1 text-sm font-semibold text-zinc-500 uppercase tracking-wide">{{ __('Total Tickets') }}</span>
   </div>
  </div>
  <div class="flex items-center gap-4 rounded-2xl border border-zinc-100 bg-zinc-50/50 p-5 dark:border-zinc-800 dark:bg-zinc-800/30">
   <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white shadow-sm dark:bg-zinc-800">
    <flux:icon name="clock" size="sm" class="text-amber-500"/>
   </div>
   <div>
    <span class="text-2xl font-semibold text-amber-600 dark:text-amber-400">{{ $waitingCount }}</span>
    <span class="ml-1 text-sm font-semibold text-zinc-500 uppercase tracking-wide">{{ __('Waiting for You') }}</span>
   </div>
  </div>
  <div class="flex items-center gap-4 rounded-2xl border border-zinc-100 bg-zinc-50/50 p-5 dark:border-zinc-800 dark:bg-zinc-800/30">
   <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-full bg-white shadow-sm dark:bg-zinc-800">
    <flux:icon name="check-circle" size="sm" class="text-emerald-500"/>
   </div>
   <div>
    <span class="text-2xl font-semibold text-emerald-600 dark:text-emerald-400">{{ $resolvedCount }}</span>
    <span class="ml-1 text-sm font-semibold text-zinc-500 uppercase tracking-wide">{{ __('Resolved') }}</span>
   </div>
  </div>
 </div>

 <div class="grid gap-8 xl:grid-cols-[1fr_24rem]">
  {{-- Recent Tickets --}}
  <div class="space-y-5">
   <div class="flex items-center justify-between">
    <flux:heading size="lg" class="font-semibold">{{ __('Active Requests') }}</flux:heading>
    <flux:button variant="ghost" size="sm":href="route('portal.tickets.index')" wire:navigate class="font-semibold">{{ __('View all') }}</flux:button>
   </div>

   <div class="grid gap-4">
    @forelse ($recentTickets as $ticket)
     <a wire:key="portal-ticket-{{ $ticket->id }}"href="{{ route('portal.tickets.show', $ticket) }}" wire:navigate class="group flex flex-col gap-3 rounded-2xl border border-zinc-200 bg-white p-4 transition-all hover:border-blue-500/50 hover:shadow-md dark:border-zinc-800 dark:bg-zinc-900">
      <div class="flex items-center justify-between gap-4">
       <div class="flex items-center gap-3">
        <span class="text-[10px] font-semibold uppercase tracking-wide text-zinc-400 bg-zinc-100 px-1.5 py-0.5 rounded-md dark:bg-zinc-800">{{ $ticket->ticket_number }}</span>
        <x-status-badge :status="$ticket->status->value"/>
       </div>
       <span class="text-xs font-medium text-zinc-400">{{ $ticket->created_at->diffForHumans() }}</span>
      </div>
      
      <div class="flex items-center justify-between gap-4">
       <div class="min-w-0">
        <h3 class="truncate text-base font-semibold text-zinc-900 dark:text-white group-hover:text-blue-600 transition-colors">{{ $ticket->title }}</h3>
        <div class="mt-1 flex items-center gap-2">
         <span class="text-xs font-medium text-zinc-500">{{ $ticket->department?->name }}</span>
         @if($ticket->priority)
          <span class="h-1 w-1 rounded-full bg-zinc-300"></span>
          <span class="text-xs font-semibold text-zinc-400 uppercase tracking-wide">{{ $ticket->priority->name }}</span>
         @endif
        </div>
       </div>
       <div class="flex h-10 w-10 shrink-0 items-center justify-center rounded-2xl bg-zinc-50 text-zinc-400 group-hover:bg-blue-50 group-hover:text-blue-600 transition-colors dark:bg-zinc-800 dark:group-hover:bg-blue-500/10">
        <flux:icon name="chevron-right" variant="mini"/>
       </div>
      </div>
     </a>
    @empty
     <div class="flex flex-col items-center justify-center py-16 text-center rounded-2xl border-2 border-dashed border-zinc-100 dark:border-zinc-800">
      <div class="mb-4 rounded-full bg-zinc-50 p-4 dark:bg-zinc-800">
       <flux:icon name="ticket" size="lg" class="text-zinc-300"/>
      </div>
      <flux:heading size="md" class="font-semibold text-zinc-400">{{ __('No active requests') }}</flux:heading>
      <flux:text class="mt-1 max-w-xs">{{ __('When you open a ticket or inquiry, it will appear here for you to track.') }}</flux:text>
      <flux:button variant="primary" icon="plus" class="mt-6":href="route('portal.tickets.create')" wire:navigate>{{ __('Open your first ticket') }}</flux:button>
     </div>
    @endforelse
   </div>
  </div>

  {{-- Knowledge Base Sidebar --}}
  <div class="space-y-5">
   <div class="flex items-center justify-between">
    <flux:heading size="lg" class="font-semibold">{{ __('Helpful Articles') }}</flux:heading>
   </div>

   <div class="flex flex-col gap-4">
    @forelse ($articles as $article)
     <a wire:key="portal-article-{{ $article->id }}"href="{{ route('portal.knowledge-base.show', $article) }}" wire:navigate class="group rounded-2xl border border-zinc-100 bg-zinc-50/50 p-4 transition-all hover:bg-white hover:shadow-md dark:border-zinc-800 dark:bg-zinc-800/30 dark:hover:bg-zinc-800/60">
      <h4 class="text-sm font-semibold text-zinc-900 dark:text-white group-hover:text-blue-600 transition-colors">{{ $article->title }}</h4>
      <p class="mt-2 line-clamp-2 text-sm leading-relaxed text-zinc-500 dark:text-zinc-400 font-medium">{{ $article->excerpt }}</p>
      <div class="mt-3 flex items-center gap-1 text-xs font-semibold text-blue-600 dark:text-blue-400">
       {{ __('Read article') }}
       <flux:icon name="arrow-right" variant="mini" class="transition-transform group-hover:translate-x-1"/>
      </div>
     </a>
    @empty
     <div class="py-8 text-center">
      <flux:text class="font-medium text-zinc-400">{{ __('No articles published yet.') }}</flux:text>
     </div>
    @endforelse
   </div>

   @if ($articles->isNotEmpty())
    <flux:button variant="ghost" class="w-full font-semibold":href="route('knowledge-base.index')" wire:navigate>
     {{ __('Browse Knowledge Base') }}
    </flux:button>
   @endif
  </div>
 </div>
</div>