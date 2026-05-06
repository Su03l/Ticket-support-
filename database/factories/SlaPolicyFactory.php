<?php

namespace Database\Factories;

use App\Enums\SlaAppliesTo;
use App\Models\Company;
use App\Models\SlaPolicy;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SlaPolicy> */
class SlaPolicyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'name' => fake()->words(2, true),
            'applies_to' => SlaAppliesTo::Tickets,
            'priority_id' => null,
            'first_response_minutes' => 60,
            'resolution_minutes' => 1440,
            'escalation_minutes' => 120,
            'is_active' => true,
        ];
    }
}
