<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Lpk;
use App\Models\User;
use Carbon\Carbon;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LpkSurveillanceCycleLogicTest extends TestCase
{
    use RefreshDatabase;

    /**
     * Skenario 1: Acuan arah terbalik (Backward Reference)
     * Ketika hanya ada expired_at (misal 2031-07-24) dan certificate_date null.
     */
    public function test_backward_reference_milestones_calculation(): void
    {
        $expiredAt = Carbon::parse('2030-01-18');

        $lpk = Lpk::create([
            'registration_number' => 'LP-BACKWARD-01',
            'name' => 'Lab Pengujian Acuan Masa Akhir',
            'status' => 'ACTIVE',
            'certificate_date' => null,
            'expired_at' => $expiredAt->toDateString(),
        ]);

        $milestones = $lpk->surveillance_milestones;

        // Masa Akhir: 2030-01-18
        // S1 Target Kunjungan (Bulan 15 kedepan): 2031-04-18
        // S1 Target Jatuh Tempo (Bulan 18 kedepan): 2031-07-18
        $this->assertEquals('2031-04-18', $milestones['s1']['visit_target_date']->toDateString());
        $this->assertEquals('2031-07-18', $milestones['s1']['target_date']->toDateString());

        // S2 Target Kunjungan (Bulan 36 kedepan): 2033-01-18
        // S2 Target Jatuh Tempo (Bulan 39 kedepan): 2033-04-18
        $this->assertEquals('2033-01-18', $milestones['s2']['visit_target_date']->toDateString());
        $this->assertEquals('2033-04-18', $milestones['s2']['target_date']->toDateString());

        // RA Target Dokumen Lengkap (Bulan 51 kedepan): 2034-04-18
        // RA Target Kunjungan Lapangan (Bulan 54 kedepan): 2034-07-18
        // RA Masa Berlaku Habis (Bulan 60 kedepan): 2035-01-18
        $this->assertEquals('2034-04-18', $milestones['ra']['target_date']->toDateString());
        $this->assertEquals('2034-07-18', $milestones['ra']['visit_target_date']->toDateString());
        $this->assertEquals('2035-01-18', $milestones['ra']['tolerance_date']->toDateString());
    }

    /**
     * Skenario 2: Masa tenggang 6 bulan (Grace Period)
     * Sertifikat telah kedaluwarsa 2 bulan yang lalu (Bulan ke-62) namun memiliki agenda asesmen
     * Re-Akreditasi yang telah berjalan/dilaksanakan sebelum siklus berakhir.
     */
    public function test_lpk_enters_grace_period_within_six_months_after_expiry(): void
    {
        Carbon::setTestNow(Carbon::parse('2031-09-24 10:00:00'));

        $admin = User::factory()->admin()->create();

        $lpk = Lpk::create([
            'registration_number' => 'LP-GRACE-01',
            'name' => 'Lab Masa Tenggang 6 Bulan',
            'status' => 'ACTIVE',
            'certificate_date' => '2026-07-24',
            'expired_at' => '2031-07-24',
        ]);

        Assessment::create([
            'lpk_id' => $lpk->id,
            'created_by' => $admin->id,
            'title' => 'Asesmen Re-Akreditasi (RA) - Lab Masa Tenggang 6 Bulan',
            'assessment_type' => Assessment::TYPE_RE_AKREDITASI,
            'start_at' => '2031-06-10 09:00:00',
            'end_at' => '2031-06-12 17:00:00',
            'status' => 'IN_PROGRESS',
        ]);

        $this->assertTrue($lpk->hasReaccreditationInFlight());
        $this->assertTrue($lpk->isInGracePeriod());
        $this->assertFalse($lpk->isRevocationOverdue());
        $this->assertEquals('2032-01-24', $lpk->grace_period_deadline->toDateString());
        $this->assertEquals('GRACE_PERIOD', $lpk->dynamic_status);
        $this->assertEquals('Masa Tenggang (6 Bln)', $lpk->dynamic_status_label);
        $this->assertStringContainsString('Masa Tenggang Toleransi', $lpk->dynamic_keterangan);
        $this->assertStringContainsString('simbol akreditasi KAN dibekukan', $lpk->dynamic_keterangan);

        Carbon::setTestNow();
    }

    /**
     * Skenario 3a: Langsung dicabut seketika saat siklus berakhir tanpa pelaksanaan asesmen RA.
     * Jika tidak ada asesmen RA yang berjalan, LPK tidak mendapatkan fasilitas masa tenggang 6 bulan.
     */
    public function test_lpk_without_reaccreditation_is_immediately_revoked_at_expiry(): void
    {
        // 1 hari setelah kedaluwarsa (Bulan ke-60 lewat 1 hari) tanpa asesmen RA
        Carbon::setTestNow(Carbon::parse('2031-07-25 10:00:00'));

        $lpk = Lpk::create([
            'registration_number' => 'LP-REVOKED-333',
            'name' => 'Lab Tanpa RA Langsung Dicabut',
            'status' => 'ACTIVE',
            'certificate_date' => '2026-07-24',
            'expired_at' => '2031-07-24',
        ]);

        $this->assertFalse($lpk->hasReaccreditationInFlight());
        $this->assertFalse($lpk->isInGracePeriod());
        $this->assertTrue($lpk->isRevocationOverdue());
        $this->assertEquals('REVOKED', $lpk->dynamic_status);
        $this->assertEquals('Dicabut', $lpk->dynamic_status_label);
        $this->assertStringContainsString('Dicabut', $lpk->dynamic_keterangan);
        $this->assertStringContainsString('tanpa pelaksanaan asesmen akreditasi ulang', $lpk->dynamic_keterangan);

        Carbon::setTestNow();
    }

    /**
     * Skenario 3b: Melewati batas toleransi 6 bulan masa tenggang -> Otomatis Dicabut.
     */
    public function test_lpk_is_automatically_revoked_when_grace_period_expires(): void
    {
        // 7 bulan setelah kedaluwarsa (lewat dari batas 6 bulan masa tenggang)
        Carbon::setTestNow(Carbon::parse('2032-02-25 10:00:00'));

        $admin = User::factory()->admin()->create();

        $lpk = Lpk::create([
            'registration_number' => 'LP-REVOKED-01',
            'name' => 'Lab Lewat Masa Tenggang',
            'status' => 'ACTIVE',
            'certificate_date' => '2026-07-24',
            'expired_at' => '2031-07-24',
        ]);

        Assessment::create([
            'lpk_id' => $lpk->id,
            'created_by' => $admin->id,
            'title' => 'Asesmen Re-Akreditasi (RA) - Lab Lewat Masa Tenggang',
            'assessment_type' => Assessment::TYPE_RE_AKREDITASI,
            'start_at' => '2031-06-10 09:00:00',
            'end_at' => '2031-06-12 17:00:00',
            'status' => 'IN_PROGRESS',
        ]);

        $this->assertTrue($lpk->hasReaccreditationInFlight());
        $this->assertFalse($lpk->isInGracePeriod());
        $this->assertTrue($lpk->isRevocationOverdue());
        $this->assertEquals('REVOKED', $lpk->dynamic_status);
        $this->assertEquals('Dicabut', $lpk->dynamic_status_label);
        $this->assertStringContainsString('Dicabut', $lpk->dynamic_keterangan);
        $this->assertStringContainsString('melewati batas 6 bulan masa tenggang', $lpk->dynamic_keterangan);
        $this->assertStringContainsString('6 bulan', $lpk->dynamic_keterangan);

        Carbon::setTestNow();
    }

    /**
     * Skenario 4: Keterlambatan RA (Selesai pada masa tenggang, misal Sept 2031 / telat 2 bulan)
     * Baseline 5 tahunan baru tetap terkunci di Juli 2031 s.d. Juli 2036.
     * S1 berikutnya tetap di target resmi Oktober 2032.
     * Jarak persiapan lab ke S1 menyempit menjadi 13 bulan.
     */
    public function test_delayed_reaccreditation_preserves_cycle_baseline_and_compresses_s1_window(): void
    {
        $admin = User::factory()->admin()->create();

        // Siklus 1: 2026-07-24 s.d. 2031-07-24
        $lpk = Lpk::create([
            'registration_number' => 'LP-CYCLE-COMPRESS-01',
            'name' => 'Lab Siklus Kompresi S1',
            'status' => 'ACTIVE',
            'certificate_date' => '2026-07-24',
            'expired_at' => '2031-07-24',
        ]);

        $ra = Assessment::create([
            'lpk_id' => $lpk->id,
            'created_by' => $admin->id,
            'title' => 'Asesmen Re-Akreditasi (RA) - Lab Siklus Kompresi S1',
            'assessment_type' => Assessment::TYPE_RE_AKREDITASI,
            'start_at' => '2031-09-10 09:00:00',
            'end_at' => '2031-09-12 17:00:00',
            'status' => 'IN_PROGRESS',
        ]);

        // Simulasikan penyelesaian RA terlambat 2 bulan pada 24 September 2031
        Carbon::setTestNow(Carbon::parse('2031-09-24 12:00:00'));

        $ra->update([
            'status' => 'COMPLETED',
            'sk_number' => 'SK.KAN.2031.TELAT',
            'sk_date' => '2031-09-24',
            'tp_status' => Assessment::TP_STATUS_SATISFIED,
        ]);

        $lpk->refresh();

        // 1. Baseline siklus baru tetap terkunci pada Juli 2031 s.d. Juli 2036 (tidak bergeser ke September)
        $this->assertEquals('2031-07-24', $lpk->certificate_date->toDateString());
        $this->assertEquals('2036-07-24', $lpk->expired_at->toDateString());
        $this->assertEquals('ACTIVE', $lpk->status);

        // 2. Jadwal target S1 siklus berikutnya (Bulan ke-15 dari masa akhir Juli 2036: 2037-10-24)
        $milestones = $lpk->surveillance_milestones;
        $this->assertEquals('2037-10-24', $milestones['s1']['visit_target_date']->toDateString());

        // 3. Jarak persiapan bagi lab menyempit menjadi 13 bulan (Sept 2031 ke Okt 2032)
        $this->assertEquals(2, $lpk->s1_delay_penalty_months);
        $this->assertEquals(13, $lpk->s1_prep_remaining_months);
        $this->assertStringContainsString('menyempit menjadi 13 bulan', $lpk->dynamic_keterangan);

        Carbon::setTestNow();
    }
}
