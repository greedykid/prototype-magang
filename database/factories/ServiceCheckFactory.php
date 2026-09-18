<?php

namespace Database\Factories;

use App\Models\Service;
use App\Models\ServiceCheck;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<ServiceCheck>
 */
class ServiceCheckFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        return [
            'service_id' => Service::factory(), 'status' => 'HEALTHY', 'checked_at' => now(), 'message' => 'Pemeriksaan manual berhasil.', 'checked_by' => User::factory(),
        ];
    }
}
