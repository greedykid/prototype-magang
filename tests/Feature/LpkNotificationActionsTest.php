<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Lpk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LpkNotificationActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_persistent_surveillance_alert_renders_schedule_action_button(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Create LPK whose S1 milestone is overdue
        $lpk = Lpk::create([
            'registration_number' => 'LP-ALERT-01',
            'name' => 'Lab Surveillance Alert Test',
            'status' => 'ACTIVE',
            'certificate_date' => now()->subMonths(20),
            'expired_at' => now()->addMonths(40),
        ]);

        $response = $this->actingAs($admin)->get(route('lpks.show', $lpk));

        $response->assertOk();
        $response->assertSee('Peringatan Persisten: LPK Memasuki Masa Jatuh Tempo Pengawasan KAN');
        $response->assertSee('lpk-alert-callout-actions');
        $response->assertSee('Buka Surveilen 1');
        $response->assertSee(route('assessments.show', 1));
        $response->assertSee('Kirim Notifikasi Email PIC Lab');
    }

    public function test_grace_period_alert_renders_sk_input_and_update_buttons(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Create LPK in grace period (expired within 6 months, has completed reaccreditation)
        $lpk = Lpk::create([
            'registration_number' => 'LP-GRACE-01',
            'name' => 'Lab Grace Period Test',
            'status' => 'ACTIVE',
            'certificate_date' => now()->subYears(5)->subMonths(2),
            'expired_at' => now()->subMonth(),
        ]);

        // Add completed reaccreditation assessment
        $raAssessment = Assessment::create([
            'lpk_id' => $lpk->id,
            'title' => 'Reakreditasi Siklus KAN',
            'assessment_type' => 'REASSESSMENT',
            'start_at' => now()->subMonths(2),
            'end_at' => now()->subMonths(2)->addDays(2),
            'status' => 'COMPLETED',
            'tp_status' => 'SATISFIED',
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('lpks.show', $lpk));

        $response->assertOk();
        if ($lpk->isInGracePeriod()) {
            $response->assertSee('Masa Tenggang Akreditasi Ulang');
            $response->assertSee('Input SK Akreditasi KAN');
            $response->assertSee('Perbarui Sertifikat');
            $response->assertSee('/edit#form-sk-block');
            $response->assertSee(route('lpks.edit', $lpk));
        }
    }

    public function test_revocation_alert_renders_initial_application_button(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Create LPK with overdue revocation (expired and no reaccreditation)
        $lpk = Lpk::create([
            'registration_number' => 'LP-REVOKED-01',
            'name' => 'Lab Revoked Test',
            'status' => 'REVOKED',
            'certificate_date' => now()->subYears(6),
            'expired_at' => now()->subYear(),
        ]);

        $response = $this->actingAs($admin)->get(route('lpks.show', $lpk));

        $response->assertOk();
        if ($lpk->isRevocationOverdue()) {
            $response->assertSee('Status Akreditasi Dicabut');
            $response->assertSee('Ajukan Akreditasi Awal');
            $response->assertSee('Perbarui Data LPK');
            $response->assertSee(route('assessments.create', [
                'lpk_id' => $lpk->id,
                'assessment_type' => 'INITIAL',
                'title' => 'Akreditasi Awal - ' . $lpk->name,
            ]));
            $response->assertSee(route('lpks.edit', $lpk));
        }
    }
}
