<?php

namespace Database\Factories;

use App\Models\Accreditation;
use App\Models\Lpk;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Accreditation>
 */
class AccreditationFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lpk_id' => Lpk::factory(),
            'status' => fake()->randomElement(['NOT_STARTED', 'IN_PROGRESS', 'COMPLETED']),
            'start_date' => fake()->dateTimeBetween('-6 months', 'now'),
            'target_date' => fake()->dateTimeBetween('now', '+4 months'),
            'notes' => fake()->optional()->sentence(),
        ];
    }
}
