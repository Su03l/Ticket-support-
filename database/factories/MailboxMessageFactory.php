<?php

namespace Database\Factories;

use App\Enums\MailboxMessageType;
use App\Models\Company;
use App\Models\MailboxMessage;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<MailboxMessage>
 */
class MailboxMessageFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'sender_id' => User::factory(),
            'recipient_id' => User::factory(),
            'subject' => fake()->sentence(5),
            'body' => fake()->paragraphs(3, true),
            'type' => MailboxMessageType::System,
            'related_type' => null,
            'related_id' => null,
            'read_at' => null,
            'archived_at' => null,
        ];
    }

    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => now(),
        ]);
    }

    public function archived(): static
    {
        return $this->state(fn (array $attributes) => [
            'archived_at' => now(),
        ]);
    }
}
