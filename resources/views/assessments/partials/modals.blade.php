{{-- MODAL BIAYA ASESOR SEMENTARA DISEMBUNYIKAN SESUAI PERMINTAAN USER --}}
@if(false)
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
@endif

{{-- Modal 3: Kelola Tindakan Perbaikan (TP & VTP) KAN --}}
<div class="simasadi-modal" id="modal-tp-tracking" role="dialog" aria-modal="true" aria-labelledby="modal-tp-tracking-heading">
    <div class="simasadi-modal-box" style="max-width: 580px;">
        <div class="simasadi-modal-head">
            <h4 id="modal-tp-tracking-heading">Kelola Tindakan Perbaikan (TP &amp; VTP)</h4>
            <button type="button" class="simasadi-modal-close" data-modal-close onclick="window.closeModal('modal-tp-tracking')" aria-label="Tutup modal">&times;</button>
        </div>
        <form method="POST" action="{{ route('assessments.tp.update', $assessment) }}">
            @csrf
            <div style="display: grid; gap: 14px;">
                <div style="padding: 12px 14px; background: var(--lavender, #f5f3ff); border: 1px solid #ddd6fe; border-radius: 8px; font-size: 12.5px; color: #5b21b6; line-height: 1.5;">
                    <strong style="display: block; margin-bottom: 4px;">Ketentuan Waktu Tindakan Perbaikan (Dokumen KAN U-01):</strong>
                    <ul style="margin: 0; padding-left: 18px; line-height: 1.5;">
                        <li><strong>Asesmen Awal (AA):</strong> Batas waktu penyelesaian 3 bulan kalender.</li>
                        <li><strong>Survailen, PRL, STT, dan Re-Akreditasi:</strong> Batas waktu penyelesaian 2 bulan kalender.</li>
                        <li><strong>Perpanjangan (+1 Bulan):</strong> Berbasis surat permohonan resmi LPK, hanya diberikan jika ada bukti progres perbaikan nyata.</li>
                    </ul>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; position: relative; z-index: 30;">
                    <label style="display: block;">
                        <span style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 4px;">Status Pelaksanaan Asesmen</span>
                        <select name="status" style="width: 100%; padding: 8px 12px; border: 1px solid var(--line); border-radius: 6px;">
                            <option value="PLANNED" @selected(old('status', $assessment->status) === 'PLANNED')>Direncanakan</option>
                            <option value="SCHEDULED" @selected(old('status', $assessment->status) === 'SCHEDULED')>Terjadwal</option>
                            <option value="IN_PROGRESS" @selected(old('status', $assessment->status) === 'IN_PROGRESS')>Sedang Berlangsung</option>
                            <option value="SUSPENDED" @selected(old('status', $assessment->status) === 'SUSPENDED')>Dibekukan</option>
                            <option value="COMPLETED" @selected(old('status', $assessment->status) === 'COMPLETED' || $assessment->tp_status === 'SATISFIED' || !empty($assessment->sk_number))>Selesai</option>
                            <option value="CANCELLED" @selected(old('status', $assessment->status) === 'CANCELLED')>Dibatalkan</option>
                        </select>
                    </label>

                    <label style="display: block;">
                        <span style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 4px;">Status Tindakan Perbaikan</span>
                        <select name="tp_status" id="modal_tp_status_select" required style="width: 100%; padding: 8px 12px; border: 1px solid var(--line); border-radius: 6px;">
                            <option value="NONE" @selected(old('tp_status', $assessment->tp_status) === 'NONE')>Nihil / Tidak Ada Temuan</option>
                            <option value="IN_PROGRESS" @selected(old('tp_status', $assessment->tp_status) === 'IN_PROGRESS')>Penyusunan Perbaikan oleh LPK</option>
                            <option value="UNDER_VERIFICATION" @selected(old('tp_status', $assessment->tp_status) === 'UNDER_VERIFICATION')>Dalam Verifikasi Tim Asesor</option>
                            <option value="SATISFIED" @selected(old('tp_status', $assessment->tp_status) === 'SATISFIED')>Dinyatakan Memenuhi (Selesai)</option>
                        </select>
                    </label>
                </div>

                <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 12px; position: relative; z-index: 10;">
                    <label>
                        <span style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 4px;">Batas Waktu Awal SLA (KAN)</span>
                        <input type="date" name="tp_due_date" value="{{ old('tp_due_date', $assessment->tp_due_date?->format('Y-m-d') ?: ($assessment->calculateDefaultTpDueDate()?->format('Y-m-d') ?: '')) }}" style="width: 100%; padding: 8px 12px; border: 1px solid var(--line); border-radius: 6px;">
                        <small style="color: var(--muted); font-size: 11px;">Otomatis +2 atau +3 bulan jika dikosongkan.</small>
                    </label>

                    <label>
                        <span style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 4px;">Tanggal Dinyatakan Memenuhi</span>
                        <input type="date" name="tp_satisfied_at" id="modal_tp_satisfied_at" onchange="if(this.value){ const s = document.getElementById('modal_tp_status_select'); if(s) s.value = 'SATISFIED'; }" value="{{ old('tp_satisfied_at', $assessment->tp_satisfied_at?->format('Y-m-d')) }}" style="width: 100%; padding: 8px 12px; border: 1px solid var(--line); border-radius: 6px;">
                        <small style="color: var(--muted); font-size: 11px;">Status otomatis beralih ke Memenuhi saat tanggal diisi.</small>
                    </label>
                </div>

                {{-- Bagian Permohonan Perpanjangan --}}
                <div style="padding: 12px 14px; background: var(--surface-subtle, #f8fafc); border: 1px solid var(--line); border-radius: 6px; display: grid; gap: 10px; position: relative; z-index: 5;">
                    <div>
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 13px; font-weight: 600; min-height: 44px;">
                            <input type="checkbox" name="tp_has_extension" value="1" @checked(old('tp_has_extension', $assessment->tp_has_extension)) onchange="document.getElementById('extension-fields').style.display = this.checked ? 'grid' : 'none'" style="width: 18px; height: 18px; min-height: 18px; max-height: 18px; min-width: 18px; max-width: 18px; margin: 0; padding: 0; cursor: pointer; flex-shrink: 0; accent-color: var(--maroon, #e11d48);">
                            <span>Ajukan Perpanjangan Masa Perbaikan (+1 Bulan Sesuai Aturan KAN)</span>
                        </label>
                        <small style="color: var(--muted); font-size: 11.5px; display: block; margin-top: 2px;">
                            Perpanjangan ditolak bila laboratorium belum memulai perbaikan sama sekali dalam batas awal.
                        </small>
                    </div>

                    <div id="extension-fields" style="display: {{ old('tp_has_extension', $assessment->tp_has_extension) ? 'grid' : 'none' }}; gap: 10px;">
                        <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                            <label>
                                <span style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 2px;">Nomor Surat Resmi LPK</span>
                                <input type="text" name="tp_extension_letter_no" placeholder="Contoh: 104/LPK-LAB/EXT/IX/2026" value="{{ old('tp_extension_letter_no', $assessment->tp_extension_letter_no) }}" style="width: 100%; padding: 7px 10px; border: 1px solid var(--line); border-radius: 4px; font-size: 12.5px;">
                            </label>
                            <label>
                                <span style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 2px;">Tanggal Surat</span>
                                <input type="date" name="tp_extension_date" value="{{ old('tp_extension_date', $assessment->tp_extension_date?->format('Y-m-d')) }}" style="width: 100%; padding: 7px 10px; border: 1px solid var(--line); border-radius: 4px; font-size: 12.5px;">
                            </label>
                        </div>
                        <label>
                            <span style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 2px;">Catatan Alasan Perpanjangan</span>
                            <input type="text" name="tp_extension_notes" placeholder="Contoh: Pengadaan bahan acuan standar dan kalibrasi ulang memerlukan waktu tambahan." value="{{ old('tp_extension_notes', $assessment->tp_extension_notes) }}" style="width: 100%; padding: 7px 10px; border: 1px solid var(--line); border-radius: 4px; font-size: 12.5px;">
                        </label>
                    </div>
                </div>

                {{-- Bagian Laporan Asesmen & EHA --}}
                <div style="padding: 10px 12px; background: #f8fafc; border: 1px solid var(--line); border-radius: 6px; display: grid; gap: 8px;">
                    <div>
                        <strong style="font-size: 12.5px; color: var(--text); display: block;">Laporan Asesmen &amp; Evaluasi Hasil Asesmen (EHA)</strong>
                        <small style="color: var(--muted); font-size: 11px;">Pencatatan tanggal laporan dan evaluasi panitia teknis.</small>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <label>
                            <span style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 2px;">Tgl Laporan Asesmen</span>
                            <input type="date" name="report_date" value="{{ old('report_date', $assessment->report_date?->format('Y-m-d')) }}" style="width: 100%; padding: 7px 10px; border: 1px solid var(--line); border-radius: 4px; font-size: 12.5px; background: #ffffff;">
                        </label>
                        <label>
                            <span style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 2px;">Tgl EHA (Panitia Teknis)</span>
                            <input type="date" name="eha_date" value="{{ old('eha_date', $assessment->eha_date?->format('Y-m-d')) }}" style="width: 100%; padding: 7px 10px; border: 1px solid var(--line); border-radius: 4px; font-size: 12.5px; background: #ffffff;">
                        </label>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr; gap: 8px;">
                        <label>
                            <span style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 2px;">Status Hasil EHA</span>
                            <select name="eha_status" style="width: 100%; padding: 7px 10px; border: 1px solid var(--line); border-radius: 4px; font-size: 12.5px; background: #ffffff;">
                                @foreach(\App\Models\Assessment::EHA_STATUSES as $ehaVal => $ehaText)
                                    <option value="{{ $ehaVal }}" @selected(old('eha_status', $assessment->eha_status ?: \App\Models\Assessment::EHA_STATUS_BELUM) === $ehaVal)>
                                        {{ $ehaText }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                        <label>
                            <span style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 2px;">Catatan Hasil EHA</span>
                            <input type="text" name="eha_notes" placeholder="Catatan atau rekomendasi panitia teknis..." value="{{ old('eha_notes', $assessment->eha_notes) }}" style="width: 100%; padding: 7px 10px; border: 1px solid var(--line); border-radius: 4px; font-size: 12.5px; background: #ffffff;">
                        </label>
                    </div>
                </div>

                {{-- Bagian SK Hasil Asesmen --}}
                <div style="padding: 10px 12px; background: #f0fdf4; border: 1px solid #bbf7d0; border-radius: 6px; display: grid; gap: 8px;">
                    <div>
                        <strong style="font-size: 12.5px; color: #166534; display: block;">Surat Keputusan (SK) Hasil Asesmen KAN</strong>
                        <small style="color: #15803d; font-size: 11px;">Diisi jika tindakan perbaikan telah selesai / dinyatakan memenuhi dan terbit SK KAN.</small>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr 1fr; gap: 10px;">
                        <label>
                            <span style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 2px;">Nomor SK KAN</span>
                            <input type="text" name="sk_number" placeholder="Contoh: SK.KAN.042/BSN/IX/2026" value="{{ old('sk_number', $assessment->sk_number) }}" style="width: 100%; padding: 7px 10px; border: 1px solid var(--line); border-radius: 4px; font-size: 12.5px; background: #ffffff;">
                        </label>
                        <label>
                            <span style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 2px;">Tanggal SK</span>
                            <input type="date" name="sk_date" value="{{ old('sk_date', $assessment->sk_date?->format('Y-m-d')) }}" style="width: 100%; padding: 7px 10px; border: 1px solid var(--line); border-radius: 4px; font-size: 12.5px; background: #ffffff;">
                        </label>
                    </div>
                    @if($assessment->sk_lead_time_label)
                        <div style="font-size: 11.5px; color: #15803d; background: #dcfce7; padding: 4px 8px; border-radius: 4px;">
                            <strong>Rentang Waktu Proses:</strong> {{ $assessment->sk_lead_time_label }}
                            (dari pelaksanaan {{ ($assessment->end_at ?? $assessment->start_at)->format('d M Y') }} s/d SK {{ $assessment->sk_date->format('d M Y') }})
                        </div>
                    @endif
                </div>

                <label style="position: relative; z-index: 1;">
                    <span style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 4px;">Catatan Temuan &amp; Bukti Tindakan Perbaikan</span>
                    <textarea name="tp_notes" rows="3" placeholder="Rangkuman temuan ketidaksesuaian atau status kelengkapan bukti tindakan perbaikan LPK..." style="width: 100%; padding: 8px 12px; border: 1px solid var(--line); border-radius: 6px;">{{ old('tp_notes', $assessment->tp_notes) }}</textarea>
                </label>
            </div>
            <div class="modal-form-actions">
                <button type="button" class="button secondary" data-modal-close onclick="window.closeModal('modal-tp-tracking')">Batal</button>
                <button type="submit" class="button primary">Simpan Status TP</button>
            </div>
        </form>
    </div>
</div>

