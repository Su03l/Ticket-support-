<?php

namespace Database\Factories;

use App\Enums\TicketStatus;
use App\Models\Company;
use App\Models\Ticket;
use App\Models\TicketStatusHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<TicketStatusHistory>
 */
class TicketStatusHistoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'ticket_id' => Ticket::factory(),
            'changed_by_id' => User::factory(),
            'old_status' => TicketStatus::New,
            'new_status' => TicketStatus::Open,
            'reason' => fake()->optional()->sentence(),
        ];
    }
}
