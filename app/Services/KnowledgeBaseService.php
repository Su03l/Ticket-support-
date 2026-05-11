<?php

namespace App\Services;

use App\Enums\ArticleStatus;
use App\Enums\ArticleVisibility;
use App\Models\Company;
use App\Models\Faq;
use App\Models\KnowledgeBaseArticle;
use App\Models\KnowledgeBaseCategory;
use App\Models\User;
use Illuminate\Database\Eloquent\Collection;
use Illuminate\Support\Str;

class KnowledgeBaseService
{
    public function search(?Company $company, ?string $search = null, bool $includeInternal = false): Collection
    {
        return KnowledgeBaseArticle::query()
            ->when($company !== null, fn ($query) => $query->where('company_id', $company->id))
            ->where('status', ArticleStatus::Published)
            ->when(! $includeInternal, fn ($query) => $query->where('visibility', ArticleVisibility::Public))
            ->when($search, fn ($query) => $query->where(fn ($query) => $query->where('title', 'like', "%{$search}%")->orWhere('content', 'like', "%{$search}%")))
            ->latest('published_at')
            ->get();
    }

    public function createArticle(User $author, array $attributes): KnowledgeBaseArticle
    {
        return KnowledgeBaseArticle::query()->create([
            'company_id' => $author->company_id,
            'author_id' => $author->id,
            'slug' => Str::slug($attributes['slug'] ?? $attributes['title']),
            'status' => ArticleStatus::Draft,
            'visibility' => ArticleVisibility::Public,
            ...$attributes,
        ]);
    }

    public function createCategory(Company $company, string $name): KnowledgeBaseCategory
    {
        return KnowledgeBaseCategory::query()->create(['company_id' => $company->id, 'name' => $name, 'slug' => Str::slug($name), 'is_active' => true]);
    }

    public function faqs(?Company $company): Collection
    {
        return Faq::query()
            ->when($company !== null, fn ($query) => $query->where('company_id', $company->id))
            ->where('is_active', true)
            ->orderBy('sort_order')
            ->get();
    }
}
