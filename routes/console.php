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

Artisan::command('lpk:check-surveillance {--force : Kirim email meskipun baru saja dikirim hari ini}', function () {
    $force = (bool) $this->option('force');
    $this->info('Memeriksa status siklus pengawasan KAN (S1, S2, Re-Akreditasi)...');

    $lpks = \App\Models\Lpk::where('status', 'ACTIVE')->get();
    $notifiedCount = 0;
    $activeNoticeCount = 0;

    foreach ($lpks as $lpk) {
        $alerts = $lpk->getActiveSurveillanceAlerts();
        if (empty($alerts)) {
            continue;
        }

        $activeNoticeCount += count($alerts);

        foreach ($alerts as $alert) {
            $this->warn("  [{$alert['code']}] {$lpk->registration_number} - {$lpk->name}: {$alert['status_label']}");

            if (! $lpk->email) {
                $this->line("    ⚠ Dilewati: Email PIC Lab tidak terdaftar.");
                continue;
            }

            if (! $force && $lpk->last_surveillance_notified_at && $lpk->last_surveillance_notified_at->isToday()) {
                $this->line("    ℹ Email sudah dikirim hari ini ({$lpk->last_surveillance_notified_at->format('H:i')}). Gunakan --force untuk mengirim ulang.");
                continue;
            }

            try {
                \Illuminate\Support\Facades\Mail::to($lpk->email)->send(new \App\Mail\SurveillanceReminderMail($lpk, $alert));
                $lpk->update(['last_surveillance_notified_at' => now()]);
                $notifiedCount++;
                $this->info("    ✓ Email pemberitahuan berhasil dikirim ke {$lpk->email} via Mailtrap.");
            } catch (\Throwable $e) {
                $this->error("    ✗ Gagal mengirim email ke {$lpk->email}: " . $e->getMessage());
            }
        }
    }

    $this->info("Pemeriksaan selesai. Total {$activeNoticeCount} notifikasi aktif terdeteksi, {$notifiedCount} email pemberitahuan terkirim.");
})->purpose('Memeriksa jadwal jatuh tempo Surveilen 1 (Bulan 14), Surveilen 2 (Bulan 35), dan Re-Akreditasi (1 Bulan sebelum habis), serta mengirim email ke PIC Lab.');


