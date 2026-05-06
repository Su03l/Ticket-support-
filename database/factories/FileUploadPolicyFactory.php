<?php

namespace Database\Factories;

use App\Models\Company;
use App\Models\FileUploadPolicy;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<FileUploadPolicy>
 */
class FileUploadPolicyFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'allowed_mime_types' => ['image/jpeg', 'image/png', 'application/pdf', 'text/plain'],
            'max_file_size_kb' => 10240,
            'max_files_per_request' => 5,
            'allow_public_attachments' => true,
            'allow_internal_attachments' => true,
        ];
    }
}
