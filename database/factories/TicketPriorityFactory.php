<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\TicketPriority;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TicketPriority>
 */
class TicketPriorityFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->randomElement(['Low', 'Normal', 'High', 'Urgent']).' '.fake()->unique()->numberBetween(100, 999);

        return [
            'company_id' => Company::factory(),
            'name' => $name,
            'slug' => Str::slug($name),
            'level' => fake()->numberBetween(1, 5),
            'color' => fake()->hexColor(),
            'response_time_minutes' => fake()->numberBetween(30, 240),
            'resolution_time_minutes' => fake()->numberBetween(240, 2880),
            'is_active' => true,
        ];
    }
}
