<?php

namespace App\Models;

use App\Enums\CompanyStatus;
use Database\Factories\CompanyFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Relations\BelongsTo;
use Illuminate\Database\Eloquent\Relations\HasMany;
use Illuminate\Database\Eloquent\Relations\HasOne;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\SoftDeletes;

// the fillable array is used to specify the columns that can be mass assigned
#[Fillable(['name', 'slug', 'email', 'phone', 'website', 'status', 'plan_id', 'trial_ends_at', 'suspended_at'])]
class Company extends Model
{
    /** @use HasFactory<CompanyFactory> */
    use HasFactory, SoftDeletes;

    /**
     * @return array<string, string>
     */
    protected function casts(): array
    {
        return [
            'status' => CompanyStatus::class,
            'trial_ends_at' => 'datetime',
            'suspended_at' => 'datetime',
        ];
    }

    // the relationship between the company and the Plan table
    public function plan(): BelongsTo
    {
        return $this->belongsTo(Plan::class);
    }

    // the relationship between the company and the User table
    public function users(): HasMany
    {
        return $this->hasMany(User::class);
    }

    // the relationship between the company and the Department table    
    public function departments(): HasMany
    {
        return $this->hasMany(Department::class);
    }

    // the relationship between the company and the Ticket table
    public function tickets(): HasMany
    {
        return $this->hasMany(Ticket::class);
    }

    // the relationship between the company and the Complaint table
    public function complaints(): HasMany
    {
        return $this->hasMany(Complaint::class);
    }

    // the relationship between the company and the CannedResponse table
    public function inquiries(): HasMany
    {
        return $this->hasMany(Inquiry::class);
    }

    public function subscriptions(): HasMany
    {
        return $this->hasMany(Subscription::class);
    }

    public function setting(): HasOne
    {
        return $this->hasOne(CompanySetting::class);
    }

    public function activeSubscription(): HasOne
    {
        return $this->hasOne(Subscription::class)->where('status', 'active')->latestOfMany();
    }
}
