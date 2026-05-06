<?php

namespace App\Policies;

use App\Enums\ArticleVisibility;
use App\Models\KnowledgeBaseArticle;
use App\Models\User;

class KnowledgeBaseArticlePolicy
{
    public function viewAny(User $user): bool { return $user->can('knowledge_base.view'); }
    public function view(User $user, KnowledgeBaseArticle $article): bool
    {
        return $article->company_id === $user->company_id && ($article->visibility !== ArticleVisibility::Internal || $user->can('tickets.reply'));
    }
    public function create(User $user): bool { return $user->can('knowledge_base.create'); }
    public function update(User $user, KnowledgeBaseArticle $article): bool { return $user->can('knowledge_base.update') && $article->company_id === $user->company_id; }
    public function delete(User $user, KnowledgeBaseArticle $article): bool { return $user->can('knowledge_base.delete') && $article->company_id === $user->company_id; }
}
