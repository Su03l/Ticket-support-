<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\TicketTransfer;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketTransfer>
 */
class TicketTransferFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'ticket_id' => Ticket::factory(),
            'transferred_by_id' => User::factory(),
            'from_department_id' => Department::factory(),
            'to_department_id' => Department::factory(),
            'reason' => fake()->optional()->sentence(),
        ];
    }
}
