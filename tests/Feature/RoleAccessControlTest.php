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
            ->assertSee('Administrator')
            ->assertSee('Staf Administrasi')
            ->assertSee('Asesor / Auditor')
            ->assertSee('admin@kanmis.local')
            ->assertSee('staf@kanmis.local')
            ->assertSee('asesor@kanmis.local');
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

    public function test_staff_can_manage_lpks_but_cannot_access_technical_monitoring(): void
    {
        $staff = User::factory()->staff()->create();

        // Staf dapat membuka pembuatan LPK, asesmen, dan proses akreditasi
        $this->actingAs($staff)->get(route('lpks.create'))->assertOk();
        $this->actingAs($staff)->get(route('assessments.create'))->assertOk();
        $this->actingAs($staff)->get(route('accreditations.index'))->assertOk();

        // Staf DITOLAK (403) saat mencoba mengakses monitoring server/backup internal
        $this->actingAs($staff)->get(route('monitoring.services'))->assertForbidden();
        $this->actingAs($staff)->get(route('monitoring.backups'))->assertForbidden();

        // Sidebar Staf menampilkan modul akreditasi tetapi menyembunyikan monitoring server
        $staffDashboard = $this->actingAs($staff)->get(route('dashboard'));
        $staffDashboard->assertOk()
            ->assertSee('Administrasi LPK')
            ->assertSee('<span class="nav-label">Akreditasi</span>', false)
            ->assertSee('<span class="nav-label">Amandemen</span>', false)
            ->assertDontSee('<span class="nav-label">Layanan KANMIS</span>', false)
            ->assertDontSee('<span class="nav-label">Backup</span>', false);
    }

    public function test_assessor_pure_kan_flow_cannot_schedule_assessments_or_manage_lpks_but_can_report_expenses(): void
    {
        $assessor = User::factory()->assessor()->create();
        $lpk = Lpk::factory()->create();
        $assessment = Assessment::factory()->create([
            'lpk_id' => $lpk->id,
            'created_by' => $assessor->id,
        ]);

        // 1. Asesor DAPAT melihat daftar asesmen dan detail asesmen yang ditugaskan
        $this->actingAs($assessor)->get(route('assessments.index'))->assertOk();
        $this->actingAs($assessor)->get(route('assessments.show', $assessment))->assertOk();

        // 2. Sesuai alur asli KAN: Asesor DITOLAK (403) menjadwalkan atau mengubah asesmen (wewenang Sekretariat KAN)
        $this->actingAs($assessor)->get(route('assessments.create'))->assertForbidden();
        $this->actingAs($assessor)->post(route('assessments.store'), [
            'lpk_id' => $lpk->id,
            'title' => 'Asesmen Tanpa Izin Sekretariat',
        ])->assertForbidden();
        $this->actingAs($assessor)->get(route('assessments.edit', $assessment))->assertForbidden();

        // Tombol Tambah asesmen dan Ubah asesmen TIDAK MUNCUL pada antarmuka Asesor
        $asmIndexResponse = $this->actingAs($assessor)->get(route('assessments.index'));
        $asmIndexResponse->assertOk()->assertDontSee('Tambah asesmen');

        $asmShowResponse = $this->actingAs($assessor)->get(route('assessments.show', $assessment));
        $asmShowResponse->assertOk()
            ->assertDontSee('Ubah asesmen')
            ->assertDontSee('Verifikasi SBM');

        // 3. Asesor DAPAT melihat kalender dan melaporkan biaya perjalanan dinas mandiri
        $this->actingAs($assessor)->get(route('calendar.index'))->assertOk();
        $expenseResponse = $this->actingAs($assessor)->post(route('assessments.expenses.store', $assessment), [
            'daily_allowance' => 500000,
            'transport_cost' => 800000,
            'accommodation_cost' => 600000,
            'package_data_cost' => 100000,
            'receipt_note' => 'Kwitansi Perjalanan Dinas',
        ]);
        $expenseResponse->assertRedirect();

        // 4. Asesor DITOLAK (403) memverifikasi biaya SBM (hanya boleh staf/admin)
        $this->actingAs($assessor)->post(route('assessments.expenses.verify', $assessment), [
            'status' => 'TERVERIFIKASI',
        ])->assertForbidden();

        // 5. Asesor DITOLAK (403) membuat atau mengedit master LPK
        $this->actingAs($assessor)->get(route('lpks.create'))->assertForbidden();
        $this->actingAs($assessor)->get(route('lpks.edit', $lpk))->assertForbidden();

        // Tombol Tambah LPK dan Ubah data tidak muncul di antarmuka Asesor
        $lpkIndexResponse = $this->actingAs($assessor)->get(route('lpks.index'));
        $lpkIndexResponse->assertOk()->assertDontSee('Tambah LPK');

        $lpkShowResponse = $this->actingAs($assessor)->get(route('lpks.show', $lpk));
        $lpkShowResponse->assertOk()->assertDontSee('Ubah data');

        // 6. Asesor DITOLAK (403) mengakses proses akreditasi (billing & esign)
        $this->actingAs($assessor)->get(route('accreditations.index'))->assertForbidden();

        // 7. Asesor DITOLAK (403) mengakses monitoring server
        $this->actingAs($assessor)->get(route('monitoring.services'))->assertForbidden();

        // Sidebar Asesor menampilkan menu audit dan menyembunyikan administrasi akreditasi
        $dashboardResponse = $this->actingAs($assessor)->get(route('dashboard'));
        $dashboardResponse->assertOk()
            ->assertSee('Penugasan Asesmen')
            ->assertSee('Kalender Kerja')
            ->assertSee('Data Lembaga (LPK)')
            ->assertDontSee('<span class="nav-label">Akreditasi</span>', false)
            ->assertDontSee('<span class="nav-label">Amandemen</span>', false);
    }

    public function test_role_helpers_and_attributes_work_correctly(): void
    {
        $admin = User::factory()->admin()->create();
        $staff = User::factory()->staff()->create();
        $assessor = User::factory()->assessor()->create();

        $this->assertTrue($admin->isAdmin());
        $this->assertFalse($admin->isStaff());
        $this->assertEquals('Administrator Sistem', $admin->role_label);
        $this->assertEquals('badge-role-admin', $admin->role_badge_class);

        $this->assertTrue($staff->isStaff());
        $this->assertFalse($staff->isAdmin());
        $this->assertEquals('Staf Administrasi', $staff->role_label);
        $this->assertEquals('badge-role-staff', $staff->role_badge_class);

        $this->assertTrue($assessor->isAssessor());
        $this->assertEquals('Auditor / Asesor KAN', $assessor->role_label);
        $this->assertEquals('badge-role-assessor', $assessor->role_badge_class);
    }
}
