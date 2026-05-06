<?php

namespace Database\Factories;

use App\Enums\AttachmentVisibility;
use App\Models\Attachment;
use App\Models\Company;
use App\Models\TicketReply;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Attachment>
 */
class AttachmentFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'uploaded_by_id' => User::factory(),
            'attachable_type' => TicketReply::class,
            'attachable_id' => TicketReply::factory(),
            'original_name' => fake()->word().'.txt',
            'stored_name' => fake()->uuid().'.txt',
            'path' => 'attachments/'.fake()->uuid().'.txt',
            'disk' => 'local',
            'mime_type' => 'text/plain',
            'size' => fake()->numberBetween(100, 10000),
            'visibility' => AttachmentVisibility::Public,
        ];
    }

    public function internal(): static
    {
        return $this->state(fn (): array => [
            'visibility' => AttachmentVisibility::Internal,
        ]);
    }
}
