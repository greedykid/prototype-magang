<?php

namespace Database\Seeders;

use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database with essential user roles and initial assessment data.
     */
    public function run(): void
    {
        $this->call([
            UserSeeder::class,
            AssessmentSeeder::class,
        ]);
    }
}
