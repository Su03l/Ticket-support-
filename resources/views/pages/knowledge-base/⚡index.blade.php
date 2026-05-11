<?php

use App\Models\KnowledgeBaseArticle;
use App\Services\KnowledgeBaseService;
use Illuminate\Support\Facades\Auth;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Knowledge base')] class extends Component
{
 public string $search = '';

 public function mount(): void { abort_unless(Auth::user()->can('knowledge_base.view'), 403); }

 public function with(KnowledgeBaseService $knowledge): array
 {
  return ['articles' => $knowledge->search(Auth::user()->company, $this->search ?: null, Auth::user()->can('tickets.reply'))];
 }
}; ?>

<div class="flex flex-col gap-6">
 <div><flux:heading size="xl">{{ __('Knowledge base') }}</flux:heading><flux:text>{{ __('Company-scoped support articles and guides.') }}</flux:text></div>
 <flux:input wire:model.live.debounce.300ms="search" icon="magnifying-glass":placeholder="__('Search articles')"/>
 <div class="grid gap-3 md:grid-cols-2">
  @forelse ($articles as $article)
   <a wire:key="article-{{ $article->id }}"href="{{ route('knowledge-base.show', $article) }}" wire:navigate class="rounded-lg border border-zinc-200 bg-white p-4 dark:border-zinc-800 dark:bg-zinc-900">
    <flux:heading size="sm">{{ $article->title }}</flux:heading>
    <flux:text class="line-clamp-2">{{ $article->excerpt ?? $article->content }}</flux:text>
   </a>
  @empty
   <flux:text>{{ __('No articles found.') }}</flux:text>
  @endforelse
 </div>
</div>
