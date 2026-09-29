<?php

namespace Tests\Feature;

use App\Models\Accreditation;
use App\Models\Assessment;
use App\Models\Lpk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimasadiFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_accreditation_release_readiness_gate_logic(): void
    {
        $user = User::factory()->create();
        $lpk = Lpk::factory()->create();
        $lpk->assessments()->delete();

        $accreditation = Accreditation::factory()->create([
            'lpk_id' => $lpk->id,
            'status' => 'IN_PROGRESS',
        ]);

        // 1. Initially without assessments, not ready
        $this->assertFalse($accreditation->isReleaseReady());

        // 2. With an incomplete assessment, still not ready
        $assessment = Assessment::factory()->create([
            'lpk_id' => $lpk->id,
            'created_by' => $user->id,
            'status' => 'MENUNGGU_PELAKSANAAN',
            'tp_status' => 'NONE',
        ]);

        $this->assertFalse($accreditation->fresh(['lpk.assessments'])->isReleaseReady());

        // 3. Incomplete with overdue TP finding
        $assessment->update([
            'status' => 'IN_PROGRESS',
            'tp_status' => Assessment::TP_STATUS_IN_PROGRESS,
            'tp_due_date' => now()->subDays(5),
        ]);

        $this->assertFalse($accreditation->fresh(['lpk.assessments'])->isReleaseReady());

        // 4. All assessments completed and no overdue TP findings
        $assessment->update([
            'tp_status' => Assessment::TP_STATUS_SATISFIED,
            'sk_number' => 'SK.KAN.001/2026',
        ]);

        $this->assertTrue($accreditation->fresh(['lpk.assessments'])->isReleaseReady());

        // 5. Directly completed or released
        $accreditation->update(['status' => 'COMPLETED']);
        $this->assertTrue($accreditation->fresh()->isReleaseReady());
    }

    public function test_can_prefill_assessment_schedule_from_surveillance_alert(): void
    {
        $user = User::factory()->create();
        $lpk = Lpk::factory()->create([
            'name' => 'Laboratorium Uji Mutu Unggul',
            'registration_number' => 'LP-999-IDN',
            'address' => 'Jl. Sangkuriang No. 1, Kota Bandung',
        ]);

        // 1. Test prefill with S1 alert
        $response = $this->actingAs($user)->get(route('assessments.create', [
            'lpk_id' => $lpk->id,
            'alert_code' => 'S1',
            'target_date' => '2026-10-15',
        ]));

        $response->assertOk();
        $response->assertSee('Jadwal Kunjungan Otomatis Disiapkan');
        $response->assertSee('Laboratorium Uji Mutu Unggul');
        $response->assertSee('Surveilen 1 Siklus KAN - Laboratorium Uji Mutu Unggul');
        $response->assertSee('Kota Bandung');
        $response->assertSee('2026-10-15T09:00');

        // 2. Test prefill with RA (Re-Akreditasi) alert
        $responseRA = $this->actingAs($user)->get(route('assessments.create', [
            'lpk_id' => $lpk->id,
            'alert_code' => 'RA',
            'target_date' => '2026-12-01',
        ]));

        $responseRA->assertOk();
        $responseRA->assertSee('Re-asesmen Siklus KAN - Laboratorium Uji Mutu Unggul');
    }
}
