<?php

namespace App\Models;

use Database\Factories\KnowledgeBaseCategoryFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;

#[Fillable(['company_id', 'name', 'slug', 'description', 'is_active'])]
class KnowledgeBaseCategory extends Model
{
    /** @use HasFactory<KnowledgeBaseCategoryFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function articles(): HasMany { return $this->hasMany(KnowledgeBaseArticle::class, 'category_id'); }
}
