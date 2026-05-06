<?php

namespace Database\Factories;

use App\Enums\ReportExportFormat;
use App\Models\Company;
use App\Models\ReportTemplate;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/** @extends Factory<ReportTemplate> */
class ReportTemplateFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => Company::factory(),
            'created_by_id' => User::factory(),
            'name' => fake()->sentence(3),
            'format' => fake()->randomElement(ReportExportFormat::cases()),
            'paper_size' => 'a4',
            'orientation' => 'portrait',
            'body' => '<h1>{{ company.name }}</h1><p>{{ generated_at }}</p>',
            'data_sources' => [],
            'is_active' => true,
        ];
    }
}
