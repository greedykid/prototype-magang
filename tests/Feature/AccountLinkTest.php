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

        // Verification via Assessment List: Terisolasi dari daftar asesmen primer
        $assessmentResponse = $this->actingAs($viewer)->get(route('assessments.index'));
        $assessmentResponse->assertOk();
        $assessmentResponse->assertDontSee('Surveilen Rutin Kimia');
        $assessmentResponse->assertDontSee('Re-akreditasi Kalibrasi');

        // Verification via Detail Akun Tertaut (Tab Program Asesmen)
        $linkedAssessmentResponse = $this->actingAs($viewer)->get(route('account-links.show', [$owner, 'tab' => 'assessments']));
        $linkedAssessmentResponse->assertOk();
        $linkedAssessmentResponse->assertSee('Surveilen Rutin Kimia');
        $linkedAssessmentResponse->assertSee('Re-akreditasi Kalibrasi');
        $linkedAssessmentResponse->assertDontSee('Asesmen Rahasia');

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

    public function test_linked_account_lpks_are_separated_from_main_lpk_index(): void
    {
        $pic1 = User::factory()->pic()->create(['name' => 'PIC Satu']);
        $pic2 = User::factory()->pic()->create(['name' => 'PIC Dua']);
        $unauthorizedPic = User::factory()->pic()->create(['name' => 'PIC Tiga']);
        $admin = User::factory()->admin()->create(['name' => 'Admin Sistem']);

        $lpk1 = Lpk::factory()->create(['name' => 'Lab Kepunyaan PIC 1', 'pic_id' => $pic1->id]);
        $lpk2 = Lpk::factory()->create(['name' => 'Lab Kepunyaan PIC 2', 'pic_id' => $pic2->id]);

        $pic1->linkedViewers()->attach($pic2->id);

        // 1. Pada Daftar LPK Utama (/lpks): PIC 2 hanya melihat LPK miliknya sendiri (tidak bercampur)
        $responseMain = $this->actingAs($pic2)->get(route('lpks.index'));
        $responseMain->assertOk();
        $responseMain->assertSee('Lab Kepunyaan PIC 2');
        $responseMain->assertDontSee('Lab Kepunyaan PIC 1');
        $responseMain->assertSee('Buka Akun Tertaut');
        $responseMain->assertDontSee('name="pic_id"', false);

        // 2. Pada Dasbor (/): Bagian "Laboratorium Terkelola" murni memuat LPK milik PIC 2, tidak memuat LPK akun tertaut
        $responseDashboard = $this->actingAs($pic2)->get(route('dashboard'));
        $responseDashboard->assertOk();
        $responseDashboard->assertViewHas('managedLpks', function ($managedLpks) {
            return $managedLpks->contains('name', 'Lab Kepunyaan PIC 2')
                && ! $managedLpks->contains('name', 'Lab Kepunyaan PIC 1');
        });
        $responseDashboard->assertViewHas('lpkCount', 1);

        // 3. Pada Halaman Detail Akun Tertaut (/account-links/{owner}):
        // PIC 2 melihat daftar LPK milik PIC 1 secara terpisah
        $responseLinked = $this->actingAs($pic2)->get(route('account-links.show', $pic1));
        $responseLinked->assertOk();
        $responseLinked->assertSee('Data Akun Tertaut');
        $responseLinked->assertSee('Daftar Laboratorium');
        $responseLinked->assertSee('PIC Satu');
        $responseLinked->assertSee('Lab Kepunyaan PIC 1');
        $responseLinked->assertDontSee('Lab Kepunyaan PIC 2');

        // 4. Filter pencarian di halaman detail akun tertaut berfungsi (full page & partial live filter)
        $responseSearch = $this->actingAs($pic2)->get(route('account-links.show', [$pic1, 'search' => 'Kepunyaan PIC 1']));
        $responseSearch->assertOk();
        $responseSearch->assertSee('Lab Kepunyaan PIC 1');

        $responsePartial = $this->actingAs($pic2)->get(
            route('account-links.show', [$pic1, 'search' => 'Kepunyaan PIC 1']),
            ['X-Requested-With' => 'XMLHttpRequest', 'X-Partial-Content' => 'table']
        );
        $responsePartial->assertOk();
        $responsePartial->assertViewIs('account-links.partials.table-content');
        $responsePartial->assertSee('Lab Kepunyaan PIC 1');

        // 5. Pengguna tanpa izin (unauthorized) ditolak dengan 403 Forbidden
        $responseForbidden = $this->actingAs($unauthorizedPic)->get(route('account-links.show', $pic1));
        $responseForbidden->assertForbidden();

        // 6. Admin dapat mengakses halaman detail akun tertaut
        $responseAdmin = $this->actingAs($admin)->get(route('account-links.show', $pic1));
        $responseAdmin->assertOk();
        $responseAdmin->assertSee('Lab Kepunyaan PIC 1');
    }

    public function test_assessments_list_isolates_primary_and_linked_accounts_assessments(): void
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

        // 1. Pada halaman asesmen utama (/assessments), PIC 2 HANYA melihat asesmen primer miliknya
        $responseMain = $this->actingAs($pic2)->get(route('assessments.index'));
        $responseMain->assertOk();
        $responseMain->assertSee('Asesmen Lapangan Khusus Lab 2');
        $responseMain->assertDontSee('Asesmen Lapangan Khusus Lab 1');
        $responseMain->assertDontSee('name="pic_id"', false);
        $responseMain->assertSee('1 akun tertaut');

        // 2. Pada halaman tautan akun detail (/account-links/{owner}?tab=assessments), PIC 2 melihat asesmen PIC 1
        $responseLinkedAssessments = $this->actingAs($pic2)->get(route('account-links.show', [$pic1, 'tab' => 'assessments']));
        $responseLinkedAssessments->assertOk();
        $responseLinkedAssessments->assertSee('Asesmen Lapangan Khusus Lab 1');
        $responseLinkedAssessments->assertDontSee('Asesmen Lapangan Khusus Lab 2');

        // 3. Filter parsial pada tab asesmen di akun tertaut
        $responsePartial = $this->actingAs($pic2)->get(
            route('account-links.show', [$pic1, 'tab' => 'assessments', 'search' => 'Khusus Lab 1']),
            ['X-Requested-With' => 'XMLHttpRequest', 'X-Partial-Content' => 'table']
        );
        $responsePartial->assertOk();
        $responsePartial->assertViewIs('account-links.partials.assessments-table-content');
        $responsePartial->assertSee('Asesmen Lapangan Khusus Lab 1');
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

    public function test_linked_viewer_can_view_dedicated_lpk_detail_page(): void
    {
        $owner = User::factory()->pic()->create(['name' => 'Owner LPK']);
        $viewer = User::factory()->pic()->create(['name' => 'Viewer LPK']);
        $unlinked = User::factory()->pic()->create(['name' => 'Unlinked PIC']);

        $owner->linkedViewers()->attach($viewer->id);

        $lpk = Lpk::factory()->create([
            'name' => 'Laboratorium Khusus Uji',
            'registration_number' => 'LP-KHUSUS-99',
            'pic_id' => $owner->id,
        ]);

        // 1. Viewer can view dedicated LPK show page
        $responseViewer = $this->actingAs($viewer)->get(route('account-links.lpks.show', [$owner, $lpk]));
        $responseViewer->assertOk();
        $responseViewer->assertViewIs('account-links.lpks.show');
        $responseViewer->assertSee('Laboratorium Khusus Uji');
        $responseViewer->assertSee('LP-KHUSUS-99');
        $responseViewer->assertSee('Daftar LPK Akun: Owner LPK');
        $responseViewer->assertSee('Mode Pemantauan (Viewer) - Milik Akun: Owner LPK');
        $responseViewer->assertDontSee('Ubah data');
        $responseViewer->assertDontSee('Hapus LPK');

        // 2. Unlinked user cannot view (403)
        $responseUnlinked = $this->actingAs($unlinked)->get(route('account-links.lpks.show', [$owner, $lpk]));
        $responseUnlinked->assertForbidden();

        // 3. LPK belonging to another user returns 404
        $otherLpk = Lpk::factory()->create(['pic_id' => $unlinked->id]);
        $responseNotFound = $this->actingAs($viewer)->get(route('account-links.lpks.show', [$owner, $otherLpk]));
        $responseNotFound->assertNotFound();
    }

    public function test_linked_viewer_can_view_dedicated_assessment_detail_page(): void
    {
        $owner = User::factory()->pic()->create(['name' => 'Owner Asm']);
        $viewer = User::factory()->pic()->create(['name' => 'Viewer Asm']);
        $unlinked = User::factory()->pic()->create(['name' => 'Unlinked Asm PIC']);

        $owner->linkedViewers()->attach($viewer->id);

        $lpk = Lpk::factory()->create([
            'name' => 'Laboratorium Terkait Asesmen',
            'registration_number' => 'LP-ASM-77',
            'pic_id' => $owner->id,
        ]);

        $assessment = Assessment::factory()->create([
            'lpk_id' => $lpk->id,
            'title' => 'Asesmen Khusus Tertaut',
            'assessment_type' => Assessment::TYPE_SURVEILEN_1,
            'status' => 'SCHEDULED',
        ]);

        // 1. Viewer can view dedicated assessment show page
        $responseViewer = $this->actingAs($viewer)->get(route('account-links.assessments.show', [$owner, $assessment]));
        $responseViewer->assertOk();
        $responseViewer->assertViewIs('account-links.assessments.show');
        $responseViewer->assertSee('Asesmen Khusus Tertaut');
        $responseViewer->assertSee('Program Asesmen Akun: Owner Asm');
        $responseViewer->assertSee('Mode Pemantauan (Viewer) - Milik Akun: Owner Asm');
        $responseViewer->assertSee(route('account-links.lpks.show', [$owner, $lpk]));
        $responseViewer->assertDontSee('Ubah asesmen');

        // 2. Unlinked user cannot view (403)
        $responseUnlinked = $this->actingAs($unlinked)->get(route('account-links.assessments.show', [$owner, $assessment]));
        $responseUnlinked->assertForbidden();
    }

    public function test_account_links_tables_use_dedicated_routes_for_rows_and_links(): void
    {
        $owner = User::factory()->pic()->create(['name' => 'Owner PIC Tabel']);
        $viewer = User::factory()->pic()->create(['name' => 'Viewer PIC Tabel']);

        $owner->linkedViewers()->attach($viewer->id);

        $lpk = Lpk::factory()->create([
            'name' => 'Lab Tabel Tertaut',
            'registration_number' => 'LP-TABEL-01',
            'pic_id' => $owner->id,
        ]);

        $assessment = Assessment::factory()->create([
            'lpk_id' => $lpk->id,
            'title' => 'Agenda Asesmen Tertaut',
            'assessment_type' => Assessment::TYPE_SURVEILEN_1,
        ]);

        // 1. LPKs table rows use dedicated route
        $respLpks = $this->actingAs($viewer)->get(route('account-links.show', [$owner, 'tab' => 'lpks']));
        $respLpks->assertOk();
        $expectedLpkUrl = route('account-links.lpks.show', [$owner, $lpk]);
        $respLpks->assertSee('data-href="' . $expectedLpkUrl . '"', false);
        $respLpks->assertSee('href="' . $expectedLpkUrl . '"', false);

        // 2. Assessments table rows use dedicated route
        $respAssessments = $this->actingAs($viewer)->get(route('account-links.show', [$owner, 'tab' => 'assessments']));
        $respAssessments->assertOk();
        $expectedAsmUrl = route('account-links.assessments.show', [$owner, $assessment]);
        $respAssessments->assertSee('data-href="' . $expectedAsmUrl . '"', false);
        $respAssessments->assertSee('href="' . $expectedAsmUrl . '"', false);
    }
}

