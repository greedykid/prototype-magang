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
}
