<?php

namespace Database\Factories;

use App\Enums\EscalationStatus;
use App\Models\Company;
use App\Models\Escalation;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Escalation> */
class EscalationFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'escalatable_type' => Ticket::class,
            'escalatable_id' => Ticket::factory(),
            'escalated_by_id' => User::factory(),
            'escalated_to_id' => null,
            'reason' => fake()->optional()->sentence(),
            'status' => EscalationStatus::Open,
            'escalated_at' => now(),
            'resolved_at' => null,
        ];
    }
}
