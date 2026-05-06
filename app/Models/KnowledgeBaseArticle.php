<?php

namespace App\Models;

use App\Enums\ArticleStatus;
use App\Enums\ArticleVisibility;
use Database\Factories\KnowledgeBaseArticleFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['company_id', 'category_id', 'author_id', 'title', 'slug', 'excerpt', 'content', 'visibility', 'status', 'published_at'])]
class KnowledgeBaseArticle extends Model
{
    /** @use HasFactory<KnowledgeBaseArticleFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return [
            'visibility' => ArticleVisibility::class,
            'status' => ArticleStatus::class,
            'published_at' => 'datetime',
        ];
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function category(): BelongsTo { return $this->belongsTo(KnowledgeBaseCategory::class, 'category_id'); }
    public function author(): BelongsTo { return $this->belongsTo(User::class, 'author_id'); }
}
