<?php

namespace App\Http\Controllers;

use App\Models\Assessment;
use App\Models\AssessmentExpense;
use Illuminate\Http\RedirectResponse;
use Illuminate\Http\Request;

class AssessmentExpenseController extends Controller
{
    public function storeOrUpdate(Request $request, Assessment $assessment): RedirectResponse
    {
        $validated = $request->validate([
            'transport_cost' => ['required', 'integer', 'min:0'],
            'accommodation_cost' => ['required', 'integer', 'min:0'],
            'daily_allowance' => ['required', 'integer', 'min:0'],
            'package_data_cost' => ['required', 'integer', 'min:0'],
            'receipt_note' => ['nullable', 'string', 'max:255'],
        ]);

        $total = $validated['transport_cost'] +
            $validated['accommodation_cost'] +
            $validated['daily_allowance'] +
            $validated['package_data_cost'];

        $expense = $assessment->expense ?? new AssessmentExpense(['assessment_id' => $assessment->id]);
        $expense->fill($validated);
        $expense->total_cost = $total;
        $expense->reported_by = $request->user()->id;
        $expense->status = $total > 0 ? 'MENUNGGU_VERIFIKASI' : 'BELUM_DILAPORKAN';
        $expense->save();

        return back()->with('success', 'Laporan biaya perjalanan dinas asesor berhasil disimpan dan diajukan ke Sekretariat KAN.');
    }

    public function verify(Request $request, Assessment $assessment): RedirectResponse
    {
        $validated = $request->validate([
            'status' => ['required', 'in:TERVERIFIKASI,PERLU_REVISI'],
            'verification_notes' => ['nullable', 'string', 'max:500'],
        ]);

        $expense = $assessment->expense;
        if (! $expense) {
            return back()->with('error', 'Belum ada data biaya perjalanan dinas untuk diverifikasi.');
        }

        $expense->status = $validated['status'];
        $expense->verification_notes = $validated['verification_notes'] ?? ($validated['status'] === 'TERVERIFIKASI' ? 'Biaya telah diverifikasi sesuai Standar Biaya Masukan (SBM).' : 'Perlu perbaikan bukti kwitansi / nominal.');
        $expense->verified_by = $request->user()->id;
        $expense->verified_at = now();
        $expense->save();

        $msg = $validated['status'] === 'TERVERIFIKASI'
            ? 'Biaya perjalanan dinas asesor berhasil diverifikasi sesuai Standar Biaya Masukan (SBM).'
            : 'Status biaya asesor diubah menjadi Perlu Revisi.';

        return back()->with('success', $msg);
    }
}
