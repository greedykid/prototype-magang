<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Lpk;
use App\Models\User;
use App\Services\LpkSurveillanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class GlobalSearchTest extends TestCase
{
    use RefreshDatabase;

    public function test_pic_user_can_perform_global_search_without_undefined_variable_error(): void
    {
        $pic = User::factory()->create([
            'role' => User::ROLE_PIC,
        ]);

        $lpk = Lpk::factory()->create([
            'name' => 'Laboratorium Pengujian Kalibrasi Mandiri',
            'pic_id' => $pic->id,
        ]);

        $response = $this->actingAs($pic)->getJson(route('search.live', ['q' => 'Kalibrasi']));

        $response->assertOk();
        $response->assertJsonStructure([
            'lpks',
            'assessments',
            'schedules',
            'accounts',
            'total',
            'view_all_url',
            'view_all_label',
        ]);
        $response->assertJsonFragment([
            'name' => 'Laboratorium Pengujian Kalibrasi Mandiri',
        ]);
    }

    public function test_assessment_team_longer_than_100_characters_saves_safely_without_truncation_crash(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $lpk = Lpk::factory()->create(['pic_id' => $admin->id]);

        $longTeamText = 'Dr. Ir. Ahmad Dahlan, M.Sc (Lead Assessor), Ir. Bambang Suryono, MT (Technical Assessor - ISO 17025), Dr. Siti Rahmawati, M.Si (Expert)';
        $this->assertGreaterThan(100, strlen($longTeamText));

        $response = $this->actingAs($admin)->post(route('assessments.store'), [
            'lpk_id' => $lpk->id,
            'title' => 'Asesmen Lapangan Khusus Kalibrasi',
            'assessment_type' => 'Surveilen 1',
            'start_at' => now()->addDays(5)->format('Y-m-d\TH:i'),
            'end_at' => now()->addDays(7)->format('Y-m-d\TH:i'),
            'location' => 'Laboratorium Sentral',
            'assessment_team' => $longTeamText,
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $assessment = Assessment::where('title', 'like', '%Asesmen Lapangan Khusus Kalibrasi%')->first();
        $this->assertNotNull($assessment);
        $this->assertEquals($longTeamText, $assessment->assessment_team);
        $this->assertLessThanOrEqual(100, strlen($assessment->lead_assessor));
    }

    public function test_cancelled_or_suspended_assessment_is_not_overwritten_to_completed_by_surveillance_sync(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $lpk = Lpk::factory()->create([
            'pic_id' => $admin->id,
            'certificate_date' => now()->subYears(4),
            'expired_at' => now()->addYear(),
        ]);

        $cancelledAssessment = Assessment::create([
            'lpk_id' => $lpk->id,
            'created_by' => $admin->id,
            'title' => 'Asesmen Surveilen 1 (S1) - Dibatalkan Resmi',
            'assessment_type' => Assessment::TYPE_SURVEILEN_1,
            'start_at' => now()->subMonths(30),
            'end_at' => now()->subMonths(30)->addDays(2),
            'status' => 'CANCELLED',
            'tp_status' => Assessment::TP_STATUS_NONE,
        ]);

        app(LpkSurveillanceService::class)->generateAssessments($lpk, $admin->id);

        $fresh = $cancelledAssessment->fresh();
        $this->assertEquals('CANCELLED', $fresh->getRawOriginal('status'));
        $this->assertNotEquals('COMPLETED', $fresh->getRawOriginal('status'));
    }
}
