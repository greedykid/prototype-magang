<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\CalendarEvent;
use App\Models\Lpk;
use App\Models\User;
use App\Services\LpkSurveillanceService;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Cache;
use Tests\TestCase;

class LpkCascadeDeleteTest extends TestCase
{
    use RefreshDatabase;

    public function test_deleting_lpk_cascades_and_removes_assessments_calendar_events_and_members(): void
    {
        $admin = User::factory()->admin()->create();
        $pic = User::factory()->pic()->create();
        $viewer = User::factory()->pic()->create();

        $lpk = Lpk::factory()->create([
            'name' => 'Lab Penguji Cascade',
            'pic_id' => $pic->id,
        ]);

        $assessment = Assessment::factory()->create([
            'lpk_id' => $lpk->id,
            'title' => 'Asesmen Cascade Test',
        ]);

        $calendarEvent = CalendarEvent::create([
            'lpk_id' => $lpk->id,
            'created_by' => $admin->id,
            'title' => 'Agenda Jadwal LPK',
            'event_type' => 'ASSESSMENT',
            'start_at' => now()->addDays(5),
            'end_at' => now()->addDays(6),
        ]);

        $lpk->members()->attach($viewer->id, ['role' => 'viewer']);

        $this->assertDatabaseHas('lpks', ['id' => $lpk->id]);
        $this->assertDatabaseHas('assessments', ['id' => $assessment->id]);
        $this->assertDatabaseHas('calendar_events', ['id' => $calendarEvent->id]);
        $this->assertDatabaseHas('lpk_members', ['lpk_id' => $lpk->id, 'user_id' => $viewer->id]);

        // Pre-populate alert cache to verify cache invalidation on delete
        $surveillanceService = app(LpkSurveillanceService::class);
        $cacheKey = $surveillanceService->getAlertsCacheKey($pic);
        Cache::put($cacheKey, ['dummy_alert'], 3600);
        $this->assertTrue(Cache::has($cacheKey));

        // Delete LPK
        $lpk->delete();

        // Verify LPK and its related entities are deleted
        $this->assertDatabaseMissing('lpks', ['id' => $lpk->id]);
        $this->assertDatabaseMissing('assessments', ['id' => $assessment->id]);
        $this->assertDatabaseMissing('calendar_events', ['id' => $calendarEvent->id]);
        $this->assertDatabaseMissing('lpk_members', ['lpk_id' => $lpk->id, 'user_id' => $viewer->id]);

        // Verify cache invalidation
        $this->assertFalse(Cache::has($cacheKey));
    }

    public function test_controller_destroy_endpoint_cascades_and_redirects(): void
    {
        $admin = User::factory()->admin()->create();
        $lpk = Lpk::factory()->create([
            'name' => 'Lab Penguji Endpoint Test',
            'pic_id' => $admin->id,
        ]);

        $assessment = Assessment::factory()->create([
            'lpk_id' => $lpk->id,
            'title' => 'Asesmen Endpoint Test',
        ]);

        $response = $this->actingAs($admin)->delete(route('lpks.destroy', $lpk));
        $response->assertRedirect(route('lpks.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('lpks', ['id' => $lpk->id]);
        $this->assertDatabaseMissing('assessments', ['id' => $assessment->id]);
    }
}
