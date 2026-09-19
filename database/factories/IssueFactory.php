<?php

namespace Database\Factories;

use App\Models\Issue;
use App\Models\Lpk;
use App\Models\User;
use Illuminate\Database\Eloquent\Factories\Factory;

/**
 * @extends Factory<Issue>
 */
class IssueFactory extends Factory
{
    /**
     * Define the model's default state.
     *
     * @return array<string, mixed>
     */
    public function definition(): array
    {
        $issueTemplates = [
            [
                'title' => 'Keterlambatan penyampaian laporan audit internal berkala',
                'description' => 'LPK belum menyerahkan rekaman pelaksanaan audit internal tahunan dan tindak lanjut tindakan korektif sesuai ketentuan klausul 8.8 SNI ISO/IEC 17025.',
            ],
            [
                'title' => 'Peralatan spektrofotometer UV-Vis melewati jadwal kalibrasi',
                'description' => 'Masa berlaku sertifikat kalibrasi instrumen uji utama telah kedaluwarsa dan belum dilakukan rekualifikasi ketertelusuran standar acuan.',
            ],
            [
                'title' => 'Perubahan personel penanggung jawab teknis pengujian pangan',
                'description' => 'Terjadi rotasi manajer teknis laboratorium mikrobiologi dan dokumen kualifikasi kompetensi pengganti belum diserahkan ke sekretariat KAN.',
            ],
            [
                'title' => 'Pembaruan ketertelusuran standar acuan kalibrasi massa',
                'description' => 'Set anak timbangan standar kelas E2 memerlukan kalibrasi ulang ke Lembaga Metrologi Nasional (SNSU-BSN) untuk verifikasi nilai ketidakpastian.',
            ],
            [
                'title' => 'Revisi prosedur penanganan dan penyimpanan sampel uji',
                'description' => 'SOP penerimaan dan kodifikasi sampel uji lingkungan memerlukan pemutakhiran untuk mencegah risiko kontaminasi silang antar pengujian.',
            ],
            [
                'title' => 'Tindak lanjut temuan ketidaksesuaian kategori minor asesmen',
                'description' => 'Perlu penyerahan bukti pemenuhan tindakan perbaikan (closing statement) hasil temuan asesmen pengawasan berkala sebelum batas waktu.',
            ],
            [
                'title' => 'Verifikasi keabsahan metode uji non-standar laboratorium',
                'description' => 'Validasi metode analisis residu pestisida in-house memerlukan pelaporan data reprodusibilitas dan batas kuantitasi (LOQ) terbaru.',
            ],
            [
                'title' => 'Uji profisiensi antarlaboratorium putaran semester berjalan',
                'description' => 'Laporan partisipasi uji banding atau profisiensi skema pengujian air bersih belum diunggah ke sistem monitoring evaluasi.',
            ],
        ];

        $template = fake()->randomElement($issueTemplates);

        return [
            'lpk_id' => Lpk::factory(),
            'created_by' => User::factory(),
            'title' => $template['title'],
            'description' => $template['description'],
            'priority' => fake()->randomElement(['LOW', 'MEDIUM', 'HIGH']),
            'status' => fake()->randomElement(['OPEN', 'IN_PROGRESS', 'RESOLVED']),
            'due_date' => fake()->dateTimeBetween('now', '+2 months'),
        ];
    }
}
