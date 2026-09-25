<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Lpk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentStatusAutoTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Lpk $lpk;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'ADMIN_UNIT',
        ]);

        $this->lpk = Lpk::factory()->create([
            'registration_number' => 'LP-TEST-001',
            'name' => 'Laboratorium Kalibrasi Uji Test',
            'status' => 'ACTIVE',
        ]);
    }

    public function test_form_renders_planned_for_past_dates_without_action(): void
    {
        $assessment = Assessment::factory()->create([
            'lpk_id' => $this->lpk->id,
            'title' => 'Surveilen Berkala Lab Test',
            'assessment_type' => 'Surveilen 1',
            'start_at' => now()->subMonths(3),
            'end_at' => now()->subMonths(3)->addHours(8),
            'status' => 'PLANNED',
            'tp_status' => 'NONE',
            'sk_number' => null,
            'report_date' => null,
            'eha_date' => null,
        ]);

        $response = $this->actingAs($this->admin)->get(route('assessments.edit', $assessment));

        $response->assertOk();
        $response->assertSee('Status Asesmen');
        $response->assertSee('status-badge-preview');
        $response->assertSee('Direncanakan');
        $response->assertSee('Lewat Jadwal');
        $response->assertDontSee('<select name="status">', false);
    }

    public function test_form_renders_completed_when_sk_is_present(): void
    {
        $assessment = Assessment::factory()->create([
            'lpk_id' => $this->lpk->id,
            'title' => 'Surveilen Berkala Lab Selesai',
            'assessment_type' => 'Surveilen 1',
            'start_at' => now()->subMonths(3),
            'end_at' => now()->subMonths(3)->addHours(8),
            'status' => 'COMPLETED',
            'tp_status' => 'SATISFIED',
            'sk_number' => 'SK.KAN.099/2026',
        ]);

        $response = $this->actingAs($this->admin)->get(route('assessments.edit', $assessment));

        $response->assertOk();
        $response->assertSee('Selesai');
        $response->assertSee('status-completed');
    }

    public function test_store_past_dates_without_action_remains_planned(): void
    {
        $response = $this->actingAs($this->admin)->post(route('assessments.store'), [
            'lpk_id' => $this->lpk->id,
            'title' => 'Asesmen Lapangan Belum Terlaksana',
            'assessment_type' => 'Surveilen 1',
            'start_at' => now()->subDays(10)->format('Y-m-d\TH:i'),
            'end_at' => now()->subDays(8)->format('Y-m-d\TH:i'),
            'location' => 'Gedung Lab',
            // No action fields provided
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('assessments', [
            'title' => 'Asesmen Lapangan Belum Terlaksana',
            'status' => 'PLANNED',
        ]);
    }

    public function test_store_past_dates_with_sk_or_report_becomes_completed(): void
    {
        $response = $this->actingAs($this->admin)->post(route('assessments.store'), [
            'lpk_id' => $this->lpk->id,
            'title' => 'Asesmen Lapangan Sudah Terbit SK',
            'assessment_type' => 'Surveilen 1',
            'start_at' => now()->subDays(10)->format('Y-m-d\TH:i'),
            'end_at' => now()->subDays(8)->format('Y-m-d\TH:i'),
            'location' => 'Gedung Lab',
            'sk_number' => 'SK.KAN.042/BSN/IX/2026',
            'sk_date' => now()->subDays(2)->format('Y-m-d'),
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('assessments', [
            'title' => 'Asesmen Lapangan Sudah Terbit SK',
            'status' => 'COMPLETED',
        ]);
    }

    public function test_store_auto_calculates_scheduled_status_for_future_dates(): void
    {
        $response = $this->actingAs($this->admin)->post(route('assessments.store'), [
            'lpk_id' => $this->lpk->id,
            'title' => 'Asesmen Lapangan Masa Depan',
            'assessment_type' => 'Surveilen 2',
            'start_at' => now()->addDays(14)->format('Y-m-d\TH:i'),
            'end_at' => now()->addDays(16)->format('Y-m-d\TH:i'),
            'location' => 'Gedung Lab',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('assessments', [
            'title' => 'Asesmen Lapangan Masa Depan',
            'status' => 'SCHEDULED',
        ]);
    }

    public function test_update_to_in_progress_when_tp_active(): void
    {
        $assessment = Assessment::factory()->create([
            'lpk_id' => $this->lpk->id,
            'title' => 'Asesmen Sedang Proses Perbaikan',
            'start_at' => now()->subDays(20),
            'end_at' => now()->subDays(18),
            'status' => 'PLANNED',
        ]);

        $response = $this->actingAs($this->admin)->put(route('assessments.update', $assessment), [
            'lpk_id' => $this->lpk->id,
            'title' => 'Asesmen Sedang Proses Perbaikan',
            'assessment_type' => 'Surveilen 1',
            'start_at' => now()->subDays(20)->format('Y-m-d\TH:i'),
            'end_at' => now()->subDays(18)->format('Y-m-d\TH:i'),
            'tp_status' => 'IN_PROGRESS',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $assessment->refresh();
        $this->assertEquals('IN_PROGRESS', $assessment->status);
    }

    public function test_determine_status_from_dates_helper_logic(): void
    {
        $pastStart = now()->subDays(10);
        $pastEnd = now()->subDays(8);

        // Past dates without action must NOT be completed
        $this->assertEquals('PLANNED', Assessment::determineStatusFromDates($pastStart, $pastEnd, 'PLANNED'));

        // Past dates with action (SK or report) IS completed
        $this->assertEquals('COMPLETED', Assessment::determineStatusFromDates($pastStart, $pastEnd, 'PLANNED', 'NONE', 'SK-123'));
        $this->assertEquals('COMPLETED', Assessment::determineStatusFromDates($pastStart, $pastEnd, 'PLANNED', 'SATISFIED'));
        $this->assertEquals('COMPLETED', Assessment::determineStatusFromDates($pastStart, $pastEnd, 'PLANNED', 'NONE', null, now()->subDays(2)));

        // Past dates with active TP within SLA is IN_PROGRESS
        $this->assertEquals('IN_PROGRESS', Assessment::determineStatusFromDates($pastStart, $pastEnd, 'PLANNED', 'IN_PROGRESS'));

        // Past dates with SLA date expired without satisfaction IS SUSPENDED (Dibekukan)
        $this->assertEquals('SUSPENDED', Assessment::determineStatusFromDates($pastStart, $pastEnd, 'PLANNED', 'IN_PROGRESS', null, null, null, now()->subDay()));
        $this->assertEquals('IN_PROGRESS', Assessment::determineStatusFromDates($pastStart, $pastEnd, 'PLANNED', 'IN_PROGRESS', null, null, null, now()->addDays(20)));
        $this->assertEquals('IN_PROGRESS', Assessment::determineStatusFromDates($pastStart, $pastEnd, 'PLANNED', 'IN_PROGRESS', null, null, null, now()->subDays(5), true, 1));
        $this->assertEquals('SUSPENDED', Assessment::determineStatusFromDates($pastStart, $pastEnd, 'PLANNED', 'IN_PROGRESS', null, null, null, now()->subMonths(2), true, 1));

        // Future dates
        $futureStart = now()->addDays(5);
        $futureEnd = now()->addDays(7);
        $this->assertEquals('SCHEDULED', Assessment::determineStatusFromDates($futureStart, $futureEnd, 'SCHEDULED'));

        // Ongoing dates
        $ongoingStart = now()->subDay();
        $ongoingEnd = now()->addDay();
        $this->assertEquals('IN_PROGRESS', Assessment::determineStatusFromDates($ongoingStart, $ongoingEnd));

        // Edge cases
        $this->assertEquals('PLANNED', Assessment::determineStatusFromDates(null, null));
        $this->assertEquals('CANCELLED', Assessment::determineStatusFromDates($pastStart, $pastEnd, 'CANCELLED'));
    }

    public function test_past_sla_due_date_without_satisfaction_becomes_suspended_and_suspends_lpk(): void
    {
        $this->lpk->assessments()->delete();

        $assessment = Assessment::create([
            'lpk_id' => $this->lpk->id,
            'created_by' => $this->admin->id,
            'title' => 'Asesmen Surveilen Lewat SLA',
            'assessment_type' => 'Surveilen 1',
            'start_at' => now()->subMonths(4),
            'end_at' => now()->subMonths(4)->addDays(2),
            'tp_status' => 'IN_PROGRESS',
            'tp_due_date' => now()->subDays(10), // Lewat SLA
            'status' => 'PLANNED',
        ]);

        $assessment->refresh();
        $this->assertEquals('SUSPENDED', $assessment->status);
        $this->assertTrue($assessment->is_tp_overdue);
        $this->assertStringContainsString('Dibekukan', $assessment->tp_sla_badge['label']);

        $this->lpk->refresh();
        $this->assertEquals('SUSPENDED', $this->lpk->dynamic_status);
        $this->assertEquals('Dibekukan', $this->lpk->dynamic_status_label);
        $this->assertStringContainsString('Dibekukan', $this->lpk->dynamic_keterangan);

        // When satisfied, status transitions to COMPLETED and LPK dynamic status returns to ACTIVE
        $assessment->update([
            'tp_status' => 'SATISFIED',
            'tp_satisfied_at' => now()->toDateString(),
        ]);

        $assessment->refresh();
        $this->assertEquals('COMPLETED', $assessment->status);
        $this->assertFalse($assessment->is_tp_overdue);

        $this->lpk->refresh();
        $this->assertEquals('ACTIVE', $this->lpk->dynamic_status);
    }

    public function test_dynamic_keterangan_selects_closest_upcoming_and_formats_with_official_8_types(): void
    {
        $this->lpk->assessments()->delete();

        // Setup LPK with 3 generated assessments: S1 (next year), S2 (3 years), RA (5 years)
        $s1 = Assessment::create([
            'lpk_id' => $this->lpk->id,
            'created_by' => $this->admin->id,
            'title' => 'Asesmen Surveilen 1 (S1) - Lab Test',
            'assessment_type' => Assessment::TYPE_SURVEILEN_1,
            'start_at' => now()->addMonths(6),
            'end_at' => now()->addMonths(6)->addDays(2),
            'status' => 'PLANNED',
        ]);

        $s2 = Assessment::create([
            'lpk_id' => $this->lpk->id,
            'created_by' => $this->admin->id,
            'title' => 'Asesmen Surveilen 2 (S2) - Lab Test',
            'assessment_type' => Assessment::TYPE_SURVEILEN_2,
            'start_at' => now()->addMonths(24),
            'end_at' => now()->addMonths(24)->addDays(2),
            'status' => 'PLANNED',
        ]);

        $ra = Assessment::create([
            'lpk_id' => $this->lpk->id,
            'created_by' => $this->admin->id,
            'title' => 'Asesmen Re-Akreditasi (RA) - Lab Test',
            'assessment_type' => Assessment::TYPE_RE_AKREDITASI,
            'start_at' => now()->addMonths(48),
            'end_at' => now()->addMonths(48)->addDays(2),
            'status' => 'PLANNED',
        ]);

        // Must pick S1 because S1 is the closest upcoming assessment, NOT RA in 48 months
        $this->lpk->refresh();
        $this->assertEquals($s1->id, $this->lpk->getActiveOrUpcomingAssessment()->id);
        $this->assertStringStartsWith('Surveilen 1: Terjadwal ', $this->lpk->dynamic_keterangan);

        // When S1 is completed with SK, it moves to S2
        $s1->update([
            'status' => 'COMPLETED',
            'sk_number' => 'SK.KAN.001/2026',
            'sk_date' => now(),
        ]);
        $this->lpk->refresh();
        $this->assertEquals($s2->id, $this->lpk->getActiveOrUpcomingAssessment()->id);
        $this->assertStringStartsWith('Surveilen 2: Terjadwal ', $this->lpk->dynamic_keterangan);

        // When S2 is completed too, it moves to Re-Akreditasi
        $s2->update([
            'status' => 'COMPLETED',
            'sk_number' => 'SK.KAN.002/2028',
            'sk_date' => now(),
        ]);
        $this->lpk->refresh();
        $this->assertEquals($ra->id, $this->lpk->getActiveOrUpcomingAssessment()->id);
        $this->assertStringStartsWith('Re-Akreditasi (Akreditasi Ulang): Terjadwal ', $this->lpk->dynamic_keterangan);
    }

    public function test_assessment_type_label_maps_to_official_8_types(): void
    {
        $a1 = new Assessment(['assessment_type' => 'INITIAL']);
        $this->assertEquals('Akreditasi Awal', $a1->assessment_type_label);

        $a2 = new Assessment(['assessment_type' => 'S1']);
        $this->assertEquals('Surveilen 1', $a2->assessment_type_label);

        $a3 = new Assessment(['assessment_type' => 'Surveilen 1 + PRL']);
        $this->assertEquals('Surveilen 1 + PRL', $a3->assessment_type_label);

        $a4 = new Assessment(['assessment_type' => 'S2']);
        $this->assertEquals('Surveilen 2', $a4->assessment_type_label);

        $a5 = new Assessment(['assessment_type' => 'Surveilen 2 + PRL']);
        $this->assertEquals('Surveilen 2 + PRL', $a5->assessment_type_label);

        $a6 = new Assessment(['assessment_type' => 'STT']);
        $this->assertEquals('Surveilen Tidak Terjadwal (STT)', $a6->assessment_type_label);

        $a7 = new Assessment(['assessment_type' => 'PRL']);
        $this->assertEquals('Perluasan Ruang Lingkup (PRL)', $a7->assessment_type_label);

        $a8 = new Assessment(['assessment_type' => 'Re-asesmen']);
        $this->assertEquals('Re-Akreditasi (Akreditasi Ulang)', $a8->assessment_type_label);
    }

    public function test_overdue_sla_immediately_resolves_to_suspended_dynamically_without_manual_save(): void
    {
        $this->lpk->assessments()->delete();

        // Simulate an assessment stored in DB with status IN_PROGRESS before SLA expired
        $assessment = Assessment::create([
            'lpk_id' => $this->lpk->id,
            'created_by' => $this->admin->id,
            'title' => 'Asesmen Tanpa Manual Save',
            'assessment_type' => 'Surveilen 1',
            'start_at' => now()->subMonths(3),
            'end_at' => now()->subMonths(3)->addDays(2),
            'tp_status' => 'IN_PROGRESS',
            'tp_due_date' => now()->subDays(10),
            'status' => 'PLANNED',
        ]);

        // Force raw DB status to 'IN_PROGRESS' to simulate passage of time without any updates/saves
        \Illuminate\Support\Facades\DB::table('assessments')
            ->where('id', $assessment->id)
            ->update(['status' => 'IN_PROGRESS']);

        // Directly reload from DB
        $fresh = Assessment::find($assessment->id);

        // Raw database column is IN_PROGRESS, but dynamically it is SUSPENDED immediately in real-time
        $this->assertEquals('IN_PROGRESS', $fresh->getRawOriginal('status'));
        $this->assertEquals('SUSPENDED', $fresh->status);
        $this->assertEquals('Dibekukan', $fresh->status_label);
        $this->assertTrue($fresh->is_tp_overdue);
        $this->assertStringContainsString('Dibekukan', $fresh->tp_sla_badge['label']);

        // Check LPK dynamic reflection
        $this->lpk->refresh();
        $this->assertEquals('SUSPENDED', $this->lpk->dynamic_status);
        $this->assertEquals('Dibekukan', $this->lpk->dynamic_status_label);
        $this->assertStringContainsString('Dibekukan', $this->lpk->dynamic_keterangan);

        // Index filter status=SUSPENDED finds it immediately
        $response = $this->actingAs($this->admin)->get(route('assessments.index', ['status' => 'SUSPENDED']));
        $response->assertOk();
        $response->assertSee('Asesmen Tanpa Manual Save');
        $response->assertSee('Dibekukan');
    }

    public function test_past_sla_with_tp_none_and_no_action_renders_suspended_in_edit_form(): void
    {
        $this->lpk->assessments()->delete();

        // S1 with overdue SLA in current cycle, tp_status NONE, no SK
        $assessment = Assessment::create([
            'lpk_id' => $this->lpk->id,
            'created_by' => $this->admin->id,
            'title' => 'Asesmen Surveilen 1 (S1) - UPT Lab',
            'assessment_type' => 'Surveilen 1',
            'start_at' => now()->subMonths(3)->format('Y-m-d 09:00:00'),
            'end_at' => now()->subMonths(3)->addDays(2)->format('Y-m-d 17:00:00'),
            'tp_status' => 'NONE',
            'tp_due_date' => now()->subMonth()->format('Y-m-d'),
            'status' => 'PLANNED',
        ]);

        $this->assertEquals('SUSPENDED', $assessment->status);
        $this->assertEquals('Dibekukan', $assessment->status_label);
        $this->assertTrue($assessment->is_tp_overdue);

        // Edit form should render Dibekukan badge and suspended description
        $response = $this->actingAs($this->admin)->get(route('assessments.edit', $assessment));
        $response->assertOk();
        $response->assertSee('status-suspended');
        $response->assertSee('Dibekukan');
        $response->assertSee('Otomatis: Melewati batas waktu awal (SLA) belum dinyatakan memenuhi sehingga status dibekukan.');
    }

    public function test_past_surveillance_for_active_lpk_is_automatically_completed(): void
    {
        // S1 from past year for active LPK should automatically be completed and satisfied
        $pastAssessment = Assessment::create([
            'lpk_id' => $this->lpk->id,
            'created_by' => $this->admin->id,
            'title' => 'Asesmen Surveilen 1 (S1) - Lampau 2023',
            'assessment_type' => 'Surveilen 1',
            'start_at' => '2023-01-03 09:00:00',
            'end_at' => '2023-01-05 17:00:00',
            'tp_status' => 'NONE',
            'status' => 'PLANNED',
        ]);

        $this->assertEquals('COMPLETED', $pastAssessment->status);
        $this->assertEquals('Selesai', $pastAssessment->status_label);
        $this->assertEquals('Dinyatakan Memenuhi (Selesai)', $pastAssessment->tp_status_label);
        $this->assertFalse($pastAssessment->is_submission_overdue);
        $this->assertFalse($pastAssessment->is_tp_overdue);
    }

    public function test_surveillance_tolerance_window_and_revocation_after_one_year(): void
    {
        $inactiveLpk = Lpk::factory()->create(['status' => 'INACTIVE']);

        // Assessment within 1-year opportunity window
        $suspendedAssessment = Assessment::create([
            'lpk_id' => $inactiveLpk->id,
            'created_by' => $this->admin->id,
            'title' => 'Surveilen Lewat Toleransi (Dalam 1 Tahun)',
            'assessment_type' => 'Surveilen 1',
            'start_at' => now()->subMonths(4),
            'end_at' => now()->subMonths(4)->addDays(2),
            'status' => 'IN_PROGRESS',
        ]);

        $this->assertTrue($suspendedAssessment->is_submission_overdue);
        $this->assertFalse($suspendedAssessment->is_suspension_expired);
        $this->assertNotNull($suspendedAssessment->suspension_resolution_deadline);
        $this->assertGreaterThan(0, $suspendedAssessment->days_remaining_suspension);

        // Assessment past 1-year window without completion -> REVOKED
        $revokedAssessment = Assessment::create([
            'lpk_id' => $inactiveLpk->id,
            'created_by' => $this->admin->id,
            'title' => 'Surveilen Lewat 1 Tahun Pembekuan',
            'assessment_type' => 'Surveilen 1',
            'start_at' => now()->subMonths(15),
            'end_at' => now()->subMonths(15)->addDays(2),
            'status' => 'IN_PROGRESS',
        ]);

        $this->assertTrue($revokedAssessment->is_suspension_expired);
        $this->assertEquals('REVOKED', $revokedAssessment->status);
        $this->assertEquals('Dicabut', $revokedAssessment->status_label);
    }

    public function test_form_renders_tp_status_automatically_without_select_input(): void
    {
        $assessment = Assessment::factory()->create([
            'lpk_id' => $this->lpk->id,
            'title' => 'Asesmen TP Sedang Berlangsung',
            'assessment_type' => 'Surveilen 1',
            'start_at' => now()->subDays(10),
            'end_at' => now()->subDays(8),
            'tp_status' => 'IN_PROGRESS',
            'tp_due_date' => now()->addMonth(),
            'tp_satisfied_at' => null,
            'status' => 'IN_PROGRESS',
        ]);

        $response = $this->actingAs($this->admin)->get(route('assessments.edit', $assessment));

        $response->assertOk();
        // Pastikan tidak ada tag select untuk tp_status
        $response->assertDontSee('<select name="tp_status"', false);
        // Pastikan ada hidden input untuk submit otomatis
        $response->assertSee('name="tp_status"', false);
        $response->assertSee('id="input-auto-tp-status"', false);
        // Pastikan preview status TP menampilkan Sedang Berlangsung
        $response->assertSee('id="tp-status-badge-preview"', false);
        $response->assertSee('Sedang Berlangsung');
        $response->assertSee('status-in_progress');
    }

    public function test_form_renders_satisfied_when_tp_satisfied_at_filled(): void
    {
        $assessment = Assessment::factory()->create([
            'lpk_id' => $this->lpk->id,
            'title' => 'Asesmen TP Selesai',
            'assessment_type' => 'Surveilen 1',
            'start_at' => now()->subMonths(2),
            'end_at' => now()->subMonths(2)->addDays(2),
            'tp_status' => 'SATISFIED',
            'tp_due_date' => now()->subMonth(),
            'tp_satisfied_at' => now()->subDays(5),
            'status' => 'COMPLETED',
        ]);

        $response = $this->actingAs($this->admin)->get(route('assessments.edit', $assessment));

        $response->assertOk();
        $response->assertDontSee('<select name="tp_status"', false);
        $response->assertSee('Dinyatakan Memenuhi (Selesai)');
        $response->assertSee('status-completed');
    }

    public function test_suspended_badge_uses_purple_color_instead_of_red(): void
    {
        $assessment = Assessment::factory()->create([
            'lpk_id' => $this->lpk->id,
            'title' => 'Asesmen Melewati SLA KAN',
            'assessment_type' => 'Surveilen 1',
            'start_at' => now()->subMonths(4),
            'end_at' => now()->subMonths(4)->addDays(2),
            'tp_status' => 'IN_PROGRESS',
            'tp_due_date' => now()->subMonths(2),
            'tp_satisfied_at' => null,
            'status' => 'PLANNED',
        ]);

        $this->assertEquals('SUSPENDED', $assessment->status);
        $this->assertEquals('suspended', $assessment->tp_sla_badge['type']);
        $this->assertStringContainsString('Dibekukan', $assessment->tp_sla_badge['label']);

        // Pastikan tampilan index menggunakan badge-tp-suspended (ungu)
        $response = $this->actingAs($this->admin)->get(route('assessments.index'));
        $response->assertOk();
        $response->assertSee('badge-tp badge-tp-suspended', false);
        $response->assertSee('status status-suspended', false);
    }
}
