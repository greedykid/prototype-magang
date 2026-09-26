<?php

namespace Tests\Feature;

use App\Models\Assessment;
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

        // 1. Visit calendar for July 2026: S1 Reminder (Month 13) should appear with amber theme
        $responseJuly = $this->actingAs($user)->get('/calendar?view=month&month=2026-07');
        $responseJuly->assertOk();
        $responseJuly->assertSee('Reminder');
        $responseJuly->assertSee('Reminder S1');
        $responseJuly->assertSee('Laboratorium Kalibrasi Uji Akurat');
        $responseJuly->assertSee('theme-amber');

        // 2. Visit calendar for December 2026: S1 Jatuh Tempo (Month 18) should appear with rose theme
        $responseDec = $this->actingAs($user)->get('/calendar?view=month&month=2026-12');
        $responseDec->assertOk();
        $responseDec->assertSee('Jatuh Tempo');
        $responseDec->assertSee('JT S1');
        $responseDec->assertSee('Laboratorium Kalibrasi Uji Akurat');
        $responseDec->assertSee('theme-rose');

        // 3. Visit calendar for October 2026: Expired_at milestone should appear
        $responseOct = $this->actingAs($user)->get('/calendar?view=month&month=2026-10');
        $responseOct->assertOk();
        $responseOct->assertSee('Kedaluwarsa');
        $responseOct->assertSee('Laboratorium Kalibrasi Uji Akurat');
        $responseOct->assertSee('theme-rose');

        // 4. Check agenda view displays the synchronized reminder milestone
        $responseAgenda = $this->actingAs($user)->get('/calendar?view=agenda&date=2026-07-15');
        $responseAgenda->assertOk();
        $responseAgenda->assertSee('Reminder');
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

    public function test_calendar_year_selector_dynamically_includes_years_from_lpk_expiry_dates(): void
    {
        $user = User::factory()->create();

        // Create an LPK with expiry date in year 2040
        Lpk::factory()->create([
            'expired_at' => '2040-08-17',
        ]);

        $response = $this->actingAs($user)->get('/calendar?view=month&month=2026-09');
        $response->assertOk();

        // The year 2040 should dynamically appear in the options
        $response->assertSee('<option value="2040">2040</option>', false);
    }

    public function test_calendar_year_selector_dynamically_includes_earlier_years_from_lpk_certificate_dates(): void
    {
        $user = User::factory()->create();

        // Create an archive LPK with certificate date in year 2014
        Lpk::factory()->create([
            'certificate_date' => '2014-05-20',
        ]);

        $response = $this->actingAs($user)->get('/calendar?view=month&month=2026-09');
        $response->assertOk();

        // The year 2014 should dynamically appear in the options
        $response->assertSee('<option value="2014">2014</option>', false);
    }

    public function test_calendar_renders_all_four_matrix_color_categories_and_reminders(): void
    {
        $user = User::factory()->create();
        $lpk = Lpk::factory()->create([
            'name' => 'PT Kalibrasi Mandiri Presisi',
            'certificate_date' => '2026-01-10',
        ]);

        // 1. Asesmen Pelaksanaan (emerald)
        $assessment = \App\Models\Assessment::create([
            'lpk_id' => $lpk->id,
            'created_by' => $user->id,
            'title' => 'Asesmen Lapangan S1 - PT Kalibrasi Mandiri Presisi',
            'assessment_type' => 'S1',
            'start_at' => '2026-09-10 09:00',
            'end_at' => '2026-09-12 17:00',
            'status' => 'COMPLETED',
            'tp_status' => \App\Models\Assessment::TP_STATUS_IN_PROGRESS,
        ]);

        // Visit calendar for September 2026: Pelaksanaan Asesmen (emerald)
        $responseSept = $this->actingAs($user)->get('/calendar?view=month&month=2026-09');
        $responseSept->assertOk();
        $responseSept->assertSee('theme-emerald');
        $responseSept->assertSee('S1');
        $responseSept->assertSee('PT Kalibrasi Mandiri Presisi');

        // Visit calendar for October 2026: Reminder TP (45 days from end_at: 2026-10-27) (amber)
        $responseOct = $this->actingAs($user)->get('/calendar?view=month&month=2026-10');
        $responseOct->assertOk();
        $responseOct->assertSee('theme-amber');
        $responseOct->assertSee('Reminder TP');
        $responseOct->assertSee('PT Kalibrasi Mandiri Presisi');

        // Visit calendar for November 2026: Batas Waktu TP (2 months from end_at: 2026-11-12) (cyan)
        $responseNov = $this->actingAs($user)->get('/calendar?view=month&month=2026-11');
        $responseNov->assertOk();
        $responseNov->assertSee('theme-cyan');
        $responseNov->assertSee('Batas TP');
        $responseNov->assertSee('PT Kalibrasi Mandiri Presisi');
    }

    public function test_calendar_partial_ajax_request_returns_calendar_shell_without_app_layout(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->get('/calendar?view=month&month=2026-10', [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-Partial-Content' => 'calendar',
        ]);

        $response->assertOk();
        $response->assertSee('class="gcal-shell"', false);
        $response->assertSee('data-active-date="2026-10-01"', false);
        $response->assertSee('data-month-label="Oktober 2026"', false);
        $response->assertSee('class="gcal-toolbar"', false);
        $response->assertSee('class="gcal-mini-cal"', false);
        // Verify full app layout shell is omitted
        $response->assertDontSee('<!DOCTYPE html>', false);
        $response->assertDontSee('id="app-sidebar"', false);
        $response->assertDontSee('id="modal-quick-add-event"', false);
    }

    public function test_calendar_partial_ajax_request_renders_events_in_month_view(): void
    {
        $user = User::factory()->create();
        $lpk = Lpk::factory()->create(['name' => 'Lab Penguji Presisi Utama']);

        CalendarEvent::create([
            'lpk_id' => $lpk->id,
            'title' => 'Rapat Evaluasi Survailen',
            'start_at' => '2026-10-12 09:00',
            'end_at' => '2026-10-12 11:00',
            'status' => 'PLANNED',
            'created_by' => $user->id,
        ]);

        $response = $this->actingAs($user)->get('/calendar?view=month&month=2026-10', [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-Partial-Content' => 'calendar',
        ]);

        $response->assertOk();
        $response->assertSee('Rapat Evaluasi Survailen');
        $response->assertSee('Lab Penguji Presisi Utama');
        $response->assertSee('class="gcal-month-grid"', false);
    }

    public function test_calendar_deep_link_with_date_and_highlight_selects_day_and_marks_target(): void
    {
        $user = User::factory()->create();
        $lpk = Lpk::factory()->create(['name' => 'Lab Penguji Presisi Riau']);

        $futureAssessment = Assessment::factory()->create([
            'lpk_id' => $lpk->id,
            'title' => 'Asesmen Re-Akreditasi (RA) - Lab Penguji Presisi Riau',
            'assessment_type' => 'Re-Akreditasi (Akreditasi Ulang)',
            'start_at' => '2027-11-22 09:00:00',
            'end_at' => '2027-11-25 17:00:00',
            'status' => 'PLANNED',
        ]);

        $response = $this->actingAs($user)->get("/calendar?view=month&date=2027-11-22&highlight={$futureAssessment->id}&selected=1");

        $response->assertOk();
        $response->assertSee('data-highlight-id="' . $futureAssessment->id . '"', false);
        $response->assertSee('data-active-date="2027-11-22"', false);
        $response->assertSee('data-month-label="November 2027"', false);
        $response->assertSee('is-highlight-target', false);
        $response->assertSee('is-selected', false);
    }

    public function test_calendar_deep_link_auto_resolves_future_date_from_assessment_highlight_alone(): void
    {
        $user = User::factory()->create();
        $lpk = Lpk::factory()->create(['name' => 'Lab Masa Depan KAN']);

        $distantAssessment = Assessment::factory()->create([
            'lpk_id' => $lpk->id,
            'title' => 'Asesmen Surveilen 2 (S2) - Lab Masa Depan KAN',
            'assessment_type' => 'Surveilen 2',
            'start_at' => '2028-06-15 09:00:00',
            'end_at' => '2028-06-18 17:00:00',
            'status' => 'PLANNED',
        ]);

        // Access calendar without date param, only highlight param
        $response = $this->actingAs($user)->get("/calendar?highlight=assessment-{$distantAssessment->id}");

        $response->assertOk();
        $response->assertSee('data-active-date="2028-06-15"', false);
        $response->assertSee('data-month-label="Juni 2028"', false);
        $response->assertSee('is-highlight-target', false);
    }

    public function test_lpk_show_view_renders_direct_calendar_deep_links_for_milestones(): void
    {
        $user = User::factory()->create();
        $lpk = Lpk::create([
            'registration_number' => 'LP-TEST-CAL-LINK',
            'name' => 'Lab Link Kalender Mandiri',
            'status' => 'ACTIVE',
            'certificate_date' => '2025-01-10',
            'expired_at' => '2030-01-10',
            'address' => 'Jl. Pengujian No. 1, Riau',
        ]);

        $response = $this->actingAs($user)->get(route('lpks.show', $lpk));

        $response->assertOk();
        $response->assertSee('/calendar?view=month&amp;date=', false);
        $response->assertSee('highlight=', false);
        $response->assertSee('Kalender', false);
    }
}
