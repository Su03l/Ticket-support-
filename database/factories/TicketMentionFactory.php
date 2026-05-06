<?php

namespace Database\Factories;

use App\Models\Ticket;
use App\Models\TicketComment;
use App\Models\TicketMention;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<TicketMention> */
class TicketMentionFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => fn (array $attributes) => Ticket::query()->find($attributes['ticket_id'])?->company_id,
            'ticket_id' => Ticket::factory(),
            'comment_id' => TicketComment::factory(),
            'mentioned_by_id' => User::factory(),
            'mentioned_user_id' => User::factory(),
            'notified_at' => now(),
        ];
    }
}
