<?php

namespace Database\Factories;

use App\Enums\ReportExportFormat;
use App\Enums\ScheduledReportFrequency;
use App\Models\Company;
use App\Models\ScheduledReport;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ScheduledReport> */
class ScheduledReportFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'created_by_id' => User::factory(),
            'name' => fake()->sentence(3),
            'frequency' => fake()->randomElement(ScheduledReportFrequency::cases()),
            'format' => fake()->randomElement(ReportExportFormat::cases()),
            'recipients' => [fake()->safeEmail()],
            'filters' => [],
            'next_run_at' => now()->addWeek(),
            'last_sent_at' => null,
            'is_active' => true,
        ];
    }
}
