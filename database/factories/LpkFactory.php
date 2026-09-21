<?php

namespace Database\Factories;

use App\Models\Lpk;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Lpk>
 */
class LpkFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        static $index = 0;

        $curatedLpks = [
            ['name' => 'PT Sucofindo (Persero) - Laboratorium Sentral', 'reg' => 'LP-001-IDN', 'address' => 'Jl. Arteri Tol Cibitung No. 1, Cikarang Barat, Bekasi', 'email' => 'lab.sentral@sucofindo.co.id', 'phone' => '021-88321176'],
            ['name' => 'PT Surveyor Indonesia - Laboratorium Penguji & Kalibrasi', 'reg' => 'LP-008-IDN', 'address' => 'Jl. Jend. Gatot Subroto Kav. 56, Jakarta Selatan', 'email' => 'contact.lab@ptsi.co.id', 'phone' => '021-5265526'],
            ['name' => 'Balai Besar Standardisasi dan Pelayanan Jasa Industri Agro (BBSPJIA)', 'reg' => 'LP-057-IDN', 'address' => 'Jl. Ir. H. Juanda No. 11, Bogor, Jawa Barat', 'email' => 'bbia@kemenperin.go.id', 'phone' => '0251-8324068'],
            ['name' => 'PT SGS Indonesia - Laboratorium Pengujian Mutu', 'reg' => 'LP-023-IDN', 'address' => 'Cilandak Commercial Estate #108C, Jakarta Selatan', 'email' => 'sgs.indonesia@sgs.com', 'phone' => '021-7818111'],
            ['name' => 'PT Carsurin Tbk - Laboratorium Penguji Mineral & Energi', 'reg' => 'LP-112-IDN', 'address' => 'Wisma Raharja Lt. 4, Jl. TB Simatupang, Jakarta Selatan', 'email' => 'lab.testing@carsurin.com', 'phone' => '021-78848123'],
            ['name' => 'Balai Pengujian Mutu dan Sertifikasi Produk Hewan (BPMSPH)', 'reg' => 'LP-041-IDN', 'address' => 'Jl. Pemuda No. 29A, Tanah Sareal, Kota Bogor', 'email' => 'bpmsph@pertanian.go.id', 'phone' => '0251-8377111'],
            ['name' => 'PT Mutuagung Lestari - Laboratorium Penguji Lingkungan', 'reg' => 'LP-079-IDN', 'address' => 'Jl. Raya Bogor KM 33.5 No. 19, Cimanggis, Depok', 'email' => 'operational@mutucertification.com', 'phone' => '021-8740202'],
            ['name' => 'PT Intertek Utama Services - Laboratorium Tekstil & Pangan', 'reg' => 'LP-064-IDN', 'address' => 'Jl. Raya Bogor KM 28, Pasar Rebo, Jakarta Timur', 'email' => 'indonesia.info@intertek.com', 'phone' => '021-87795111'],
            ['name' => 'Balai Besar Standardisasi dan Pelayanan Jasa Industri Tekstil (BBSPJIT)', 'reg' => 'LP-012-IDN', 'address' => 'Jl. Jend. A. Yani No. 390, Kota Bandung, Jawa Barat', 'email' => 'bbt@kemenperin.go.id', 'phone' => '022-7206214'],
            ['name' => 'Balai Besar Standardisasi dan Pelayanan Jasa Industri Keramik (BBSPJIK)', 'reg' => 'LP-028-IDN', 'address' => 'Jl. Jend. A. Yani No. 392, Kota Bandung, Jawa Barat', 'email' => 'bbk@kemenperin.go.id', 'phone' => '022-7206221'],
            ['name' => 'Balai Pengujian dan Fasilitas Kalibrasi Kesehatan (BPFK) Jakarta', 'reg' => 'LK-015-IDN', 'address' => 'Jl. Percetakan Negara No. 23, Jakarta Pusat', 'email' => 'bpfk.jakarta@kemkes.go.id', 'phone' => '021-4245214'],
            ['name' => 'PT Caltek Mandiri Instrumentasi - Laboratorium Kalibrasi', 'reg' => 'LK-088-IDN', 'address' => 'Kawasan Industri Jababeka II, Cikarang, Kab. Bekasi', 'email' => 'info@caltek-mandiri.co.id', 'phone' => '021-89834112'],
            ['name' => 'Laboratorium Terpadu IPB University', 'reg' => 'LP-145-IDN', 'address' => 'Kampus IPB Dramaga, Gedung Lab Terpadu, Bogor', 'email' => 'labterpadu@apps.ipb.ac.id', 'phone' => '0251-8622642'],
            ['name' => 'Laboratorium Pengujian Fakultas Farmasi Universitas Indonesia', 'reg' => 'LP-210-IDN', 'address' => 'Kampus UI Depok, Jawa Barat', 'email' => 'labfarmasi@farmasi.ui.ac.id', 'phone' => '021-7270031'],
            ['name' => 'Balai Besar Industri Kimia dan Kemasan (BBSPJIKK)', 'reg' => 'LP-035-IDN', 'address' => 'Jl. Balai Kimia No. 1, Pekayon, Jakarta Timur', 'email' => 'bbkk@kemenperin.go.id', 'phone' => '021-8710630'],
            ['name' => 'PT Geoservices - Laboratorium Eksplorasi Batubara & Energi', 'reg' => 'LP-093-IDN', 'address' => 'Jl. Setiabudhi No. 41, Bandung, Jawa Barat', 'email' => 'bdgoffice@geoservices.co.id', 'phone' => '022-2031456'],
            ['name' => 'Balai Besar Industri Hasil Perkebunan & Maritim (BBIHPMM) Makassar', 'reg' => 'LP-071-IDN', 'address' => 'Jl. Prof. Dr. Abdurahman Basalamah No. 28, Makassar', 'email' => 'bpihp@kemenperin.go.id', 'phone' => '0411-441203'],
            ['name' => 'PT TUV Rheinland Indonesia - Laboratorium Kelistrikan & Elektronik', 'reg' => 'LP-168-IDN', 'address' => 'Menara Karya Lt. 10, Jl. HR Rasuna Said, Jakarta Selatan', 'email' => 'info@idn.tuv.com', 'phone' => '021-57944579'],
            ['name' => 'Balai Riset dan Standardisasi Industri Surabaya (Baristand Surabaya)', 'reg' => 'LP-052-IDN', 'address' => 'Jl. Jagir Wonokromo No. 360, Surabaya, Jawa Timur', 'email' => 'baristandsurabaya@kemenperin.go.id', 'phone' => '031-8705333'],
            ['name' => 'PT Petrokimia Gresik - Laboratorium Uji Mutu Pupuk & Kimia', 'reg' => 'LP-180-IDN', 'address' => 'Jl. Jenderal Ahmad Yani, Kebomas, Gresik, Jawa Timur', 'email' => 'labuji@petrokimia-gresik.com', 'phone' => '031-3981811'],
            ['name' => 'PT Pupuk Kalimantan Timur - Laboratorium Kalibrasi & Kimia', 'reg' => 'LP-195-IDN', 'address' => 'Jl. James Simandjuntak No. 1, Bontang, Kalimantan Timur', 'email' => 'lab@pupukkaltim.com', 'phone' => '0548-41202'],
            ['name' => 'PT Citrabuana Indoloka - Lembaga Sertifikasi Produk (LSPro)', 'reg' => 'LSPr-014-IDN', 'address' => 'Komp. Ruko Golden Fatmawati, Jakarta Selatan', 'email' => 'info@citrabuana.co.id', 'phone' => '021-75908811'],
            ['name' => 'PT Biro Klasifikasi Indonesia (Persero) - Laboratorium Material', 'reg' => 'LP-134-IDN', 'address' => 'Jl. Yos Sudarso No. 38-40, Tanjung Priok, Jakarta Utara', 'email' => 'lab.uji@bki.co.id', 'phone' => '021-4301017'],
            ['name' => 'PT Qualis Indonesia - Laboratorium Pengujian Produk Konsumen', 'reg' => 'LP-230-IDN', 'address' => 'Kawasan Pergudangan Tekno BSD City, Tangerang Selatan', 'email' => 'testing@qualis-indonesia.com', 'phone' => '021-7566723'],
            ['name' => 'Balai Pengujian Kesehatan Ikan dan Lingkungan (BPKIL) Serang', 'reg' => 'LP-118-IDN', 'address' => 'Jl. Raya Mantingan KM 24, Serang, Banten', 'email' => 'bpkil.serang@kkp.go.id', 'phone' => '0291-591147'],
        ];

        $item = $curatedLpks[$index % count($curatedLpks)];
        $index++;

        $scopeMap = [
            'LP' => 'Laboratorium Pengujian Kimia, Fisika, Biologi, dan Lingkungan Hidup',
            'LK' => 'Laboratorium Kalibrasi Suhu, Tekanan, Massa, dan Kelistrikan',
            'LSPr' => 'Lembaga Sertifikasi Produk SNI Sektor Manufaktur & Pangan',
            'LI' => 'Lembaga Inspeksi Instalasi Pipa, Bejana Tekan, dan Struktur',
        ];

        $prefix = explode('-', $item['reg'])[0] ?? 'LP';
        $scope = $scopeMap[$prefix] ?? 'Pengujian Mutu, Kalibrasi Instrumen, dan Asesmen Penilaian Kesesuaian';

        return [
            'registration_number' => $item['reg'],
            'name' => $item['name'],
            'scope' => $scope,
            'address' => $item['address'],
            'email' => $item['email'],
            'phone' => $item['phone'],
            'status' => 'ACTIVE',
            'expired_at' => now()->addYears(3)->addMonths(random_int(1, 12))->toDateString(),
            'drive_url' => 'https://drive.google.com/drive/folders/1demo-berkas-' . strtolower(str_replace(['-', ' '], '', $item['reg'])),
            'notes' => 'Lembaga Penilaian Kesesuaian terakreditasi KAN (Komite Akreditasi Nasional).',
        ];
    }
}
