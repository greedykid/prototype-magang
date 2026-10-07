<?php

namespace Tests\Feature;

use App\Models\CalendarEvent;
use App\Models\Lpk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Http\UploadedFile;
use Tests\TestCase;

class SecurityHardeningTest extends TestCase
{
    use RefreshDatabase;

    protected User $admin;
    protected User $picA;
    protected User $picB;

    protected function setUp(): void
    {
        parent::setUp();

        $this->admin = User::factory()->create([
            'email' => 'admin@simasadi.local',
            'role' => User::ROLE_ADMIN,
        ]);

        $this->picA = User::factory()->create([
            'email' => 'pica@simasadi.local',
            'role' => User::ROLE_PIC,
        ]);

        $this->picB = User::factory()->create([
            'email' => 'picb@simasadi.local',
            'role' => User::ROLE_PIC,
        ]);
    }

    public function test_security_headers_are_present(): void
    {
        $response = $this->get('/login');

        $response->assertOk();
        $response->assertHeader('X-Frame-Options', 'SAMEORIGIN');
        $response->assertHeader('X-Content-Type-Options', 'nosniff');
        $response->assertHeader('Referrer-Policy', 'strict-origin-when-cross-origin');
        $response->assertHeader('Permissions-Policy', 'camera=(), microphone=(), geolocation=()');
    }

    public function test_ssrf_blocked_on_lpk_import(): void
    {
        $response = $this->actingAs($this->picA)->post(route('lpks.import'), [
            'sheets_url' => 'http://127.0.0.1:8000/internal-secret',
        ]);

        $response->assertSessionHas('error');
    }

    public function test_ssrf_blocked_on_assessment_import(): void
    {
        $response = $this->actingAs($this->picA)->post(route('assessments.import'), [
            'sheets_url' => 'http://169.254.169.254/latest/meta-data/',
        ]);

        $response->assertSessionHas('error');
    }

    public function test_pic_cannot_overwrite_another_pic_lpk_via_batch_import(): void
    {
        $targetLpk = Lpk::factory()->create([
            'pic_id' => $this->picB->id,
            'registration_number' => 'LP-999-IDN',
            'name' => 'Original LPK Name',
        ]);

        $csvContent = "registration_number,name\nLP-999-IDN,Hacked Name By Pic A\n";
        $file = UploadedFile::fake()->createWithContent('import.csv', $csvContent);

        $response = $this->actingAs($this->picA)->post(route('lpks.import'), [
            'csv_file' => $file,
        ]);

        $response->assertRedirect();
        $this->assertEquals('Original LPK Name', $targetLpk->fresh()->name);
    }

    public function test_pic_cannot_create_calendar_event_for_unauthorized_lpk(): void
    {
        $otherLpk = Lpk::factory()->create([
            'pic_id' => $this->picB->id,
        ]);

        $response = $this->actingAs($this->picA)->post(route('calendar.events.store'), [
            'lpk_id' => $otherLpk->id,
            'title' => 'Unauthorized Event',
            'start_at' => now()->addDay()->format('Y-m-d H:i:s'),
            'end_at' => now()->addDays(2)->format('Y-m-d H:i:s'),
            'status' => 'PLANNED',
        ]);

        $response->assertStatus(403);
    }

    public function test_pic_cannot_edit_calendar_event_of_another_pic_lpk(): void
    {
        $lpkB = Lpk::factory()->create([
            'pic_id' => $this->picB->id,
        ]);

        $event = CalendarEvent::create([
            'lpk_id' => $lpkB->id,
            'title' => 'Meeting for LPK B',
            'start_at' => now()->addDay()->setTime(9, 0),
            'end_at' => now()->addDay()->setTime(10, 0),
            'status' => 'PLANNED',
            'created_by' => $this->picB->id,
        ]);

        $response = $this->actingAs($this->picA)->get(route('calendar.events.edit', $event));
        $response->assertStatus(403);

        $updateResponse = $this->actingAs($this->picA)->put(route('calendar.events.update', $event), [
            'lpk_id' => $lpkB->id,
            'title' => 'Tampered Title',
            'start_at' => now()->addDay()->setTime(9, 0)->format('Y-m-d H:i:s'),
            'end_at' => now()->addDay()->setTime(10, 0)->format('Y-m-d H:i:s'),
            'status' => 'PLANNED',
        ]);
        $updateResponse->assertStatus(403);

        $adminResponse = $this->actingAs($this->admin)->get(route('calendar.events.edit', $event));
        $adminResponse->assertOk();
    }

    public function test_csv_export_sanitizes_formula_injection(): void
    {
        Lpk::factory()->create([
            'name' => '=HYPERLINK("http://evil.com","Click")',
            'registration_number' => '+62812345678',
            'scope' => '@SUM(1+1)',
            'status' => 'ACTIVE',
        ]);

        $response = $this->actingAs($this->admin)->get(route('reports.lpks.export'));
        $content = $response->streamedContent();

        $this->assertStringContainsString("'=HYPERLINK", $content);
        $this->assertStringContainsString("'+62812345678", $content);
        $this->assertStringContainsString("'@SUM(1+1)", $content);
    }

    public function test_login_rate_limiting_triggers(): void
    {
        for ($i = 0; $i < 10; $i++) {
            $this->post(route('login.store'), [
                'email' => 'wrong@test.local',
                'password' => 'wrong-pass',
            ]);
        }

        $throttledResponse = $this->post(route('login.store'), [
            'email' => 'wrong@test.local',
            'password' => 'wrong-pass',
        ]);

        $throttledResponse->assertStatus(429);
    }

    public function test_feed_endpoint_rejects_default_key_on_staging(): void
    {
        $this->app['env'] = 'staging';

        $response = $this->get(route('feeds.lpks', ['key' => 'simasadi-live']));
        $response->assertStatus(401);

        $this->app['env'] = 'testing';
    }

    public function test_audit_logging_triggered_on_bulk_delete(): void
    {
        $lpk = Lpk::factory()->create(['name' => 'Lab Audit Log Test']);

        \Illuminate\Support\Facades\Log::shouldReceive('info')
            ->atLeast()->once()
            ->withArgs(function ($message, $context) use ($lpk) {
                return $message === 'LPKs bulk deleted'
                    && in_array($lpk->id, $context['deleted_ids'] ?? [], true);
            });

        $response = $this->actingAs($this->admin)->post(route('lpks.bulk-destroy'), [
            'ids' => [$lpk->id],
        ]);

        $response->assertRedirect();
        $this->assertDatabaseMissing('lpks', ['id' => $lpk->id]);
    }
}

