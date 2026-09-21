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

    public function test_calendar_displays_lpk_surveillance_and_reaccreditation_milestones(): void
    {
        $user = User::factory()->create();

        $lpk = Lpk::factory()->create([
            'name' => 'Laboratorium Kalibrasi Uji Akurat',
            'certificate_date' => '2025-06-15',
            'expired_at' => '2026-10-20',
        ]);

        // 1. Visit calendar for September 2026: S1 milestone should appear
        $responseSept = $this->actingAs($user)->get('/calendar?view=month&month=2026-09');
        $responseSept->assertOk();
        $responseSept->assertSee('Jatuh Tempo Surveilen (S1/S2)');
        $responseSept->assertSee('Kedaluwarsa Akreditasi');
        $responseSept->assertSee('[S1] Surveilen 1: Laboratorium Kalibrasi Uji Akurat');
        $responseSept->assertSee('theme-amber');

        // 2. Visit calendar for October 2026: Expired_at milestone should appear
        $responseOct = $this->actingAs($user)->get('/calendar?view=month&month=2026-10');
        $responseOct->assertOk();
        $responseOct->assertSee('[Kedaluwarsa] Akreditasi: Laboratorium Kalibrasi Uji Akurat');
        $responseOct->assertSee('theme-rose');

        // 3. Check agenda view displays the synchronized milestone
        $responseAgenda = $this->actingAs($user)->get('/calendar?view=agenda&date=2026-09-15');
        $responseAgenda->assertOk();
        $responseAgenda->assertSee('Jatuh Tempo Surveilen');
        $responseAgenda->assertSee('Laboratorium Kalibrasi Uji Akurat');
    }

    public function test_calendar_renders_month_and_year_direct_selectors(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/calendar?view=month&month=2028-05');
        $response->assertOk();

        // Check selectors exist
        $response->assertSee('id="gcal-select-month"', false);
        $response->assertSee('id="gcal-select-year"', false);

        // Check selected month and year
        $response->assertSee('<option value="05" selected>Mei</option>', false);
        $response->assertSee('<option value="2028" selected>2028</option>', false);

        // Check future year options exist for KAN 5-year accreditation cycles
        $response->assertSee('<option value="2030">2030</option>', false);
        $response->assertSee('<option value="2035">2035</option>', false);
    }
}
