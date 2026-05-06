<?php

namespace Database\Factories;

use App\Enums\DepartmentStatus;
use App\Models\Company;
use App\Models\Department;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/**
 * @extends Factory<Department>
 */
class DepartmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $name = fake()->randomElement([
            'Technical Support',
            'Customer Success',
            'Billing Support',
            'Operations',
            'Escalations',
            'Field Services',
        ]).'-'.fake()->unique()->bothify('??###');

        return [
            'company_id' => Company::factory(),
            'manager_id' => null,
            'deputy_id' => null,
            'name' => $name,
            'slug' => Str::slug($name),
            'status' => DepartmentStatus::Active,
            'description' => fake()->sentence(),
        ];
    }
}
