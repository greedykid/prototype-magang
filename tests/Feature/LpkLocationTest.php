<?php

namespace Tests\Feature;

use App\Models\Assessment;
use App\Models\Lpk;
use App\Models\LpkLocation;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class LpkLocationTest extends TestCase
{
    use RefreshDatabase;

    public function test_user_can_add_branch_location_to_lpk(): void
    {
        $admin = User::factory()->admin()->create();
        $lpk = Lpk::factory()->create(['name' => 'Lab Penguji Mandiri']);

        // ensureMainLocation creates Kantor Pusat
        $this->assertEquals(1, $lpk->locations()->count());
        $this->assertTrue($lpk->locations()->first()->is_main);

        $response = $this->actingAs($admin)->post(route('lpks.locations.store', $lpk), [
            'name' => 'Laboratorium Cabang Surabaya',
            'location_type' => LpkLocation::TYPE_BRANCH_LAB,
            'address' => 'Jl. Rungkut Industri No. 10, Surabaya',
            'contact_person' => 'Budi Santoso',
            'contact_phone' => '08123456789',
            'contact_email' => 'budi@surabaya.lab.id',
        ]);

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertEquals(2, $lpk->locations()->count());
        $this->assertDatabaseHas('lpk_locations', [
            'lpk_id' => $lpk->id,
            'name' => 'Laboratorium Cabang Surabaya',
            'location_type' => LpkLocation::TYPE_BRANCH_LAB,
            'is_main' => false,
        ]);
    }

    public function test_setting_location_as_head_office_switches_previous_head_office_to_branch(): void
    {
        $admin = User::factory()->admin()->create();
        $lpk = Lpk::factory()->create();

        $mainLocation = $lpk->locations()->where('is_main', true)->first();
        $branchLocation = $lpk->locations()->create([
            'name' => 'Cabang Bandung',
            'location_type' => LpkLocation::TYPE_BRANCH_LAB,
            'address' => 'Jl. Soekarno Hatta No. 45, Bandung',
            'is_main' => false,
        ]);

        $response = $this->actingAs($admin)->put(route('lpks.locations.update', [$lpk, $branchLocation]), [
            'name' => 'Cabang Bandung Menjadi Pusat',
            'location_type' => LpkLocation::TYPE_HEAD_OFFICE,
            'address' => 'Jl. Soekarno Hatta No. 45, Bandung',
        ]);

        $response->assertRedirect();

        $this->assertTrue($branchLocation->fresh()->is_main);
        $this->assertEquals(LpkLocation::TYPE_HEAD_OFFICE, $branchLocation->fresh()->location_type);
        $this->assertFalse($mainLocation->fresh()->is_main);
        $this->assertEquals(LpkLocation::TYPE_BRANCH_LAB, $mainLocation->fresh()->location_type);
    }

    public function test_cannot_delete_only_remaining_location(): void
    {
        $admin = User::factory()->admin()->create();
        $lpk = Lpk::factory()->create();

        $soleLocation = $lpk->locations()->first();
        $this->assertEquals(1, $lpk->locations()->count());

        $response = $this->actingAs($admin)->delete(route('lpks.locations.destroy', [$lpk, $soleLocation]));

        $response->assertRedirect();
        $response->assertSessionHas('error', 'Tidak dapat menghapus satu-satunya lokasi yang terdaftar pada LPK ini.');
        $this->assertDatabaseHas('lpk_locations', ['id' => $soleLocation->id]);
    }

    public function test_cannot_delete_location_linked_to_assessments(): void
    {
        $admin = User::factory()->admin()->create();
        $lpk = Lpk::factory()->create();

        $branch = $lpk->locations()->create([
            'name' => 'Fasilitas Uji Cilegon',
            'location_type' => LpkLocation::TYPE_BRANCH_LAB,
            'address' => 'Kawasan Industri Krakatau, Cilegon',
            'is_main' => false,
        ]);

        Assessment::factory()->create([
            'lpk_id' => $lpk->id,
            'lpk_location_id' => $branch->id,
            'title' => 'Asesmen Lapangan Cilegon',
        ]);

        $response = $this->actingAs($admin)->delete(route('lpks.locations.destroy', [$lpk, $branch]));

        $response->assertRedirect();
        $response->assertSessionHas('error');
        $this->assertDatabaseHas('lpk_locations', ['id' => $branch->id]);
    }

    public function test_deleting_main_location_reassigns_main_status_to_remaining_location(): void
    {
        $admin = User::factory()->admin()->create();
        $lpk = Lpk::factory()->create();

        $mainLocation = $lpk->locations()->where('is_main', true)->first();
        $branchLocation = $lpk->locations()->create([
            'name' => 'Fasilitas Pengujian Kedua',
            'location_type' => LpkLocation::TYPE_BRANCH_LAB,
            'address' => 'Jl. Gatot Subroto No. 20',
            'is_main' => false,
        ]);

        $response = $this->actingAs($admin)->delete(route('lpks.locations.destroy', [$lpk, $mainLocation]));

        $response->assertRedirect();
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('lpk_locations', ['id' => $mainLocation->id]);
        $this->assertTrue($branchLocation->fresh()->is_main);
    }
}
