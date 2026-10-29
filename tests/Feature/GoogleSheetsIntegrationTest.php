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
        $this->assertStringContainsString('No Reg LPK', $content);
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

    public function test_ui_renders_export_modal(): void
    {
        $assessmentsPage = $this->actingAs($this->user)->get(route('assessments.index'));
        $assessmentsPage->assertOk();
        $assessmentsPage->assertSee('Ekspor Data');
        $assessmentsPage->assertSee('modal-sheets-sync-assessments');

        $lpksPage = $this->actingAs($this->user)->get(route('lpks.index'));
        $lpksPage->assertOk();
        $lpksPage->assertSee('Ekspor Data');
        $lpksPage->assertSee('modal-sheets-sync-lpks');
    }
}
