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

    public function test_user_can_create_lpk_with_expiry_and_drive_links(): void
    {
        $user = User::factory()->create();

        $response = $this->actingAs($user)->post('/lpks', [
            'registration_number' => 'LPK-TEST-DRIVE-01',
            'name' => 'LPK Pengujian Drive',
            'status' => 'ACTIVE',
            'expired_at' => '2028-10-15',
            'certificate_drive_url' => 'https://drive.google.com/file/d/12345/view',
            'amendment_drive_url' => 'https://drive.google.com/drive/folders/67890',
        ]);

        $response->assertRedirect();

        $lpk = Lpk::where('registration_number', 'LPK-TEST-DRIVE-01')->firstOrFail();
        $this->assertEquals('2028-10-15', $lpk->expired_at->format('Y-m-d'));
        $this->assertEquals('https://drive.google.com/file/d/12345/view', $lpk->certificate_drive_url);
        $this->assertEquals('https://drive.google.com/drive/folders/67890', $lpk->amendment_drive_url);

        $showResponse = $this->actingAs($user)->get(route('lpks.show', $lpk));
        $showResponse->assertOk()
            ->assertSee('15 Oct 2028')
            ->assertSee('Buka Sertifikat di Drive')
            ->assertSee('Buka Amandemen Lampiran di Drive');
    }
}

