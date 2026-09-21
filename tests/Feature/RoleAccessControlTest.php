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
        $this->actingAs($admin)->get(route('monitoring.services'))->assertOk();
        $this->actingAs($admin)->get(route('monitoring.backups'))->assertOk();

        // Dapat mengakses form tambah LPK
        $this->actingAs($admin)->get(route('lpks.create'))->assertOk();

        // Dapat mengakses dashboard
        $this->actingAs($admin)->get(route('dashboard'))->assertOk();
    }

    public function test_pic_role_can_view_dashboard_and_labs_but_forbidden_from_administrative_write(): void
    {
        $pic = User::factory()->pic()->create();
        $lpk = Lpk::factory()->create();
        $assessment = Assessment::factory()->create([
            'lpk_id' => $lpk->id,
            'created_by' => $pic->id,
        ]);

        // 1. PIC DAPAT melihat dashboard, daftar lab, dan detail lab
        $this->actingAs($pic)->get(route('dashboard'))->assertOk();
        $this->actingAs($pic)->get(route('lpks.index'))->assertOk();
        $this->actingAs($pic)->get(route('lpks.show', $lpk))->assertOk();
        $this->actingAs($pic)->get(route('assessments.index'))->assertOk();
        $this->actingAs($pic)->get(route('calendar.index'))->assertOk();

        // 2. PIC DITOLAK (403) membuat atau mengubah LPK (wewenang Admin Unit)
        $this->actingAs($pic)->get(route('lpks.create'))->assertForbidden();
        $this->actingAs($pic)->get(route('lpks.edit', $lpk))->assertForbidden();
        $this->actingAs($pic)->post(route('lpks.store'), [
            'registration_number' => 'LP-UNAUTH-01',
            'name' => 'Lab Ilegal',
            'status' => 'ACTIVE',
        ])->assertForbidden();

        // Tombol Tambah LPK dan Ubah data TIDAK MUNCUL pada antarmuka PIC
        $lpkIndexResponse = $this->actingAs($pic)->get(route('lpks.index'));
        $lpkIndexResponse->assertOk()->assertDontSee('Tambah LPK');

        $lpkShowResponse = $this->actingAs($pic)->get(route('lpks.show', $lpk));
        $lpkShowResponse->assertOk()->assertDontSee('Ubah data');

        // 3. PIC DITOLAK (403) mengakses manajemen asesmen resmi (create/edit)
        $this->actingAs($pic)->get(route('assessments.create'))->assertForbidden();
        $this->actingAs($pic)->get(route('assessments.edit', $assessment))->assertForbidden();

        // 4. PIC DITOLAK (403) mengakses monitoring server teknis
        $this->actingAs($pic)->get(route('monitoring.services'))->assertForbidden();
        $this->actingAs($pic)->get(route('monitoring.backups'))->assertForbidden();

        // 5. PIC DITOLAK (403) mengakses proses akreditasi internal
        $this->actingAs($pic)->get(route('accreditations.index'))->assertForbidden();

        // Sidebar PIC menampilkan navigasi khusus lab terakreditasi
        $picDashboard = $this->actingAs($pic)->get(route('dashboard'));
        $picDashboard->assertOk()
            ->assertSee('Laboratorium Terakreditasi')
            ->assertSee('Data Laboratorium')
            ->assertDontSee('<span class="nav-label">Administrasi Laboratorium</span>', false)
            ->assertDontSee('<span class="nav-label">Layanan KANMIS</span>', false)
            ->assertDontSee('<span class="nav-label">Backup</span>', false);
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

    public function test_pic_is_forbidden_from_deleting_lpk(): void
    {
        $pic = User::factory()->pic()->create();
        $lpk = Lpk::factory()->create();

        $response = $this->actingAs($pic)->delete(route('lpks.destroy', $lpk));

        $response->assertForbidden();
        $this->assertDatabaseHas('lpks', ['id' => $lpk->id]);
    }
}

