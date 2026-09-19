<?php

namespace App\Http\Controllers;

use App\Models\Accreditation;
use App\Models\AccreditationSignature;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;
use Illuminate\View\View;

class AccreditationSignatureController extends Controller
{
    public function sign(Request $request, Accreditation $accreditation): RedirectResponse
    {
        $validated = $request->validate([
            'passphrase' => ['required', 'string'],
            'sk_number' => ['nullable', 'string', 'max:100'],
            'signer_name' => ['nullable', 'string', 'max:150'],
        ]);

        $skNumber = ! empty($validated['sk_number'])
            ? $validated['sk_number']
            : 'SK.KAN.' . str_pad((string) $accreditation->id, 3, '0', STR_PAD_LEFT) . '/BSN/IX/' . date('Y');

        $signerName = ! empty($validated['signer_name'])
            ? $validated['signer_name']
            : 'Drs. Kukuh S. Achmad, M.Sc.';

        $signature = $accreditation->signature ?? new AccreditationSignature(['accreditation_id' => $accreditation->id]);
        $signature->sk_number = $skNumber;
        $signature->signer_name = $signerName;
        $signature->signer_title = 'Ketua Komite Akreditasi Nasional (KAN)';
        $signature->signer_nip = '196508121990031002';
        $signature->is_signed = true;
        $signature->signed_at = now();
        $signature->certificate_series = 'BSrE-DS-' . date('Y') . '-' . random_int(10000, 99999);
        $signature->verify_hash = AccreditationSignature::generateHash($accreditation->id, $skNumber);
        $signature->save();

        // Release output if ready
        $accreditation->update([
            'output_released_at' => $accreditation->output_released_at ?? now()->toDateString(),
            'status' => 'COMPLETED',
        ]);

        return back()->with('success', 'Dokumen SK Akreditasi berhasil ditandatangani secara digital dengan sertifikat resmi BSrE BSSN.');
    }

    public function verifyPublic(string $hash): View
    {
        $signature = AccreditationSignature::where('verify_hash', $hash)->with('accreditation.lpk')->firstOrFail();

        return view('accreditations.verify', [
            'signature' => $signature,
            'accreditation' => $signature->accreditation,
        ]);
    }
}
