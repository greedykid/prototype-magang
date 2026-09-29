<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Lpk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class TableMultiselectTest extends TestCase
{
    use RefreshDatabase;

    public function test_ui_renders_multiselect_checkboxes_and_bulk_bar_in_lpks(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $lpk = Lpk::factory()->create(['name' => 'Laboratorium Pengujian Mutu']);

        $response = $this->actingAs($admin)->get(route('lpks.index'));

        $response->assertOk();
        $response->assertSee('table-select-all');
        $response->assertSee('table-row-select');
        $response->assertSee('floating-bulk-bar');
        $response->assertSee('id="lpks-table-bulk-bar"', false);
        $response->assertSee('Ekspor CSV');
        $response->assertSee('Hapus Terpilih');
    }

    public function test_ui_renders_multiselect_checkboxes_and_bulk_bar_in_assessments(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $lpk = Lpk::factory()->create(['name' => 'Laboratorium Kalibrasi Presisi']);
        $assessment = Assessment::factory()->create([
            'lpk_id' => $lpk->id,
            'title' => 'Asesmen Rutin S1',
            'created_by' => $admin->id,
        ]);

        $response = $this->actingAs($admin)->get(route('assessments.index'));

        $response->assertOk();
        $response->assertSee('table-select-all');
        $response->assertSee('table-row-select');
        $response->assertSee('floating-bulk-bar');
        $response->assertSee('id="assessments-table-bulk-bar"', false);
        $response->assertSee('Ekspor CSV');
        $response->assertSee('Hapus Terpilih');
    }

    public function test_bulk_export_lpks_filters_by_selected_ids(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $lpk1 = Lpk::factory()->create(['name' => 'LPK Alpha Satu']);
        $lpk2 = Lpk::factory()->create(['name' => 'LPK Beta Dua']);
        $lpk3 = Lpk::factory()->create(['name' => 'LPK Gamma Tiga']);

        // Export with ids=lpk1,lpk3
        $response = $this->actingAs($user)->get(route('reports.lpks.export', [
            'ids' => "{$lpk1->id},{$lpk3->id}",
        ]));

        $response->assertOk();
        $content = $response->streamedContent();

        $this->assertStringContainsString('LPK Alpha Satu', $content);
        $this->assertStringContainsString('LPK Gamma Tiga', $content);
        $this->assertStringNotContainsString('LPK Beta Dua', $content);
    }

    public function test_bulk_export_assessments_filters_by_selected_ids(): void
    {
        $user = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $lpk = Lpk::factory()->create(['name' => 'LPK Penguji']);

        $asm1 = Assessment::factory()->create([
            'lpk_id' => $lpk->id,
            'title' => 'Agenda Asesmen Kesatu',
            'created_by' => $user->id,
        ]);
        $asm2 = Assessment::factory()->create([
            'lpk_id' => $lpk->id,
            'title' => 'Agenda Asesmen Kedua',
            'created_by' => $user->id,
        ]);

        // Export with ids=asm2
        $response = $this->actingAs($user)->get(route('reports.assessments.export', [
            'ids' => (string) $asm2->id,
        ]));

        $response->assertOk();
        $content = $response->streamedContent();

        $this->assertStringContainsString('Agenda Asesmen Kedua', $content);
        $this->assertStringNotContainsString('Agenda Asesmen Kesatu', $content);
    }

    public function test_admin_can_bulk_delete_lpks_via_json(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $lpk1 = Lpk::factory()->create(['name' => 'LPK Akan Dihapus 1']);
        $lpk2 = Lpk::factory()->create(['name' => 'LPK Akan Dihapus 2']);
        $lpk3 = Lpk::factory()->create(['name' => 'LPK Tetap Ada']);

        $response = $this->actingAs($admin)->postJson(route('lpks.bulk-destroy'), [
            'ids' => [$lpk1->id, $lpk2->id],
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'deleted_count' => 2,
        ]);

        $this->assertDatabaseMissing('lpks', ['id' => $lpk1->id]);
        $this->assertDatabaseMissing('lpks', ['id' => $lpk2->id]);
        $this->assertDatabaseHas('lpks', ['id' => $lpk3->id]);
    }

    public function test_pic_can_only_bulk_delete_managed_lpks(): void
    {
        $pic = User::factory()->create(['role' => User::ROLE_PIC]);
        $otherPic = User::factory()->create(['role' => User::ROLE_PIC]);

        $myLpk = Lpk::factory()->create(['name' => 'LPK Milik Saya', 'pic_id' => $pic->id]);
        $otherLpk = Lpk::factory()->create(['name' => 'LPK Milik Orang Lain', 'pic_id' => $otherPic->id]);

        $response = $this->actingAs($pic)->postJson(route('lpks.bulk-destroy'), [
            'ids' => [$myLpk->id, $otherLpk->id],
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'deleted_count' => 1,
        ]);

        $this->assertDatabaseMissing('lpks', ['id' => $myLpk->id]);
        $this->assertDatabaseHas('lpks', ['id' => $otherLpk->id]);
    }

    public function test_admin_can_bulk_delete_assessments_via_json(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $lpk = Lpk::factory()->create();

        $asm1 = Assessment::factory()->create(['lpk_id' => $lpk->id, 'created_by' => $admin->id]);
        $asm2 = Assessment::factory()->create(['lpk_id' => $lpk->id, 'created_by' => $admin->id]);

        $response = $this->actingAs($admin)->postJson(route('assessments.bulk-destroy'), [
            'ids' => [$asm1->id, $asm2->id],
        ]);

        $response->assertOk();
        $response->assertJson([
            'success' => true,
            'deleted_count' => 2,
        ]);

        $this->assertDatabaseMissing('assessments', ['id' => $asm1->id]);
        $this->assertDatabaseMissing('assessments', ['id' => $asm2->id]);
    }

    public function test_bulk_delete_returns_422_when_no_ids_provided(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $responseLpk = $this->actingAs($admin)->postJson(route('lpks.bulk-destroy'), [
            'ids' => [],
        ]);
        $responseLpk->assertStatus(422);

        $responseAsm = $this->actingAs($admin)->postJson(route('assessments.bulk-destroy'), [
            'ids' => [],
        ]);
        $responseAsm->assertStatus(422);
    }
}
