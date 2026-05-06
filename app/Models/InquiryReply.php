<?php

namespace App\Models;

use App\Enums\ReplyVisibility;
use Database\Factories\InquiryReplyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\MorphMany;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['company_id', 'inquiry_id', 'user_id', 'body', 'visibility'])]
class InquiryReply extends Model
{
    /** @use HasFactory<InquiryReplyFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return ['visibility' => ReplyVisibility::class];
    }

    public function company(): BelongsTo { return $this->belongsTo(Company::class); }
    public function inquiry(): BelongsTo { return $this->belongsTo(Inquiry::class); }
    public function user(): BelongsTo { return $this->belongsTo(User::class); }
    public function attachments(): MorphMany { return $this->morphMany(Attachment::class, 'attachable'); }
}
