<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database with essential user roles only.
     */
    public function run(): void
    {
        User::firstOrCreate(
            ['email' => 'admin@simasadi.local'],
            [
                'name' => 'Budi Administrator Unit',
                'password' => 'password',
                'role' => User::ROLE_ADMIN,
            ]
        );

        User::firstOrCreate(
            ['email' => 'pic@simasadi.local'],
            [
                'name' => 'PIC Laboratorium Penguji & Kalibrasi',
                'password' => 'password',
                'role' => User::ROLE_PIC,
            ]
        );


        User::firstOrCreate(
            ['email' => 'demo@simasadi.local'],
            [
                'name' => 'Petugas Demo',
                'password' => 'password',
                'role' => User::ROLE_ADMIN,
            ]
        );
    }
}
