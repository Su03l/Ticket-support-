<?php

namespace Database\Factories;

use App\Enums\ComplaintStatus;
use App\Models\Company;
use App\Models\Complaint;
use App\Models\ComplaintStatusHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ComplaintStatusHistory>
 */
class ComplaintStatusHistoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'complaint_id' => Complaint::factory(),
            'changed_by_id' => User::factory(),
            'old_status' => ComplaintStatus::New,
            'new_status' => ComplaintStatus::UnderReview,
            'reason' => fake()->optional()->sentence(),
        ];
    }
}
