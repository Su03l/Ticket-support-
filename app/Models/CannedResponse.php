<?php

namespace App\Models;

use Database\Factories\CannedResponseFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\SoftDeletes;

#[Fillable(['company_id', 'department_id', 'created_by_id', 'title', 'body', 'category', 'is_active'])]
class CannedResponse extends Model
{
    /** @use HasFactory<CannedResponseFactory> */
    use HasFactory, SoftDeletes;

    protected function casts(): array
    {
        return ['is_active' => 'boolean'];
    }
    // the relationships between the tables with the comment  
    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    // the relationship between the department and the Department table
    public function department(): BelongsTo
    {
        return $this->belongsTo(Department::class);
    }

    // the relationship between the createdBy and the User table
    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
