{{-- Modal 1: Input / Perbarui Laporan Biaya Asesor --}}
<div class="simasadi-modal" id="modal-report-expense" role="dialog" aria-modal="true">
    <div class="simasadi-modal-box">
        <div class="simasadi-modal-head">
            <h4>Laporan Biaya Perjalanan Dinas Asesor</h4>
            <button type="button" class="simasadi-modal-close" data-modal-close onclick="window.closeModal('modal-report-expense')" aria-label="Tutup modal">&times;</button>
        </div>
        <form method="POST" action="{{ route('assessments.expenses.store', $assessment) }}">
            @csrf
            <div style="display: grid; gap: 14px;">
                <label>
                    <span style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 4px;">Uang Harian Asesor (Rp)</span>
                    <input type="number" name="daily_allowance" min="0" step="1000" value="{{ old('daily_allowance', $assessment->expense->daily_allowance ?? 860000) }}" required style="width: 100%; padding: 8px 12px; border: 1px solid var(--line); border-radius: 6px;">
                    <small style="color: var(--muted); font-size: 11.5px;">Mengacu pada SBM uang harian per hari penugasan asesor.</small>
                </label>
                <label>
                    <span style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 4px;">Biaya Transportasi (Rp)</span>
                    <input type="number" name="transport_cost" min="0" step="1000" value="{{ old('transport_cost', $assessment->expense->transport_cost ?? 1250000) }}" required style="width: 100%; padding: 8px 12px; border: 1px solid var(--line); border-radius: 6px;">
                    <small style="color: var(--muted); font-size: 11.5px;">Tiket pesawat/kereta, taksi bandara, atau BBM/tol.</small>
                </label>
                <label>
                    <span style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 4px;">Biaya Akomodasi / Hotel (Rp)</span>
                    <input type="number" name="accommodation_cost" min="0" step="1000" value="{{ old('accommodation_cost', $assessment->expense->accommodation_cost ?? 750000) }}" required style="width: 100%; padding: 8px 12px; border: 1px solid var(--line); border-radius: 6px;">
                    <small style="color: var(--muted); font-size: 11.5px;">Biaya penginapan hotel per malam sesuai plafon SBM.</small>
                </label>
                <label>
                    <span style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 4px;">Paket Data & Komunikasi (Rp)</span>
                    <input type="number" name="package_data_cost" min="0" step="1000" value="{{ old('package_data_cost', $assessment->expense->package_data_cost ?? 150000) }}" required style="width: 100%; padding: 8px 12px; border: 1px solid var(--line); border-radius: 6px;">
                    <small style="color: var(--muted); font-size: 11.5px;">Kebutuhan koordinasi data asesmen daring/lapangan.</small>
                </label>
                <label>
                    <span style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 4px;">Catatan Bukti Kwitansi / No. SPPD</span>
                    <input type="text" name="receipt_note" placeholder="Contoh: Tiket Garuda GA-412 & Kwitansi Hotel Santika #8192" value="{{ old('receipt_note', $assessment->expense->receipt_note ?? '') }}" style="width: 100%; padding: 8px 12px; border: 1px solid var(--line); border-radius: 6px;">
                </label>
            </div>
            <div class="modal-form-actions">
                <button type="button" class="button secondary" data-modal-close onclick="window.closeModal('modal-report-expense')">Batal</button>
                <button type="submit" class="button primary">Simpan & Ajukan Verifikasi</button>
            </div>
        </form>
    </div>
</div>

{{-- Modal 2: Verifikasi Kepatuhan SBM oleh Sekretariat KAN --}}
@if($assessment->expense && auth()->user()?->isAdmin())
<div class="simasadi-modal" id="modal-verify-expense" role="dialog" aria-modal="true">
    <div class="simasadi-modal-box">
        <div class="simasadi-modal-head">
            <h4>Verifikasi Kepatuhan SBM (Sekretariat KAN)</h4>
            <button type="button" class="simasadi-modal-close" data-modal-close onclick="window.closeModal('modal-verify-expense')" aria-label="Tutup modal">&times;</button>
        </div>
        <form method="POST" action="{{ route('assessments.expenses.verify', $assessment) }}">
            @csrf
            <div style="display: grid; gap: 14px;">
                <p style="font-size: 13.5px; color: var(--muted); margin: 0;">
                    Total tagihan yang dilaporkan: <strong style="color: var(--ink);">Rp {{ number_format($assessment->expense->total_cost, 0, ',', '.') }}</strong>
                </p>
                <label>
                    <span style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 4px;">Keputusan Verifikasi</span>
                    <select name="status" required style="width: 100%; padding: 8px 12px; border: 1px solid var(--line); border-radius: 6px;">
                        <option value="TERVERIFIKASI" {{ $assessment->expense->status === 'TERVERIFIKASI' ? 'selected' : '' }}>Terverifikasi Sesuai SBM (Disetujui)</option>
                        <option value="PERLU_REVISI" {{ $assessment->expense->status === 'PERLU_REVISI' ? 'selected' : '' }}>Perlu Revisi Bukti Kwitansi / Nominal</option>
                    </select>
                </label>
                <label>
                    <span style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 4px;">Catatan Verifikator</span>
                    <textarea name="verification_notes" rows="3" placeholder="Contoh: Bukti tiket pesawat dan hotel telah lengkap dan sesuai pagu PMK No. 49." style="width: 100%; padding: 8px 12px; border: 1px solid var(--line); border-radius: 6px;">{{ $assessment->expense->verification_notes }}</textarea>
                </label>
            </div>
            <div class="modal-form-actions">
                <button type="button" class="button secondary" data-modal-close onclick="window.closeModal('modal-verify-expense')">Batal</button>
                <button type="submit" class="button primary">Simpan Keputusan</button>
            </div>
        </form>
    </div>
</div>
@endif
