<?php

namespace App\Http\Controllers;

use App\Models\Accreditation;
use App\Models\AccreditationBilling;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AccreditationBillingController extends Controller
{
    public function store(Request $request, Accreditation $accreditation): RedirectResponse
    {
        $validated = $request->validate([
            'tariff_name' => ['nullable', 'string', 'max:255'],
            'amount' => ['nullable', 'integer', 'min:100000'],
        ]);

        $billing = AccreditationBilling::create([
            'accreditation_id' => $accreditation->id,
            'billing_code' => AccreditationBilling::generateSimponiCode(),
            'tariff_name' => $validated['tariff_name'] ?? 'PNBP Jasa Akreditasi Laboratorium / Lembaga Sertifikasi (PP PNBP BSN)',
            'amount' => $validated['amount'] ?? 7500000,
            'issued_at' => now(),
            'expired_at' => now()->addDays(7),
            'status' => 'UNPAID',
        ]);

        return back()->with('success', "Kode Billing SIMPONI ({$billing->billing_code}) berhasil diterbitkan dengan masa berlaku 7 hari.");
    }

    public function pay(Request $request, Accreditation $accreditation, AccreditationBilling $billing): RedirectResponse
    {
        $validated = $request->validate([
            'payment_channel' => ['required', 'string', 'max:100'],
            'ntpn' => ['nullable', 'string', 'max:20'],
        ]);

        $ntpn = ! empty($validated['ntpn'])
            ? strtoupper(trim($validated['ntpn']))
            : AccreditationBilling::generateNtpn();

        $billing->update([
            'status' => 'PAID',
            'payment_channel' => $validated['payment_channel'],
            'ntpn' => $ntpn,
            'ntb' => 'NTB-' . random_int(10000000, 99999999),
            'paid_at' => now(),
        ]);

        return back()->with('success', "Pembayaran Billing PNBP SIMPONI berhasil direalisasikan. NTPN sah: {$ntpn}.");
    }
}
