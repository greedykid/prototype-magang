<?php

namespace Database\Seeders;

use App\Models\Accreditation;
use App\Models\Amendment;
use App\Models\Assessment;
use App\Models\Backup;
use App\Models\CalendarEvent;
use App\Models\Issue;
use App\Models\Lpk;
use App\Models\Service;
use App\Models\User;
use Illuminate\Database\Console\Seeds\WithoutModelEvents;
use Illuminate\Database\Seeder;

class DatabaseSeeder extends Seeder
{
    use WithoutModelEvents;

    /**
     * Seed the application's database.
     */
    public function run(): void
    {
        $user = User::factory()->create([
            'name' => 'Petugas Demo',
            'email' => 'demo@kanmis.local',
            'password' => 'password',
        ]);

        $lpks = Lpk::factory(8)->create();

        $lpks->each(function (Lpk $lpk, int $index): void {
            Accreditation::factory()->create([
                'lpk_id' => $lpk->id,
                'status' => ['IN_PROGRESS', 'NOT_STARTED', 'COMPLETED'][$index % 3],
            ]);
        });

        $issues = Issue::factory(6)->create(['created_by' => $user->id]);
        $issues->take(3)->each(function (Issue $issue) use ($user): void {
            $issue->followups()->create([
                'user_id' => $user->id,
                'note' => 'Data awal untuk mempelajari alur tindak lanjut.',
            ]);
        });

        $lpks->take(4)->each(function (Lpk $lpk, int $index) use ($user): void {
            $start = now()->startOfMonth()->addDays($index * 2 + 1)->setTime(9, 0);
            CalendarEvent::factory()->create([
                'lpk_id' => $lpk->id,
                'created_by' => $user->id,
                'start_at' => $start,
                'end_at' => $start->copy()->addHours(2),
                'status' => ['PLANNED', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED'][$index],
            ]);
        });
        Service::factory(3)->create()->each(fn (Service $service) => $service->checks()->create(['status' => $service->status, 'checked_at' => now(), 'message' => 'Pemeriksaan manual untuk latihan.', 'checked_by' => $user->id]));
        Backup::factory(4)->create(['recorded_by' => $user->id]);
        $assessments = Assessment::factory(6)->create(['created_by' => $user->id]);
        $assessments->each(function (Assessment $assessment, int $idx) use ($user): void {
            $daily = 860000;
            $transport = 1250000 + ($idx * 200000);
            $accommodation = 750000;
            $data = 150000;
            $status = ['TERVERIFIKASI', 'MENUNGGU_VERIFIKASI', 'BELUM_DILAPORKAN'][$idx % 3];

            $assessment->expense()->create([
                'reported_by' => $user->id,
                'daily_allowance' => $daily,
                'transport_cost' => $transport,
                'accommodation_cost' => $accommodation,
                'package_data_cost' => $data,
                'total_cost' => $daily + $transport + $accommodation + $data,
                'receipt_note' => 'Tiket GA-412 & Kwitansi Hotel Santika #' . (1000 + $idx),
                'status' => $status,
                'verification_notes' => $status === 'TERVERIFIKASI' ? 'Biaya perjalanan dinas diverifikasi sesuai SBM PMK No. 49.' : null,
                'verified_by' => $status === 'TERVERIFIKASI' ? $user->id : null,
                'verified_at' => $status === 'TERVERIFIKASI' ? now()->subDays(2) : null,
            ]);
        });

        Accreditation::all()->each(function (Accreditation $accreditation, int $idx): void {
            $isPaid = $accreditation->status === 'COMPLETED' || $idx % 2 === 0;
            $billing = $accreditation->billings()->create([
                'billing_code' => '8' . str_pad((string) (2026091800000 + $idx), 14, '0', STR_PAD_LEFT),
                'tariff_name' => 'PNBP Jasa Akreditasi Laboratorium / Lembaga Sertifikasi (PP PNBP BSN)',
                'amount' => 7500000,
                'issued_at' => now()->subDays(5),
                'expired_at' => now()->addDays(2),
                'status' => $isPaid ? 'PAID' : 'UNPAID',
                'ntpn' => $isPaid ? 'NTPN' . strtoupper(substr(md5((string) $idx), 0, 12)) : null,
                'ntb' => $isPaid ? 'NTB-' . (80000000 + $idx) : null,
                'payment_channel' => $isPaid ? 'Bank Mandiri (ATM / Livin)' : null,
                'paid_at' => $isPaid ? now()->subDays(3) : null,
            ]);

            if ($accreditation->status === 'COMPLETED') {
                $skNum = 'SK.KAN.' . str_pad((string) $accreditation->id, 3, '0', STR_PAD_LEFT) . '/BSN/IX/2026';
                $accreditation->signature()->create([
                    'sk_number' => $skNum,
                    'signer_name' => 'Drs. Kukuh S. Achmad, M.Sc.',
                    'signer_title' => 'Ketua Komite Akreditasi Nasional (KAN)',
                    'signer_nip' => '196508121990031002',
                    'is_signed' => true,
                    'signed_at' => now()->subDays(1),
                    'certificate_series' => 'BSrE-DS-2026-' . (80000 + $idx),
                    'verify_hash' => hash('sha256', "KAN-BSN:{$accreditation->id}:{$skNum}:demo"),
                ]);
            }
        });
    }
}
