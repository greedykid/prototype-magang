<?php

namespace Database\Seeders;

use App\Models\User;
use Illuminate\Database\Seeder;

class UserSeeder extends Seeder
{
    /**
     * Menyiapkan 4 akun pengguna resmi SIMASADI untuk masing-masing peran RBAC.
     */
    public function run(): void
    {
        User::updateOrCreate(
            ['email' => 'admin@simasadi.local'],
            [
                'name' => 'Budi Administrator',
                'password' => 'password',
                'role' => User::ROLE_ADMIN,
            ]
        );

        User::updateOrCreate(
            ['email' => 'staf@simasadi.local'],
            [
                'name' => 'Siti Sekretariat',
                'password' => 'password',
                'role' => User::ROLE_STAFF,
            ]
        );

        User::updateOrCreate(
            ['email' => 'asesor@simasadi.local'],
            [
                'name' => 'Dr. Hendra Asesor',
                'password' => 'password',
                'role' => User::ROLE_ASSESSOR,
            ]
        );

        User::updateOrCreate(
            ['email' => 'demo@simasadi.local'],
            [
                'name' => 'Petugas Demo',
                'password' => 'password',
                'role' => User::ROLE_ADMIN,
            ]
        );
    }
}
