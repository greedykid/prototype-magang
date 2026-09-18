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
}
