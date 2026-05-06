<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\TicketTimeEntry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TicketTimeEntry> */
class TicketTimeEntryFactory extends Factory
{
    public function definition(): array
    {
        $startedAt = now()->subMinutes(fake()->numberBetween(5, 90));

        return [
            'company_id' => fn (array $attributes) => Ticket::query()->find($attributes['ticket_id'])?->company_id,
            'ticket_id' => Ticket::factory(),
            'user_id' => User::factory(),
            'started_at' => $startedAt,
            'stopped_at' => now(),
            'duration_seconds' => $startedAt->diffInSeconds(now()),
            'note' => fake()->optional()->sentence(),
        ];
    }
}
