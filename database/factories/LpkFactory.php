<?php

namespace Database\Factories;

use App\Models\Lpk;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lpk>
 */
class LpkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'registration_number' => 'LPK-'.fake()->unique()->numerify('####'),
            'name' => fake()->company().' Laboratorium',
            'address' => fake()->address(),
            'email' => fake()->companyEmail(),
            'phone' => fake()->phoneNumber(),
            'status' => 'ACTIVE',
            'notes' => null,
        ];
    }
}
