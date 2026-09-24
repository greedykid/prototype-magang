<?php

namespace Tests\Feature;

use App\Models\Accreditation;
use App\Models\AccreditationBilling;
use App\Models\Assessment;
use App\Models\AssessmentExpense;
use App\Models\Lpk;
use App\Models\User;
use Illuminate\Foundation\Testing\RefreshDatabase;
use Tests\TestCase;

class SimasadiFeaturesTest extends TestCase
{
    use RefreshDatabase;

    public function test_can_report_and_verify_assessment_expenses(): void
    {
        $user = User::factory()->create();
        $lpk = Lpk::factory()->create();
        $assessment = Assessment::factory()->create([
            'lpk_id' => $lpk->id,
            'created_by' => $user->id,
        ]);

        // 1. Report expense
        $response = $this->actingAs($user)->post(route('assessments.expenses.store', $assessment), [
            'daily_allowance' => 860000,
            'transport_cost' => 1200000,
            'accommodation_cost' => 700000,
            'package_data_cost' => 150000,
            'receipt_note' => 'Kwitansi GA-412 & Hotel Santika',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('assessment_expenses', [
            'assessment_id' => $assessment->id,
            'reported_by' => $user->id,
            'total_cost' => 2910000,
            'status' => 'MENUNGGU_VERIFIKASI',
        ]);

        // 2. Verify expense
        $verifyResponse = $this->actingAs($user)->post(route('assessments.expenses.verify', $assessment), [
            'status' => 'TERVERIFIKASI',
            'verification_notes' => 'Sesuai SBM PMK No. 49/2023.',
        ]);

        $verifyResponse->assertRedirect();
        $this->assertDatabaseHas('assessment_expenses', [
            'assessment_id' => $assessment->id,
            'status' => 'TERVERIFIKASI',
            'verified_by' => $user->id,
        ]);

        // Check show page displays verified status and costs
        $this->actingAs($user)->get(route('assessments.show', $assessment))
            ->assertOk()
            ->assertSee('Terverifikasi SBM')
            ->assertSee('2.910.000');
    }

    public function test_can_generate_and_pay_pnbp_billing(): void
    {
        $user = User::factory()->create();
        $lpk = Lpk::factory()->create();
        $accreditation = Accreditation::factory()->create(['lpk_id' => $lpk->id]);

        // 1. Generate SIMPONI billing
        $response = $this->actingAs($user)->post(route('accreditations.billings.store', $accreditation), [
            'tariff_name' => 'PNBP Akreditasi Lab Uji',
            'amount' => 7500000,
        ]);

        $response->assertRedirect();
        $billing = AccreditationBilling::where('accreditation_id', $accreditation->id)->firstOrFail();
        $this->assertEquals('UNPAID', $billing->status);
        $this->assertStringStartsWith('8', $billing->billing_code);
        $this->assertEquals(15, strlen($billing->billing_code));

        // 2. Simulate payment (Pelunasan Billing SIMPONI)
        $payResponse = $this->actingAs($user)->post(route('accreditations.billings.pay', [$accreditation, $billing]), [
            'payment_channel' => 'Bank Mandiri (Livin)',
            'ntpn' => 'NTPN12345678ABCD',
        ]);

        $payResponse->assertRedirect();
        $billing->refresh();
        $this->assertEquals('PAID', $billing->status);
        $this->assertEquals('NTPN12345678ABCD', $billing->ntpn);
        $this->assertNotNull($billing->paid_at);

        // Check show page displays paid billing
        $this->actingAs($user)->get(route('accreditations.show', $accreditation))
            ->assertOk()
            ->assertSee('Terbayar')
            ->assertSee('NTPN12345678ABCD');
    }

    public function test_can_sign_accreditation_with_bsre_esign_and_verify_publicly(): void
    {
        $user = User::factory()->create();
        $lpk = Lpk::factory()->create();
        $accreditation = Accreditation::factory()->create(['lpk_id' => $lpk->id]);

        // 1. Sign digital document
        $response = $this->actingAs($user)->post(route('accreditations.esign.sign', $accreditation), [
            'passphrase' => 'kan-secret-key',
            'sk_number' => 'SK.KAN.099/BSN/IX/2026',
            'signer_name' => 'Drs. Kukuh S. Achmad, M.Sc.',
        ]);

        $response->assertRedirect();
        $this->assertDatabaseHas('accreditation_signatures', [
            'accreditation_id' => $accreditation->id,
            'is_signed' => true,
            'sk_number' => 'SK.KAN.099/BSN/IX/2026',
        ]);

        $accreditation->refresh();
        $this->assertEquals('COMPLETED', $accreditation->status);
        $this->assertNotNull($accreditation->output_released_at);

        $signature = $accreditation->signature;
        $this->assertNotNull($signature->verify_hash);

        // 2. Public verification page
        $this->get(route('accreditations.esign.verify', $signature->verify_hash))
            ->assertOk()
            ->assertSee('DOKUMEN ASLI', false)
            ->assertSee('TERVERIFIKASI SAH', false)
            ->assertSee('SK.KAN.099/BSN/IX/2026')
            ->assertSee('Drs. Kukuh S. Achmad, M.Sc.');
    }

    public function test_accreditation_release_readiness_gate_logic(): void
    {
        $lpk = Lpk::factory()->create();
        $accreditation = Accreditation::factory()->create(['lpk_id' => $lpk->id]);

        // Initially not ready
        $this->assertFalse($accreditation->isReleaseReady());

        // Create paid billing
        $accreditation->billings()->create([
            'billing_code' => '820260918000001',
            'tariff_name' => 'PNBP Test',
            'amount' => 7500000,
            'issued_at' => now(),
            'expired_at' => now()->addDays(7),
            'status' => 'PAID',
            'ntpn' => 'NTPN001122334455',
            'paid_at' => now(),
        ]);

        // Still not ready because not signed
        $this->assertFalse($accreditation->fresh()->isReleaseReady());

        // Sign document
        $accreditation->signature()->create([
            'sk_number' => 'SK.KAN.001/2026',
            'signer_name' => 'Ketua KAN',
            'signer_title' => 'Ketua KAN',
            'is_signed' => true,
            'signed_at' => now(),
            'certificate_series' => 'BSrE-001',
            'verify_hash' => 'hash123',
        ]);

        // Now release ready!
        $this->assertTrue($accreditation->fresh()->isReleaseReady());
    }

    public function test_can_prefill_assessment_schedule_from_surveillance_alert(): void
    {
        $user = User::factory()->create();
        $lpk = Lpk::factory()->create([
            'name' => 'Laboratorium Uji Mutu Unggul',
            'registration_number' => 'LP-999-IDN',
            'address' => 'Jl. Sangkuriang No. 1, Kota Bandung',
        ]);

        // 1. Test prefill with S1 alert
        $response = $this->actingAs($user)->get(route('assessments.create', [
            'lpk_id' => $lpk->id,
            'alert_code' => 'S1',
            'target_date' => '2026-10-15',
        ]));

        $response->assertOk();
        $response->assertSee('Jadwal Kunjungan Otomatis Disiapkan');
        $response->assertSee('Laboratorium Uji Mutu Unggul');
        $response->assertSee('Surveilen 1 Siklus KAN - Laboratorium Uji Mutu Unggul');
        $response->assertSee('Kota Bandung');
        $response->assertSee('2026-10-15T09:00');

        // 2. Test prefill with RA (Re-Akreditasi) alert
        $responseRA = $this->actingAs($user)->get(route('assessments.create', [
            'lpk_id' => $lpk->id,
            'alert_code' => 'RA',
            'target_date' => '2026-12-01',
        ]));

        $responseRA->assertOk();
        $responseRA->assertSee('Re-asesmen Siklus KAN - Laboratorium Uji Mutu Unggul');
    }
}
