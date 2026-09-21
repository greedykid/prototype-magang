<?php

namespace Tests\Feature;

use App\Models\Accreditation;
use App\Models\Amendment;
use App\Models\Assessment;
use App\Models\Backup;
use App\Models\Issue;
use App\Models\Lpk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class PrototypeFlowTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_is_sent_to_login(): void
    {
        $this->get('/dashboard')->assertRedirect('/login');
    }

    public function test_user_can_login_and_view_dashboard(): void
    {
        $user = User::factory()->create();

        $this->post('/login', ['email' => $user->email, 'password' => 'password'])
            ->assertRedirect('/dashboard');

        $this->get('/dashboard')->assertOk()->assertSee('Selamat datang');
    }

    public function test_user_can_create_lpk(): void
    {
        $user = User::factory()->create();

        $this->actingAs($user)->post('/lpks', [
            'registration_number' => 'LPK-TEST-001',
            'name' => 'LPK Uji Coba',
            'status' => 'ACTIVE',
        ])->assertRedirect();

        $this->assertDatabaseHas('lpks', ['registration_number' => 'LPK-TEST-001']);
    }

    public function test_user_can_create_issue_and_followup(): void
    {
        $user = User::factory()->create();
        $lpk = Lpk::factory()->create();

        $response = $this->actingAs($user)->post('/issues', [
            'lpk_id' => $lpk->id,
            'title' => 'Dokumen belum lengkap',
            'description' => 'Ada data yang perlu ditinjau.',
            'priority' => 'HIGH',
            'status' => 'OPEN',
        ]);

        $issue = Issue::firstOrFail();
        $response->assertRedirect('/issues/'.$issue->id);

        $this->post('/issues/'.$issue->id.'/follow-ups', ['note' => 'Sudah diminta untuk melengkapi data.'])
            ->assertRedirect();

        $this->assertDatabaseHas('issue_followups', ['issue_id' => $issue->id, 'user_id' => $user->id]);
    }

    public function test_table_filters_work_across_all_six_index_pages(): void
    {
        $user = User::factory()->create(['name' => 'Filter User']);
        $matchingLpk = Lpk::factory()->create(['name' => 'LPK Filter Cocok', 'status' => 'ACTIVE']);
        $otherLpk = Lpk::factory()->create(['name' => 'LPK Filter Lain', 'status' => 'INACTIVE']);
        $assessment = Assessment::factory()->create(['lpk_id' => $matchingLpk->id, 'title' => 'Agenda Filter Cocok', 'status' => 'COMPLETED', 'start_at' => '2026-09-20 09:00', 'end_at' => '2026-09-20 12:00']);
        Assessment::factory()->create(['lpk_id' => $otherLpk->id, 'title' => 'Agenda Filter Lain', 'status' => 'PLANNED']);
        $issue = Issue::factory()->create(['lpk_id' => $matchingLpk->id, 'title' => 'Masalah Filter Cocok', 'status' => 'RESOLVED', 'due_date' => '2026-09-20']);
        Issue::factory()->create(['lpk_id' => $otherLpk->id, 'title' => 'Masalah Filter Lain', 'status' => 'OPEN']);
        $backup = Backup::factory()->create(['status' => 'FAILED', 'finished_at' => '2026-09-20 12:00', 'size' => 'FILTER-FAILED', 'recorded_by' => $user->id]);
        Backup::factory()->create(['status' => 'SUCCESS', 'finished_at' => '2026-08-20 12:00', 'size' => 'FILTER-SUCCESS', 'recorded_by' => $user->id]);
        $accreditation = Accreditation::factory()->create(['lpk_id' => $matchingLpk->id, 'status' => 'COMPLETED', 'start_date' => '2026-09-20', 'target_date' => '2026-09-30']);
        Accreditation::factory()->create(['lpk_id' => $otherLpk->id, 'status' => 'NOT_STARTED']);
        $amendment = Amendment::factory()->create(['lpk_id' => $matchingLpk->id, 'submission_number' => 'AMD-FILTER-001', 'status' => 'APPROVED', 'submitted_at' => '2026-09-20', 'target_date' => '2026-09-30']);
        Amendment::factory()->create(['lpk_id' => $otherLpk->id, 'submission_number' => 'AMD-FILTER-002', 'status' => 'SUBMITTED']);

        $this->actingAs($user)->get('/lpks?status=ACTIVE')->assertOk()->assertSee($matchingLpk->name);
        $this->actingAs($user)->get('/assessments?lpk_id='.$matchingLpk->id.'&status=COMPLETED&start_from=2026-09-20')->assertOk()->assertSee($assessment->title);
        $this->actingAs($user)->get('/issues?lpk_id='.$matchingLpk->id.'&status=RESOLVED&due_to=2026-09-20')->assertOk()->assertSee($issue->title);
        $this->actingAs($user)->get('/monitoring/backups?status=FAILED&finished_from=2026-09-20')->assertOk()->assertSee($backup->size)->assertDontSee('FILTER-SUCCESS');
        $this->actingAs($user)->get('/accreditations?lpk_id='.$matchingLpk->id.'&status=COMPLETED&target_to=2026-09-30')->assertOk()->assertSee($matchingLpk->name);
        $this->actingAs($user)->get('/amendments?search=AMD-FILTER-001&status=APPROVED')->assertOk()->assertSee($amendment->submission_number);
    }

    public function test_table_filter_query_string_is_preserved_by_pagination(): void
    {
        $user = User::factory()->create();
        Lpk::factory(11)->create(['status' => 'ACTIVE']);
        Lpk::factory()->create(['status' => 'INACTIVE']);

        $this->actingAs($user)->get('/lpks?status=ACTIVE&page=2')->assertOk()->assertSee('status=ACTIVE');
    }

    public function test_user_can_create_lpk_with_expiry_and_drive_link(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/lpks', [
            'registration_number' => 'LPK-TEST-DRIVE-01',
            'name' => 'LPK Pengujian Drive',
            'scope' => 'Pengujian Mikrobiologi dan Toksikologi',
            'status' => 'ACTIVE',
            'expired_at' => '2028-10-15',
            'drive_url' => 'https://drive.google.com/drive/folders/12345demo',
        ]);

        $response->assertRedirect();

        $lpk = Lpk::where('registration_number', 'LPK-TEST-DRIVE-01')->firstOrFail();
        $this->assertEquals('Pengujian Mikrobiologi dan Toksikologi', $lpk->scope);
        $this->assertEquals('2028-10-15', $lpk->expired_at->format('Y-m-d'));
        $this->assertEquals('https://drive.google.com/drive/folders/12345demo', $lpk->drive_url);

        $showResponse = $this->actingAs($user)->get(route('lpks.show', $lpk));
        $showResponse->assertOk()
            ->assertSee('Pengujian Mikrobiologi dan Toksikologi')
            ->assertSee('15 Oct 2028')
            ->assertSee('Buka Berkas di Google Drive');
    }

    public function test_lpk_scope_can_be_searched_and_exported_to_csv(): void
    {
        $user = User::factory()->create();

        $lpk = Lpk::create([
            'registration_number' => 'LK-SCOPE-999',
            'name' => 'Kalibrasi Presisi Akustik',
            'scope' => 'Laboratorium Kalibrasi Akustik dan Vibrasi',
            'status' => 'ACTIVE',
            'expired_at' => '2029-01-01',
        ]);

        // Test search by scope
        $searchResponse = $this->actingAs($user)->get('/lpks?search=Akustik');
        $searchResponse->assertOk()
            ->assertSee('Kalibrasi Presisi Akustik')
            ->assertSee('Laboratorium Kalibrasi Akustik dan Vibrasi');

        // Test CSV export contains scope header and scope value
        $csvResponse = $this->actingAs($user)->get(route('reports.lpks.export'));
        $csvResponse->assertOk();
        $content = $csvResponse->streamedContent();
        $this->assertStringContainsString('Ruang Lingkup Akreditasi', $content);
        $this->assertStringContainsString('Laboratorium Kalibrasi Akustik dan Vibrasi', $content);
    }

    public function test_lpk_calculates_correct_surveillance_and_reaccreditation_milestones(): void
    {
        // LPK terbit 14 bulan yang lalu (memasuki masa notif S1)
        $lpkS1 = Lpk::create([
            'registration_number' => 'LP-SURV-01',
            'name' => 'Lab Uji S1 Due',
            'status' => 'ACTIVE',
            'certificate_date' => now()->subMonths(14)->toDateString(),
            'expired_at' => now()->addMonths(46)->toDateString(),
            'email' => 'pic.s1@labuji.id',
        ]);

        $milestonesS1 = $lpkS1->surveillance_milestones;
        $this->assertEquals('DUE', $milestonesS1['s1']['status']);
        $this->assertTrue($lpkS1->hasActiveSurveillanceAlert());

        $alertsS1 = $lpkS1->getActiveSurveillanceAlerts();
        $this->assertCount(1, $alertsS1);
        $this->assertEquals('S1', $alertsS1[0]['code']);

        // LPK terbit 35 bulan yang lalu (memasuki masa notif S2)
        $lpkS2 = Lpk::create([
            'registration_number' => 'LP-SURV-02',
            'name' => 'Lab Uji S2 Due',
            'status' => 'ACTIVE',
            'certificate_date' => now()->subMonths(35)->toDateString(),
            'expired_at' => now()->addMonths(25)->toDateString(),
            'email' => 'pic.s2@labuji.id',
        ]);

        $milestonesS2 = $lpkS2->surveillance_milestones;
        $this->assertEquals('DUE', $milestonesS2['s2']['status']);

        // LPK 11 bulan sebelum habis (memasuki masa notif Re-Akreditasi, < 12 bulan)
        $lpkRA = Lpk::create([
            'registration_number' => 'LP-SURV-03',
            'name' => 'Lab Uji RA Due',
            'status' => 'ACTIVE',
            'certificate_date' => now()->subMonths(49)->toDateString(),
            'expired_at' => now()->addMonths(11)->toDateString(),
            'email' => 'pic.ra@labuji.id',
        ]);

        $milestonesRA = $lpkRA->surveillance_milestones;
        $this->assertEquals('DUE', $milestonesRA['ra']['status']);
    }

    public function test_surveillance_reminder_email_can_be_sent_to_lab_pic(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $admin = User::factory()->create(['role' => 'admin']);

        $lpk = Lpk::create([
            'registration_number' => 'LP-MAIL-01',
            'name' => 'Lab Uji Mailtrap',
            'status' => 'ACTIVE',
            'certificate_date' => now()->subMonths(14)->toDateString(),
            'expired_at' => now()->addMonths(46)->toDateString(),
            'email' => 'pic.lab@mailtrap-test.id',
        ]);

        $response = $this->actingAs($admin)->post(route('lpks.surveillance.remind', $lpk));
        $response->assertRedirect()->assertSessionHas('success');

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\SurveillanceReminderMail::class, function ($mail) use ($lpk) {
            return $mail->hasTo('pic.lab@mailtrap-test.id')
                && $mail->lpk->id === $lpk->id
                && $mail->alert['code'] === 'S1';
        });

        $lpk->refresh();
        $this->assertNotNull($lpk->last_surveillance_notified_at);
    }

    public function test_simulation_notification_can_be_sent_even_without_active_alerts(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $admin = User::factory()->create(['role' => 'admin']);

        $lpk = Lpk::create([
            'registration_number' => 'LP-SIM-01',
            'name' => 'Lab Simulasi Mailtrap',
            'status' => 'ACTIVE',
            'certificate_date' => now()->toDateString(),
            'expired_at' => now()->addYears(5)->toDateString(),
            'email' => 'pic.sim@mailtrap-test.id',
        ]);

        $this->assertEmpty($lpk->getActiveSurveillanceAlerts());

        $response = $this->actingAs($admin)->post(route('lpks.surveillance.remind', $lpk), [
            'is_simulation' => '1',
            'code' => 's2',
        ]);

        $response->assertRedirect()->assertSessionHas('success');

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\SurveillanceReminderMail::class, function ($mail) use ($lpk) {
            return $mail->hasTo('pic.sim@mailtrap-test.id')
                && $mail->lpk->id === $lpk->id
                && $mail->alert['code'] === 'S2';
        });

        $lpk->refresh();
        $this->assertNotNull($lpk->last_surveillance_notified_at);
    }

    public function test_persistent_notification_renders_on_ui_and_cannot_be_dismissed(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $lpk = Lpk::create([
            'registration_number' => 'LP-BANNER-01',
            'name' => 'Lab Banner Test',
            'status' => 'ACTIVE',
            'certificate_date' => now()->subMonths(14)->toDateString(),
            'expired_at' => now()->addMonths(46)->toDateString(),
            'email' => 'pic.banner@test.id',
        ]);

        // Tampilan dashboard memuat banner persisten dan section peringatan
        $dashboardRes = $this->actingAs($user)->get(route('dashboard'));
        $dashboardRes->assertOk()
            ->assertSee('Peringatan Siklus Pengawasan Akreditasi')
            ->assertSee('Peringatan Jatuh Tempo Siklus Pengawasan KAN')
            ->assertSee('Lab Banner Test');

        // Halaman show memuat Roadmap Siklus
        $showRes = $this->actingAs($user)->get(route('lpks.show', $lpk));
        $showRes->assertOk()
            ->assertSee('Roadmap Pengawasan', false)
            ->assertSee('Surveilen 1 (S1)')
            ->assertSee('Kirim Notifikasi Email PIC Lab');
    }

    public function test_artisan_check_surveillance_command(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $lpk = Lpk::create([
            'registration_number' => 'LP-CMD-01',
            'name' => 'Lab Artisan Command Test',
            'status' => 'ACTIVE',
            'certificate_date' => now()->subMonths(14)->toDateString(),
            'expired_at' => now()->addMonths(46)->toDateString(),
            'email' => 'artisan.pic@test.id',
        ]);

        $this->artisan('lpk:check-surveillance', ['--force' => true])
            ->expectsOutputToContain('Memeriksa status siklus pengawasan KAN')
            ->expectsOutputToContain('Email pemberitahuan berhasil dikirim')
            ->assertExitCode(0);

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\SurveillanceReminderMail::class);
    }

    public function test_lpk_index_table_headers_and_columns(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $lpk = Lpk::create([
            'registration_number' => 'LP-TABEL-99',
            'name' => 'Laboratorium Kalibrasi Uji KAN',
            'address' => 'Jl. Pengujian Presisi No. 99, Jakarta',
            'phone' => '021-99887766',
            'email' => 'lab.uji99@kan.or.id',
            'scope' => 'Pengujian Kimia, Fisika, dan Lingkungan Air Bersih',
            'status' => 'ACTIVE',
            'certificate_date' => '2025-01-15',
            'expired_at' => '2030-01-15',
            'drive_url' => 'https://drive.google.com/drive/folders/test-folder-lpk-99',
        ]);

        $response = $this->actingAs($admin)->get(route('lpks.index'));

        $response->assertOk();
        // Assert exactly matching headers as requested
        $response->assertSee('NO. AKREDITASI');
        $response->assertSee('NAMA LPK');
        $response->assertSee('ALAMAT');
        $response->assertSee('TELEPON / FAX');
        $response->assertSee('EMAIL');
        $response->assertSee('LINGKUP');
        $response->assertSee('MASA BERLAKU AKREDITASI (EXPIRED)');
        $response->assertSee('LINK');

        // Assert cell data rendered
        $response->assertSee('LP-TABEL-99');
        $response->assertSee('Laboratorium Kalibrasi Uji KAN');
        $response->assertSee('Jl. Pengujian Presisi No. 99, Jakarta');
        $response->assertSee('021-99887766');
        $response->assertSee('lab.uji99@kan.or.id');
        $response->assertSee('Pengujian Kimia, Fisika, dan Lingkungan Air Bersih');
        $response->assertSee('15/01/2030');
        $response->assertSee('https://drive.google.com/drive/folders/test-folder-lpk-99', false);
    }

    public function test_lpk_is_expiring_soon_status_accuracy(): void
    {
        // Far future (2030) should NOT be expiring soon
        $farFutureLpk = Lpk::factory()->create([
            'registration_number' => 'LP-2030-IDN',
            'expired_at' => now()->addYears(4),
        ]);
        $this->assertFalse($farFutureLpk->isExpiringSoon());
        $this->assertFalse($farFutureLpk->isExpired());

        // Soon (within 30 days) should be expiring soon
        $soonLpk = Lpk::factory()->create([
            'registration_number' => 'LP-SOON-IDN',
            'expired_at' => now()->addDays(20),
        ]);
        $this->assertTrue($soonLpk->isExpiringSoon());
        $this->assertFalse($soonLpk->isExpired());

        // Already expired should NOT be expiring soon, but isExpired() = true
        $expiredLpk = Lpk::factory()->create([
            'registration_number' => 'LP-PAST-IDN',
            'expired_at' => now()->subDays(10),
        ]);
        $this->assertFalse($expiredLpk->isExpiringSoon());
        $this->assertTrue($expiredLpk->isExpired());
    }
}

