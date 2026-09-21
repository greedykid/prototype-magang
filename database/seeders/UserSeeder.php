<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Menyiapkan 2 akun pengguna resmi SIMASADI untuk peran admin dan pic.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@simasadi.local'],
            [
                'name' => 'Budi Administrator Unit',
                'password' => 'password',
                'role' => User::ROLE_ADMIN,
            ]
        );

        User::updateOrCreate(
            ['email' => 'pic@simasadi.local'],
            [
                'name' => 'PIC Laboratorium Penguji & Kalibrasi',
                'password' => 'password',
                'role' => User::ROLE_PIC,
            ]
        );

        User::updateOrCreate(
            ['email' => 'demo@simasadi.local'],
            [
                'name' => 'Petugas Demo Unit Lab',
                'password' => 'password',
                'role' => User::ROLE_ADMIN,
            ]
        );
    }
}
