<?php

use Database\Seeders\UserSeeder;
use Illuminate\Foundation\Inspiring;
use Illuminate\Support\Facades\Artisan;
use Illuminate\Support\Facades\DB;
use Illuminate\Support\Facades\Schedule;
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
        'assessment_expenses',
        'assessments',
        'calendar_events',
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

    // Alur Opsi 2: Ambil seluruh email staf PIC internal Unit Akreditasi Laboratorium BSN
    $picEmails = \App\Models\User::where('role', \App\Models\User::ROLE_PIC)->pluck('email')->filter()->values()->all();
    if (empty($picEmails)) {
        $picEmails = \App\Models\User::where('role', \App\Models\User::ROLE_ADMIN)->pluck('email')->filter()->values()->all();
    }
    if (empty($picEmails)) {
        $picEmails = [config('mail.from.address', 'simasadi@kan.or.id')];
    }
    $picSummary = implode(', ', $picEmails);
    $this->line("Target penerima pengingat internal: {$picSummary}");

    foreach ($lpks as $lpk) {
        $alerts = $lpk->getActiveSurveillanceAlerts();
        if (empty($alerts)) {
            continue;
        }

        $activeNoticeCount += count($alerts);

        foreach ($alerts as $alert) {
            $this->warn("  [{$alert['code']}] {$lpk->registration_number} - {$lpk->name}: {$alert['status_label']}");

            if (! $force && $lpk->last_surveillance_notified_at && $lpk->last_surveillance_notified_at->isToday()) {
                $this->line("    ℹ Email sudah dikirim hari ini ({$lpk->last_surveillance_notified_at->format('H:i')}). Gunakan --force untuk mengirim ulang.");
                continue;
            }

            try {
                \Illuminate\Support\Facades\Mail::to($picEmails)->send(new \App\Mail\SurveillanceReminderMail($lpk, $alert));
                $lpk->update(['last_surveillance_notified_at' => now()]);
                $notifiedCount++;
                $this->info("    ✓ Email pengingat internal berhasil dikirim ke PIC ({$picSummary}) via Mailtrap.");
            } catch (\Throwable $e) {
                $this->error("    ✗ Gagal mengirim email ke PIC: " . $e->getMessage());
            }
        }
    }

    $this->info("Pemeriksaan selesai. Total {$activeNoticeCount} notifikasi aktif terdeteksi, {$notifiedCount} email pemberitahuan terkirim.");
})->purpose('Memeriksa jadwal jatuh tempo pengawasan KAN untuk seluruh LPK dan mengirim email pengingat ke staf/PIC internal Unit Akreditasi Laboratorium BSN.');

Artisan::command('lpk:generate-assessments', function () {
    $this->info('Menjalankan pembuatan otomatis agenda asesmen surveilen & re-akreditasi LPK...');

    $lpks = \App\Models\Lpk::all();
    $totalCreated = 0;

    foreach ($lpks as $lpk) {
        $count = $lpk->generateSurveillanceAssessments();
        if ($count > 0) {
            $this->line("  ✓ {$lpk->registration_number} ({$lpk->name}): {$count} agenda asesmen berhasil dibuat.");
            $totalCreated += $count;
        }
    }

    $this->info("Selesai! Sebanyak {$totalCreated} agenda asesmen surveilen berhasil dibuat/disinkronkan.");
})->purpose('Otomatis membuat agenda asesmen surveilen (S1, S2) dan Re-Akreditasi (RA) untuk seluruh LPK berdasarkan siklus tanggal sertifikat KAN.');

Schedule::command('lpk:check-surveillance')
    ->dailyAt('07:00')
    ->withoutOverlapping()
    ->runInBackground();




