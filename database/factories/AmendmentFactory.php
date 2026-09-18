<?php

namespace Database\Factories;

use App\Models\Amendment;
use App\Models\Lpk;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Amendment>
 */
class AmendmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lpk_id' => Lpk::factory(), 'created_by' => User::factory(), 'submission_number' => 'AMD-'.fake()->unique()->numerify('####'), 'amendment_type' => fake()->randomElement(['Perubahan alamat', 'Perubahan ruang lingkup', 'Perubahan lampiran']), 'submitted_at' => now()->subDays(12), 'status' => fake()->randomElement(['SUBMITTED', 'UNDER_REVIEW', 'NEED_REVISION', 'COMPLETED']), 'target_date' => now()->addDays(14), 'completed_at' => null, 'notes' => null,
        ];
    }
}
