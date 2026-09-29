<?php

namespace Database\Seeders;

use App\Models\Lpk;
use Carbon\Carbon;
use Illuminate\Database\Seeder;

class LpkSeeder extends Seeder
{
    /**
     * Menyiapkan 10 data master LPK rujukan resmi untuk program asesmen dan surveilen KAN.
     */
    public function run(): void
    {
        $lpks = [
            [
                'registration_number' => 'LP-077-IDN',
                'name' => 'BRIN Lab Pengujian Kekuatan Struktur',
                'scope' => 'Pengujian uji tarik statis, kelelahan struktur baja, dan uji impak sesuai ISO/IEC 17025:2017',
                'certificate_date' => Carbon::parse('2025-04-20'),
                'expired_at' => Carbon::parse('2030-04-20'),
                'address' => 'Kawasan PUSPIPTEK Gedung 220, Setu, Tangerang Selatan, Banten',
                'email' => 'lab.struktur@brin.go.id',
                'phone' => '021-7560562',
                'status' => 'ACTIVE',
            ],
            [
                'registration_number' => 'LP-186-IDN',
                'name' => 'UPT Lab Pengujian Konstruksi DPU Bina Marga Jawa Timur',
                'scope' => 'Pengujian material jalan, aspal beton, dan agregat konstruksi',
                'certificate_date' => Carbon::parse('2021-06-10'),
                'expired_at' => Carbon::parse('2026-06-10'),
                'address' => 'Jl. Ngampelsari No. 100, Candi, Sidoarjo, Jawa Timur',
                'email' => 'lab.konstruksi@jatimprov.go.id',
                'phone' => '031-8921456',
                'status' => 'ACTIVE',
            ],
            [
                'registration_number' => 'LP-487-IDN',
                'name' => 'Balai Pengembangan Jasa Konstruksi DPUPESDM DIY',
                'scope' => 'Uji kuat tekan kubus dan silinder beton serta uji konsistensi campuran semen',
                'certificate_date' => Carbon::parse('2025-05-15'),
                'expired_at' => Carbon::parse('2030-05-15'),
                'address' => 'Jl. Ring Road Utara Maguwoharjo, Depok, Sleman, D.I. Yogyakarta',
                'email' => 'bpjk.jogja@jogjaprov.go.id',
                'phone' => '0274-488123',
                'status' => 'ACTIVE',
            ],
            [
                'registration_number' => 'LP-750-IDN',
                'name' => 'UPT Lab Bahan Konstruksi DPUPR Riau',
                'scope' => 'Pengujian tanah mekanik dan bahan jalan',
                'certificate_date' => Carbon::parse('2023-01-15'),
                'expired_at' => Carbon::parse('2028-01-15'),
                'address' => 'Jl. Sudirman No. 197, Pekanbaru, Riau',
                'email' => 'lab.pupr@riau.go.id',
                'phone' => '0761-34567',
                'status' => 'ACTIVE',
            ],
            [
                'registration_number' => 'LP-1178-IDN',
                'name' => 'Lab Pengujian Material Konstruksi DPUPR Badung',
                'scope' => 'Penambahan lingkup akreditasi metode SNI 2052:2017 untuk pengujian baja tulangan sirip dan polos',
                'certificate_date' => Carbon::parse('2024-03-10'),
                'expired_at' => Carbon::parse('2029-03-10'),
                'address' => 'Jl. Kebo Iwa No. 39, Ubung Kaja, Denpasar, Bali',
                'email' => 'lab.badung@badungkab.go.id',
                'phone' => '0361-423567',
                'status' => 'ACTIVE',
            ],
            [
                'registration_number' => 'LP-1450-IDN',
                'name' => 'Laboratorium Pengujian PT Guna Sukses Inti',
                'scope' => 'Sistem manajemen mutu laboratorium ISO/IEC 17025:2017',
                'certificate_date' => Carbon::parse('2026-01-15'),
                'expired_at' => Carbon::parse('2031-01-15'),
                'address' => 'Puri Sentra Niaga T3 No. 7, Kembangan, Jakarta Barat',
                'email' => 'qa@gunasukses.co.id',
                'phone' => '021-58301234',
                'status' => 'ACTIVE',
            ],
            [
                'registration_number' => 'LP-1540-IDN',
                'name' => 'UPTD Laboratorium Bahan Konstruksi BMBK Lampung',
                'scope' => 'Pengujian aspal, agregat, dan pemadatan tanah',
                'certificate_date' => Carbon::parse('2021-06-02'),
                'expired_at' => Carbon::parse('2026-06-02'),
                'address' => 'Jl. Z.A. Pagar Alam KM.11 Rajabasa, Bandar Lampung',
                'email' => 'lab.bmbk@lampungprov.go.id',
                'phone' => '0721-701234',
                'status' => 'ACTIVE',
            ],
            [
                'registration_number' => 'LP-1541-IDN',
                'name' => 'UPT Lab Konstruksi DPU Kab. Kutai Timur',
                'scope' => 'Pengujian kepadatan lapangan (sand cone) dan CBR',
                'certificate_date' => Carbon::parse('2021-10-15'),
                'expired_at' => Carbon::parse('2026-10-15'),
                'address' => 'Kawasan Bukit Pelangi, Sangatta, Kab. Kutai Timur, Kalimantan Timur',
                'email' => 'lab.dpu@kutaitimurkab.go.id',
                'phone' => '0549-21876',
                'status' => 'ACTIVE',
            ],
            [
                'registration_number' => 'LP-1582-IDN',
                'name' => 'UPTD Lab Bahan Konstruksi BMPR Jawa Barat',
                'scope' => 'Pengujian beton, aspal AC-WC, dan agregat pondasi kelas A',
                'certificate_date' => Carbon::parse('2023-04-10'),
                'expired_at' => Carbon::parse('2028-04-10'),
                'address' => 'Jl. A.H. Nasution No. 117, Ujung Berung, Bandung, Jawa Barat',
                'email' => 'lab.bmpr@jabarprov.go.id',
                'phone' => '022-7801234',
                'status' => 'ACTIVE',
            ],
            [
                'registration_number' => 'LP-1639-IDN',
                'name' => 'UPTD Lab Konstruksi Dinas SDABM Sulawesi Tenggara',
                'scope' => 'Uji kuat tekan silinder beton dan pengujian abrasi agregat',
                'certificate_date' => Carbon::parse('2024-08-20'),
                'expired_at' => Carbon::parse('2029-08-20'),
                'address' => 'Jl. S. Parman No. 1A, Kendari, Sulawesi Tenggara',
                'email' => 'lab.sdabm@sultraprov.go.id',
                'phone' => '0401-312345',
                'status' => 'ACTIVE',
            ],
        ];

        foreach ($lpks as $item) {
            Lpk::updateOrCreate(
                ['registration_number' => $item['registration_number']],
                $item
            );
        }
    }
}
