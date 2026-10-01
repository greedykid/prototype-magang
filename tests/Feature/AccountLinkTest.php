<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Lpk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class AccountLinkTest extends TestCase
{
    use RefreshDatabase;

    public function test_pic_can_view_account_links_page(): void
    {
        $pic = User::factory()->pic()->create();

        $response = $this->actingAs($pic)->get(route('account-links.index'));

        $response->assertOk();
        $response->assertViewIs('account-links.index');
        $response->assertSee('Tautan Akun Kolaborasi');
        $response->assertSee('Belum Ada Akun Viewer Tertaut');
        $response->assertSee('Belum Menerima Akses Akun Lain');
        $response->assertSee('width: 44px', false);
    }

    public function test_owner_pic_can_link_another_pic_as_account_level_viewer(): void
    {
        $owner = User::factory()->pic()->create(['name' => 'Owner PIC']);
        $viewer = User::factory()->pic()->create(['name' => 'Viewer PIC']);

        $response = $this->actingAs($owner)
            ->from(route('account-links.index'))
            ->post(route('account-links.store'), [
                'viewer_id' => $viewer->id,
            ]);

        $response->assertRedirect(route('account-links.index'));
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('user_account_links', [
            'user_id' => $owner->id,
            'viewer_id' => $viewer->id,
        ]);

        $this->assertTrue($owner->hasLinkedViewer($viewer));
        $this->assertTrue($viewer->isViewerFor($owner));
    }

    public function test_cannot_link_self_or_duplicate_viewer(): void
    {
        $owner = User::factory()->pic()->create();
        $viewer = User::factory()->pic()->create();

        // 1. Cannot link self
        $responseSelf = $this->actingAs($owner)
            ->from(route('account-links.index'))
            ->post(route('account-links.store'), [
                'viewer_id' => $owner->id,
            ]);
        $responseSelf->assertSessionHasErrors('viewer_id');

        // 2. Link viewer first time
        $this->actingAs($owner)
            ->from(route('account-links.index'))
            ->post(route('account-links.store'), [
                'viewer_id' => $viewer->id,
            ]);

        // 3. Try to link again (duplicate)
        $responseDuplicate = $this->actingAs($owner)
            ->from(route('account-links.index'))
            ->post(route('account-links.store'), [
                'viewer_id' => $viewer->id,
            ]);
        $responseDuplicate->assertSessionHasErrors('viewer_id');
    }

    public function test_linked_viewer_sees_all_lpks_assessments_and_calendar_events_of_owner(): void
    {
        $owner = User::factory()->pic()->create(['name' => 'Dr. Budi Utomo']);
        $viewer = User::factory()->pic()->create(['name' => 'Siti Viewer']);
        $otherPic = User::factory()->pic()->create(['name' => 'Other PIC']);

        // Owner owns 3 LPKs
        $lpk1 = Lpk::factory()->create(['name' => 'Laboratorium Kimia Terpadu', 'pic_id' => $owner->id]);
        $lpk2 = Lpk::factory()->create(['name' => 'Laboratorium Kalibrasi Presisi', 'pic_id' => $owner->id]);
        $lpk3 = Lpk::factory()->create(['name' => 'Laboratorium Lingkungan Hijau', 'pic_id' => $owner->id]);

        // Other PIC owns 1 LPK
        $otherLpk = Lpk::factory()->create(['name' => 'Laboratorium Rahasia', 'pic_id' => $otherPic->id]);

        // Create assessments for owner's LPKs
        $assessment1 = Assessment::factory()->create([
            'lpk_id' => $lpk1->id,
            'title' => 'Surveilen Rutin Kimia',
            'assessment_type' => Assessment::TYPE_SURVEILEN_1,
            'start_at' => '2026-10-15 09:00:00',
            'end_at' => '2026-10-16 16:00:00',
        ]);
        $assessment2 = Assessment::factory()->create([
            'lpk_id' => $lpk2->id,
            'title' => 'Re-akreditasi Kalibrasi',
            'assessment_type' => Assessment::TYPE_RE_AKREDITASI,
            'start_at' => '2026-11-01 09:00:00',
            'end_at' => '2026-11-03 16:00:00',
        ]);

        // Assessment for other LPK
        $otherAssessment = Assessment::factory()->create([
            'lpk_id' => $otherLpk->id,
            'title' => 'Asesmen Rahasia',
            'assessment_type' => Assessment::TYPE_SURVEILEN_2,
            'start_at' => '2026-12-01 09:00:00',
            'end_at' => '2026-12-02 16:00:00',
        ]);

        // Before linking, viewer sees 0 LPKs and 0 assessments
        $this->assertEquals(0, Lpk::accessibleBy($viewer)->count());
        $this->assertFalse($lpk1->canView($viewer));

        // Owner links Viewer
        $owner->linkedViewers()->attach($viewer->id);

        // After linking: Viewer query scope must return all 3 of owner's LPKs
        $accessibleLpks = Lpk::accessibleBy($viewer)->pluck('id')->all();
        $this->assertContains($lpk1->id, $accessibleLpks);
        $this->assertContains($lpk2->id, $accessibleLpks);
        $this->assertContains($lpk3->id, $accessibleLpks);
        $this->assertNotContains($otherLpk->id, $accessibleLpks);

        // Verification via Dashboard
        $dashboardResponse = $this->actingAs($viewer)->get(route('dashboard'));
        $dashboardResponse->assertOk();
        $dashboardResponse->assertSee('3'); // 3 LPKs terdaftar
        $dashboardResponse->assertSee('Laboratorium Kimia Terpadu');

        // Verification via Assessment List
        $assessmentResponse = $this->actingAs($viewer)->get(route('assessments.index'));
        $assessmentResponse->assertOk();
        $assessmentResponse->assertSee('Surveilen Rutin Kimia');
        $assessmentResponse->assertSee('Re-akreditasi Kalibrasi');
        $assessmentResponse->assertDontSee('Asesmen Rahasia');

        // Verification via Calendar (integrated LPK assessments & events)
        $calendarResponse = $this->actingAs($viewer)->get('/calendar?view=month&date=2026-10-15');
        $calendarResponse->assertOk();
        $calendarResponse->assertSee('Laboratorium Kimia Terpadu');
        $calendarResponse->assertDontSee('Laboratorium Rahasia');
    }

    public function test_linked_viewer_has_readonly_access_to_owner_lpks_and_cannot_manage(): void
    {
        $owner = User::factory()->pic()->create();
        $viewer = User::factory()->pic()->create();
        $lpk = Lpk::factory()->create(['name' => 'Lab Fisika', 'pic_id' => $owner->id]);
        $assessment = Assessment::factory()->create([
            'lpk_id' => $lpk->id,
            'title' => 'Asesmen Fisika',
            'assessment_type' => Assessment::TYPE_SURVEILEN_1,
            'start_at' => '2026-10-20 09:00:00',
            'end_at' => '2026-10-21 16:00:00',
        ]);

        $owner->linkedViewers()->attach($viewer->id);

        // Can view show page
        $this->assertTrue($lpk->canView($viewer));
        $this->assertFalse($lpk->canManage($viewer));

        $showResponse = $this->actingAs($viewer)->get(route('lpks.show', $lpk));
        $showResponse->assertOk();
        $showResponse->assertSee('Lab Fisika');

        // Cannot access edit page
        $editResponse = $this->actingAs($viewer)->get(route('lpks.edit', $lpk));
        $editResponse->assertForbidden();

        // Cannot update LPK
        $updateResponse = $this->actingAs($viewer)->put(route('lpks.update', $lpk), [
            'name' => 'Lab Fisika Diubah',
            'type' => $lpk->type,
            'scope' => $lpk->scope,
            'address' => $lpk->address,
            'city' => $lpk->city,
            'province' => $lpk->province,
            'status' => $lpk->status,
        ]);
        $updateResponse->assertForbidden();

        // Cannot delete LPK
        $deleteResponse = $this->actingAs($viewer)->delete(route('lpks.destroy', $lpk));
        $deleteResponse->assertForbidden();

        // Cannot edit assessment
        $editAssessmentResponse = $this->actingAs($viewer)->get(route('assessments.edit', $assessment));
        $editAssessmentResponse->assertForbidden();

        // Cannot delete assessment
        $deleteAssessmentResponse = $this->actingAs($viewer)->delete(route('assessments.destroy', $assessment));
        $deleteAssessmentResponse->assertForbidden();
    }

    public function test_owner_can_unlink_viewer_and_access_is_immediately_revoked(): void
    {
        $owner = User::factory()->pic()->create();
        $viewer = User::factory()->pic()->create();
        $lpk = Lpk::factory()->create(['pic_id' => $owner->id]);

        $owner->linkedViewers()->attach($viewer->id);
        $this->assertTrue($lpk->canView($viewer));

        $unlinkResponse = $this->actingAs($owner)
            ->from(route('account-links.index'))
            ->delete(route('account-links.destroy', $viewer));

        $unlinkResponse->assertRedirect(route('account-links.index'));
        $unlinkResponse->assertSessionHas('success');

        $this->assertDatabaseMissing('user_account_links', [
            'user_id' => $owner->id,
            'viewer_id' => $viewer->id,
        ]);

        $this->assertFalse($lpk->fresh()->canView($viewer));
    }

    public function test_viewer_can_detach_from_owner_account(): void
    {
        $owner = User::factory()->pic()->create();
        $viewer = User::factory()->pic()->create();
        $lpk = Lpk::factory()->create(['pic_id' => $owner->id]);

        $owner->linkedViewers()->attach($viewer->id);

        $unlinkResponse = $this->actingAs($viewer)
            ->from(route('account-links.index'))
            ->delete(route('account-links.destroy', $owner));

        $unlinkResponse->assertRedirect(route('account-links.index'));

        $this->assertDatabaseMissing('user_account_links', [
            'user_id' => $owner->id,
            'viewer_id' => $viewer->id,
        ]);

        $this->assertFalse($lpk->fresh()->canView($viewer));
    }

    public function test_admin_can_manage_account_links(): void
    {
        $admin = User::factory()->admin()->create();
        $owner = User::factory()->pic()->create();
        $viewer = User::factory()->pic()->create();

        // Admin links viewer to owner
        $response = $this->actingAs($admin)
            ->from(route('account-links.index'))
            ->post(route('account-links.store'), [
                'owner_id' => $owner->id,
                'viewer_id' => $viewer->id,
            ]);
        $response->assertRedirect(route('account-links.index'));

        $this->assertDatabaseHas('user_account_links', [
            'user_id' => $owner->id,
            'viewer_id' => $viewer->id,
        ]);

        // Admin unlinks viewer from owner
        $unlinkResponse = $this->actingAs($admin)
            ->from(route('account-links.index'))
            ->delete(route('account-links.destroy', $viewer), [
                'owner_id' => $owner->id,
            ]);
        $unlinkResponse->assertRedirect(route('account-links.index'));

        $this->assertDatabaseMissing('user_account_links', [
            'user_id' => $owner->id,
            'viewer_id' => $viewer->id,
        ]);
    }

    public function test_profile_page_displays_account_links_sections(): void
    {
        $owner = User::factory()->pic()->create(['name' => 'Owner Profil']);
        $viewer = User::factory()->pic()->create(['name' => 'Viewer Profil']);

        $owner->linkedViewers()->attach($viewer->id);

        // Owner visits profile
        $ownerProfile = $this->actingAs($owner)->get(route('profile.edit'));
        $ownerProfile->assertOk();
        $ownerProfile->assertSee('Tautan Akun Kolaborasi');
        $ownerProfile->assertSee('Viewer Profil');

        // Viewer visits profile
        $viewerProfile = $this->actingAs($viewer)->get(route('profile.edit'));
        $viewerProfile->assertOk();
        $viewerProfile->assertSee('Akses Viewer yang Diterima');
        $viewerProfile->assertSee('Owner Profil');
    }

    public function test_calendar_hides_edit_button_data_for_viewer_account(): void
    {
        $owner = User::factory()->pic()->create(['name' => 'Owner Lab']);
        $viewer = User::factory()->pic()->create(['name' => 'Viewer Lab']);
        $lpk = Lpk::factory()->create(['name' => 'UPTD Balai Konstruksi', 'pic_id' => $owner->id]);

        $assessment = Assessment::factory()->create([
            'lpk_id' => $lpk->id,
            'title' => 'Surveilen 1 Balai Konstruksi',
            'assessment_type' => Assessment::TYPE_SURVEILEN_1,
            'start_at' => '2028-01-03 09:00:00',
            'end_at' => '2028-01-03 17:00:00',
        ]);

        $owner->linkedViewers()->attach($viewer->id);

        // When Owner visits calendar, can_manage is true and edit_url is provided
        $ownerCalendar = $this->actingAs($owner)->get('/calendar?view=month&date=2028-01-03');
        $ownerCalendar->assertOk();
        $ownerCalendar->assertSee('&quot;can_manage&quot;:true', false);
        $ownerCalendar->assertSee('assessments\/' . $assessment->id . '\/edit', false);

        // When Viewer visits calendar, can_manage is false and edit_url is null
        $viewerCalendar = $this->actingAs($viewer)->get('/calendar?view=month&date=2028-01-03');
        $viewerCalendar->assertOk();
        $viewerCalendar->assertSee('&quot;can_manage&quot;:false', false);
        $viewerCalendar->assertSee('&quot;edit_url&quot;:null', false);
        $viewerCalendar->assertDontSee('assessments\/' . $assessment->id . '\/edit', false);
    }

    public function test_pic_can_filter_lpks_by_linked_pic_account(): void
    {
        $pic1 = User::factory()->pic()->create(['name' => 'PIC Satu']);
        $pic2 = User::factory()->pic()->create(['name' => 'PIC Dua']);

        $lpk1 = Lpk::factory()->create(['name' => 'Lab Kepunyaan PIC 1', 'pic_id' => $pic1->id]);
        $lpk2 = Lpk::factory()->create(['name' => 'Lab Kepunyaan PIC 2', 'pic_id' => $pic2->id]);

        $pic1->linkedViewers()->attach($pic2->id);

        // PIC 2 sees both LPKs without filter
        $responseAll = $this->actingAs($pic2)->get(route('lpks.index'));
        $responseAll->assertOk();
        $responseAll->assertSee('Lab Kepunyaan PIC 1');
        $responseAll->assertSee('Lab Kepunyaan PIC 2');
        $responseAll->assertSee('Akun PIC');

        // PIC 2 filters by PIC 1
        $responsePic1 = $this->actingAs($pic2)->get(route('lpks.index', ['pic_id' => $pic1->id]));
        $responsePic1->assertOk();
        $responsePic1->assertSee('Lab Kepunyaan PIC 1');
        $responsePic1->assertDontSee('Lab Kepunyaan PIC 2');

        // PIC 2 filters by PIC 2 (self)
        $responsePic2 = $this->actingAs($pic2)->get(route('lpks.index', ['pic_id' => $pic2->id]));
        $responsePic2->assertOk();
        $responsePic2->assertDontSee('Lab Kepunyaan PIC 1');
        $responsePic2->assertSee('Lab Kepunyaan PIC 2');
    }

    public function test_pic_can_filter_assessments_by_linked_pic_account(): void
    {
        $pic1 = User::factory()->pic()->create(['name' => 'PIC Pertama']);
        $pic2 = User::factory()->pic()->create(['name' => 'PIC Kedua']);

        $lpk1 = Lpk::factory()->create(['name' => 'Lab Akreditasi 1', 'pic_id' => $pic1->id]);
        $lpk2 = Lpk::factory()->create(['name' => 'Lab Akreditasi 2', 'pic_id' => $pic2->id]);

        $assessment1 = Assessment::factory()->create([
            'lpk_id' => $lpk1->id,
            'title' => 'Asesmen Lapangan Khusus Lab 1',
        ]);
        $assessment2 = Assessment::factory()->create([
            'lpk_id' => $lpk2->id,
            'title' => 'Asesmen Lapangan Khusus Lab 2',
        ]);

        $pic1->linkedViewers()->attach($pic2->id);

        // PIC 2 sees both assessments without filter
        $responseAll = $this->actingAs($pic2)->get(route('assessments.index'));
        $responseAll->assertOk();
        $responseAll->assertSee('Asesmen Lapangan Khusus Lab 1');
        $responseAll->assertSee('Asesmen Lapangan Khusus Lab 2');
        $responseAll->assertSee('Akun PIC');

        // PIC 2 filters by PIC 1
        $responsePic1 = $this->actingAs($pic2)->get(route('assessments.index', ['pic_id' => $pic1->id]));
        $responsePic1->assertOk();
        $responsePic1->assertSee('Asesmen Lapangan Khusus Lab 1');
        $responsePic1->assertDontSee('Asesmen Lapangan Khusus Lab 2');

        // PIC 2 filters by PIC 2 (self)
        $responsePic2 = $this->actingAs($pic2)->get(route('assessments.index', ['pic_id' => $pic2->id]));
        $responsePic2->assertOk();
        $responsePic2->assertDontSee('Asesmen Lapangan Khusus Lab 1');
        $responsePic2->assertSee('Asesmen Lapangan Khusus Lab 2');
    }

    public function test_pic_can_filter_calendar_by_linked_pic_account(): void
    {
        $pic1 = User::factory()->pic()->create(['name' => 'PIC Alfa']);
        $pic2 = User::factory()->pic()->create(['name' => 'PIC Beta']);

        $lpk1 = Lpk::factory()->create(['name' => 'Lab Alfa', 'pic_id' => $pic1->id]);
        $lpk2 = Lpk::factory()->create(['name' => 'Lab Beta', 'pic_id' => $pic2->id]);

        Assessment::factory()->create([
            'lpk_id' => $lpk1->id,
            'title' => 'Surveilen Lab Alfa 2028',
            'assessment_type' => Assessment::TYPE_SURVEILEN_1,
            'start_at' => '2028-02-10 09:00:00',
            'end_at' => '2028-02-10 17:00:00',
        ]);

        Assessment::factory()->create([
            'lpk_id' => $lpk2->id,
            'title' => 'Surveilen Lab Beta 2028',
            'assessment_type' => Assessment::TYPE_SURVEILEN_1,
            'start_at' => '2028-02-12 09:00:00',
            'end_at' => '2028-02-12 17:00:00',
        ]);

        $pic1->linkedViewers()->attach($pic2->id);

        // PIC 2 visits calendar: sees both in unified events data
        $responseAll = $this->actingAs($pic2)->get('/calendar?view=month&date=2028-02-10');
        $responseAll->assertOk();
        $responseAll->assertSee('Lab Alfa');
        $responseAll->assertSee('Lab Beta');
        $responseAll->assertSee('id="gcal-filter-pic"', false);

        // Filter by PIC 1 only
        $responsePic1 = $this->actingAs($pic2)->get('/calendar?view=month&date=2028-02-10&pic_id=' . $pic1->id);
        $responsePic1->assertOk();
        $responsePic1->assertSee('Lab Alfa');
        $responsePic1->assertDontSee('Lab Beta');

        // Filter by PIC 2 only
        $responsePic2 = $this->actingAs($pic2)->get('/calendar?view=month&date=2028-02-10&pic_id=' . $pic2->id);
        $responsePic2->assertOk();
        $responsePic2->assertDontSee('Lab Alfa');
        $responsePic2->assertSee('Lab Beta');
    }
}
