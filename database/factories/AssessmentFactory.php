<?php

namespace Database\Factories;

use App\Models\Assessment;
use App\Models\Lpk;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Assessment>
 */
class AssessmentFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'lpk_id' => Lpk::factory(), 'created_by' => User::factory(), 'title' => fake()->randomElement(['Asesmen awal', 'Asesmen ulang', 'Surveilen']), 'assessment_type' => fake()->randomElement(['INITIAL', 'SURVEILLANCE', 'REASSESSMENT']), 'start_at' => now()->addDays(fake()->numberBetween(1, 25))->setTime(9, 0), 'end_at' => now()->addDays(fake()->numberBetween(1, 25))->setTime(16, 0), 'location' => fake()->randomElement(['Ruang rapat 2', 'Online', 'Lokasi LPK']), 'status' => fake()->randomElement(['PLANNED', 'SCHEDULED', 'IN_PROGRESS']), 'lead_assessor' => null, 'notes' => null,
        ];
    }
}
