<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserManagementTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_user_management(): void
    {
        $response = $this->get('/users');
        $response->assertRedirect('/login');
    }

    public function test_pic_cannot_access_user_management(): void
    {
        $pic = User::factory()->create([
            'role' => User::ROLE_PIC,
        ]);

        $response = $this->actingAs($pic)->get('/users');
        $response->assertForbidden();

        $createResponse = $this->actingAs($pic)->get('/users/create');
        $createResponse->assertForbidden();
    }

    public function test_admin_can_view_user_management_list(): void
    {
        $admin = User::factory()->create([
            'name' => 'Budi Administrator Unit',
            'role' => User::ROLE_ADMIN,
        ]);

        $pic = User::factory()->create([
            'name' => 'Siti PIC Laboratorium',
            'role' => User::ROLE_PIC,
        ]);

        $response = $this->actingAs($admin)->get('/users');

        $response->assertOk();
        $response->assertSee('Manajemen Anggota', false);
        $response->assertSee('Budi Administrator Unit');
        $response->assertSee('Siti PIC Laboratorium');
        $response->assertSee('Ketua Tim');
        $response->assertSee('PIC Laboratorium');
    }

    public function test_admin_can_view_member_detail_and_workload(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $pic = User::factory()->create([
            'name' => 'Agus PIC Terjadwal',
            'role' => User::ROLE_PIC,
        ]);

        $leadLpk = \App\Models\Lpk::factory()->create([
            'name' => 'Lab Pengujian Utama Agus',
            'registration_number' => 'LP-TEST-AGUS-01',
            'pic_id' => $pic->id,
        ]);

        $otherLead = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $collabLpk = \App\Models\Lpk::factory()->create([
            'name' => 'Lab Kalibrasi Tim Kolaborasi',
            'registration_number' => 'LK-TEST-COL-02',
            'pic_id' => $otherLead->id,
        ]);
        $collabLpk->members()->attach($pic->id, [
            'role' => \App\Models\Lpk::MEMBER_ROLE_VIEWER,
        ]);

        $response = $this->actingAs($admin)->get('/users/' . $pic->id);

        $response->assertOk();
        $response->assertSee('Daftar Laboratorium yang Dikerjakan');
        $response->assertSee('Agus PIC Terjadwal');
        $response->assertSee('Lab Pengujian Utama Agus');
        $response->assertSee('PIC Utama');
        $response->assertSee('Lab Kalibrasi Tim Kolaborasi');
        $response->assertSee('PIC Viewer');
    }

    public function test_pic_cannot_view_member_detail(): void
    {
        $pic1 = User::factory()->create(['role' => User::ROLE_PIC]);
        $pic2 = User::factory()->create(['role' => User::ROLE_PIC]);

        $response = $this->actingAs($pic1)->get('/users/' . $pic2->id);
        $response->assertForbidden();
    }

    public function test_admin_can_search_and_filter_users(): void
    {
        $admin = User::factory()->create(['name' => 'Admin Utama', 'role' => User::ROLE_ADMIN]);
        $targetUser = User::factory()->create(['name' => 'Laboratorium Kalibrasi Bandung', 'role' => User::ROLE_PIC]);
        $otherUser = User::factory()->create(['name' => 'Laboratorium Penguji Surabaya', 'role' => User::ROLE_PIC]);

        $response = $this->actingAs($admin)->get('/users?search=Bandung');

        $response->assertOk();
        $response->assertSee('Laboratorium Kalibrasi Bandung');
        $response->assertDontSee('Laboratorium Penguji Surabaya');

        $filterResponse = $this->actingAs($admin)->get('/users?role=admin');
        $filterResponse->assertOk();
        $filterResponse->assertSee('Admin Utama');
        $filterResponse->assertDontSee('Laboratorium Kalibrasi Bandung');
    }

    public function test_admin_can_create_new_user(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->post('/users', [
            'name' => 'Dharma Staf PIC',
            'email' => 'dharma@lab-uji.id',
            'role' => User::ROLE_PIC,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertRedirect('/users');
        $response->assertSessionHas('success');

        $this->assertDatabaseHas('users', [
            'name' => 'Dharma Staf PIC',
            'email' => 'dharma@lab-uji.id',
            'role' => User::ROLE_PIC,
        ]);

        $newUser = User::where('email', 'dharma@lab-uji.id')->firstOrFail();
        $this->assertTrue(Hash::check('password123', $newUser->password));
    }

    public function test_admin_can_update_user_info_and_password(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $targetUser = User::factory()->create([
            'name' => 'PIC Lama',
            'email' => 'pic-lama@lab.id',
            'role' => User::ROLE_PIC,
            'password' => Hash::make('oldpassword'),
        ]);

        $response = $this->actingAs($admin)->put('/users/' . $targetUser->id, [
            'name' => 'PIC Baru Ditunjuk',
            'email' => 'pic-baru@lab.id',
            'role' => User::ROLE_PIC,
            'password' => 'newpassword123',
            'password_confirmation' => 'newpassword123',
        ]);

        $response->assertRedirect('/users');
        $response->assertSessionHas('success');

        $targetUser->refresh();
        $this->assertSame('PIC Baru Ditunjuk', $targetUser->name);
        $this->assertSame('pic-baru@lab.id', $targetUser->email);
        $this->assertTrue(Hash::check('newpassword123', $targetUser->password));
    }

    public function test_admin_cannot_degrade_own_role(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->put('/users/' . $admin->id, [
            'name' => $admin->name,
            'email' => $admin->email,
            'role' => User::ROLE_PIC, // Mencoba menurunkan peran sendiri
        ]);

        $response->assertSessionHas('error');
        $admin->refresh();
        $this->assertSame(User::ROLE_ADMIN, $admin->role);
    }

    public function test_admin_can_delete_other_user(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $targetUser = User::factory()->create(['role' => User::ROLE_PIC]);

        $response = $this->actingAs($admin)->delete('/users/' . $targetUser->id);

        $response->assertRedirect('/users');
        $response->assertSessionHas('success');

        $this->assertDatabaseMissing('users', ['id' => $targetUser->id]);
    }

    public function test_admin_cannot_delete_own_account(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->delete('/users/' . $admin->id);

        $response->assertSessionHas('error');
        $this->assertDatabaseHas('users', ['id' => $admin->id]);
    }

    public function test_cannot_create_or_promote_second_admin_user(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        // 1. Mencoba membuat admin kedua ditolak validasi
        $response = $this->actingAs($admin)->post('/users', [
            'name' => 'Admin Kedua',
            'email' => 'admin2@lab.id',
            'role' => User::ROLE_ADMIN,
            'password' => 'password123',
            'password_confirmation' => 'password123',
        ]);

        $response->assertSessionHasErrors('role');
        $this->assertDatabaseMissing('users', ['email' => 'admin2@lab.id']);

        // 2. Mencoba menaikkan PIC menjadi admin kedua juga ditolak
        $pic = User::factory()->create(['role' => User::ROLE_PIC]);
        $updateResponse = $this->actingAs($admin)->put('/users/' . $pic->id, [
            'name' => $pic->name,
            'email' => $pic->email,
            'role' => User::ROLE_ADMIN,
        ]);

        $updateResponse->assertSessionHasErrors('role');
        $pic->refresh();
        $this->assertSame(User::ROLE_PIC, $pic->role);
    }

    public function test_partial_ajax_request_returns_users_table_content_partial(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $pic = User::factory()->create([
            'name' => 'Bambang PIC Pengujian',
            'role' => User::ROLE_PIC,
        ]);

        $response = $this->actingAs($admin)->get(route('users.index'), [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-Partial-Content' => 'table',
        ]);

        $response->assertOk();
        $response->assertViewIs('users.partials.table-content');
        $response->assertSee('Bambang PIC Pengujian');
        $response->assertDontSee('Kelola akses anggota dan pantau distribusi penugasan laboratorium.');
    }

    public function test_partial_ajax_request_applies_live_search_and_role_filters(): void
    {
        $admin = User::factory()->create(['name' => 'Ketua Tim Tunggal', 'role' => User::ROLE_ADMIN]);
        $pic1 = User::factory()->create(['name' => 'Ahmad Teknisi', 'role' => User::ROLE_PIC]);
        $pic2 = User::factory()->create(['name' => 'Budi Analis', 'role' => User::ROLE_PIC]);

        // Filter search live
        $searchResponse = $this->actingAs($admin)->get(route('users.index', ['search' => 'Ahmad']), [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-Partial-Content' => 'table',
        ]);
        $searchResponse->assertOk();
        $searchResponse->assertViewIs('users.partials.table-content');
        $searchResponse->assertSee('Ahmad Teknisi');
        $searchResponse->assertDontSee('Budi Analis');

        // Filter role live
        $roleResponse = $this->actingAs($admin)->get(route('users.index', ['role' => 'admin']), [
            'X-Requested-With' => 'XMLHttpRequest',
            'X-Partial-Content' => 'table',
        ]);
        $roleResponse->assertOk();
        $roleResponse->assertViewIs('users.partials.table-content');
        $roleResponse->assertSee('Ketua Tim Tunggal');
        $roleResponse->assertDontSee('Ahmad Teknisi');
    }
}
