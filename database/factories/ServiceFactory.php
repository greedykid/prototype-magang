<?php

namespace Database\Factories;

use App\Models\Service;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Service>
 */
class ServiceFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'name' => fake()->randomElement(['Portal monitoring akreditasi', 'Layanan data LPK', 'Akses pelaporan KANMIS']),
            'system' => 'KANMIS', 'status' => fake()->randomElement(['HEALTHY', 'HEALTHY', 'DEGRADED', 'UNKNOWN']),
            'last_checked_at' => now()->subMinutes(fake()->numberBetween(5, 180)), 'notes' => null,
        ];
    }
}
