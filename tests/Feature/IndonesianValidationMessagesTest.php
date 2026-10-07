<?php

namespace Tests\Feature;

use App\Models\Lpk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class IndonesianValidationMessagesTest extends TestCase
{
    use RefreshDatabase;

    public function test_lpk_duplicate_no_reg_returns_indonesian_validation_message(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        Lpk::factory()->create([
            'no_reg' => 'LP-001-IDN',
            'registration_number' => 'REG-001',
        ]);

        $response = $this->actingAs($admin)->post(route('lpks.store'), [
            'no_reg' => 'LP-001-IDN',
            'name' => 'Laboratorium Uji Coba',
            'status' => 'ACTIVE',
        ]);

        $response->assertSessionHasErrors(['no_reg']);

        $errors = session('errors')->get('no_reg');
        $this->assertNotEmpty($errors);
        $this->assertStringContainsString('Nomor registrasi sudah terdaftar', $errors[0]);
        $this->assertStringNotContainsString('The no reg has already been taken', $errors[0]);
    }

    public function test_lpk_required_fields_return_indonesian_validation_messages(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->post(route('lpks.store'), []);

        $response->assertSessionHasErrors(['name', 'status']);

        $nameErrors = session('errors')->get('name');
        $this->assertStringContainsString('Nama LPK wajib diisi', $nameErrors[0]);

        $statusErrors = session('errors')->get('status');
        $this->assertStringContainsString('Status operasional LPK wajib dipilih', $statusErrors[0]);
    }

    public function test_assessment_validation_returns_indonesian_messages(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);
        $lpk = Lpk::factory()->create();

        $response = $this->actingAs($admin)->post(route('assessments.store'), [
            'lpk_id' => $lpk->id,
            'title' => '',
            'assessment_type' => '',
            'start_at' => '2026-05-10 10:00',
            'end_at' => '2026-05-09 10:00',
        ]);

        $response->assertSessionHasErrors(['title', 'assessment_type', 'end_at']);

        $titleErrors = session('errors')->get('title');
        $this->assertStringContainsString('Judul kegiatan asesmen wajib diisi', $titleErrors[0]);

        $typeErrors = session('errors')->get('assessment_type');
        $this->assertStringContainsString('Jenis asesmen wajib dipilih', $typeErrors[0]);

        $endErrors = session('errors')->get('end_at');
        $this->assertStringContainsString('Waktu selesai pelaksanaan harus setelah waktu mulai pelaksanaan', $endErrors[0]);
    }

    public function test_calendar_event_validation_returns_indonesian_messages(): void
    {
        $admin = User::factory()->create(['role' => User::ROLE_ADMIN]);

        $response = $this->actingAs($admin)->post(route('calendar.events.store'), [
            'title' => '',
            'start_at' => '2026-06-15 10:00',
            'end_at' => '2026-06-15 09:00',
        ]);

        $response->assertSessionHasErrors(['lpk_id', 'title', 'end_at', 'status']);

        $lpkErrors = session('errors')->get('lpk_id');
        $this->assertStringContainsString('LPK wajib dipilih', $lpkErrors[0]);

        $titleErrors = session('errors')->get('title');
        $this->assertStringContainsString('Judul kegiatan agenda wajib diisi', $titleErrors[0]);

        $endErrors = session('errors')->get('end_at');
        $this->assertStringContainsString('Waktu selesai kegiatan harus setelah waktu mulai kegiatan', $endErrors[0]);
    }
}
