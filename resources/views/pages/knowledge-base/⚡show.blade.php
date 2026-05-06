<?php

use App\Models\KnowledgeBaseArticle;
use Illuminate\Foundation\Auth\Access\AuthorizesRequests;
use Livewire\Attributes\Title;
use Livewire\Component;

new #[Title('Article')] class extends Component
{
    use AuthorizesRequests;
    public KnowledgeBaseArticle $article;
    public function mount(KnowledgeBaseArticle $article): void { $this->authorize('view', $article); $this->article = $article; }
}; ?>

<article class="mx-auto max-w-3xl rounded-lg border border-zinc-200 bg-white p-6 dark:border-zinc-800 dark:bg-zinc-900">
    <flux:heading size="xl">{{ $article->title }}</flux:heading>
    <div class="mt-5 whitespace-pre-line text-sm text-zinc-700 dark:text-zinc-200">{{ $article->content }}</div>
</article>
