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

Artisan::command('data:clear {--include-users : Hapus juga seluruh akun pengguna} {--force : Jalankan pengosongan data tanpa konfirmasi interaktif}', function () {
    $includeUsers = (bool) $this->option('include-users');
    $force = (bool) $this->option('force');

    if (app()->isProduction() && ! $force) {
        $this->error('Perintah data:clear dilarang dijalankan pada environment production tanpa opsi --force!');
        return 1;
    }

    if (! $force) {
        if (! $this->confirm('PERINGATAN: Perintah ini akan menghapus seluruh data operasional SIMASADI. Apakah Anda yakin ingin melanjutkan?')) {
            $this->info('Operasi pengosongan data dibatalkan.');
            return 0;
        }
    }

    $this->warn('Mengosongkan data transaksi dan master SIMASADI...');

    $driver = DB::getDriverName();
    if ($driver === 'sqlite') {
        DB::statement('PRAGMA foreign_keys = OFF;');
    } else {
        Schema::disableForeignKeyConstraints();
    }

    $tables = [
        'lpk_members',
        'user_account_links',
        'assessments',
        'calendar_events',
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
})->purpose('Mengosongkan seluruh data operasional SIMASADI (LPK, Akreditasi, Asesmen, dll.)');

Artisan::command('lpk:check-surveillance {--force : Kirim email meskipun baru saja dikirim hari ini}', function () {
    $force = (bool) $this->option('force');
    $this->info('Memeriksa status siklus pengawasan KAN (S1, S2, Re-Akreditasi)...');

    $notifiedCount = 0;
    $activeNoticeCount = 0;

    // Fallback penerima jika LPK belum memiliki PIC yang ditugaskan
    $fallbackEmails = \App\Models\User::where('role', \App\Models\User::ROLE_ADMIN)->pluck('email')->filter()->values()->all();
    if (empty($fallbackEmails)) {
        $fallbackEmails = [config('mail.from.address', 'simasadi@kan.or.id')];
    }

    \App\Models\Lpk::whereIn('status', ['ACTIVE', 'SUSPENDED', 'REVOKED'])
        ->with(['assessments', 'pic', 'members'])
        ->chunk(100, function ($lpks) use (&$notifiedCount, &$activeNoticeCount, $force, $fallbackEmails) {
            foreach ($lpks as $lpk) {
                $alerts = $lpk->getActiveSurveillanceAlerts();
                if (empty($alerts)) {
                    continue;
                }

                $activeNoticeCount += count($alerts);

                if (! $force && $lpk->last_surveillance_notified_at && $lpk->last_surveillance_notified_at->isToday()) {
                    $this->line("  ℹ {$lpk->registration_number} - {$lpk->name}: Email sudah dikirim hari ini ({$lpk->last_surveillance_notified_at->format('H:i')}). Gunakan --force untuk mengirim ulang.");
                    continue;
                }

                $targetEmails = array_values(array_unique(array_filter([
                    $lpk->pic?->email,
                    ...$lpk->members->pluck('email')->all(),
                ])));

                if (empty($targetEmails)) {
                    $targetEmails = $fallbackEmails;
                }
                $targetSummary = implode(', ', $targetEmails);

                $hasSentForLpk = false;

                foreach ($alerts as $alert) {
                    $this->warn("  [{$alert['code']}] {$lpk->registration_number} - {$lpk->name}: {$alert['status_label']}");

                    try {
                        \Illuminate\Support\Facades\Mail::to($targetEmails)->queue(new \App\Mail\SurveillanceReminderMail($lpk, $alert));
                        $notifiedCount++;
                        $hasSentForLpk = true;
                        $this->info("    ✓ Email pengingat internal berhasil dimasukkan ke antrean kirim ({$targetSummary}).");
                    } catch (\Throwable $e) {
                        $this->error("    ✗ Gagal mengirim email pengingat: " . $e->getMessage());
                    }
                }

                if ($hasSentForLpk) {
                    $lpk->update(['last_surveillance_notified_at' => now()]);
                }
            }
        });

    $this->info("Pemeriksaan selesai. Total {$activeNoticeCount} notifikasi aktif terdeteksi, {$notifiedCount} email pemberitahuan terkirim.");
})->purpose('Memeriksa jadwal jatuh tempo pengawasan KAN untuk seluruh LPK dan mengirim email pengingat ke staf/PIC internal Unit Akreditasi Laboratorium BSN.');

Artisan::command('lpk:generate-assessments', function () {
    $this->info('Menjalankan pembuatan otomatis agenda asesmen surveilen & re-akreditasi LPK...');

    $totalCreated = 0;

    \App\Models\Lpk::chunk(100, function ($lpks) use (&$totalCreated) {
        foreach ($lpks as $lpk) {
            $count = $lpk->generateSurveillanceAssessments();
            if ($count > 0) {
                $this->line("  ✓ {$lpk->registration_number} ({$lpk->name}): {$count} agenda asesmen berhasil dibuat.");
                $totalCreated += $count;
            }
        }
    });

    $this->info("Selesai! Sebanyak {$totalCreated} agenda asesmen surveilen berhasil dibuat/disinkronkan.");
})->purpose('Otomatis membuat agenda asesmen surveilen (S1, S2) dan Re-Akreditasi (RA) untuk seluruh LPK berdasarkan siklus tanggal sertifikat KAN.');

Schedule::command('lpk:check-surveillance')
    ->dailyAt('07:00')
    ->withoutOverlapping()
    ->runInBackground();

Artisan::command('assessments:sync-overdue-status', function () {
    $count = 0;

    \App\Models\Assessment::query()
        ->with('lpk')
        ->whereNotIn('status', ['SUSPENDED', 'CANCELLED', 'COMPLETED'])
        ->tpOverdue()
        ->chunkById(100, function ($assessments) use (&$count) {
            foreach ($assessments as $assessment) {
                // Cegah pembekuan otomatis untuk riwayat surveilen siklus lampau pada LPK aktif
                if ($assessment->isPastSurveillanceForActiveLpk()) {
                    continue;
                }

                $assessment->status = 'SUSPENDED';
                $assessment->save();
                $count++;
            }
        });

    \Illuminate\Support\Facades\Cache::put('simasadi_surveillance_version', microtime(true));

    $this->info("Sinkronisasi status selesai: {$count} asesmen yang melewati batas waktu KAN diperbarui menjadi SUSPENDED.");
})->purpose('Menyinkronkan status asesmen yang melewati batas waktu tindakan perbaikan menjadi SUSPENDED.');

Schedule::command('assessments:sync-overdue-status')
    ->dailyAt('07:15')
    ->withoutOverlapping()
    ->runInBackground();





