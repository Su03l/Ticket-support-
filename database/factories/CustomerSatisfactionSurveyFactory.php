<?php

namespace Database\Factories;

use App\Enums\NpsCategory;
use App\Models\CustomerSatisfactionSurvey;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<CustomerSatisfactionSurvey> */
class CustomerSatisfactionSurveyFactory extends Factory
{
    public function definition(): array
    {
        $npsScore = fake()->numberBetween(0, 10);

        return [
            'company_id' => fn (array $attributes) => Ticket::query()->find($attributes['ticket_id'])?->company_id,
            'ticket_id' => Ticket::factory(),
            'customer_id' => User::factory(),
            'agent_id' => User::factory(),
            'department_id' => Department::factory(),
            'csat_score' => fake()->numberBetween(1, 5),
            'nps_score' => $npsScore,
            'nps_category' => NpsCategory::fromScore($npsScore),
            'feedback' => fake()->optional()->sentence(),
            'sent_at' => now()->subDay(),
            'submitted_at' => now(),
        ];
    }
}
