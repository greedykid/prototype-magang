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
        $response->assertSee('Manajemen Pengguna & PIC', false);
        $response->assertSee('Budi Administrator Unit');
        $response->assertSee('Siti PIC Laboratorium');
        $response->assertSee('Admin Unit Akreditasi Lab');
        $response->assertSee('PIC Laboratorium');
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
}
