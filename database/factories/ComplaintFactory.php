<?php

namespace Database\Factories;

use App\Enums\ComplaintSeverity;
use App\Enums\ComplaintStatus;
use App\Models\Company;
use App\Models\Complaint;
use App\Models\Department;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Complaint>
 */
class ComplaintFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'department_id' => Department::factory(),
            'customer_id' => User::factory(),
            'assigned_to_id' => null,
            'related_ticket_id' => null,
            'complaint_number' => 'CMP-'.now()->format('Ymd').'-'.fake()->unique()->numerify('######'),
            'title' => fake()->sentence(5),
            'description' => fake()->paragraphs(2, true),
            'severity' => ComplaintSeverity::Medium,
            'status' => ComplaintStatus::New,
            'resolved_at' => null,
            'closed_at' => null,
        ];
    }

    public function forCompanyDepartmentCustomer(Company $company, ?Department $department, User $customer): static
    {
        return $this->state(fn (): array => [
            'company_id' => $company->id,
            'department_id' => $department?->id,
            'customer_id' => $customer->id,
        ]);
    }
}
