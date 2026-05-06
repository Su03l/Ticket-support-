<?php

namespace App\Models;

use App\Enums\ReportExportFormat;
use App\Enums\ScheduledReportFrequency;
use Database\Factories\ScheduledReportFactory;
use Illuminate\Database\Eloquent\Attributes\Fillable;
use Illuminate\Database\Eloquent\Factories\HasFactory;
use Illuminate\Database\Eloquent\Model;
use Illuminate\Database\Eloquent\Relations\BelongsTo;

#[Fillable(['company_id', 'created_by_id', 'name', 'frequency', 'format', 'recipients', 'filters', 'next_run_at', 'last_sent_at', 'is_active'])]
class ScheduledReport extends Model
{
    /** @use HasFactory<ScheduledReportFactory> */
    use HasFactory;

    protected function casts(): array
    {
        return [
            'frequency' => ScheduledReportFrequency::class,
            'format' => ReportExportFormat::class,
            'recipients' => 'array',
            'filters' => 'array',
            'next_run_at' => 'datetime',
            'last_sent_at' => 'datetime',
            'is_active' => 'boolean',
        ];
    }

    public function company(): BelongsTo
    {
        return $this->belongsTo(Company::class);
    }

    public function createdBy(): BelongsTo
    {
        return $this->belongsTo(User::class, 'created_by_id');
    }
}
