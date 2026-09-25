<?php

namespace Tests\Feature;

use App\Models\Accreditation;
use App\Models\Assessment;
use App\Models\Lpk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class RoleAccessControlTest extends TestCase
{
    use RefreshDatabase;

    public function test_quick_login_page_renders_all_role_options(): void
    {
        $response = $this->get(route('login'));

        $response->assertOk()
            ->assertSee('Admin Unit Lab')
            ->assertSee('PIC Laboratorium')
            ->assertSee('admin@simasadi.local')
            ->assertSee('pic@simasadi.local');
    }

    public function test_admin_has_full_access_to_monitoring_and_administration(): void
    {
        $admin = User::factory()->admin()->create();

        // Dapat mengakses monitoring
        $this->actingAs($admin)->get(route('monitoring.backups'))->assertOk();

        // Dapat mengakses form tambah LPK
        $this->actingAs($admin)->get(route('lpks.create'))->assertOk();

        // Dapat mengakses dashboard
        $this->actingAs($admin)->get(route('dashboard'))->assertOk();
    }

    public function test_pic_role_can_manage_own_lpks_and_assessments_but_forbidden_from_other_pic_lpks_and_administrative_tools(): void
    {
        $pic = User::factory()->pic()->create();
        $ownLpk = Lpk::factory()->create(['pic_id' => $pic->id]);
        $otherPic = User::factory()->pic()->create();
        $otherLpk = Lpk::factory()->create(['pic_id' => $otherPic->id]);

        $assessment = Assessment::factory()->create([
            'lpk_id' => $ownLpk->id,
            'created_by' => $pic->id,
        ]);

        // 1. PIC DAPAT melihat dashboard, daftar lab, dan detail lab miliknya
        $this->actingAs($pic)->get(route('dashboard'))->assertOk();
        $this->actingAs($pic)->get(route('lpks.index'))->assertOk();
        $this->actingAs($pic)->get(route('lpks.show', $ownLpk))->assertOk();
        $this->actingAs($pic)->get(route('assessments.index'))->assertOk();
        $this->actingAs($pic)->get(route('calendar.index'))->assertOk();

        // 2. PIC DAPAT membuat LPK miliknya sendiri
        $this->actingAs($pic)->get(route('lpks.create'))->assertOk();
        $this->actingAs($pic)->post(route('lpks.store'), [
            'registration_number' => 'LP-OWN-01',
            'name' => 'Lab Binaan PIC',
            'status' => 'ACTIVE',
        ])->assertRedirect();
        $this->assertDatabaseHas('lpks', [
            'registration_number' => 'LP-OWN-01',
            'pic_id' => $pic->id,
        ]);

        // Tombol Tambah LPK MUNCUL pada antarmuka PIC
        $lpkIndexResponse = $this->actingAs($pic)->get(route('lpks.index'));
        $lpkIndexResponse->assertOk()->assertSee('Tambah LPK');

        // Ubah data MUNCUL untuk LPK miliknya sendiri
        $ownLpkShowResponse = $this->actingAs($pic)->get(route('lpks.show', $ownLpk));
        $ownLpkShowResponse->assertOk()->assertSee('Ubah data');

        // 3. PIC DITOLAK (403) mengubah LPK milik PIC lain
        $this->actingAs($pic)->get(route('lpks.edit', $otherLpk))->assertForbidden();
        $this->actingAs($pic)->put(route('lpks.update', $otherLpk), [
            'name' => 'Lab Bajakan',
        ])->assertForbidden();

        // 4. PIC DAPAT memasukkan dan mengelola agenda asesmen (wewenang tim internal unit)
        $this->actingAs($pic)->get(route('assessments.create'))->assertOk();
        $this->actingAs($pic)->get(route('assessments.edit', $assessment))->assertOk();

        // Tombol Tambah asesmen dan Ubah asesmen MUNCUL untuk PIC
        $this->actingAs($pic)->get(route('assessments.index'))
            ->assertOk()
            ->assertSee('Tambah asesmen');

        $this->actingAs($pic)->get(route('assessments.show', $assessment))
            ->assertOk()
            ->assertSee('Ubah asesmen')
            ->assertDontSee('Verifikasi SBM')
            ->assertDontSee('modal-verify-expense');

        // Verifikasi biaya SBM tetap dibatasi hanya untuk Administrator
        $this->actingAs($pic)->post(route('assessments.expenses.verify', $assessment), [
            'status' => 'TERVERIFIKASI',
        ])->assertForbidden();

        // 5. PIC DITOLAK (403) mengakses monitoring server teknis
        $this->actingAs($pic)->get(route('monitoring.backups'))->assertForbidden();

        // 6. PIC DITOLAK (403) mengakses proses akreditasi internal
        $this->actingAs($pic)->get(route('accreditations.index'))->assertForbidden();

        // Tampilan Dashboard PIC TIDAK memuat tombol atau tautan yang dibatasi (403)
        $picDashboard = $this->actingAs($pic)->get(route('dashboard'));
        $picDashboard->assertOk()
            ->assertSee('Laboratorium Terakreditasi')
            ->assertSee('Data Laboratorium')
            ->assertDontSee('<span class="nav-label">Administrasi Laboratorium</span>', false)
            ->assertDontSee('<span class="nav-label">Layanan KANMIS</span>', false)
            ->assertDontSee('<span class="nav-label">Backup</span>', false)
            ->assertDontSee(route('accreditations.index'))
            ->assertDontSee('Finansial &amp; Administrasi', false);
    }

    public function test_role_helpers_and_attributes_work_correctly(): void
    {
        $admin = User::factory()->admin()->create();
        $pic = User::factory()->pic()->create();

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isPic());
        $this->assertEquals('Admin Unit Akreditasi Lab', $admin->role_label);
        $this->assertEquals('badge-role-admin', $admin->role_badge_class);

        $this->assertTrue($pic->isPic());
        $this->assertFalse($pic->isAdmin());
        $this->assertEquals('PIC Laboratorium', $pic->role_label);
        $this->assertEquals('badge-role-pic', $pic->role_badge_class);
    }

    public function test_admin_can_delete_lpk(): void
    {
        $admin = User::factory()->admin()->create();
        $lpk = Lpk::factory()->create();

        $response = $this->actingAs($admin)->delete(route('lpks.destroy', $lpk));

        $response->assertRedirect(route('lpks.index'))
            ->assertSessionHas('success');
        $this->assertDatabaseMissing('lpks', ['id' => $lpk->id]);
    }

    public function test_pic_can_delete_own_lpk_but_forbidden_from_deleting_other_pic_lpk(): void
    {
        $pic = User::factory()->pic()->create();
        $otherPic = User::factory()->pic()->create();

        $ownLpk = Lpk::factory()->create(['pic_id' => $pic->id]);
        $otherLpk = Lpk::factory()->create(['pic_id' => $otherPic->id]);

        // PIC dilarang menghapus LPK milik PIC lain (403)
        $responseForbidden = $this->actingAs($pic)->delete(route('lpks.destroy', $otherLpk));
        $responseForbidden->assertForbidden();
        $this->assertDatabaseHas('lpks', ['id' => $otherLpk->id]);

        // PIC dapat menghapus LPK miliknya sendiri
        $responseOwn = $this->actingAs($pic)->delete(route('lpks.destroy', $ownLpk));
        $responseOwn->assertRedirect(route('lpks.index'))
            ->assertSessionHas('success');
        $this->assertDatabaseMissing('lpks', ['id' => $ownLpk->id]);
    }
}

