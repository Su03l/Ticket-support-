<?php

namespace App\Services;

use App\Models\Company;
use App\Models\WorkingHour;
use Carbon\CarbonInterface;

class WorkingHoursService
{
    public function upsert(Company $company, int $dayOfWeek, string $startsAt, string $endsAt, bool $isWorkingDay = true): WorkingHour
    {
        return WorkingHour::query()->updateOrCreate([
            'company_id' => $company->id,
            'day_of_week' => $dayOfWeek,
        ], [
            'starts_at' => $startsAt,
            'ends_at' => $endsAt,
            'is_working_day' => $isWorkingDay,
        ]);
    }

    public function dueAt(Company $company, int $minutes, ?CarbonInterface $from = null): CarbonInterface
    {
        $from ??= now();

        return $from->copy()->addMinutes($minutes);
    }
}
