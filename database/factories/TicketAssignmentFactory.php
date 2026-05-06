<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Ticket;
use App\Models\TicketAssignment;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketAssignment>
 */
class TicketAssignmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'ticket_id' => Ticket::factory(),
            'assigned_by_id' => User::factory(),
            'assigned_to_id' => User::factory(),
            'from_user_id' => null,
            'note' => fake()->optional()->sentence(),
        ];
    }
}
