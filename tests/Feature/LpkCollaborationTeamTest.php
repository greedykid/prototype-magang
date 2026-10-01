<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Lpk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LpkCollaborationTeamTest extends TestCase
{
    use RefreshDatabase;

    public function test_lead_pic_can_link_another_pic_as_viewer(): void
    {
        $leadPic = User::factory()->pic()->create(['name' => 'Ketua Tim Lab']);
        $viewerPic = User::factory()->pic()->create(['name' => 'Anggota Tim PIC']);
        $lpk = Lpk::factory()->create([
            'name' => 'Laboratorium Pengujian Mutu',
            'registration_number' => 'LP-TEST-001',
            'pic_id' => $leadPic->id,
        ]);

        $response = $this->actingAs($leadPic)->post(route('lpks.members.store', $lpk), [
            'user_id' => $viewerPic->id,
            'role' => 'viewer',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('lpk_members', [
            'lpk_id' => $lpk->id,
            'user_id' => $viewerPic->id,
            'role' => 'viewer',
        ]);

        $this->assertTrue($lpk->isLeadPic($leadPic));
        $this->assertTrue($lpk->isViewerPic($viewerPic));
        $this->assertTrue($lpk->canView($viewerPic));
        $this->assertFalse($lpk->canManage($viewerPic));
    }

    public function test_admin_can_link_and_remove_members_for_any_lpk(): void
    {
        $admin = User::factory()->admin()->create();
        $leadPic = User::factory()->pic()->create();
        $viewerPic = User::factory()->pic()->create();
        $lpk = Lpk::factory()->create(['pic_id' => $leadPic->id]);

        // Admin menautkan PIC
        $this->actingAs($admin)->post(route('lpks.members.store', $lpk), [
            'user_id' => $viewerPic->id,
            'role' => 'viewer',
        ])->assertRedirect();

        $this->assertDatabaseHas('lpk_members', [
            'lpk_id' => $lpk->id,
            'user_id' => $viewerPic->id,
        ]);

        // Admin memutuskan tautan PIC
        $this->actingAs($admin)->delete(route('lpks.members.destroy', [$lpk, $viewerPic]))
            ->assertRedirect();

        $this->assertDatabaseMissing('lpk_members', [
            'lpk_id' => $lpk->id,
            'user_id' => $viewerPic->id,
        ]);
    }

    public function test_viewer_pic_can_view_lpk_index_and_show_without_duplicates(): void
    {
        $leadPic = User::factory()->pic()->create();
        $viewerPic = User::factory()->pic()->create();
        $lpk = Lpk::factory()->create([
            'name' => 'Lab Kolaborasi Terakreditasi',
            'registration_number' => 'LP-COLLAB-01',
            'pic_id' => $leadPic->id,
        ]);

        $lpk->members()->attach($viewerPic->id, ['role' => 'viewer']);

        // 1. Muncul di daftar LPK milik viewer
        $indexResponse = $this->actingAs($viewerPic)->get(route('lpks.index'));
        $indexResponse->assertOk()
            ->assertSee('Lab Kolaborasi Terakreditasi')
            ->assertSee('Viewer');

        // Pastikan hanya muncul 1 kali (deduplikasi query accessibleBy)
        $content = $indexResponse->getContent();
        $this->assertEquals(1, substr_count($content, 'data-sort-value="Lab Kolaborasi Terakreditasi"'));

        // 2. Bisa membuka detail LPK
        $showResponse = $this->actingAs($viewerPic)->get(route('lpks.show', $lpk));
        $showResponse->assertOk()
            ->assertSee('Lab Kolaborasi Terakreditasi')
            ->assertSee('Akses: Viewer (Hanya Lihat)')
            ->assertDontSee('Ubah data')
            ->assertDontSee('Hapus LPK')
            ->assertDontSee('modal-input-keterangan');
    }

    public function test_viewer_pic_is_forbidden_from_editing_updating_and_deleting_lpk(): void
    {
        $leadPic = User::factory()->pic()->create();
        $viewerPic = User::factory()->pic()->create();
        $lpk = Lpk::factory()->create([
            'name' => 'Lab Uji Kalibrasi',
            'registration_number' => 'LK-001',
            'pic_id' => $leadPic->id,
        ]);

        $lpk->members()->attach($viewerPic->id, ['role' => 'viewer']);

        // Form edit LPK
        $this->actingAs($viewerPic)->get(route('lpks.edit', $lpk))->assertForbidden();

        // Update LPK
        $this->actingAs($viewerPic)->put(route('lpks.update', $lpk), [
            'name' => 'Lab Ubahan Viewer',
        ])->assertForbidden();

        // Update Catatan
        $this->actingAs($viewerPic)->post(route('lpks.notes.update', $lpk), [
            'notes' => 'Catatan ilegal',
        ])->assertForbidden();

        // Hapus LPK
        $this->actingAs($viewerPic)->delete(route('lpks.destroy', $lpk))->assertForbidden();
    }

    public function test_viewer_pic_is_forbidden_from_managing_team_members(): void
    {
        $leadPic = User::factory()->pic()->create();
        $viewerPic = User::factory()->pic()->create();
        $otherPic = User::factory()->pic()->create();
        $lpk = Lpk::factory()->create(['pic_id' => $leadPic->id]);

        $lpk->members()->attach($viewerPic->id, ['role' => 'viewer']);

        // Viewer mencoba menautkan PIC lain
        $this->actingAs($viewerPic)->post(route('lpks.members.store', $lpk), [
            'user_id' => $otherPic->id,
            'role' => 'viewer',
        ])->assertForbidden();

        // Viewer mencoba memutuskan Ketua Tim
        $this->actingAs($viewerPic)->delete(route('lpks.members.destroy', [$lpk, $leadPic]))
            ->assertForbidden();
    }

    public function test_viewer_pic_cannot_create_or_modify_assessments_for_the_lpk(): void
    {
        $leadPic = User::factory()->pic()->create();
        $viewerPic = User::factory()->pic()->create();
        $lpk = Lpk::factory()->create(['pic_id' => $leadPic->id]);

        $lpk->members()->attach($viewerPic->id, ['role' => 'viewer']);

        $assessment = Assessment::factory()->create([
            'lpk_id' => $lpk->id,
            'created_by' => $leadPic->id,
        ]);

        // Form tambah asesmen khusus LPK ini
        $this->actingAs($viewerPic)->get(route('assessments.create', ['lpk_id' => $lpk->id]))
            ->assertForbidden();

        // Simpan asesmen baru
        $this->actingAs($viewerPic)->post(route('assessments.store'), [
            'lpk_id' => $lpk->id,
            'title' => 'Surveilen Ilegal',
            'assessment_type' => 'SURVEILLANCE',
            'start_at' => now()->addDays(10)->format('Y-m-d H:i:s'),
            'end_at' => now()->addDays(12)->format('Y-m-d H:i:s'),
        ])->assertForbidden();

        // Edit asesmen
        $this->actingAs($viewerPic)->get(route('assessments.edit', $assessment))->assertForbidden();

        // Update asesmen
        $this->actingAs($viewerPic)->put(route('assessments.update', $assessment), [
            'title' => 'Judul Baru',
        ])->assertForbidden();

        // Hapus asesmen
        $this->actingAs($viewerPic)->delete(route('assessments.destroy', $assessment))->assertForbidden();
    }

    public function test_unlinking_member_revokes_access_immediately(): void
    {
        $leadPic = User::factory()->pic()->create();
        $viewerPic = User::factory()->pic()->create();
        $lpk = Lpk::factory()->create([
            'name' => 'Lab Segera Diputus',
            'pic_id' => $leadPic->id,
        ]);

        $lpk->members()->attach($viewerPic->id, ['role' => 'viewer']);

        // Verifikasi awalnya bisa akses
        $this->actingAs($viewerPic)->get(route('lpks.show', $lpk))->assertOk();

        // Ketua tim memutuskan tautan
        $this->actingAs($leadPic)->delete(route('lpks.members.destroy', [$lpk, $viewerPic]))
            ->assertRedirect();

        // Akses detail sekarang menjadi 403
        $this->actingAs($viewerPic)->get(route('lpks.show', $lpk))->assertForbidden();

        // Tidak lagi muncul di index viewer
        $this->actingAs($viewerPic)->get(route('lpks.index'))
            ->assertDontSee('Lab Segera Diputus');
    }

    public function test_cannot_link_same_pic_twice_or_link_owner_as_member(): void
    {
        $leadPic = User::factory()->pic()->create();
        $otherPic = User::factory()->pic()->create();
        $lpk = Lpk::factory()->create(['pic_id' => $leadPic->id]);

        // Coba tautkan pemilik sendiri
        $response1 = $this->actingAs($leadPic)->post(route('lpks.members.store', $lpk), [
            'user_id' => $leadPic->id,
            'role' => 'viewer',
        ]);
        $response1->assertRedirect();
        $response1->assertSessionHas('error');

        // Tautkan pertama kali
        $this->actingAs($leadPic)->post(route('lpks.members.store', $lpk), [
            'user_id' => $otherPic->id,
            'role' => 'viewer',
        ])->assertRedirect()->assertSessionHas('success');

        // Coba tautkan kedua kali untuk PIC yang sama
        $response2 = $this->actingAs($leadPic)->post(route('lpks.members.store', $lpk), [
            'user_id' => $otherPic->id,
            'role' => 'viewer',
        ]);
        $response2->assertRedirect();
        $response2->assertSessionHas('error');
    }
}
