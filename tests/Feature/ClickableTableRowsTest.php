<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Lpk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ClickableTableRowsTest extends TestCase
{
    use RefreshDatabase;

    private User $admin;
    private Lpk $lpk;
    private Assessment $assessment;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'role' => 'ADMIN_UNIT',
        ]);

        $this->lpk = Lpk::factory()->create([
            'registration_number' => 'LP-CLICK-01',
            'name' => 'Laboratorium Pengujian Klik Baris',
            'status' => 'ACTIVE',
        ]);

        $this->assessment = Assessment::factory()->create([
            'lpk_id' => $this->lpk->id,
            'title' => 'Asesmen Baris Interaktif',
            'assessment_type' => 'Surveilen 1',
            'status' => 'SCHEDULED',
        ]);
    }

    public function test_lpk_index_table_rows_are_clickable_with_show_link(): void
    {
        $response = $this->actingAs($this->admin)->get(route('lpks.index'));

        $response->assertOk();
        $expectedUrl = route('lpks.show', $this->lpk);
        $response->assertSee('clickable-row');
        $response->assertSee('data-href="' . $expectedUrl . '"', false);
    }

    public function test_assessments_index_table_rows_are_clickable_with_show_link(): void
    {
        $response = $this->actingAs($this->admin)->get(route('assessments.index'));

        $response->assertOk();
        $expectedUrl = route('assessments.show', $this->assessment);
        $response->assertSee('clickable-row');
        $response->assertSee('data-href="' . $expectedUrl . '"', false);
    }
}
