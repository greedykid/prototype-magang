<?php

namespace Tests\Feature;

use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class ErrorPageTest extends TestCase
{
    use RefreshDatabase;

    public function test_403_forbidden_page_renders_custom_access_restriction_view(): void
    {
        $pic = User::factory()->pic()->create(['name' => 'Budi PIC']);

        // PIC attempting to access admin-only route
        $response = $this->actingAs($pic)->get(route('users.index'));

        $response->assertForbidden();
        $response->assertSee('403 &bull; Batasan Akses', false);
        $response->assertSee('Akses ke Fitur Ini Dibatasi');
        $response->assertSee('PIC Laboratorium');
        $response->assertSee('Administrator Unit Akreditasi KAN');
        $response->assertSee('Kembali ke Ringkasan');
    }

    public function test_404_not_found_page_renders_custom_view(): void
    {
        $admin = User::factory()->admin()->create();

        $response = $this->actingAs($admin)->get('/rute-yang-pasti-tidak-ada-dalam-sistem');

        $response->assertNotFound();
        $response->assertSee('404 &bull; Tidak Ditemukan', false);
        $response->assertSee('Halaman Tidak Ditemukan');
        $response->assertSee('Kembali ke Ringkasan');
    }

    public function test_guest_404_renders_standalone_layout(): void
    {
        $response = $this->get('/rute-tamu-tidak-ada');

        $response->assertNotFound();
        $response->assertSee('404 &bull; Tidak Ditemukan', false);
        $response->assertSee('Menuju Halaman Masuk');
    }
}
