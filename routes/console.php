<?php

use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schema;

Artisan::command('inspire', function () {
    $this->comment(Inspiring::quote());
})->purpose('Display an inspiring quote');

Artisan::command('data:clear {--include-users : Hapus juga seluruh akun pengguna}', function () {
    $includeUsers = (bool) $this->option('include-users');

    $this->warn('Mengosongkan data transaksi dan master SIMASADI...');

    $driver = DB::getDriverName();
    if ($driver === 'sqlite') {
        DB::statement('PRAGMA foreign_keys = OFF;');
    } else {
        Schema::disableForeignKeyConstraints();
    }

    $tables = [
        'accreditation_signatures',
        'accreditation_billings',
        'accreditations',
        'amendments',
        'assessment_expenses',
        'assessments',
        'issue_followups',
        'issues',
        'calendar_events',
        'service_checks',
        'services',
        'backups',
        'lpks',
    ];

    if ($includeUsers) {
        $tables[] = 'users';
    }

    foreach ($tables as $table) {
        if (Schema::hasTable($table)) {
            DB::table($table)->delete();
            $this->line("  ✓ Tabel {$table} dikosongkan.");
        }
    }

    if ($driver === 'sqlite') {
        $escaped = implode("','", $tables);
        DB::statement("DELETE FROM sqlite_sequence WHERE name IN ('{$escaped}')");
        DB::statement('PRAGMA foreign_keys = ON;');
    } else {
        Schema::enableForeignKeyConstraints();
    }

    if (! $includeUsers) {
        $this->call(UserSeeder::class);
        $this->info('Akun pengguna resmi (admin, staf, asesor, demo) dipastikan aktif.');
    }

    $this->info('Seluruh data operasional berhasil dikosongkan!');
})->purpose('Mengosongkan seluruh data operasional SIMASADI (LPK, Akreditasi, Asesmen, Biaya, dll.)');

