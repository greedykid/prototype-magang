<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Lpk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentPartialFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_standard_request_returns_full_assessments_index_view(): void
    {
        $user = User::factory()->create();
        $lpk = Lpk::factory()->create(['name' => 'LPK Akreditasi Mandiri']);
        Assessment::factory()->create([
            'lpk_id' => $lpk->id,
            'title' => 'Surveilen 1 Tahunan',
        ]);

        $response = $this->actingAs($user)->get(route('assessments.index'));

        $response->assertStatus(200);
        $response->assertViewIs('assessments.index');
        $response->assertSee('Program asesmen');
        $response->assertSee('assessment-table-container');
        $response->assertSee('Surveilen 1 Tahunan');
    }

    public function test_partial_ajax_request_returns_only_table_content_partial(): void
    {
        $user = User::factory()->create();
        $lpk = Lpk::factory()->create(['name' => 'LPK Uji Mutu']);
        Assessment::factory()->create([
            'lpk_id' => $lpk->id,
            'title' => 'Re-Akreditasi Siklus 5 Tahun',
        ]);

        $response = $this->actingAs($user)->get(route('assessments.index'), [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-Partial-Content' => 'table',
        ]);

        $response->assertStatus(200);
        $response->assertViewIs('assessments.partials.table-content');
        $response->assertSee('Re-Akreditasi Siklus 5 Tahun');
        // Partial view does not contain the page header subtitle
        $response->assertDontSee('Jadwal dan progres asesmen semua LPK.');
    }

    public function test_partial_ajax_request_applies_live_search_and_status_filter(): void
    {
        $user = User::factory()->create();
        $lpk = Lpk::factory()->create(['name' => 'LPK Kalibrasi Prima']);
        Assessment::factory()->create([
            'lpk_id' => $lpk->id,
            'title' => 'Asesmen Khusus Kalibrasi',
            'status' => 'IN_PROGRESS',
        ]);
        Assessment::factory()->create([
            'lpk_id' => $lpk->id,
            'title' => 'Audit Pengawasan Normal',
            'status' => 'COMPLETED',
        ]);

        $response = $this->actingAs($user)->get(route('assessments.index', [
            'search' => 'Kalibrasi',
            'status' => 'IN_PROGRESS',
        ]), [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-Partial-Content' => 'table',
        ]);

        $response->assertStatus(200);
        $response->assertViewIs('assessments.partials.table-content');
        $response->assertSee('Asesmen Khusus Kalibrasi');
        $response->assertDontSee('Audit Pengawasan Normal');
    }

    public function test_partial_ajax_request_returns_empty_state_when_no_match(): void
    {
        $user = User::factory()->create();
        $lpk = Lpk::factory()->create(['name' => 'LPK Pengujian']);
        Assessment::factory()->create([
            'lpk_id' => $lpk->id,
            'title' => 'Asesmen Normal',
            'status' => 'COMPLETED',
        ]);

        $response = $this->actingAs($user)->get(route('assessments.index', [
            'search' => 'awdawdawdaw',
            'status' => 'CANCELLED',
        ]), [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-Partial-Content' => 'table',
        ]);

        $response->assertStatus(200);
        $response->assertViewIs('assessments.partials.table-content');
        $response->assertSee('Tidak ada program asesmen yang cocok dengan filter.');
        $response->assertSee('Reset filter');
        $response->assertDontSee('Asesmen Normal');
    }
}
