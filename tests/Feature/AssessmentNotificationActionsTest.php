<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Lpk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentNotificationActionsTest extends TestCase
{
    use RefreshDatabase;

    public function test_suspended_assessment_renders_resolution_action_buttons(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $lpk = Lpk::create([
            'registration_number' => 'LP-SUSP-01',
            'name' => 'Laboratorium Pengujian Uji Suspensi',
            'status' => 'ACTIVE',
            'certificate_date' => now()->subYears(2),
            'expired_at' => now()->addYears(3),
        ]);

        $assessment = Assessment::create([
            'lpk_id' => $lpk->id,
            'title' => 'Surveilen 1 Siklus KAN',
            'assessment_type' => 'SURVEILLANCE 1',
            'start_at' => now()->subMonths(2),
            'end_at' => now()->subMonths(2)->addDays(2),
            'tp_due_date' => now()->subDays(10),
            'status' => 'SUSPENDED',
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('assessments.show', $assessment));

        $response->assertOk();
        $response->assertSee('Perhatian: Status Asesmen / Akreditasi Dibekukan');
        $response->assertSee('lpk-alert-callout-actions');
        $response->assertSee('Input SK KAN');
        $response->assertSee(route('assessments.edit', $assessment) . '#form-sk-block');
        $response->assertSee('Perbarui Asesmen');
        $response->assertSee(route('assessments.edit', $assessment));
    }

    public function test_submission_overdue_assessment_renders_tuntaskan_button(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $lpk = Lpk::create([
            'registration_number' => 'LP-OVERDUE-01',
            'name' => 'Laboratorium Pengujian Overdue',
            'status' => 'ACTIVE',
            'certificate_date' => now()->subMonths(25),
            'expired_at' => now()->addMonths(35),
        ]);

        // S1 submission due is cycle start + 18 months, which was 7 months ago
        $assessment = Assessment::create([
            'lpk_id' => $lpk->id,
            'title' => 'Surveilen 1 Siklus KAN',
            'assessment_type' => 'SURVEILLANCE 1',
            'start_at' => now()->subMonths(5),
            'end_at' => now()->subMonths(5)->addDays(2),
            'status' => 'SCHEDULED',
            'created_by' => $admin->id,
        ]);

        $this->assertTrue($assessment->is_submission_overdue);

        $response = $this->actingAs($admin)->get(route('assessments.show', $assessment));

        $response->assertOk();
        $response->assertSee('Perhatian: Status Asesmen / Akreditasi Dibekukan');
        $response->assertSee('lpk-alert-callout-actions');
        $response->assertSee('Tuntaskan Asesmen');
        $response->assertSee('Input SK KAN');
        $response->assertSee('Perbarui Asesmen');
    }

    public function test_revoked_assessment_renders_initial_application_and_update_buttons(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $lpk = Lpk::create([
            'registration_number' => 'LP-REVOKED-02',
            'name' => 'Laboratorium Pengujian Revoked',
            'status' => 'REVOKED',
            'certificate_date' => now()->subYears(6),
            'expired_at' => now()->subYear(),
        ]);

        $assessment = Assessment::create([
            'lpk_id' => $lpk->id,
            'title' => 'Surveilen 1 Siklus KAN',
            'assessment_type' => 'SURVEILLANCE 1',
            'start_at' => now()->subYears(2),
            'end_at' => now()->subYears(2)->addDays(2),
            'status' => 'REVOKED',
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('assessments.show', $assessment));

        $response->assertOk();
        $response->assertSee('Perhatian: Status Akreditasi Dicabut');
        $response->assertSee('lpk-alert-callout-actions');
        $response->assertSee('Ajukan Akreditasi Awal');
        $response->assertSee('Perbarui Data Asesmen');
        $response->assertSee(route('assessments.create', [
            'lpk_id' => $lpk->id,
            'assessment_type' => 'INITIAL',
            'title' => 'Akreditasi Awal - ' . $lpk->name,
        ]));
    }
}
