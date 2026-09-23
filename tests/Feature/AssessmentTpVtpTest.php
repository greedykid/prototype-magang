<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Lpk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentTpVtpTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private User $staff;
    private Lpk $lpk;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'admin',
            'email' => 'admin.test@example.com',
        ]);

        $this->staff = User::factory()->create([
            'role' => 'staff',
            'email' => 'staff.test@example.com',
        ]);

        $this->lpk = Lpk::factory()->create([
            'name' => 'Laboratorium Uji Presisi',
            'status' => 'ACTIVE',
        ]);
    }

    public function test_initial_assessment_calculates_3_months_sla_for_tp(): void
    {
        $endAt = now()->subDays(10);

        $assessment = Assessment::create([
            'lpk_id' => $this->lpk->id,
            'created_by' => $this->admin->id,
            'title' => 'Asesmen Awal Akreditasi Lab Lingkungan',
            'assessment_type' => 'Asesmen Awal',
            'start_at' => $endAt->copy()->subDays(2),
            'end_at' => $endAt,
            'status' => 'COMPLETED',
            'tp_status' => Assessment::TP_STATUS_IN_PROGRESS,
        ]);

        $expectedDueDate = $endAt->copy()->addMonths(3)->startOfDay();

        $this->assertNotNull($assessment->tp_due_date);
        $this->assertEquals($expectedDueDate->toDateString(), $assessment->tp_due_date->toDateString());
        $this->assertEquals($expectedDueDate->toDateString(), $assessment->effective_tp_due_date->toDateString());
    }

    public function test_surveillance_and_reassessment_calculate_2_months_sla_for_tp(): void
    {
        $endAt = now()->subDays(5);

        $surveillance = Assessment::create([
            'lpk_id' => $this->lpk->id,
            'created_by' => $this->admin->id,
            'title' => 'Surveilen 1 Siklus KAN',
            'assessment_type' => 'Surveilen',
            'start_at' => $endAt->copy()->subDay(),
            'end_at' => $endAt,
            'status' => 'COMPLETED',
            'tp_status' => Assessment::TP_STATUS_IN_PROGRESS,
        ]);

        $expectedSurvDueDate = $endAt->copy()->addMonths(2)->startOfDay();
        $this->assertEquals($expectedSurvDueDate->toDateString(), $surveillance->tp_due_date->toDateString());

        $reassessment = Assessment::create([
            'lpk_id' => $this->lpk->id,
            'created_by' => $this->admin->id,
            'title' => 'Re-asesmen Siklus 5 Tahun',
            'assessment_type' => 'Re-asesmen',
            'start_at' => $endAt->copy()->subDay(),
            'end_at' => $endAt,
            'status' => 'COMPLETED',
            'tp_status' => Assessment::TP_STATUS_IN_PROGRESS,
        ]);

        $expectedReDueDate = $endAt->copy()->addMonths(2)->startOfDay();
        $this->assertEquals($expectedReDueDate->toDateString(), $reassessment->tp_due_date->toDateString());
    }

    public function test_tp_extension_with_official_letter_adds_1_month_to_effective_due_date(): void
    {
        $endAt = now()->subMonths(1);

        $assessment = Assessment::create([
            'lpk_id' => $this->lpk->id,
            'created_by' => $this->admin->id,
            'title' => 'Asesmen Awal Lab Pertanian',
            'assessment_type' => 'Asesmen Awal',
            'start_at' => $endAt->copy()->subDays(2),
            'end_at' => $endAt,
            'status' => 'COMPLETED',
            'tp_status' => Assessment::TP_STATUS_IN_PROGRESS,
            'tp_has_extension' => true,
            'tp_extension_months' => 1,
            'tp_extension_letter_no' => '082/LAB-PER/EXT/IX/2026',
            'tp_extension_date' => now()->toDateString(),
            'tp_extension_notes' => 'Pengadaan certified reference material (CRM) dari luar negeri memerlukan waktu tambahan.',
        ]);

        $baseDueDate = $endAt->copy()->addMonths(3)->startOfDay();
        $expectedEffectiveDate = $baseDueDate->copy()->addMonth()->startOfDay();

        $this->assertEquals($baseDueDate->toDateString(), $assessment->tp_due_date->toDateString());
        $this->assertEquals($expectedEffectiveDate->toDateString(), $assessment->effective_tp_due_date->toDateString());
        $this->assertStringContainsString('+1 Bulan', $assessment->tp_sla_badge['label']);
    }

    public function test_overdue_tp_detection_and_resolution(): void
    {
        // 4 months ago for Surveillance (2 months limit -> 2 months overdue)
        $pastEnd = now()->subMonths(4);

        $assessment = Assessment::create([
            'lpk_id' => $this->lpk->id,
            'created_by' => $this->admin->id,
            'title' => 'Surveilen Melewati Batas Waktu',
            'assessment_type' => 'Surveilen',
            'start_at' => $pastEnd->copy()->subDay(),
            'end_at' => $pastEnd,
            'status' => 'COMPLETED',
            'tp_status' => Assessment::TP_STATUS_IN_PROGRESS,
        ]);

        $this->assertTrue($assessment->is_tp_overdue);
        $this->assertLessThan(0, $assessment->days_remaining_tp);
        $this->assertEquals('danger', $assessment->tp_sla_badge['type']);
        $this->assertStringContainsString('Terlambat', $assessment->tp_sla_badge['label']);

        // Selesaikan tindakan perbaikan
        $assessment->update([
            'tp_status' => Assessment::TP_STATUS_SATISFIED,
            'tp_satisfied_at' => now()->toDateString(),
        ]);

        $this->assertFalse($assessment->fresh()->is_tp_overdue);
        $this->assertEquals('success', $assessment->fresh()->tp_sla_badge['type']);
        $this->assertEquals('Memenuhi', $assessment->fresh()->tp_sla_badge['label']);
    }

    public function test_admin_can_update_tp_tracking_via_post_endpoint(): void
    {
        $assessment = Assessment::create([
            'lpk_id' => $this->lpk->id,
            'created_by' => $this->admin->id,
            'title' => 'Asesmen Uji Lapangan',
            'assessment_type' => 'Surveilen',
            'start_at' => now()->subDays(10),
            'end_at' => now()->subDays(8),
            'status' => 'COMPLETED',
            'tp_status' => Assessment::TP_STATUS_NONE,
        ]);

        $response = $this->actingAs($this->admin)->post(route('assessments.tp.update', $assessment), [
            'tp_status' => Assessment::TP_STATUS_IN_PROGRESS,
            'tp_due_date' => now()->addDays(45)->toDateString(),
            'tp_has_extension' => '1',
            'tp_extension_letter_no' => '404/EXT-KAN/2026',
            'tp_extension_date' => now()->toDateString(),
            'tp_extension_notes' => 'Permohonan dispensasi 1 bulan resmi disetujui.',
            'tp_notes' => 'Ketidaksesuaian pada klausul 6.4 kalibrasi alat.',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $assessment->refresh();
        $this->assertEquals(Assessment::TP_STATUS_IN_PROGRESS, $assessment->tp_status);
        $this->assertTrue($assessment->tp_has_extension);
        $this->assertEquals('404/EXT-KAN/2026', $assessment->tp_extension_letter_no);
        $this->assertEquals('Permohonan dispensasi 1 bulan resmi disetujui.', $assessment->tp_extension_notes);
    }

    public function test_non_admin_cannot_update_tp_tracking(): void
    {
        $assessment = Assessment::create([
            'lpk_id' => $this->lpk->id,
            'created_by' => $this->admin->id,
            'title' => 'Asesmen Non Admin Test',
            'assessment_type' => 'Surveilen',
            'start_at' => now()->subDays(5),
            'end_at' => now()->subDays(4),
            'status' => 'COMPLETED',
            'tp_status' => Assessment::TP_STATUS_NONE,
        ]);

        $response = $this->actingAs($this->staff)->post(route('assessments.tp.update', $assessment), [
            'tp_status' => Assessment::TP_STATUS_IN_PROGRESS,
        ]);

        $response->assertForbidden();
    }

    public function test_assessments_index_filters_by_tp_status(): void
    {
        $overdueAssessment = Assessment::create([
            'lpk_id' => $this->lpk->id,
            'created_by' => $this->admin->id,
            'title' => 'Agenda TP Overdue Khusus',
            'assessment_type' => 'Surveilen',
            'start_at' => now()->subMonths(5),
            'end_at' => now()->subMonths(4),
            'status' => 'COMPLETED',
            'tp_status' => Assessment::TP_STATUS_IN_PROGRESS,
        ]);

        $satisfiedAssessment = Assessment::create([
            'lpk_id' => $this->lpk->id,
            'created_by' => $this->admin->id,
            'title' => 'Agenda TP Selesai Memenuhi',
            'assessment_type' => 'Asesmen Awal',
            'start_at' => now()->subMonths(2),
            'end_at' => now()->subMonths(1),
            'status' => 'COMPLETED',
            'tp_status' => Assessment::TP_STATUS_SATISFIED,
            'tp_satisfied_at' => now()->subDays(5)->toDateString(),
        ]);

        // Filter OVERDUE
        $responseOverdue = $this->actingAs($this->admin)->get(route('assessments.index', ['tp_status' => 'OVERDUE']));
        $responseOverdue->assertOk();
        $responseOverdue->assertSee('Agenda TP Overdue Khusus');
        $responseOverdue->assertDontSee('Agenda TP Selesai Memenuhi');

        // Filter SATISFIED
        $responseSatisfied = $this->actingAs($this->admin)->get(route('assessments.index', ['tp_status' => 'SATISFIED']));
        $responseSatisfied->assertOk();
        $responseSatisfied->assertSee('Agenda TP Selesai Memenuhi');
        $responseSatisfied->assertDontSee('Agenda TP Overdue Khusus');
    }

    public function test_dashboard_surfaces_urgent_tp_alerts(): void
    {
        Assessment::create([
            'lpk_id' => $this->lpk->id,
            'created_by' => $this->admin->id,
            'title' => 'Asesmen Urgent TP Dashboard',
            'assessment_type' => 'Surveilen',
            'start_at' => now()->subMonths(4),
            'end_at' => now()->subMonths(3),
            'status' => 'COMPLETED',
            'tp_status' => Assessment::TP_STATUS_IN_PROGRESS,
        ]);

        $response = $this->actingAs($this->admin)->get(route('dashboard'));
        $response->assertOk();
        $response->assertSee('Peringatan Batas Waktu Tindakan Perbaikan (TP &amp; VTP) KAN', false);
        $response->assertSee('Asesmen Urgent TP Dashboard');
        $response->assertSee('TP melewati batas waktu KAN');
    }

    public function test_tp_deadlines_are_synchronized_with_calendar(): void
    {
        $endAt = now()->addDays(5)->subMonths(2); // AA +3 bulan = 1 bulan ke depan (dalam jangkauan kalender)

        Assessment::create([
            'lpk_id' => $this->lpk->id,
            'created_by' => $this->admin->id,
            'title' => 'Asesmen Kalender Uji Mutu',
            'assessment_type' => 'Asesmen Awal',
            'start_at' => $endAt->copy()->subDays(2),
            'end_at' => $endAt,
            'status' => 'COMPLETED',
            'tp_status' => Assessment::TP_STATUS_IN_PROGRESS,
        ]);

        $response = $this->actingAs($this->admin)->get(route('calendar.index', ['view' => 'agenda']));
        $response->assertOk();
        $response->assertSee('[Batas TP]');
        $response->assertSee('Laboratorium Uji Presisi');
        $response->assertSee('TINDAKAN_PERBAIKAN');
    }
}
