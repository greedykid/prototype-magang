<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Illuminate\Support\Facades\Hash;
use Tests\TestCase;

class UserProfileTest extends TestCase
{
    use RefreshDatabase;

    public function test_guest_cannot_access_profile(): void
    {
        $response = $this->get('/profile');
        $response->assertRedirect('/login');
    }

    public function test_authenticated_user_can_view_profile_page(): void
    {
        $user = User::factory()->create([
            'name' => 'Budi Administrator Unit',
            'email' => 'budi@simasadi.local',
            'role' => User::ROLE_ADMIN,
        ]);

        $response = $this->actingAs($user)->get('/profile');

        $response->assertOk();
        $response->assertSee('Profil &amp; Kata Sandi', false);
        $response->assertSee('Budi Administrator Unit');
        $response->assertSee('budi@simasadi.local');
        $response->assertSee('Admin Unit Akreditasi Lab');
    }

    public function test_user_can_update_profile_info(): void
    {
        $user = User::factory()->create([
            'name' => 'Nama Lama',
            'email' => 'lama@simasadi.local',
        ]);

        $response = $this->actingAs($user)->put('/profile', [
            'name' => 'Nama Baru Petugas',
            'email' => 'baru@simasadi.local',
        ]);

        $response->assertSessionHas('success');
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'name' => 'Nama Baru Petugas',
            'email' => 'baru@simasadi.local',
        ]);
    }

    public function test_user_cannot_update_email_to_already_taken_email(): void
    {
        User::factory()->create(['email' => 'sudahada@simasadi.local']);
        $user = User::factory()->create(['email' => 'saya@simasadi.local']);

        $response = $this->actingAs($user)->put('/profile', [
            'name' => 'Nama Saya',
            'email' => 'sudahada@simasadi.local',
        ]);

        $response->assertSessionHasErrors('email');
        $this->assertDatabaseHas('users', [
            'id' => $user->id,
            'email' => 'saya@simasadi.local',
        ]);
    }

    public function test_user_can_update_password_with_correct_current_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($user)->put('/profile/password', [
            'current_password' => 'password123',
            'password' => 'passwordBaru2026',
            'password_confirmation' => 'passwordBaru2026',
        ]);

        $response->assertSessionHas('success');
        $user->refresh();
        $this->assertTrue(Hash::check('passwordBaru2026', $user->password));
    }

    public function test_user_cannot_update_password_with_incorrect_current_password(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('passwordBenar'),
        ]);

        $response = $this->actingAs($user)->put('/profile/password', [
            'current_password' => 'passwordSalah',
            'password' => 'passwordBaru2026',
            'password_confirmation' => 'passwordBaru2026',
        ]);

        $response->assertSessionHasErrors('current_password');
        $user->refresh();
        $this->assertTrue(Hash::check('passwordBenar', $user->password));
    }

    public function test_user_cannot_update_password_with_mismatched_confirmation(): void
    {
        $user = User::factory()->create([
            'password' => Hash::make('password123'),
        ]);

        $response = $this->actingAs($user)->put('/profile/password', [
            'current_password' => 'password123',
            'password' => 'passwordBaru123',
            'password_confirmation' => 'passwordBedaTotal',
        ]);

        $response->assertSessionHasErrors('password');
    }
}
