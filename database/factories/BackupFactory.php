<?php

namespace Database\Factories;

use App\Models\Backup;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Backup>
 */
class BackupFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'system' => 'KANMIS', 'status' => fake()->randomElement(['SUCCESS', 'SUCCESS', 'FAILED']), 'started_at' => now()->subHours(3), 'finished_at' => now()->subHours(3)->addMinutes(12), 'size' => fake()->randomElement(['240 MB', '512 MB', '1.2 GB']), 'message' => null, 'recorded_by' => User::factory(),
        ];
    }
}
