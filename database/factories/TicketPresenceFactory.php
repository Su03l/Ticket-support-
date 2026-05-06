<?php

namespace Database\Factories;

use App\Enums\TicketPresenceAction;
use App\Models\Ticket;
use App\Models\TicketPresence;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TicketPresence> */
class TicketPresenceFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => fn (array $attributes) => Ticket::query()->find($attributes['ticket_id'])?->company_id,
            'ticket_id' => Ticket::factory(),
            'user_id' => User::factory(),
            'action' => fake()->randomElement(TicketPresenceAction::cases()),
            'last_seen_at' => now(),
        ];
    }
}
