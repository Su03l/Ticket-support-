<?php

namespace Database\Factories;

use App\Enums\TicketSource;
use App\Enums\TicketStatus;
use App\Models\Company;
use App\Models\Department;
use App\Models\Ticket;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Ticket>
 */
class TicketFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'department_id' => Department::factory(),
            'customer_id' => User::factory(),
            'assigned_to_id' => null,
            'category_id' => null,
            'priority_id' => null,
            'ticket_number' => 'TCK-'.now()->format('Ymd').'-'.fake()->unique()->numerify('######'),
            'title' => fake()->sentence(5),
            'description' => fake()->paragraphs(2, true),
            'status' => TicketStatus::New,
            'source' => TicketSource::Web,
            'first_response_at' => null,
            'resolved_at' => null,
            'closed_at' => null,
            'reopened_at' => null,
        ];
    }

    public function forCompanyDepartmentCustomer(Company $company, Department $department, User $customer): static
    {
        return $this->state(fn (): array => [
            'company_id' => $company->id,
            'department_id' => $department->id,
            'customer_id' => $customer->id,
        ]);
    }
}
