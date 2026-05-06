<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\Department;
use App\Models\TicketCategory;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<TicketCategory>
 */
class TicketCategoryFactory extends Factory
{
    public function definition(): array
    {
        $name = fake()->words(2, true);

        return [
            'company_id' => Company::factory(),
            'department_id' => null,
            'name' => $name,
            'slug' => Str::slug($name).'-'.fake()->unique()->numberBetween(100, 999),
            'description' => fake()->optional()->sentence(),
            'is_active' => true,
        ];
    }

    public function forDepartment(Department $department): static
    {
        return $this->state(fn (): array => [
            'company_id' => $department->company_id,
            'department_id' => $department->id,
        ]);
    }
}
