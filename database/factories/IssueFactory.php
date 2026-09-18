<?php

namespace Database\Factories;

use App\Models\Issue;
use App\Models\Lpk;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Issue>
 */
class IssueFactory extends Factory
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
            'created_by' => User::factory(),
            'title' => fake()->sentence(5),
            'description' => fake()->paragraph(),
            'priority' => fake()->randomElement(['LOW', 'MEDIUM', 'HIGH']),
            'status' => fake()->randomElement(['OPEN', 'IN_PROGRESS', 'RESOLVED']),
            'due_date' => fake()->dateTimeBetween('now', '+2 months'),
        ];
    }
}
