<?php

namespace Database\Factories;

use App\Enums\BillingCycle;
use App\Models\Plan;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Plan>
 */
class PlanFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->unique()->words(2, true);

        return [
            'name' => Str::title($name),
            'slug' => Str::slug($name).'-'.fake()->unique()->numerify('###'),
            'price' => fake()->randomFloat(2, 0, 999),
            'billing_cycle' => fake()->randomElement(BillingCycle::cases()),
            'max_users' => fake()->numberBetween(5, 250),
            'max_departments' => fake()->numberBetween(1, 50),
            'max_tickets_per_month' => fake()->numberBetween(100, 10000),
            'is_active' => true,
        ];
    }
}
