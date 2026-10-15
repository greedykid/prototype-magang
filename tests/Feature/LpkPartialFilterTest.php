<?php

namespace Tests\Feature;

use App\Models\Lpk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LpkPartialFilterTest extends TestCase
{
    use RefreshDatabase;

    public function test_standard_request_returns_full_lpk_index_view(): void
    {
        $user = User::factory()->create();
        Lpk::factory()->create(['name' => 'LPK Maju Bersama']);

        $response = $this->actingAs($user)->get(route('lpks.index'));

        $response->assertStatus(200);
        $response->assertViewIs('lpks.index');
        $response->assertSee('Daftar LPK');
        $response->assertSee('lpk-table-container');
        $response->assertSee('LPK Maju Bersama');
    }

    public function test_partial_ajax_request_returns_only_table_content_partial(): void
    {
        $user = User::factory()->create();
        Lpk::factory()->create(['name' => 'LPK Nusantara Gemilang']);

        $response = $this->actingAs($user)->get(route('lpks.index'), [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-Partial-Content' => 'table',
        ]);

        $response->assertStatus(200);
        $response->assertViewIs('lpks.partials.table-content');
        $response->assertSee('LPK Nusantara Gemilang');
        $response->assertSee('table-lpks-custom');
        // Partial view does not contain page header or navbar
        $response->assertDontSee('Kelola data profil, siklus pengawasan surveilan');
    }

    public function test_partial_ajax_request_applies_live_search_filter(): void
    {
        $user = User::factory()->create();
        Lpk::factory()->create(['name' => 'LPK Cepat Pintar']);
        Lpk::factory()->create(['name' => 'Balai Latihan Kerja Mandiri']);

        $response = $this->actingAs($user)->get(route('lpks.index', ['search' => 'Pintar']), [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-Partial-Content' => 'table',
        ]);

        $response->assertStatus(200);
        $response->assertViewIs('lpks.partials.table-content');
        $response->assertSee('LPK Cepat Pintar');
        $response->assertDontSee('Balai Latihan Kerja Mandiri');
    }

    public function test_form_and_partial_sort_preserves_per_page(): void
    {
        $user = User::factory()->create();
        Lpk::factory()->count(15)->create();

        // 1. Full page request has hidden input with per_page
        $fullResponse = $this->actingAs($user)->get(route('lpks.index', ['per_page' => 25]));
        $fullResponse->assertStatus(200);
        $fullResponse->assertSee('<input type="hidden" name="per_page" value="25">', false);

        // 2. Partial sort request with per_page=25 returns all 15 items on page 1 without capping at 10
        $partialResponse = $this->actingAs($user)->get(route('lpks.index', [
            'per_page' => 25,
            'sort' => 'name',
            'direction' => 'asc',
        ]), [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-Partial-Content' => 'table',
        ]);

        $partialResponse->assertStatus(200);
        $partialResponse->assertViewIs('lpks.partials.table-content');
        $this->assertCount(15, $partialResponse->viewData('lpks'));
    }
}
