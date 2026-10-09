<?php

namespace Tests\Feature;

use App\Models\Assessment;
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
        config(['services.sheets.feed_key' => 'valid-secret-key-kan-2026']);

        $lpk = Lpk::factory()->create(['name' => 'LPK Pengujian Unggulan']);
        Assessment::factory()->create([
            'lpk_id' => $lpk->id,
            'title' => 'Asesmen Lapangan Bersama',
        ]);

        // Akses publik oleh crawler Google Sheets via feed endpoint
        $response = $this->get(route('feeds.assessments', ['key' => 'valid-secret-key-kan-2026']));

        $response->assertOk();
        $response->assertHeader('content-type', 'text/csv; charset=UTF-8');
        $this->assertStringContainsString('inline;', $response->headers->get('content-disposition'));

        $content = $response->streamedContent();
        $this->assertStringContainsString('LPK Pengujian Unggulan', $content);
        $this->assertStringContainsString('Asesmen Lapangan Bersama', $content);
    }

    public function test_google_sheets_live_feed_endpoint_rejects_default_placeholder_key(): void
    {
        config(['services.sheets.feed_key' => 'simasadi-live']);

        $response = $this->get(route('feeds.assessments', ['key' => 'simasadi-live']));

        $response->assertStatus(401);
        $this->assertStringContainsString('Unauthorized', $response->getContent());
    }

    public function test_google_sheets_live_feed_endpoint_rejects_invalid_key(): void
    {
        config(['services.sheets.feed_key' => 'valid-secret-key-kan-2026']);

        $response = $this->get(route('feeds.assessments', ['key' => 'invalid-token-123']));

        $response->assertStatus(401);
        $this->assertStringContainsString('Unauthorized', $response->getContent());
    }

    public function test_ui_renders_google_sheets_modal_and_formula(): void
    {
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
    }
}
