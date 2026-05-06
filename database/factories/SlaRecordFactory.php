<?php

namespace Database\Factories;

use App\Enums\SlaStatus;
use App\Models\Company;
use App\Models\SlaPolicy;
use App\Models\SlaRecord;
use App\Models\Ticket;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<SlaRecord> */
class SlaRecordFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'slable_type' => Ticket::class,
            'slable_id' => Ticket::factory(),
            'policy_id' => SlaPolicy::factory(),
            'first_response_due_at' => now()->addHour(),
            'resolution_due_at' => now()->addDay(),
            'first_responded_at' => null,
            'resolved_at' => null,
            'breached_first_response_at' => null,
            'breached_resolution_at' => null,
            'status' => SlaStatus::Active,
        ];
    }
}
