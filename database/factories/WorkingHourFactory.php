<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\WorkingHour;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<WorkingHour> */
class WorkingHourFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'day_of_week' => fake()->numberBetween(1, 7),
            'starts_at' => '09:00',
            'ends_at' => '17:00',
            'is_working_day' => true,
        ];
    }
}
