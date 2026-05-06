<?php

namespace Database\Factories;

use App\Enums\InquiryStatus;
use App\Models\Company;
use App\Models\Department;
use App\Models\Inquiry;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<Inquiry> */
class InquiryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'department_id' => Department::factory(),
            'customer_id' => User::factory(),
            'assigned_to_id' => null,
            'inquiry_number' => 'INQ-'.now()->format('Ymd').'-'.fake()->unique()->numerify('######'),
            'subject' => fake()->sentence(5),
            'body' => fake()->paragraphs(2, true),
            'status' => InquiryStatus::New,
            'converted_ticket_id' => null,
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
