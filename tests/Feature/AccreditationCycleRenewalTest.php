<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Lpk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccreditationCycleRenewalTest extends TestCase
{
    use RefreshDatabase;

    public function test_reaccreditation_completion_with_sk_automatically_advances_lpk_to_next_cycle(): void
    {
        $admin = User::factory()->admin()->create();

        // Siklus lama: 2021-10-01 s.d. 2026-10-01
        $lpk = Lpk::create([
            'registration_number' => 'LP-CYCLE-001',
            'name' => 'Laboratorium Siklus Akreditasi',
            'status' => 'ACTIVE',
            'certificate_date' => '2021-10-01',
            'expired_at' => '2026-10-01',
        ]);

        // Asesmen Re-Akreditasi periode lama
        $ra = Assessment::create([
            'lpk_id' => $lpk->id,
            'created_by' => $admin->id,
            'title' => 'Asesmen Re-Akreditasi (RA) - Laboratorium Siklus Akreditasi',
            'assessment_type' => Assessment::TYPE_RE_AKREDITASI,
            'start_at' => '2026-04-05 09:00:00',
            'end_at' => '2026-04-08 17:00:00',
            'status' => 'IN_PROGRESS',
        ]);

        // Simulasikan penyelesaian Re-Akreditasi dengan penerbitan SK baru
        $ra->update([
            'status' => 'COMPLETED',
            'sk_number' => 'SK.KAN.2026.999',
            'sk_date' => '2026-10-01',
            'tp_status' => Assessment::TP_STATUS_SATISFIED,
        ]);

        $lpk->refresh();

        // 1. Masa akreditasi otomatis bertambah 5 tahun ke siklus baru (2026 s.d. 2031)
        $this->assertEquals('2026-10-01', $lpk->certificate_date->toDateString());
        $this->assertEquals('2031-10-01', $lpk->expired_at->toDateString());
        $this->assertEquals('ACTIVE', $lpk->status);

        // 2. Status pemantauan milestone siklus kembali ke awal (Akan Datang / UPCOMING)
        $milestones = $lpk->surveillance_milestones;
        $this->assertEquals('UPCOMING', $milestones['s1']['status']);
        $this->assertEquals('UPCOMING', $milestones['s2']['status']);
        $this->assertEquals('UPCOMING', $milestones['ra']['status']);

        // 3. Tanggal target dihitung dari tanggal SK siklus baru
        $this->assertEquals('2027-11-01', $milestones['s1']['notice_date']->toDateString());
        $this->assertEquals('2028-04-01', $milestones['s1']['target_date']->toDateString());
        $this->assertEquals('2029-08-01', $milestones['s2']['notice_date']->toDateString());
        $this->assertEquals('2030-01-01', $milestones['s2']['target_date']->toDateString());
        $this->assertEquals('2030-10-01', $milestones['ra']['notice_date']->toDateString());
        $this->assertEquals('2031-04-01', $milestones['ra']['target_date']->toDateString());

        // 4. Asesmen Re-Akreditasi lama tetap tersimpan sebagai riwayat
        $this->assertDatabaseHas('assessments', [
            'id' => $ra->id,
            'title' => 'Asesmen Re-Akreditasi (RA) - Laboratorium Siklus Akreditasi',
            'sk_number' => 'SK.KAN.2026.999',
            'status' => 'COMPLETED',
        ]);

        // 5. Tampilan detail LPK menampilkan siklus periode baru dan status Akan Datang
        $response = $this->actingAs($admin)->get(route('lpks.show', $lpk));
        $response->assertOk();
        $response->assertSee('Periode 2026 - 2031');
        $response->assertSee('Akan Datang');
    }
}
