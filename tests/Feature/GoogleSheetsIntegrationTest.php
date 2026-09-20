<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\AssessmentExpense;
use App\Models\Lpk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GoogleSheetsIntegrationTest extends TestCase
{
    use RefreshDatabase;

    protected User $user;

    protected function setUp(): void
    {
        parent::setUp();

        $this->user = User::factory()->create([
            'email' => 'admin@simasadi.local',
            'role' => User::ROLE_ADMIN,
        ]);
    }

    public function test_authenticated_user_can_export_expenses_csv(): void
    {
        $lpk = Lpk::factory()->create([
            'name' => 'Balai Pengujian Mutu Hasil Pertanian',
            'registration_number' => 'LP-999-IDN',
        ]);

        $assessment = Assessment::factory()->create([
            'lpk_id' => $lpk->id,
            'title' => 'Asesmen Lapangan Terpadu',
            'assessment_type' => 'Surveilen',
        ]);

        AssessmentExpense::create([
            'assessment_id' => $assessment->id,
            'reported_by' => $this->user->id,
            'daily_allowance' => 950000,
            'transport_cost' => 1500000,
            'accommodation_cost' => 800000,
            'package_data_cost' => 150000,
            'total_cost' => 3400000,
            'status' => 'TERVERIFIKASI',
            'receipt_note' => 'Tiket GA-214 dan Hotel Grand',
        ]);

        $response = $this->actingAs($this->user)->get(route('reports.expenses.export'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('attachment;', $response->headers->get('content-disposition'));

        $content = $response->streamedContent();
        $this->assertStringContainsString('ID Asesmen', $content);
        $this->assertStringContainsString('Uang Harian (Rp)', $content);
        $this->assertStringContainsString('Balai Pengujian Mutu Hasil Pertanian', $content);
        $this->assertStringContainsString('TERVERIFIKASI', $content);
        $this->assertStringContainsString('3400000', $content);
    }

    public function test_authenticated_user_can_export_lpks_csv(): void
    {
        Lpk::factory()->create([
            'name' => 'Lab Kalibrasi Presisi Nasional',
            'registration_number' => 'LK-777-IDN',
            'status' => 'ACTIVE',
        ]);

        $response = $this->actingAs($this->user)->get(route('reports.lpks.export'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('Nomor Registrasi', $content);
        $this->assertStringContainsString('Nama Lembaga Penilaian Kesesuaian', $content);
        $this->assertStringContainsString('Lab Kalibrasi Presisi Nasional', $content);
        $this->assertStringContainsString('LK-777-IDN', $content);
    }

    public function test_authenticated_user_can_export_assessments_csv(): void
    {
        $lpk = Lpk::factory()->create();
        Assessment::factory()->create([
            'lpk_id' => $lpk->id,
            'title' => 'Surveilen Tahunan Akreditasi',
        ]);

        $response = $this->actingAs($this->user)->get(route('reports.assessments.export'));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');

        $content = $response->streamedContent();
        $this->assertStringContainsString('Judul Agenda Asesmen', $content);
        $this->assertStringContainsString('Surveilen Tahunan Akreditasi', $content);
    }

    public function test_google_sheets_live_feed_endpoint_with_valid_key(): void
    {
        $lpk = Lpk::factory()->create(['name' => 'LPK Pengujian Unggulan']);
        Assessment::factory()->create([
            'lpk_id' => $lpk->id,
            'title' => 'Asesmen Lapangan Bersama',
        ]);

        // Akses publik oleh crawler Google Sheets via feed endpoint
        $response = $this->get(route('feeds.expenses', ['key' => 'simasadi-live']));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('inline;', $response->headers->get('content-disposition'));

        $content = $response->streamedContent();
        $this->assertStringContainsString('LPK Pengujian Unggulan', $content);
        $this->assertStringContainsString('Asesmen Lapangan Bersama', $content);
    }

    public function test_google_sheets_live_feed_endpoint_rejects_invalid_key(): void
    {
        $response = $this->get(route('feeds.expenses', ['key' => 'invalid-token-123']));

        $response->assertStatus(401);
        $this->assertStringContainsString('Unauthorized', $response->getContent());
    }

    public function test_ui_renders_google_sheets_modal_and_formula(): void
    {
        $lpk = Lpk::factory()->create();
        $assessment = Assessment::factory()->create(['lpk_id' => $lpk->id]);

        $assessmentsPage = $this->actingAs($this->user)->get(route('assessments.index'));
        $assessmentsPage->assertOk();
        $assessmentsPage->assertSee('Google Sheets');
        $assessmentsPage->assertSee('modal-sheets-sync-assessments');
        $assessmentsPage->assertSee('=IMPORTDATA(', false);

        $lpksPage = $this->actingAs($this->user)->get(route('lpks.index'));
        $lpksPage->assertOk();
        $lpksPage->assertSee('Google Sheets');
        $lpksPage->assertSee('modal-sheets-sync-lpks');
        $lpksPage->assertSee('=IMPORTDATA(', false);

        $assessmentDetail = $this->actingAs($this->user)->get(route('assessments.show', $assessment));
        $assessmentDetail->assertOk();
        $assessmentDetail->assertSee('Google Sheets');
        $assessmentDetail->assertSee('modal-sheets-sync-expenses');
    }
}
