<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\EmployeeKpiTarget;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<EmployeeKpiTarget> */
class EmployeeKpiTargetFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'user_id' => User::factory(),
            'managed_by_id' => null,
            'month' => now()->month,
            'year' => now()->year,
            'tickets_resolved_target' => 20,
            'first_response_minutes_target' => 30,
            'csat_target' => 4,
            'quality_score_target' => 90,
            'manual_adjustments' => [],
        ];
    }
}
