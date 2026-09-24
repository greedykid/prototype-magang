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

        $this->get('/dashboard')->assertOk()
            ->assertSee('Selamat datang')
            ->assertSee('Asesmen Terdekat')
            ->assertSee('Finansial &amp; Administrasi', false)
            ->assertSee('Keandalan Sistem')
            ->assertSee('Semua Aman &amp; Kepatuhan Terkendali', false);
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

        // LPK kurang dari 1 bulan sebelum habis (memasuki masa notif Re-Akreditasi, < 1 bulan)
        $lpkRA = Lpk::create([
            'registration_number' => 'LP-SURV-03',
            'name' => 'Lab Uji RA Due',
            'status' => 'ACTIVE',
            'certificate_date' => now()->subYears(5)->addDays(20)->toDateString(),
            'expired_at' => now()->addDays(20)->toDateString(),
            'email' => 'pic.ra@labuji.id',
        ]);

        $milestonesRA = $lpkRA->surveillance_milestones;
        $this->assertEquals('DUE', $milestonesRA['ra']['status']);
    }

    public function test_surveillance_reminder_email_can_be_sent_to_lab_pic(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $picUser = User::factory()->create(['role' => 'pic', 'email' => 'pic.internal@bsn.go.id']);

        $lpk = Lpk::create([
            'registration_number' => 'LP-MAIL-01',
            'name' => 'Lab Uji Mailtrap',
            'status' => 'ACTIVE',
            'certificate_date' => now()->subMonths(14)->toDateString(),
            'expired_at' => now()->addMonths(46)->toDateString(),
            'email' => 'lab.external@mailtrap-test.id',
        ]);

        $response = $this->actingAs($admin)->post(route('lpks.surveillance.remind', $lpk));
        $response->assertRedirect()->assertSessionHas('success');

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\SurveillanceReminderMail::class, function ($mail) use ($lpk, $picUser) {
            return $mail->hasTo($picUser->email)
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
        $picUser = User::factory()->create(['role' => 'pic', 'email' => 'pic.sim@simasadi.local']);

        $lpk = Lpk::create([
            'registration_number' => 'LP-SIM-01',
            'name' => 'Lab Simulasi Mailtrap',
            'status' => 'ACTIVE',
            'certificate_date' => now()->toDateString(),
            'expired_at' => now()->addYears(5)->toDateString(),
            'email' => 'lab.sim.external@mailtrap-test.id',
        ]);

        $this->assertEmpty($lpk->getActiveSurveillanceAlerts());

        $response = $this->actingAs($admin)->post(route('lpks.surveillance.remind', $lpk), [
            'is_simulation' => '1',
            'code' => 's2',
        ]);

        $response->assertRedirect()->assertSessionHas('success');

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\SurveillanceReminderMail::class, function ($mail) use ($lpk, $picUser) {
            return $mail->hasTo($picUser->email)
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

        // Halaman show memuat Siklus Pengawasan
        $showRes = $this->actingAs($user)->get(route('lpks.show', $lpk));
        $showRes->assertOk()
            ->assertSee('Siklus Pengawasan', false)
            ->assertSee('Surveilen 1 (S1)')
            ->assertSee('Kirim Notifikasi Email PIC Lab');
    }

    public function test_artisan_check_surveillance_command(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $picUser = User::factory()->create(['role' => 'pic', 'email' => 'artisan.pic@bsn.go.id']);

        $lpk = Lpk::create([
            'registration_number' => 'LP-CMD-01',
            'name' => 'Lab Artisan Command Test',
            'status' => 'ACTIVE',
            'certificate_date' => now()->subMonths(14)->toDateString(),
            'expired_at' => now()->addMonths(46)->toDateString(),
            'email' => 'lab.external@test.id',
        ]);

        $this->artisan('lpk:check-surveillance', ['--force' => true])
            ->expectsOutputToContain('Memeriksa status siklus pengawasan KAN')
            ->expectsOutputToContain('Email pengingat internal berhasil dikirim ke PIC')
            ->assertExitCode(0);

        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\SurveillanceReminderMail::class, function ($mail) use ($picUser) {
            return $mail->hasTo($picUser->email);
        });
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

    public function test_lpk_index_filters_by_expiry_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        $validLpk = Lpk::create([
            'registration_number' => 'LP-VAL-01',
            'name' => 'Lab Sertifikat Valid',
            'status' => 'ACTIVE',
            'certificate_date' => now()->subYear()->toDateString(),
            'expired_at' => now()->addYears(4)->toDateString(),
        ]);

        $soonLpk = Lpk::create([
            'registration_number' => 'LP-SOON-01',
            'name' => 'Lab Mendekati Expired',
            'status' => 'ACTIVE',
            'certificate_date' => now()->subYears(4)->toDateString(),
            'expired_at' => now()->addDays(30)->toDateString(),
        ]);

        $expiredLpk = Lpk::create([
            'registration_number' => 'LP-EXP-01',
            'name' => 'Lab Sudah Expired',
            'status' => 'ACTIVE',
            'certificate_date' => now()->subYears(5)->subMonth()->toDateString(),
            'expired_at' => now()->subDays(10)->toDateString(),
        ]);

        // Test filter EXPIRED
        $responseExp = $this->actingAs($admin)->get(route('lpks.index', ['expiry' => 'EXPIRED']));
        $responseExp->assertOk();
        $responseExp->assertSee('LP-EXP-01');
        $responseExp->assertDontSee('LP-VAL-01');
        $responseExp->assertDontSee('LP-SOON-01');

        // Test filter EXPIRING_SOON
        $responseSoon = $this->actingAs($admin)->get(route('lpks.index', ['expiry' => 'EXPIRING_SOON']));
        $responseSoon->assertOk();
        $responseSoon->assertSee('LP-SOON-01');
        $responseSoon->assertDontSee('LP-EXP-01');
        $responseSoon->assertDontSee('LP-VAL-01');

        // Test filter VALID
        $responseVal = $this->actingAs($admin)->get(route('lpks.index', ['expiry' => 'VALID']));
        $responseVal->assertOk();
        $responseVal->assertSee('LP-VAL-01');
        $responseVal->assertDontSee('LP-EXP-01');
        $responseVal->assertDontSee('LP-SOON-01');
    }

    public function test_lpk_index_filters_by_surveillance_status(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // S1 due: cert date 14 months ago
        $s1DueLpk = Lpk::create([
            'registration_number' => 'LP-S1-01',
            'name' => 'Lab Jatuh Tempo S1',
            'status' => 'ACTIVE',
            'certificate_date' => now()->subMonths(14)->toDateString(),
            'expired_at' => now()->addMonths(46)->toDateString(),
        ]);

        // Fresh cert: cert date 1 month ago (no alerts)
        $freshLpk = Lpk::create([
            'registration_number' => 'LP-FRESH-01',
            'name' => 'Lab Baru Terakreditasi',
            'status' => 'ACTIVE',
            'certificate_date' => now()->subMonth()->toDateString(),
            'expired_at' => now()->addMonths(59)->toDateString(),
        ]);

        // Test filter NEEDS_ACTION
        $responseAlert = $this->actingAs($admin)->get(route('lpks.index', ['surveillance' => 'NEEDS_ACTION']));
        $responseAlert->assertOk();
        $responseAlert->assertSee('LP-S1-01');
        $responseAlert->assertDontSee('LP-FRESH-01');

        // Test filter DUE_S1
        $responseS1 = $this->actingAs($admin)->get(route('lpks.index', ['surveillance' => 'DUE_S1']));
        $responseS1->assertOk();
        $responseS1->assertSee('LP-S1-01');
        $responseS1->assertDontSee('LP-FRESH-01');
    }

    public function test_surveillance_notification_buttons_link_to_filtered_lpk_index(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Create LPKs: 6 with active alerts so dashboard shows "Lihat seluruh X LPK yang jatuh tempo"
        for ($i = 1; $i <= 6; $i++) {
            Lpk::create([
                'registration_number' => "LP-ALERT-0{$i}",
                'name' => "Lab Jatuh Tempo {$i}",
                'status' => 'ACTIVE',
                'certificate_date' => now()->subMonths(14)->toDateString(),
                'expired_at' => now()->addMonths(46)->toDateString(),
            ]);
        }

        $expectedUrl = route('lpks.index', ['surveillance' => 'NEEDS_ACTION']);

        // 1. Dashboard contains filtered link
        $dashboardResponse = $this->actingAs($admin)->get(route('dashboard'));
        $dashboardResponse->assertOk();
        $dashboardResponse->assertSee($expectedUrl, false);
        $dashboardResponse->assertSee('Tinjau LPK Jatuh Tempo');
        $dashboardResponse->assertSee('Lihat seluruh');

        // 2. Visiting the filtered URL pre-selects the NEEDS_ACTION filter and displays LPKs needing action
        $filteredResponse = $this->actingAs($admin)->get($expectedUrl);
        $filteredResponse->assertOk();
        $filteredResponse->assertSee('selected', false);
        $filteredResponse->assertSee('Perlu Tindak Lanjut');
        $filteredResponse->assertSee('LP-ALERT-01');
    }

    public function test_surveillance_reminder_simulation_email_sends_successfully(): void
    {
        \Illuminate\Support\Facades\Mail::fake();

        $admin = User::factory()->create(['role' => 'admin']);
        $picUser = User::factory()->create(['role' => 'pic', 'email' => 'pic.petugas@bsn.go.id']);
        $lpk = Lpk::create([
            'registration_number' => 'LP-EMAIL-TEST',
            'name' => 'Lab Pengujian Simulasi Email',
            'status' => 'ACTIVE',
            'email' => 'lab.external@example.com',
            'certificate_date' => now()->subMonths(14)->toDateString(),
            'expired_at' => now()->addMonths(46)->toDateString(),
        ]);

        $response = $this->actingAs($admin)->post(route('lpks.surveillance.remind', $lpk), [
            'is_simulation' => true,
            'code' => 'S1',
        ]);

        $response->assertSessionHas('success');
        \Illuminate\Support\Facades\Mail::assertSent(\App\Mail\SurveillanceReminderMail::class, function ($mail) use ($lpk, $picUser) {
            return $mail->hasTo($picUser->email) && $mail->lpk->id === $lpk->id;
        });
    }

    public function test_lpk_dynamic_status_identifies_overdue_surveillance_and_expired(): void
    {
        // 1. LPK whose S1 target date has passed (16 months ago) without assessment -> SURVEILLANCE_OVERDUE
        $overdueLpk = Lpk::create([
            'registration_number' => 'LP-TEST-OVERDUE',
            'name' => 'Lab Overdue S1',
            'status' => 'ACTIVE',
            'certificate_date' => now()->subMonths(16)->toDateString(),
            'expired_at' => now()->addMonths(44)->toDateString(),
        ]);
        $this->assertSame('SURVEILLANCE_OVERDUE', $overdueLpk->dynamic_status);
        $this->assertSame('Lewat Jadwal Surveilen', $overdueLpk->dynamic_status_label);

        // 2. LPK with S1 assessment scheduled/completed -> ACTIVE
        Assessment::factory()->create([
            'lpk_id' => $overdueLpk->id,
            'title' => 'Asesmen Surveilen 1 (S1)',
            'assessment_type' => 'SURVEILLANCE',
            'status' => 'COMPLETED',
            'start_at' => now()->subMonth(),
            'end_at' => now()->subMonth(),
        ]);
        $overdueLpk->unsetRelation('assessments');
        $this->assertSame('ACTIVE', $overdueLpk->dynamic_status);

        // 3. LPK whose certificate is expired -> EXPIRED
        $expiredLpk = Lpk::create([
            'registration_number' => 'LP-TEST-EXP',
            'name' => 'Lab Expired',
            'status' => 'ACTIVE',
            'certificate_date' => now()->subYears(6)->toDateString(),
            'expired_at' => now()->subDay()->toDateString(),
        ]);
        $this->assertSame('EXPIRED', $expiredLpk->dynamic_status);
        $this->assertSame('Kedaluwarsa', $expiredLpk->dynamic_status_label);

        // 4. LPK with status INACTIVE -> INACTIVE
        $inactiveLpk = Lpk::create([
            'registration_number' => 'LP-TEST-INACT',
            'name' => 'Lab Inactive',
            'status' => 'INACTIVE',
            'certificate_date' => now()->subMonths(2)->toDateString(),
            'expired_at' => now()->addYears(4)->toDateString(),
        ]);
        $this->assertSame('INACTIVE', $inactiveLpk->dynamic_status);
        $this->assertSame('Tidak Aktif', $inactiveLpk->dynamic_status_label);

        // 5. Test tolerance for assessment filling (end of same month and year of visit)
        $recentAssessment = Assessment::factory()->create([
            'lpk_id' => $inactiveLpk->id,
            'title' => 'Asesmen Baru Selesai Kunjungan',
            'assessment_type' => 'SURVEILLANCE',
            'status' => 'IN_PROGRESS',
            'start_at' => now()->startOfMonth()->addDays(2),
            'end_at' => now()->startOfMonth()->addDays(4),
        ]);
        $this->assertFalse($recentAssessment->is_submission_overdue);
        $this->assertEquals($recentAssessment->end_at->copy()->endOfMonth()->endOfDay(), $recentAssessment->submission_due_date);
        $this->assertEquals($recentAssessment->end_at->year, $recentAssessment->submission_due_date->year);
        $this->assertEquals($recentAssessment->end_at->month, $recentAssessment->submission_due_date->month);

        $overdueAssessment = Assessment::factory()->create([
            'lpk_id' => $inactiveLpk->id,
            'title' => 'Asesmen Lewat Toleransi Akhir Bulan',
            'assessment_type' => 'SURVEILLANCE',
            'status' => 'IN_PROGRESS',
            'start_at' => now()->subMonth()->startOfMonth()->addDays(2),
            'end_at' => now()->subMonth()->startOfMonth()->addDays(4),
        ]);
        $this->assertTrue($overdueAssessment->is_submission_overdue);
        $this->assertEquals($overdueAssessment->end_at->year, $overdueAssessment->submission_due_date->year);
        $this->assertEquals($overdueAssessment->end_at->month, $overdueAssessment->submission_due_date->month);

        // 5. LPK in notice window (14 months) -> SURVEILLANCE_DUE
        $dueLpk = Lpk::create([
            'registration_number' => 'LP-TEST-DUE',
            'name' => 'Lab Due S1',
            'status' => 'ACTIVE',
            'certificate_date' => now()->subMonths(14)->toDateString(),
            'expired_at' => now()->addMonths(46)->toDateString(),
        ]);
        $this->assertSame('SURVEILLANCE_DUE', $dueLpk->dynamic_status);
        $this->assertSame('Jatuh Tempo Surveilen', $dueLpk->dynamic_status_label);
    }

    public function test_lpk_index_and_show_render_dynamic_status_badge(): void
    {
        $admin = User::factory()->create(['role' => 'admin']);

        // Create LPK with overdue S1
        $overdueLpk = Lpk::create([
            'registration_number' => 'LP-BADGE-OVERDUE',
            'name' => 'Lab Badge Overdue',
            'status' => 'ACTIVE',
            'certificate_date' => now()->subMonths(18)->toDateString(),
            'expired_at' => now()->addMonths(42)->toDateString(),
        ]);

        // Create LPK with recent certificate (compliant ACTIVE)
        $activeLpk = Lpk::create([
            'registration_number' => 'LP-BADGE-ACTIVE',
            'name' => 'Lab Badge Aktif Sejati',
            'status' => 'ACTIVE',
            'certificate_date' => now()->subMonths(2)->toDateString(),
            'expired_at' => now()->addYears(4)->toDateString(),
        ]);

        // Check index page renders dynamic status badge
        $indexResponse = $this->actingAs($admin)->get('/lpks');
        $indexResponse->assertOk();
        $indexResponse->assertSee('Lewat Jadwal Surveilen');
        $indexResponse->assertSee('status-surveillance_overdue');

        // Check show page renders dynamic status badge
        $showResponse = $this->actingAs($admin)->get(route('lpks.show', $overdueLpk));
        $showResponse->assertOk();
        $showResponse->assertSee('Lewat Jadwal Surveilen');

        // Test filtering by SURVEILLANCE_OVERDUE
        $filteredResponse = $this->actingAs($admin)->get('/lpks?status=SURVEILLANCE_OVERDUE');
        $filteredResponse->assertOk();
        $filteredResponse->assertSee('Lab Badge Overdue');
        $filteredResponse->assertDontSee('Lab Badge Aktif Sejati');
    }

    public function test_lpk_automatically_generates_surveillance_and_reaccreditation_assessments_on_creation(): void
    {
        $admin = User::factory()->admin()->create();

        $certDate = now()->toDateString();
        $lpk = Lpk::create([
            'registration_number' => 'LP-AUTO-ASSESS-01',
            'name' => 'Lab Otomatis Asesmen',
            'status' => 'ACTIVE',
            'certificate_date' => $certDate,
            'expired_at' => now()->addYears(5)->toDateString(),
            'address' => 'Jl. Pengujian No. 99, Jakarta',
        ]);

        // Pastikan 3 agenda asesmen otomatis terbuat
        $assessments = $lpk->assessments()->orderBy('start_at')->get();
        $this->assertCount(3, $assessments);

        // 1. Asesmen Surveilen 1 (S1) - Bulan 15
        $s1 = $assessments[0];
        $this->assertStringContainsString('Surveilen 1', $s1->title);
        $this->assertEquals('Surveilen', $s1->assessment_type);
        $this->assertEquals('PLANNED', $s1->status);
        $this->assertEquals(now()->addMonths(15)->format('Y-m'), $s1->start_at->format('Y-m'));

        // 2. Asesmen Surveilen 2 (S2) - Bulan 36
        $s2 = $assessments[1];
        $this->assertStringContainsString('Surveilen 2', $s2->title);
        $this->assertEquals('Surveilen', $s2->assessment_type);
        $this->assertEquals('PLANNED', $s2->status);
        $this->assertEquals(now()->addMonths(36)->format('Y-m'), $s2->start_at->format('Y-m'));

        // 3. Asesmen Re-Akreditasi (RA) - Bulan 54
        $ra = $assessments[2];
        $this->assertStringContainsString('Re-Akreditasi', $ra->title);
        $this->assertEquals('Re-asesmen', $ra->assessment_type);
        $this->assertEquals('PLANNED', $ra->status);
        $this->assertEquals(now()->addMonths(54)->format('Y-m'), $ra->start_at->format('Y-m'));

        // Pastikan muncul pada halaman daftar asesmen
        $this->actingAs($admin)->get(route('assessments.index', ['lpk_id' => $lpk->id]))
            ->assertOk()
            ->assertSee('Asesmen Surveilen 1 (S1) - Lab Otomatis Asesmen')
            ->assertSee('Asesmen Surveilen 2 (S2) - Lab Otomatis Asesmen')
            ->assertSee('Asesmen Re-Akreditasi (Re-asesmen) - Lab Otomatis Asesmen');

        // Pastikan muncul pada halaman detail LPK
        $this->actingAs($admin)->get(route('lpks.show', $lpk))
            ->assertOk()
            ->assertSee('Daftar Asesmen Surveilen')
            ->assertSee('Asesmen Surveilen 1 (S1) - Lab Otomatis Asesmen');

        // Pastikan update tanggal sertifikat tidak membuat duplikat
        $lpk->update(['name' => 'Lab Otomatis Asesmen Updated']);
        $this->assertEquals(3, $lpk->assessments()->count());
    }
}



