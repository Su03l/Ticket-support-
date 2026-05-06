<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\SupportNotification;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<SupportNotification>
 */
class SupportNotificationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'recipient_id' => User::factory(),
            'company_id' => Company::factory(),
            'type' => 'system.alert',
            'title' => fake()->sentence(4),
            'body' => fake()->sentence(10),
            'link' => null,
            'data' => null,
            'read_at' => null,
        ];
    }

    public function read(): static
    {
        return $this->state(fn (array $attributes) => [
            'read_at' => now(),
        ]);
    }
}
