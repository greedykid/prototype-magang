<?php

namespace Database\Factories;

use App\Models\CalendarEvent;
use App\Models\Lpk;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<CalendarEvent>
 */
class CalendarEventFactory extends Factory
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
            'title' => fake()->randomElement(['Persiapan asesmen awal', 'Review dokumen LPK', 'Tindak lanjut hasil monitoring']),
            'description' => fake()->sentence(),
            'start_at' => fake()->dateTimeBetween('now', '+2 months'),
            'end_at' => fake()->dateTimeBetween('+2 months', '+2 months 2 hours'),
            'location' => fake()->randomElement(['Ruang rapat 2', 'Online', 'Kantor Direktorat']),
            'status' => fake()->randomElement(['PLANNED', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED']),
            'notes' => null,
        ];
    }
}
