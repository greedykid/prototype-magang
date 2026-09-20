<?php

namespace Tests\Feature;

use App\Models\CalendarEvent;
use App\Models\Lpk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class CalendarEventTest extends TestCase
{
    use RefreshDatabase;

    public function test_authenticated_user_can_view_calendar(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->get('/calendar')->assertOk()->assertSee('Kalender kegiatan');
    }

    public function test_authenticated_user_can_create_calendar_event(): void
    {
        $user = User::factory()->create();
        $lpk = Lpk::factory()->create();

        $response = $this->actingAs($user)->post('/calendar/events', [
            'lpk_id' => $lpk->id,
            'title' => 'Persiapan asesmen awal',
            'start_at' => '2026-09-21 09:00',
            'end_at' => '2026-09-21 11:00',
            'status' => 'PLANNED',
        ]);

        $event = CalendarEvent::firstOrFail();
        $response->assertRedirect('/calendar/events/'.$event->id);
        $this->assertDatabaseHas('calendar_events', ['title' => 'Persiapan asesmen awal', 'created_by' => $user->id]);
    }

    public function test_calendar_date_opens_create_form_with_selected_date(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)
            ->get('/calendar/events/create?date=2026-09-21')
            ->assertOk()
            ->assertSee('name="start_date" value="2026-09-21"', false)
            ->assertSee('name="end_date" value="2026-09-21"', false)
            ->assertSee('name="start_time"', false)
            ->assertSee('name="end_time"', false);
    }

    public function test_event_rejects_end_time_before_start_time(): void
    {
        $user = User::factory()->create();
        $lpk = Lpk::factory()->create();

        $this->actingAs($user)->from('/calendar/events/create')->post('/calendar/events', [
            'lpk_id' => $lpk->id,
            'title' => 'Waktu tidak valid',
            'start_at' => '2026-09-21 11:00',
            'end_at' => '2026-09-21 09:00',
            'status' => 'PLANNED',
        ])->assertSessionHasErrors('end_at');
    }

    public function test_calendar_month_navigation_does_not_carry_over_circle_highlight_unless_selected(): void
    {
        $user = User::factory()->create();

        // 1. When browsing months via navigation (e.g. month=2026-10)
        $response = $this->actingAs($user)->get('/calendar?view=month&month=2026-10');
        $response->assertOk();

        // Month navigation arrows should use month parameter
        $response->assertSee('href="http://localhost:8000/calendar?view=month&amp;month=2026-11" class="button ghost gcal-arrow-btn" aria-label="Berikutnya"', false);
        $response->assertSee('href="http://localhost:8000/calendar?view=month&amp;month=2026-11" class="gcal-mini-nav-btn" aria-label="Bulan berikutnya"', false);
        $response->assertSee('href="http://localhost:8000/calendar?view=month&amp;month=2026-09" class="button ghost gcal-arrow-btn" aria-label="Sebelumnya"', false);
        $response->assertSee('href="http://localhost:8000/calendar?view=month&amp;month=2026-09" class="gcal-mini-nav-btn" aria-label="Bulan sebelumnya"', false);

        // No date cell in the mini calendar should have 'active' class (no circle highlight)
        $response->assertDontSee('gcal-mini-cell active', false);
        $response->assertDontSee('gcal-mini-cell  active', false);

        // 2. When user explicitly selects/clicks a specific date (e.g. date=2026-10-15&selected=1)
        $selectedResponse = $this->actingAs($user)->get('/calendar?view=month&date=2026-10-15&selected=1');
        $selectedResponse->assertOk();

        // Only the clicked date should have the active circle highlight
        $selectedResponse->assertSee('href="http://localhost:8000/calendar?view=month&amp;date=2026-10-15&amp;selected=1"', false);
        $selectedResponse->assertSee('active', false);

        // 3. When viewing current month, today has the 'today' class with the circle highlight
        $todayResponse = $this->actingAs($user)->get('/calendar');
        $todayResponse->assertOk();
        $todayResponse->assertSee('today', false);
    }
}
