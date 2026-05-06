<?php

namespace App\Services;

use App\Enums\ReportExportFormat;
use App\Enums\ScheduledReportFrequency;
use App\Models\ScheduledReport;
use App\Models\User;
use Carbon\CarbonInterface;
use Illuminate\Support\Collection;

class ScheduledReportService
{
    /**
     * @param  array<int, string>  $recipients
     * @param  array<string, mixed>  $filters
     */
    public function create(User $creator, string $name, ScheduledReportFrequency $frequency, ReportExportFormat $format, array $recipients, array $filters = []): ScheduledReport
    {
        return ScheduledReport::query()->create([
            'company_id' => $creator->company_id,
            'created_by_id' => $creator->id,
            'name' => $name,
            'frequency' => $frequency,
            'format' => $format,
            'recipients' => array_values(array_filter($recipients)),
            'filters' => $filters,
            'next_run_at' => $this->nextRunAt($frequency),
        ]);
    }

    /**
     * @return Collection<int, ScheduledReport>
     */
    public function due(): Collection
    {
        return ScheduledReport::query()
            ->where('is_active', true)
            ->where('next_run_at', '<=', now())
            ->get();
    }

    public function markSent(ScheduledReport $report): ScheduledReport
    {
        $report->forceFill([
            'last_sent_at' => now(),
            'next_run_at' => $this->nextRunAt($report->frequency),
        ])->save();

        return $report->refresh();
    }

    private function nextRunAt(ScheduledReportFrequency $frequency): CarbonInterface
    {
        return match ($frequency) {
            ScheduledReportFrequency::Weekly => now()->next('monday')->setTime(8, 0),
            ScheduledReportFrequency::Monthly => now()->addMonthNoOverflow()->startOfMonth()->setTime(8, 0),
        };
    }
}
