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
        $response->assertSee('Peringatan Kritis: LPK Lewat Jatuh Tempo Pengawasan KAN (Terancam Dibekukan)');
        $response->assertSee('lpk-alert-callout-actions');
        $response->assertSee('Jadwalkan Surveilen 1');
        $response->assertSee(route('assessments.create', ['lpk_id' => $lpk->id, 'alert_code' => 'S1', 'target_date' => $lpk->certificate_date->copy()->addMonths(15)->format('Y-m-d')]));
        $response->assertSee('Kirim Notifikasi Email PIC Lab');
    }

    public function test_due_surveillance_alert_renders_persistent_due_banner(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Create LPK whose S1 milestone is due (month 14)
        $lpk = Lpk::create([
            'registration_number' => 'LP-DUE-01',
            'name' => 'Lab Surveillance Due Test',
            'status' => 'ACTIVE',
            'certificate_date' => now()->subMonths(14),
            'expired_at' => now()->addMonths(46),
        ]);

        $response = $this->actingAs($admin)->get(route('lpks.show', $lpk));

        $response->assertOk();
        $response->assertSee('Peringatan Persisten: LPK Memasuki Masa Jatuh Tempo Pengawasan KAN');
        $response->assertSee('lpk-alert-callout-actions');
        $response->assertSee('Jadwalkan Surveilen 1');
    }

    public function test_ra_milestone_card_is_yellow_reminder_even_with_planned_assessment(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Create LPK in RA window (month 50: certificate 50 months ago, expires in 10 months)
        $lpk = Lpk::create([
            'registration_number' => 'LP-RA-DUE-01',
            'name' => 'Lab Re-Akreditasi Due Test',
            'status' => 'ACTIVE',
            'certificate_date' => now()->subMonths(50),
            'expired_at' => now()->addMonths(10),
        ]);

        // Add a planned RA assessment
        Assessment::create([
            'lpk_id' => $lpk->id,
            'created_by' => $admin->id,
            'title' => 'Re-asesmen Siklus KAN',
            'assessment_type' => 'Re-Akreditasi',
            'start_at' => now()->addMonths(4),
            'end_at' => now()->addMonths(4)->addDays(2),
            'status' => 'PLANNED',
            'tp_status' => 'NONE',
        ]);

        $response = $this->actingAs($admin)->get(route('lpks.show', $lpk));

        $response->assertOk();
        $response->assertSee('Peringatan Persisten: LPK Memasuki Masa Jatuh Tempo Pengawasan KAN');
        $response->assertSee('is-reminder is-due');
        $response->assertSee('Reminder RA');
        $response->assertSee('Rencana (Bulan 54)');
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
        $response->assertSee('lpk-alert-callout is-revoked');
        $response->assertSee('Status Akreditasi Dicabut');
        $response->assertSee('Ajukan Akreditasi Awal');
        $response->assertSee('Perbarui Data LPK');
        $response->assertDontSee('Peringatan Persisten: LPK Memasuki Masa Jatuh Tempo Pengawasan KAN');
        $response->assertDontSee('Masa pengawasan aktif untuk Re-Akreditasi');
        $response->assertDontSee('Jadwalkan Re-Akreditasi');
        $response->assertSee(route('assessments.create', [
            'lpk_id' => $lpk->id,
            'assessment_type' => 'INITIAL',
            'title' => 'Akreditasi Awal - ' . $lpk->name,
        ]));
        $response->assertSee(route('lpks.edit', $lpk));
    }

    public function test_cannot_send_surveillance_reminder_for_revoked_lpk(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);
        $lpk = Lpk::create([
            'registration_number' => 'LP-REVOKED-02',
            'name' => 'Lab Revoked Test 2',
            'status' => 'REVOKED',
            'certificate_date' => now()->subYears(6),
            'expired_at' => now()->subYear(),
        ]);

        $response = $this->actingAs($admin)->post(route('lpks.surveillance.remind', $lpk));
        $response->assertSessionHas('error', 'Status akreditasi LPK telah dicabut. Notifikasi pengawasan berkala tidak dapat dikirim.');
    }

    public function test_cron_command_skips_revoked_lpk_and_does_not_send_email(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin.cron@simasadi.local']);
        $pic = User::factory()->create(['role' => 'pic', 'email' => 'pic.cron@simasadi.local']);

        $revokedLpk = Lpk::create([
            'registration_number' => 'LP-REVOKED-CRON',
            'name' => 'Lab Revoked Cron Test',
            'status' => 'REVOKED',
            'pic_id' => $pic->id,
            'certificate_date' => now()->subYears(6),
            'expired_at' => now()->subMonths(14),
        ]);

        $this->artisan('lpk:check-surveillance', ['--force' => true])
            ->assertExitCode(0);

        \Illuminate\Support\Facades\Mail::assertNothingQueued();
        \Illuminate\Support\Facades\Mail::assertNothingSent();
    }

    public function test_unassigned_lpk_reminder_falls_back_to_admin_only(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $admin = User::factory()->create(['role' => 'admin', 'email' => 'admin.fallback@simasadi.local']);
        $otherPic = User::factory()->create(['role' => 'pic', 'email' => 'other.pic@simasadi.local']);

        $unassignedLpk = Lpk::create([
            'registration_number' => 'LP-UNASSIGNED-01',
            'name' => 'Lab Tanpa PIC',
            'status' => 'ACTIVE',
            'pic_id' => null,
            'certificate_date' => now()->subMonths(14),
            'expired_at' => now()->subMonths(14)->addYears(5),
            'email' => 'lab.unassigned@example.com',
        ]);

        $this->actingAs($admin)->post(route('lpks.surveillance.remind', $unassignedLpk));

        \Illuminate\Support\Facades\Mail::assertQueued(\App\Mail\SurveillanceReminderMail::class, function ($mail) use ($admin, $otherPic) {
            return $mail->hasTo($admin->email) && ! $mail->hasTo($otherPic->email);
        });
    }
}
