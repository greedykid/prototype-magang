<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Lpk;
use App\Models\LpkLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AssessmentLocationRadioTest extends TestCase
{
    use RefreshDatabase;

    public function test_assessment_form_renders_location_radio_buttons(): void
    {
        $admin = User::factory()->admin()->create();
        $lpk = Lpk::factory()->create();

        $response = $this->actingAs($admin)->get(route('assessments.create', ['lpk_id' => $lpk->id]));

        $response->assertOk();
        $response->assertSee('name="location_category"', false);
        $response->assertSee('id="loc_type_main"', false);
        $response->assertSee('id="loc_type_branch"', false);
        $response->assertSee('Laboratorium Utama');
        $response->assertSee('Laboratorium Cabang');
        $response->assertSee('id="assessment-lpk-location-id"', false);
        $response->assertSee('id="branch-select-wrapper"', false);
        $response->assertSee('id="branch-location-select"', false);
    }

    public function test_assessment_edit_form_prefills_location_for_branch(): void
    {
        $admin = User::factory()->admin()->create();
        $lpk = Lpk::factory()->create();
        $branch = $lpk->locations()->create([
            'name' => 'Laboratorium Cabang Surabaya',
            'location_type' => LpkLocation::TYPE_BRANCH_LAB,
            'address' => 'Jl. Rungkut Industri No. 10',
            'is_main' => false,
        ]);

        $assessment = Assessment::factory()->create([
            'lpk_id' => $lpk->id,
            'lpk_location_id' => $branch->id,
        ]);

        $response = $this->actingAs($admin)->get(route('assessments.edit', $assessment));

        $response->assertOk();
        $response->assertSee('value="'.$branch->id.'"', false);
        $response->assertSee('name="location_category"', false);
        $response->assertSee('Laboratorium Cabang');
    }
}
