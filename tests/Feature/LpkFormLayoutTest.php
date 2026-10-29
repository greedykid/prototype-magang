<?php

namespace Tests\Feature;

use App\Models\Lpk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LpkFormLayoutTest extends TestCase
{
    use RefreshDatabase;

    public function test_lpk_edit_view_renders_two_column_redesign(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $lpk = Lpk::factory()->create([
            'name' => 'Laboratorium Pengujian Mutu Terpadu',
            'no_reg' => 'LPK-0312',
            'accreditation_type' => 'Laboratorium Penguji',
            'accreditation_number' => 'LP-077-IDN',
            'status' => 'ACTIVE',
            'certificate_date' => now()->subYears(1),
            'expired_at' => now()->addYears(4),
            'scope' => 'Pengujian air dan mineral konstruksi.',
            'email' => 'labmutu@example.com',
            'phone' => '021-889977',
            'address' => 'Gedung KAN Lantai 3, Jakarta',
        ]);

        $response = $this->actingAs($admin)->get(route('lpks.edit', $lpk));

        $response->assertOk();
        $response->assertSee('Ubah Data LPK');
        $response->assertSee('lpk-form-v2-container', false);
        $response->assertSee('Profil &amp; Legalitas LPK', false);
        $response->assertSee('Ruang Lingkup Akreditasi (Scope)');
        $response->assertSee('Masa Berlaku Sertifikat');
        $response->assertSee('SIKLUS PENGAWASAN KAN');
        $response->assertSee('Kontak &amp; Fasilitas', false);
        $response->assertSee('Zona Bahaya');
        $response->assertSee('Riwayat perubahan');
        $response->assertSee('modal-lpk-history');
        $response->assertSee('lpk-dirty-pill', false);
        $response->assertSee('Simpan perubahan');
        $response->assertSee('Laboratorium Pengujian Mutu Terpadu');
        $response->assertSee('LPK-0312');
        $response->assertSee('LP-077-IDN');
    }

    public function test_lpk_create_view_renders_uniform_layout_without_danger_zone(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->get(route('lpks.create'));

        $response->assertOk();
        $response->assertSee('Tambah LPK');
        $response->assertSee('lpk-form-v2-container', false);
        $response->assertSee('Profil &amp; Legalitas LPK', false);
        $response->assertSee('Ruang Lingkup Akreditasi (Scope)');
        $response->assertSee('Masa Berlaku Sertifikat');
        $response->assertSee('Kontak &amp; Fasilitas', false);
        $response->assertDontSee('Zona Bahaya');
        $response->assertDontSee('modal-lpk-history');
        $response->assertSee('Simpan data LPK');
    }

    public function test_lpk_can_be_updated_through_form_submission(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $lpk = Lpk::factory()->create([
            'name' => 'Lab Fisika Lama',
            'status' => 'ACTIVE',
        ]);

        $response = $this->actingAs($admin)->put(route('lpks.update', $lpk), [
            'name' => 'Lab Fisika dan Kalibrasi Terbaru',
            'accreditation_type' => 'Laboratorium Kalibrasi',
            'no_reg' => 'LK-9988',
            'accreditation_number' => 'LK-001-IDN',
            'status' => 'ACTIVE',
            'certificate_date' => '2025-01-15',
            'expired_at' => '2030-01-15',
            'scope' => 'Kalibrasi dimensi, suhu, dan massa.',
            'email' => 'kalibrasi@lab.co.id',
            'phone' => '021-5551234',
            'address' => 'Jl. Pengujian No. 10, Tangerang',
        ]);

        $response->assertRedirect(route('lpks.show', $lpk));
        $this->assertDatabaseHas('lpks', [
            'id' => $lpk->id,
            'name' => 'Lab Fisika dan Kalibrasi Terbaru',
            'no_reg' => 'LK-9988',
            'accreditation_number' => 'LK-001-IDN',
        ]);
    }
}
