<?php

namespace Database\Factories;

use App\Enums\ReplyVisibility;
use App\Models\Company;
use App\Models\Complaint;
use App\Models\ComplaintReply;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ComplaintReply>
 */
class ComplaintReplyFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'complaint_id' => Complaint::factory(),
            'user_id' => User::factory(),
            'body' => fake()->paragraphs(2, true),
            'visibility' => ReplyVisibility::Public,
        ];
    }

    public function internal(): static
    {
        return $this->state(fn (): array => [
            'visibility' => ReplyVisibility::Internal,
        ]);
    }
}
