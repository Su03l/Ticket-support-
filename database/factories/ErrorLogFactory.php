<?php

namespace Database\Factories;

use App\Models\ErrorLog;
use Illuminate\Database\Eloquent\Factories\Factory;
use Illuminate\Support\Str;

/** @extends Factory<ErrorLog> */
class ErrorLogFactory extends Factory
{
    public function definition(): array
    {
        return [
            'company_id' => null,
            'user_id' => null,
            'error_id' => 'ERR-'.Str::upper(Str::random(10)),
            'exception_class' => \RuntimeException::class,
            'message' => fake()->sentence(),
            'file' => null,
            'line' => null,
            'route' => null,
            'method' => 'GET',
            'url' => fake()->url(),
            'ip' => '127.0.0.1',
            'user_agent' => 'Pest',
            'request_payload' => [],
            'stack_trace' => null,
            'environment' => 'testing',
            'resolved_at' => null,
            'resolved_by_id' => null,
            'notes' => null,
        ];
    }
}
