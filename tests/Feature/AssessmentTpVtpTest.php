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
            'pic_id' => $this->staff->id,
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
        $this->assertEquals('suspended', $assessment->tp_sla_badge['type']);
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

    public function test_pic_can_update_tp_tracking(): void
    {
        $assessment = Assessment::create([
            'lpk_id' => $this->lpk->id,
            'created_by' => $this->admin->id,
            'title' => 'Asesmen PIC Update Test',
            'assessment_type' => 'Surveilen',
            'start_at' => now()->subDays(5),
            'end_at' => now()->subDays(4),
            'status' => 'COMPLETED',
            'tp_status' => Assessment::TP_STATUS_NONE,
        ]);

        $response = $this->actingAs($this->staff)->post(route('assessments.tp.update', $assessment), [
            'tp_status' => Assessment::TP_STATUS_IN_PROGRESS,
            'tp_notes' => 'Catatan perbaikan diisi oleh tim internal PIC.',
        ]);

        $response->assertRedirect();
        $this->assertEquals(Assessment::TP_STATUS_IN_PROGRESS, $assessment->fresh()->tp_status);
    }

    public function test_guest_cannot_update_tp_tracking(): void
    {
        $assessment = Assessment::create([
            'lpk_id' => $this->lpk->id,
            'created_by' => $this->admin->id,
            'title' => 'Asesmen Guest Test',
            'assessment_type' => 'Surveilen',
            'start_at' => now()->subDays(5),
            'end_at' => now()->subDays(4),
            'status' => 'COMPLETED',
            'tp_status' => Assessment::TP_STATUS_NONE,
        ]);

        $response = $this->post(route('assessments.tp.update', $assessment), [
            'tp_status' => Assessment::TP_STATUS_IN_PROGRESS,
        ]);

        $response->assertRedirect(route('login'));
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
        $response->assertSee('Batas TP');
        $response->assertSee('Laboratorium Uji Presisi');
        $response->assertSee('BATAS_TP');
    }

    public function test_tp_extension_is_disallowed_when_tp_status_is_none(): void
    {
        $assessment = Assessment::create([
            'lpk_id' => $this->lpk->id,
            'created_by' => $this->admin->id,
            'title' => 'Asesmen Tanpa TP Aktif',
            'assessment_type' => 'Surveilen',
            'start_at' => now()->subDays(10),
            'end_at' => now()->subDays(8),
            'status' => 'COMPLETED',
            'tp_status' => Assessment::TP_STATUS_NONE,
        ]);

        $response = $this->actingAs($this->admin)->post(route('assessments.tp.update', $assessment), [
            'tp_status' => Assessment::TP_STATUS_NONE,
            'tp_has_extension' => '1',
            'tp_extension_letter_no' => 'EXT-INVALID/2026',
        ]);

        $response->assertSessionHasErrors(['tp_has_extension']);
        $this->assertFalse($assessment->fresh()->tp_has_extension);
    }

    public function test_tp_extension_is_allowed_when_tp_status_is_in_progress_or_under_verification(): void
    {
        $assessment = Assessment::create([
            'lpk_id' => $this->lpk->id,
            'created_by' => $this->admin->id,
            'title' => 'Asesmen Dengan Usaha TP',
            'assessment_type' => 'Surveilen',
            'start_at' => now()->subDays(10),
            'end_at' => now()->subDays(8),
            'status' => 'COMPLETED',
            'tp_status' => Assessment::TP_STATUS_UNDER_VERIFICATION,
        ]);

        $response = $this->actingAs($this->admin)->post(route('assessments.tp.update', $assessment), [
            'tp_status' => Assessment::TP_STATUS_UNDER_VERIFICATION,
            'tp_has_extension' => '1',
            'tp_extension_letter_no' => 'EXT-VALID-01/2026',
            'tp_extension_date' => now()->toDateString(),
            'tp_extension_notes' => 'Ada perbaikan namun belum tuntas seluruh klausul.',
        ]);

        $response->assertSessionHasNoErrors();
        $this->assertTrue($assessment->fresh()->tp_has_extension);
        $this->assertEquals('EXT-VALID-01/2026', $assessment->fresh()->tp_extension_letter_no);
    }

    public function test_assessment_report_date_and_eha_fields_can_be_saved_and_viewed(): void
    {
        $assessment = Assessment::create([
            'lpk_id' => $this->lpk->id,
            'created_by' => $this->admin->id,
            'title' => 'Asesmen Siklus dengan EHA',
            'assessment_type' => 'Surveilen',
            'start_at' => now()->subDays(15),
            'end_at' => now()->subDays(12),
            'status' => 'COMPLETED',
            'assessment_team' => 'Dr. Budi Santoso (Ketua), Ir. Siti Aminah (Anggota)',
            'report_date' => now()->subDays(5)->toDateString(),
            'eha_date' => now()->subDays(2)->toDateString(),
            'eha_status' => Assessment::EHA_STATUS_DIREKOMENDASIKAN,
            'eha_notes' => 'Seluruh temuan minor telah diverifikasi dan disetujui komite.',
        ]);

        $this->assertEquals('Dr. Budi Santoso (Ketua), Ir. Siti Aminah (Anggota)', $assessment->assessment_team);
        $this->assertEquals(Assessment::EHA_STATUS_DIREKOMENDASIKAN, $assessment->eha_status);
        $this->assertEquals('Direkomendasikan (Memenuhi)', $assessment->eha_status_label);

        $response = $this->actingAs($this->admin)->get(route('assessments.show', $assessment));
        $response->assertOk();
        $response->assertSee('Dr. Budi Santoso (Ketua), Ir. Siti Aminah (Anggota)');
        $response->assertSee('Rapat Panitia Teknis');
        $response->assertSee('Laporan Asesmen');
        $response->assertSee('Direkomendasikan (Memenuhi)');
        $response->assertSee('Seluruh temuan minor telah diverifikasi dan disetujui komite.');
    }

    public function test_admin_can_update_assessment_using_unified_status(): void
    {
        $assessment = Assessment::create([
            'lpk_id' => $this->lpk->id,
            'created_by' => $this->admin->id,
            'title' => 'Asesmen Uji Status Terpadu',
            'assessment_type' => 'Surveilen',
            'start_at' => now()->subDays(5),
            'end_at' => now()->subDays(3),
            'status' => 'IN_PROGRESS',
            'tp_status' => Assessment::TP_STATUS_IN_PROGRESS,
        ]);

        // 1. Modal renders the unified select and no longer has dual selects
        $viewResp = $this->actingAs($this->admin)->get(route('assessments.show', $assessment));
        $viewResp->assertOk();
        $viewResp->assertSee('name="unified_status"', false);
        $viewResp->assertSee('id="modal_unified_status_select"', false);

        // 2. Submit unified status: UNDER_VERIFICATION
        $respUnderVerif = $this->actingAs($this->admin)->post(route('assessments.tp.update', $assessment), [
            'unified_status' => 'UNDER_VERIFICATION',
        ]);
        $respUnderVerif->assertRedirect();
        $assessment->refresh();
        $this->assertEquals('IN_PROGRESS', $assessment->status);
        $this->assertEquals(Assessment::TP_STATUS_UNDER_VERIFICATION, $assessment->tp_status);

        // 3. Submit unified status: COMPLETED
        $respCompleted = $this->actingAs($this->admin)->post(route('assessments.tp.update', $assessment), [
            'unified_status' => 'COMPLETED',
        ]);
        $respCompleted->assertRedirect();
        $assessment->refresh();
        $this->assertEquals('COMPLETED', $assessment->status);
        $this->assertEquals(Assessment::TP_STATUS_SATISFIED, $assessment->tp_status);
        $this->assertNotNull($assessment->tp_satisfied_at);
    }

    public function test_scopes_respect_tp_has_extension(): void
    {
        // 1. tp_due_date is 15 days ago, but has +1 month extension -> Not overdue yet!
        $extendedAssessment = Assessment::create([
            'lpk_id' => $this->lpk->id,
            'created_by' => $this->admin->id,
            'title' => 'Asesmen TP dengan Perpanjangan',
            'assessment_type' => 'Surveilen',
            'start_at' => now()->subMonths(3),
            'end_at' => now()->subMonths(3)->addDays(2),
            'status' => 'IN_PROGRESS',
            'tp_status' => Assessment::TP_STATUS_IN_PROGRESS,
            'tp_due_date' => now()->subDays(15),
            'tp_has_extension' => true,
            'tp_extension_months' => 1,
            'tp_extension_letter_no' => 'EXT-123',
        ]);

        $this->assertFalse(Assessment::tpOverdue()->where('id', $extendedAssessment->id)->exists());

        // 2. tp_due_date is 45 days ago, with +1 month (30 days) extension -> Overdue!
        $extendedOverdue = Assessment::create([
            'lpk_id' => $this->lpk->id,
            'created_by' => $this->admin->id,
            'title' => 'Asesmen TP Perpanjangan Terlewat',
            'assessment_type' => 'Surveilen',
            'start_at' => now()->subMonths(4),
            'end_at' => now()->subMonths(4)->addDays(2),
            'status' => 'IN_PROGRESS',
            'tp_status' => Assessment::TP_STATUS_IN_PROGRESS,
            'tp_due_date' => now()->subDays(45),
            'tp_has_extension' => true,
            'tp_extension_months' => 1,
            'tp_extension_letter_no' => 'EXT-456',
        ]);

        $this->assertTrue(Assessment::tpOverdue()->where('id', $extendedOverdue->id)->exists());
    }

    public function test_filling_tp_satisfied_at_automatically_updates_tp_status_to_satisfied(): void
    {
        $assessment = Assessment::create([
            'lpk_id' => $this->lpk->id,
            'created_by' => $this->admin->id,
            'title' => 'Asesmen TP Verifikasi Sukses',
            'assessment_type' => 'Surveilen',
            'start_at' => now()->subMonths(2),
            'end_at' => now()->subMonths(2)->addDays(2),
            'status' => 'IN_PROGRESS',
            'tp_status' => Assessment::TP_STATUS_IN_PROGRESS,
            'tp_due_date' => now()->addDays(10),
        ]);

        $response = $this->actingAs($this->admin)->post(route('assessments.tp.update', $assessment), [
            'tp_satisfied_at' => now()->toDateString(),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $fresh = $assessment->fresh();
        $this->assertEquals(Assessment::TP_STATUS_SATISFIED, $fresh->tp_status);
        $this->assertNotNull($fresh->tp_satisfied_at);
    }

    public function test_tp_satisfied_at_cannot_exceed_effective_due_date(): void
    {
        $dueDate = now()->subDays(2)->startOfDay();

        $assessment = Assessment::create([
            'lpk_id' => $this->lpk->id,
            'created_by' => $this->admin->id,
            'title' => 'Asesmen Lewat Tenggat TP',
            'assessment_type' => 'Surveilen',
            'start_at' => now()->subMonths(3),
            'end_at' => now()->subMonths(3)->addDays(2),
            'status' => 'IN_PROGRESS',
            'tp_status' => Assessment::TP_STATUS_IN_PROGRESS,
            'tp_due_date' => $dueDate,
            'tp_has_extension' => false,
        ]);

        // Mencoba input tanggal memenuhi setelah tenggat waktu TP
        $response = $this->actingAs($this->admin)->post(route('assessments.tp.update', $assessment), [
            'tp_satisfied_at' => now()->toDateString(),
            'tp_due_date' => $dueDate->toDateString(),
        ]);

        $response->assertSessionHasErrors(['tp_satisfied_at']);
        $errors = session('errors')->get('tp_satisfied_at');
        $this->assertStringContainsString('2.11.12', $errors[0]);

        $fresh = $assessment->fresh();
        $this->assertNotEquals(Assessment::TP_STATUS_SATISFIED, $fresh->tp_status);
        $this->assertNull($fresh->tp_satisfied_at);
    }

    public function test_tp_satisfied_at_within_extension_due_date_succeeds(): void
    {
        $baseDueDate = now()->subDays(5)->startOfDay();

        $assessment = Assessment::create([
            'lpk_id' => $this->lpk->id,
            'created_by' => $this->admin->id,
            'title' => 'Asesmen TP dengan Perpanjangan',
            'assessment_type' => 'Surveilen',
            'start_at' => now()->subMonths(3),
            'end_at' => now()->subMonths(3)->addDays(2),
            'status' => 'IN_PROGRESS',
            'tp_status' => Assessment::TP_STATUS_IN_PROGRESS,
            'tp_due_date' => $baseDueDate,
            'tp_has_extension' => true,
            'tp_extension_months' => 1,
            'tp_extension_letter_no' => '104/LPK-LAB/EXT/2026',
            'tp_extension_date' => now()->subDays(10)->toDateString(),
        ]);

        // Input tanggal memenuhi setelah baseDueDate tetapi masih di dalam batas perpanjangan (+1 bulan)
        $extendedDueDate = $baseDueDate->copy()->addMonth();
        $satisfiedAt = now()->toDateString(); // masih sebelum extendedDueDate

        $response = $this->actingAs($this->admin)->post(route('assessments.tp.update', $assessment), [
            'tp_satisfied_at' => $satisfiedAt,
            'tp_has_extension' => '1',
            'tp_extension_letter_no' => '104/LPK-LAB/EXT/2026',
            'tp_extension_date' => now()->subDays(10)->toDateString(),
            'tp_due_date' => $extendedDueDate->toDateString(),
        ]);

        $response->assertSessionDoesntHaveErrors();
        $response->assertRedirect();

        $fresh = $assessment->fresh();
        $this->assertEquals(Assessment::TP_STATUS_SATISFIED, $fresh->tp_status);
        $this->assertEquals($satisfiedAt, $fresh->tp_satisfied_at->toDateString());
    }

    public function test_setting_tp_status_satisfied_when_overdue_fails_validation(): void
    {
        $pastDueDate = now()->subDays(5)->startOfDay();

        $assessment = Assessment::create([
            'lpk_id' => $this->lpk->id,
            'created_by' => $this->admin->id,
            'title' => 'Asesmen Mencoba Status Selesai Saat Lewat Waktu',
            'assessment_type' => 'Surveilen',
            'start_at' => now()->subMonths(3),
            'end_at' => now()->subMonths(3)->addDays(2),
            'status' => 'IN_PROGRESS',
            'tp_status' => Assessment::TP_STATUS_IN_PROGRESS,
            'tp_due_date' => $pastDueDate,
            'tp_has_extension' => false,
        ]);

        // Memilih status SATISFIED tanpa mengisi tanggal memenuhi (default now()) padahal sudah lewat tenggat
        $response = $this->actingAs($this->admin)->post(route('assessments.tp.update', $assessment), [
            'tp_status' => Assessment::TP_STATUS_SATISFIED,
            'tp_due_date' => $pastDueDate->toDateString(),
        ]);

        $response->assertSessionHasErrors(['tp_satisfied_at']);
        $errors = session('errors')->get('tp_satisfied_at');
        $this->assertStringContainsString('2.11.12', $errors[0]);
    }

    public function test_reverting_satisfied_tp_to_none_with_disabled_extension_succeeds(): void
    {
        $assessment = Assessment::create([
            'lpk_id' => $this->lpk->id,
            'created_by' => $this->admin->id,
            'title' => 'Asesmen Sebelumnya Memenuhi dengan Ekstensi',
            'assessment_type' => 'Surveilen',
            'start_at' => now()->subMonths(3),
            'end_at' => now()->subMonths(3)->addDays(2),
            'status' => 'COMPLETED',
            'tp_status' => Assessment::TP_STATUS_SATISFIED,
            'tp_satisfied_at' => now()->subDays(10),
            'tp_due_date' => now()->addDays(20),
            'tp_has_extension' => true,
            'tp_extension_months' => 1,
            'tp_extension_letter_no' => '104/LPK-LAB/EXT/IX/2026',
            'tp_extension_date' => now()->subDays(15)->toDateString(),
        ]);

        // Pengguna mengembalikan status ke NONE dan mematikan checkbox ekstensi (tp_has_extension = 0)
        // bahkan jika nilai nomor surat lama masih terkirim dari form sebelumnya
        $response = $this->actingAs($this->admin)->post(route('assessments.tp.update', $assessment), [
            'tp_status' => Assessment::TP_STATUS_NONE,
            'tp_has_extension' => '0',
            'tp_extension_letter_no' => '104/LPK-LAB/EXT/IX/2026',
        ]);

        $response->assertSessionDoesntHaveErrors();
        $response->assertRedirect();

        $fresh = $assessment->fresh();
        $this->assertEquals(Assessment::TP_STATUS_NONE, $fresh->tp_status);
        $this->assertFalse((bool) $fresh->tp_has_extension);
        $this->assertNull($fresh->tp_extension_letter_no);
        $this->assertNull($fresh->tp_satisfied_at);
    }

    public function test_smart_assist_fast_track_sk_completes_assessment_and_satisfies_tp(): void
    {
        $assessment = Assessment::create([
            'lpk_id' => $this->lpk->id,
            'created_by' => $this->admin->id,
            'title' => 'Asesmen Langsung Isi SK Resmi',
            'assessment_type' => 'Surveilen',
            'start_at' => now()->subMonths(2),
            'end_at' => now()->subMonths(2)->addDays(2),
            'status' => 'IN_PROGRESS',
            'tp_status' => Assessment::TP_STATUS_NONE,
            'tp_due_date' => now()->addMonth(),
        ]);

        // Fast track SK tanpa menyertakan pilihan TP spesifik -> otomatis tuntaskan TP
        $response = $this->actingAs($this->admin)->post(route('assessments.tp.update', $assessment), [
            'sk_number' => 'SK.KAN.999/BSN/X/2026',
            'sk_date' => now()->subDays(2)->toDateString(),
        ]);

        $response->assertSessionDoesntHaveErrors();
        $response->assertRedirect();

        $fresh = $assessment->fresh();
        $this->assertEquals('COMPLETED', $fresh->status);
        $this->assertEquals(Assessment::TP_STATUS_SATISFIED, $fresh->tp_status);
        $this->assertEquals(now()->subDays(2)->toDateString(), $fresh->tp_satisfied_at->toDateString());
    }

    public function test_assessment_with_sk_can_save_tp_status_none_for_nihil_temuan(): void
    {
        $assessment = Assessment::create([
            'lpk_id' => $this->lpk->id,
            'created_by' => $this->admin->id,
            'title' => 'Asesmen Nihil Temuan Tetap Simpan Status None',
            'assessment_type' => 'Surveilen',
            'start_at' => now()->subMonths(2),
            'end_at' => now()->subMonths(2)->addDays(2),
            'status' => 'COMPLETED',
            'tp_status' => Assessment::TP_STATUS_SATISFIED,
            'tp_satisfied_at' => now()->subDays(1),
            'sk_number' => 'SK.KAN.999/BSN/X/2026',
            'sk_date' => now()->subDays(2)->toDateString(),
        ]);

        // Pengguna memilih Belum Ada Temuan / Nihil Temuan (NONE) secara eksplisit
        $response = $this->actingAs($this->admin)->post(route('assessments.tp.update', $assessment), [
            'tp_status' => Assessment::TP_STATUS_NONE,
            'sk_number' => 'SK.KAN.999/BSN/X/2026',
            'sk_date' => now()->subDays(2)->toDateString(),
        ]);

        $response->assertSessionDoesntHaveErrors();
        $response->assertRedirect();

        $fresh = $assessment->fresh();
        $this->assertEquals('COMPLETED', $fresh->status);
        $this->assertEquals(Assessment::TP_STATUS_NONE, $fresh->tp_status);
        $this->assertNull($fresh->tp_satisfied_at);
    }

    public function test_filling_eha_date_defaults_eha_status_to_direkomendasikan(): void
    {
        $assessment = Assessment::create([
            'lpk_id' => $this->lpk->id,
            'created_by' => $this->admin->id,
            'title' => 'Asesmen Uji Otomasi EHA Status',
            'assessment_type' => 'Surveilen',
            'start_at' => now()->subMonths(1),
            'end_at' => now()->subMonths(1)->addDays(2),
            'status' => 'IN_PROGRESS',
            'tp_status' => Assessment::TP_STATUS_SATISFIED,
            'tp_satisfied_at' => now()->subDays(5),
            'eha_status' => Assessment::EHA_STATUS_BELUM,
        ]);

        $response = $this->actingAs($this->admin)->post(route('assessments.tp.update', $assessment), [
            'tp_status' => Assessment::TP_STATUS_SATISFIED,
            'eha_date' => now()->subDays(2)->toDateString(),
            'eha_status' => Assessment::EHA_STATUS_BELUM,
        ]);

        $response->assertSessionDoesntHaveErrors();
        $response->assertRedirect();

        $fresh = $assessment->fresh();
        $this->assertEquals(Assessment::EHA_STATUS_DIREKOMENDASIKAN, $fresh->eha_status);
        $this->assertEquals(now()->subDays(2)->toDateString(), $fresh->eha_date->toDateString());
    }
}
