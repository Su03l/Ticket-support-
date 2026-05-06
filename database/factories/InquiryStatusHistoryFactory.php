<?php

namespace Database\Factories;

use App\Enums\InquiryStatus;
use App\Models\Company;
use App\Models\Inquiry;
use App\Models\InquiryStatusHistory;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<InquiryStatusHistory> */
class InquiryStatusHistoryFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'inquiry_id' => Inquiry::factory(),
            'changed_by_id' => User::factory(),
            'old_status' => InquiryStatus::New,
            'new_status' => InquiryStatus::Open,
            'reason' => fake()->optional()->sentence(),
        ];
    }
}
