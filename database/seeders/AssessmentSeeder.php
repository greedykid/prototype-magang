<?php

namespace Database\Seeders;

use App\Models\Assessment;
use App\Models\AssessmentExpense;
use App\Models\Lpk;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class AssessmentSeeder extends Seeder
{
    /**
     * Seed assessment programs with real TP/VTP SLA tracking for imported LPKs.
     */
    public function run(): void
    {
        $admin = User::where('role', User::ROLE_ADMIN)->first() ?? User::first();
        $adminId = $admin ? $admin->id : 1;
        $asesorUser = User::where('email', 'asesor@simasadi.local')->first() ?? $admin;

        // 1. LP-077-IDN - BRIN Lab Pengujian Kekuatan Struktur
        $lpk1 = Lpk::where('registration_number', 'LP-077-IDN')->first();
        if ($lpk1) {
            $a1 = Assessment::updateOrCreate(
                ['lpk_id' => $lpk1->id, 'title' => 'Asesmen Surveilen 1 (S1) Lab Pengujian Kekuatan Struktur BRIN'],
                [
                    'created_by' => $adminId,
                    'assessment_type' => 'Surveilen',
                    'start_at' => Carbon::parse('2026-07-20 09:00:00'),
                    'end_at' => Carbon::parse('2026-07-22 17:00:00'),
                    'location' => 'Kawasan PUSPIPTEK Gedung 220, Setu, Tangerang Selatan, Banten',
                    'status' => 'COMPLETED',
                    'lead_assessor' => 'Dr. Ir. Suryadi, M.Eng.',
                    'notes' => 'Surveilen berkala lingkup pengujian uji tarik statis, kelelahan struktur baja, dan uji impak sesuai ISO/IEC 17025:2017.',
                    'tp_status' => Assessment::TP_STATUS_IN_PROGRESS,
                    'tp_due_date' => Carbon::parse('2026-09-30'),
                    'tp_has_extension' => false,
                    'tp_extension_months' => 0,
                    'tp_notes' => 'Terdapat 2 temuan Kategori 2 terkait bukti rekaman kalibrasi load cell mesin uji dinamis kapasitas 500 kN dan evaluasi estimasi ketidakpastian pengukuran.',
                ]
            );

            AssessmentExpense::updateOrCreate(
                ['assessment_id' => $a1->id],
                [
                    'reported_by' => $asesorUser->id,
                    'transport_cost' => 850000,
                    'accommodation_cost' => 1400000,
                    'daily_allowance' => 1140000,
                    'package_data_cost' => 150000,
                    'total_cost' => 3540000,
                    'receipt_note' => 'Kwitansi Taksi Bandara & Hotel Santika Premiere Bintaro (2 malam)',
                    'status' => 'TERVERIFIKASI',
                    'verification_notes' => 'Telah diverifikasi sesuai SBM PMK No. 49 Standar Biaya Masukan TA 2026.',
                    'verified_by' => $adminId,
                    'verified_at' => Carbon::parse('2026-07-26 14:30:00'),
                ]
            );
        }

        // 2. LP-186-IDN - UPT Lab Pengujian Konstruksi DPU Bina Marga Jawa Timur
        $lpk2 = Lpk::where('registration_number', 'LP-186-IDN')->first();
        if ($lpk2) {
            $a2 = Assessment::updateOrCreate(
                ['lpk_id' => $lpk2->id, 'title' => 'Re-asesmen Siklus Akreditasi UPT Lab Pengujian Konstruksi DPU Bina Marga Jatim'],
                [
                    'created_by' => $adminId,
                    'assessment_type' => 'Re-asesmen',
                    'start_at' => Carbon::parse('2026-06-10 08:30:00'),
                    'end_at' => Carbon::parse('2026-06-12 16:30:00'),
                    'location' => 'Jl. Ngampelsari No. 100, Candi, Sidoarjo, Jawa Timur',
                    'status' => 'COMPLETED',
                    'lead_assessor' => 'Ir. Bambang Suwito, M.T.',
                    'notes' => 'Re-asesmen siklus 5 tahunan untuk perpanjangan masa akreditasi pengujian material jalan, aspal beton, dan agregat konstruksi.',
                    'tp_status' => Assessment::TP_STATUS_IN_PROGRESS,
                    'tp_due_date' => Carbon::parse('2026-08-12'),
                    'tp_has_extension' => true,
                    'tp_extension_months' => 1,
                    'tp_extension_letter_no' => '050/782/103.4/2026',
                    'tp_extension_date' => Carbon::parse('2026-08-05'),
                    'tp_extension_notes' => 'Permohonan perpanjangan masa perbaikan karena keterlambatan suku cadang alat Marshall Test dan re-kalibrasi eksternal.',
                    'tp_notes' => 'Temuan ketidaksesuaian kategori 2 mengenai sertifikat kalibrasi termometer digital oven aspal dan partisipasi uji profisiensi agregat.',
                ]
            );

            AssessmentExpense::updateOrCreate(
                ['assessment_id' => $a2->id],
                [
                    'reported_by' => $asesorUser->id,
                    'transport_cost' => 1850000,
                    'accommodation_cost' => 1500000,
                    'daily_allowance' => 1230000,
                    'package_data_cost' => 150000,
                    'total_cost' => 4730000,
                    'receipt_note' => 'Tiket Garuda GA-318 Jakarta-Surabaya PP & Hotel Aston Sidoarjo',
                    'status' => 'TERVERIFIKASI',
                    'verification_notes' => 'Kelengkapan bukti boarding pass dan invoice hotel telah sesuai pagu SBM Jawa Timur.',
                    'verified_by' => $adminId,
                    'verified_at' => Carbon::parse('2026-06-16 10:15:00'),
                ]
            );
        }

        // 3. LP-487-IDN - Balai Pengembangan Jasa Konstruksi DPUPESDM DIY
        $lpk3 = Lpk::where('registration_number', 'LP-487-IDN')->first();
        if ($lpk3) {
            Assessment::updateOrCreate(
                ['lpk_id' => $lpk3->id, 'title' => 'Asesmen Surveilen 1 (S1) Balai Pengembangan Jasa Konstruksi DPUPESDM DIY'],
                [
                    'created_by' => $adminId,
                    'assessment_type' => 'Surveilen',
                    'start_at' => Carbon::parse('2026-08-18 09:00:00'),
                    'end_at' => Carbon::parse('2026-08-20 16:00:00'),
                    'location' => 'Jl. Ring Road Utara Maguwoharjo, Depok, Sleman, D.I. Yogyakarta',
                    'status' => 'COMPLETED',
                    'lead_assessor' => 'Dra. Siti Rahayu, M.Si.',
                    'notes' => 'Pengawasan berkala parameter uji kuat tekan kubus dan silinder beton serta uji konsistensi campuran semen.',
                    'tp_status' => Assessment::TP_STATUS_IN_PROGRESS,
                    'tp_due_date' => Carbon::parse('2026-10-20'),
                    'tp_has_extension' => false,
                    'tp_extension_months' => 0,
                    'tp_notes' => 'Tindakan perbaikan dokumen SOP perawatan bak perendaman sampel beton (curing room) dan rekaman kontrol suhu/kelembaban ruangan uji.',
                ]
            );
        }

        // 4. LP-750-IDN - UPT Lab Bahan Konstruksi DPUPR Riau
        $lpk4 = Lpk::where('registration_number', 'LP-750-IDN')->first();
        if ($lpk4) {
            Assessment::updateOrCreate(
                ['lpk_id' => $lpk4->id, 'title' => 'Asesmen Surveilen 2 (S2) UPT Laboratorium Bahan Konstruksi PUPR Riau'],
                [
                    'created_by' => $adminId,
                    'assessment_type' => 'Surveilen',
                    'start_at' => Carbon::parse('2026-04-14 08:30:00'),
                    'end_at' => Carbon::parse('2026-04-16 16:30:00'),
                    'location' => 'Jl. Sudirman No. 197, Pekanbaru, Riau',
                    'status' => 'COMPLETED',
                    'lead_assessor' => 'Ir. Hendra Gunawan, M.T.',
                    'notes' => 'Surveilen kedua untuk menjamin konsistensi sistem manajemen ISO/IEC 17025 pada pengujian tanah mekanik dan bahan jalan.',
                    'tp_status' => Assessment::TP_STATUS_SATISFIED,
                    'tp_due_date' => Carbon::parse('2026-06-16'),
                    'tp_has_extension' => false,
                    'tp_satisfied_at' => Carbon::parse('2026-06-10'),
                    'tp_notes' => 'Seluruh tindakan perbaikan atas 3 temuan asesmen lapangan telah diverifikasi lengkap dan dinyatakan memenuhi regulasi KAN.',
                ]
            );
        }

        // 5. LP-1178-IDN - Lab Pengujian Material Konstruksi DPUPR Badung
        $lpk5 = Lpk::where('registration_number', 'LP-1178-IDN')->first();
        if ($lpk5) {
            $a5 = Assessment::updateOrCreate(
                ['lpk_id' => $lpk5->id, 'title' => 'Asesmen Perluasan Lingkup Uji Tarik Baja Tulangan Beton DPUPR Badung'],
                [
                    'created_by' => $adminId,
                    'assessment_type' => 'Perluasan Lingkup',
                    'start_at' => Carbon::parse('2026-07-28 09:00:00'),
                    'end_at' => Carbon::parse('2026-07-30 16:30:00'),
                    'location' => 'Jl. Kebo Iwa No. 39, Ubung Kaja, Denpasar, Bali',
                    'status' => 'COMPLETED',
                    'lead_assessor' => 'Dr. Hendra Asesor',
                    'notes' => 'Penambahan lingkup akreditasi metode SNI 2052:2017 untuk pengujian baja tulangan sirip dan polos.',
                    'tp_status' => Assessment::TP_STATUS_UNDER_VERIFICATION,
                    'tp_due_date' => Carbon::parse('2026-09-30'),
                    'tp_has_extension' => false,
                    'tp_notes' => 'Bukti verifikasi unjuk kerja mesin uji Universal Testing Machine (UTM) 1000 kN telah diunggah dan dalam penelaahan akhir Lead Assessor.',
                ]
            );

            AssessmentExpense::updateOrCreate(
                ['assessment_id' => $a5->id],
                [
                    'reported_by' => $asesorUser->id,
                    'transport_cost' => 2200000,
                    'accommodation_cost' => 1600000,
                    'daily_allowance' => 1290000,
                    'package_data_cost' => 150000,
                    'total_cost' => 5240000,
                    'receipt_note' => 'Tiket Pesawat Jakarta-Denpasar PP & Hotel Grand Santhi Denpasar (2 malam)',
                    'status' => 'MENUNGGU_VERIFIKASI',
                    'verification_notes' => null,
                ]
            );
        }

        // 6. LP-1450-IDN - PT Guna Sukses Inti
        $lpk6 = Lpk::where('registration_number', 'LP-1450-IDN')->first();
        if ($lpk6) {
            Assessment::updateOrCreate(
                ['lpk_id' => $lpk6->id, 'title' => 'Asesmen Awal Akreditasi Laboratorium Pengujian PT Guna Sukses Inti'],
                [
                    'created_by' => $adminId,
                    'assessment_type' => 'Asesmen Awal',
                    'start_at' => Carbon::parse('2026-01-12 09:00:00'),
                    'end_at' => Carbon::parse('2026-01-14 17:00:00'),
                    'location' => 'Puri Sentra Niaga T3 No. 7, Kembangan, Jakarta Barat',
                    'status' => 'COMPLETED',
                    'lead_assessor' => 'Ir. Ahmad Fauzi, M.Si.',
                    'notes' => 'Asesmen awal sistem manajemen mutu laboratorium ISO/IEC 17025:2017.',
                    'tp_status' => Assessment::TP_STATUS_NONE,
                    'tp_due_date' => null,
                    'tp_has_extension' => false,
                    'tp_notes' => 'Tidak ditemukan ketidaksesuaian kategori 1 maupun 2. Hasil asesmen direkomendasikan langsung untuk penetapan akreditasi.',
                ]
            );
        }

        // 7. LP-1540-IDN - UPTD Lab Bahan Konstruksi Dinas BMBK Lampung
        $lpk7 = Lpk::where('registration_number', 'LP-1540-IDN')->first();
        if ($lpk7) {
            Assessment::updateOrCreate(
                ['lpk_id' => $lpk7->id, 'title' => 'Re-asesmen Akreditasi UPTD Laboratorium Bahan Konstruksi BMBK Lampung'],
                [
                    'created_by' => $adminId,
                    'assessment_type' => 'Re-asesmen',
                    'start_at' => Carbon::parse('2026-06-02 08:30:00'),
                    'end_at' => Carbon::parse('2026-06-04 16:30:00'),
                    'location' => 'Jl. Z.A. Pagar Alam KM.11 Rajabasa, Bandar Lampung',
                    'status' => 'COMPLETED',
                    'lead_assessor' => 'Dr. Hendra Asesor',
                    'notes' => 'Re-asesmen 5 tahunan ruang lingkup pengujian aspal, agregat, dan pemadatan tanah.',
                    'tp_status' => Assessment::TP_STATUS_IN_PROGRESS,
                    'tp_due_date' => Carbon::parse('2026-08-04'),
                    'tp_has_extension' => false,
                    'tp_notes' => 'Temuan Kategori 1 terkait ketertelusuran kalibrasi timbangan analitik serta ketiadaan bukti audit internal tahun berjalan. Menunggu tanggapan kepala lab.',
                ]
            );
        }

        // 8. LP-1541-IDN - UPT Lab Konstruksi DPUPR Kutai Timur
        $lpk8 = Lpk::where('registration_number', 'LP-1541-IDN')->first();
        if ($lpk8) {
            Assessment::updateOrCreate(
                ['lpk_id' => $lpk8->id, 'title' => 'Re-asesmen Akreditasi UPT Lab Konstruksi DPU Kab. Kutai Timur'],
                [
                    'created_by' => $adminId,
                    'assessment_type' => 'Re-asesmen',
                    'start_at' => Carbon::parse('2026-09-28 09:00:00'),
                    'end_at' => Carbon::parse('2026-09-30 17:00:00'),
                    'location' => 'Kawasan Bukit Pelangi, Sangatta, Kab. Kutai Timur, Kalimantan Timur',
                    'status' => 'SCHEDULED',
                    'lead_assessor' => 'Ir. Dedi Kurniawan, M.T.',
                    'notes' => 'Re-asesmen lapangan sebelum masa berlaku sertifikat berakhir pada Oktober 2026. Fokus pada pengujian kepadatan lapangan (sand cone) dan CBR.',
                    'tp_status' => Assessment::TP_STATUS_NONE,
                    'tp_due_date' => null,
                ]
            );
        }

        // 9. LP-1582-IDN - UPTD Lab Bahan Konstruksi Dinas BMPR Jawa Barat
        $lpk9 = Lpk::where('registration_number', 'LP-1582-IDN')->first();
        if ($lpk9) {
            $a9 = Assessment::updateOrCreate(
                ['lpk_id' => $lpk9->id, 'title' => 'Asesmen Surveilen 2 (S2) UPTD Lab Bahan Konstruksi BMPR Jawa Barat'],
                [
                    'created_by' => $adminId,
                    'assessment_type' => 'Surveilen',
                    'start_at' => Carbon::parse('2026-07-06 09:00:00'),
                    'end_at' => Carbon::parse('2026-07-08 16:30:00'),
                    'location' => 'Jl. A.H. Nasution No. 117, Ujung Berung, Bandung, Jawa Barat',
                    'status' => 'COMPLETED',
                    'lead_assessor' => 'Dr. Hendra Asesor',
                    'notes' => 'Surveilen 2 pengawasan parameter uji beton, aspal AC-WC, dan agregat pondasi kelas A.',
                    'tp_status' => Assessment::TP_STATUS_UNDER_VERIFICATION,
                    'tp_due_date' => Carbon::parse('2026-09-08'),
                    'tp_has_extension' => true,
                    'tp_extension_months' => 1,
                    'tp_extension_letter_no' => '602/Lab-JBR/VII/2026',
                    'tp_extension_date' => Carbon::parse('2026-08-25'),
                    'tp_extension_notes' => 'Perpanjangan masa perbaikan karena menunggu sertifikat uji banding antarlaboratorium dari Balai Besar Standardisasi dan Pelayanan Jasa Industri Bahan dan Barang Teknik (BBSPJIBBT).',
                    'tp_notes' => 'Tindakan perbaikan pembaruan lembar kerja uji ketahanan aus agregat (Los Angeles Machine) dan kaji ulang manajemen laboratorium.',
                ]
            );

            AssessmentExpense::updateOrCreate(
                ['assessment_id' => $a9->id],
                [
                    'reported_by' => $asesorUser->id,
                    'transport_cost' => 600000,
                    'accommodation_cost' => 1200000,
                    'daily_allowance' => 1140000,
                    'package_data_cost' => 150000,
                    'total_cost' => 3090000,
                    'receipt_note' => 'Kereta Cepat Whoosh Halim-Tegalluar PP & Hotel De Braga Bandung (2 malam)',
                    'status' => 'TERVERIFIKASI',
                    'verification_notes' => 'Kwitansi transportasi Whoosh dan invoice penginapan sesuai SBM.',
                    'verified_by' => $adminId,
                    'verified_at' => Carbon::parse('2026-07-12 11:00:00'),
                ]
            );
        }

        // 10. LP-1639-IDN - UPTD Lab Konstruksi Dinas SDABM Sulawesi Tenggara
        $lpk10 = Lpk::where('registration_number', 'LP-1639-IDN')->first();
        if ($lpk10) {
            Assessment::updateOrCreate(
                ['lpk_id' => $lpk10->id, 'title' => 'Penyaksian Pengujian Laboratorium UPTD Konstruksi SDABM Sultra'],
                [
                    'created_by' => $adminId,
                    'assessment_type' => 'Penyaksian',
                    'start_at' => Carbon::parse('2026-10-12 08:30:00'),
                    'end_at' => Carbon::parse('2026-10-14 16:00:00'),
                    'location' => 'Jl. S. Parman No. 1A, Kendari, Sulawesi Tenggara',
                    'status' => 'SCHEDULED',
                    'lead_assessor' => 'Dr. Ir. Suryadi, M.Eng.',
                    'notes' => 'Penyaksian pelaksanaan uji kuat tekan silinder beton dan pengujian abrasi agregat di laboratorium Kendari.',
                    'tp_status' => Assessment::TP_STATUS_NONE,
                    'tp_due_date' => null,
                ]
            );
        }
    }
}
