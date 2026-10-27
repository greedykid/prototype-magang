{{-- Modal: Selesaikan Pelaksanaan Asesmen Lapangan --}}
<div class="simasadi-modal" id="modal-complete-field-assessment" role="dialog" aria-modal="true" aria-labelledby="modal-complete-field-heading">
    <div class="simasadi-modal-box" style="max-width: min(540px, 100%); width: 100%; box-sizing: border-box;">
        <div class="simasadi-modal-head">
            <div class="modal-title-group">
                <div class="modal-title-icon-badge badge-green" aria-hidden="true">
                    <x-icon name="check-circle" size="17" />
                </div>
                <h4 id="modal-complete-field-heading">Selesaikan Pelaksanaan Lapangan</h4>
            </div>
            <button type="button" class="simasadi-modal-close" data-modal-close onclick="window.closeModal('modal-complete-field-assessment')" aria-label="Tutup modal">&times;</button>
        </div>
        <form method="POST" action="{{ route('assessments.tp.update', $assessment) }}" style="width: 100%; max-width: 100%; min-width: 0; box-sizing: border-box;">
            @csrf
            <div class="simasadi-modal-body">
                <div style="display: grid; gap: 14px; width: 100%; max-width: 100%; min-width: 0; box-sizing: border-box;">
                    <div style="padding: 10px 14px; background: rgba(25, 114, 245, 0.06); border: 1px solid rgba(25, 114, 245, 0.2); border-radius: var(--radius-lg, 12px); font-size: 12.5px; color: var(--ink); line-height: 1.5;">
                        Tandai bahwa kunjungan audit lapangan di fasilitas/laboratorium telah selesai dilaksanakan. Tanggal selesai akan menjadi acuan perhitungan batas waktu Tindakan Perbaikan sesuai ketentuan KAN.
                    </div>

                    {{-- Tanggal Selesai Kunjungan Lapangan --}}
                    <div class="modal-field-label">
                        <span style="font-weight: 600; color: var(--ink); font-size: 13px;">Tanggal Selesai Kunjungan Lapangan <span style="color: var(--danger, #ef4444);">*</span></span>
                        <input type="date" name="end_at" id="complete_end_at_{{ $assessment->id }}" value="{{ old('end_at', $assessment->end_at ? $assessment->end_at->format('Y-m-d') : now()->format('Y-m-d')) }}" required class="modal-form-input">
                        <small style="color: var(--muted); font-size: 11px;">* Tanggal closing meeting atau berakhirnya verifikasi lapangan.</small>
                    </div>

                    {{-- Status Hasil Temuan Lapangan (2 Pilihan Tegas) --}}
                    <div style="display: flex; flex-direction: column; gap: 6px;">
                        <span style="font-weight: 600; color: var(--ink); font-size: 13px;">Hasil Temuan Asesmen Lapangan <span style="color: var(--danger, #ef4444);">*</span></span>
                        <div style="display: grid; gap: 8px;">
                            <label class="assessment-finding-card" for="tp_opt_findings_{{ $assessment->id }}">
                                <input type="radio" name="tp_status" id="tp_opt_findings_{{ $assessment->id }}" value="IN_PROGRESS" checked class="assessment-finding-radio">
                                <div class="assessment-finding-content">
                                    <div class="assessment-finding-title-row">
                                        <strong class="assessment-finding-title">Ada Temuan Ketidaksesuaian</strong>
                                        <span class="badge-tp badge-tp-warning" style="font-size: 10px;">Wajib TP</span>
                                    </div>
                                    <p class="assessment-finding-desc">LPK wajib menyampaikan bukti perbaikan. Batas waktu KAN ({{ in_array($assessment->assessment_type, ['Asesmen Awal', 'INITIAL'], true) ? '3 bulan' : '2 bulan' }}) dihitung otomatis.</p>
                                </div>
                            </label>

                            <label class="assessment-finding-card" for="tp_opt_nofindings_{{ $assessment->id }}">
                                <input type="radio" name="tp_status" id="tp_opt_nofindings_{{ $assessment->id }}" value="SATISFIED" class="assessment-finding-radio">
                                <div class="assessment-finding-content">
                                    <div class="assessment-finding-title-row">
                                        <strong class="assessment-finding-title">Tanpa Temuan / Nol Temuan</strong>
                                        <span class="badge-tp badge-tp-success" style="font-size: 10px;">Lolos Memenuhi</span>
                                    </div>
                                    <p class="assessment-finding-desc">Seluruh kriteria asesmen dinyatakan memenuhi standar KAN, langsung siap lanjut ke Rapat Panitia Teknis.</p>
                                </div>
                            </label>
                        </div>
                    </div>

                    {{-- Tanggal Laporan Asesmen (Opsional) --}}
                    <div class="modal-field-label">
                        <span style="font-weight: 600; color: var(--ink); font-size: 13px;">Tanggal Laporan Asesmen (Opsional)</span>
                        <input type="date" name="report_date" id="complete_report_date_{{ $assessment->id }}" value="{{ old('report_date', $assessment->report_date?->format('Y-m-d')) }}" class="modal-form-input">
                        <small style="color: var(--muted); font-size: 11px;">Isi jika laporan asesmen lapangan dari tim asesor sudah diterbitkan.</small>
                    </div>

                    {{-- Catatan Hasil Kunjungan / Temuan --}}
                    <label class="modal-field-label">
                        <span style="font-weight: 600; color: var(--ink); font-size: 13px;">Catatan Hasil Kunjungan / Temuan (Opsional)</span>
                        <textarea name="tp_notes" id="complete_tp_notes_{{ $assessment->id }}" rows="3" maxlength="2000" placeholder="Ringkasan temuan asesmen lapangan, catatan ketidaksesuaian, atau catatan penutupan..." class="modal-form-input" style="resize: vertical;">{{ old('tp_notes', $assessment->tp_notes) }}</textarea>
                    </label>
                </div>
            </div>
            <div class="modal-form-actions">
                <button type="button" class="button ghost" data-modal-close onclick="window.closeModal('modal-complete-field-assessment')">Batal</button>
                <button type="submit" class="button primary">
                    <x-icon name="check-circle" size="14" />
                    <span>Selesaikan &amp; Lanjut ke Tahap 2</span>
                </button>
            </div>
        </form>
    </div>
</div>
