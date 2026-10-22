{{-- Modal: Pembaruan Tindak Lanjut Asesmen (TP, Rapat Panitia Teknis & SK) KAN --}}
<div class="simasadi-modal" id="modal-tp-tracking" role="dialog" aria-modal="true" aria-labelledby="modal-tp-tracking-heading">
    <div class="simasadi-modal-box" style="max-width: min(580px, 100%); width: 100%; box-sizing: border-box;">
        <div class="simasadi-modal-head">
            <h4 id="modal-tp-tracking-heading">Pembaruan Tindak Lanjut Asesmen (TP, Rapat Panitia Teknis &amp; SK)</h4>
            <button type="button" class="simasadi-modal-close" data-modal-close onclick="window.closeModal('modal-tp-tracking')" aria-label="Tutup modal">&times;</button>
        </div>
        <form method="POST" action="{{ route('assessments.tp.update', $assessment) }}" style="width: 100%; max-width: 100%; min-width: 0; box-sizing: border-box;">
            @csrf
            <div style="display: grid; gap: 14px; width: 100%; max-width: 100%; min-width: 0; box-sizing: border-box;">

                {{-- Navigasi Tab Seksi Regulasi KAN U-01 --}}
                <div class="modal-tp-nav-tabs" role="tablist" aria-label="Navigasi Seksi Tindak Lanjut Asesmen">
                    <button type="button" class="tp-modal-tab-btn is-active" id="tp-modal-tab-btn-tp" role="tab" aria-selected="true" aria-controls="modal-tp-panel-tp" onclick="window.switchModalTpTab('tp')">
                        1. Tindakan Perbaikan
                    </button>
                    <button type="button" class="tp-modal-tab-btn" id="tp-modal-tab-btn-eha" role="tab" aria-selected="false" aria-controls="modal-tp-panel-eha" onclick="window.switchModalTpTab('eha')">
                        2. Rapat Panitia Teknis
                    </button>
                    <button type="button" class="tp-modal-tab-btn" id="tp-modal-tab-btn-sk" role="tab" aria-selected="false" aria-controls="modal-tp-panel-sk" onclick="window.switchModalTpTab('sk')">
                        3. SK KAN
                    </button>
                </div>

                @php
                    $currentUnifiedStatus = 'SCHEDULED';
                    if ($assessment->status === 'CANCELLED') {
                        $currentUnifiedStatus = 'CANCELLED';
                    } elseif ($assessment->status === 'COMPLETED' || !empty($assessment->sk_number)) {
                        $currentUnifiedStatus = 'COMPLETED';
                    } elseif ($assessment->status === 'SUSPENDED' || $assessment->is_tp_overdue || $assessment->is_submission_overdue) {
                        $currentUnifiedStatus = 'SUSPENDED';
                    } elseif (in_array($assessment->tp_status, ['UNDER_VERIFICATION', 'IN_PROGRESS', 'SATISFIED'], true) || $assessment->status === 'IN_PROGRESS') {
                        $currentUnifiedStatus = 'IN_PROGRESS';
                    } elseif ($assessment->status === 'PLANNED') {
                        $currentUnifiedStatus = 'PLANNED';
                    }
                    $selectedUnified = old('unified_status', $currentUnifiedStatus);

                    $modalTpHasExt = (bool) old('tp_has_extension', $assessment->tp_has_extension || !empty($assessment->tp_extension_letter_no));
                    $modalDefaultDueDateCarbon = $assessment->calculateDefaultTpDueDate();
                    $modalBaseTpDate = $assessment->tp_due_date?->format('Y-m-d') ?: ($modalDefaultDueDateCarbon?->format('Y-m-d') ?: '');
                    $modalInitTpDueDate = old('tp_due_date');
                    if (! $modalInitTpDueDate) {
                        if ($assessment->tp_due_date) {
                            if ($modalTpHasExt && $modalDefaultDueDateCarbon && $assessment->tp_due_date->toDateString() === $modalDefaultDueDateCarbon->toDateString()) {
                                $modalInitTpDueDate = $assessment->tp_due_date->copy()->addMonth()->format('Y-m-d');
                            } else {
                                $modalInitTpDueDate = $assessment->tp_due_date->format('Y-m-d');
                            }
                        } elseif ($modalDefaultDueDateCarbon) {
                            $modalInitTpDueDate = $modalTpHasExt ? $modalDefaultDueDateCarbon->copy()->addMonth()->format('Y-m-d') : $modalDefaultDueDateCarbon->format('Y-m-d');
                        } else {
                            $modalInitTpDueDate = '';
                        }
                    }
                @endphp

                {{-- Panel 1: Tindakan Perbaikan (TP & VTP) --}}
                <div id="modal-tp-panel-tp" class="tp-modal-panel is-active" role="tabpanel" aria-labelledby="tp-modal-tab-btn-tp" style="display: grid; gap: 14px; width: 100%; max-width: 100%; min-width: 0; box-sizing: border-box;">
                    <div class="modal-tp-info-callout">
                        <strong style="display: block; margin-bottom: 4px;">Ketentuan Waktu Tindakan Perbaikan (Dokumen KAN U-01):</strong>
                        <ul style="margin: 0; padding-left: 18px; line-height: 1.5;">
                            <li><strong>Asesmen Awal (AA):</strong> Batas waktu penyelesaian 3 bulan kalender.</li>
                            <li><strong>Survailen, PRL, STT, dan Re-Akreditasi:</strong> Batas waktu penyelesaian 2 bulan kalender.</li>
                            <li><strong>Perpanjangan (+1 Bulan):</strong> Berbasis surat permohonan resmi LPK, hanya diberikan jika ada bukti progres perbaikan nyata.</li>
                        </ul>
                    </div>

                    <div id="modal-section-tp-{{ $assessment->id }}" style="scroll-margin-top: 14px; width: 100%; max-width: 100%; min-width: 0;">
                        <label style="display: block; width: 100%; max-width: 100%; min-width: 0;">
                            <span style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 4px; color: var(--ink);">Status Asesmen</span>
                            <select name="unified_status" id="modal_unified_status_select" required onchange="const satInput = document.getElementById('modal_tp_satisfied_at_{{ $assessment->id }}'); if(this.value === 'COMPLETED' && satInput && !satInput.value){ satInput.value = new Date().toISOString().split('T')[0]; }" style="width: 100%; max-width: 100%; min-width: 0; box-sizing: border-box; text-overflow: ellipsis; padding: 8px 12px; border: 1px solid var(--input-border, var(--line)); border-radius: var(--radius-lg, 12px); background: var(--input-bg, #ffffff); color: var(--ink); font-size: 13px;">
                                <option value="SCHEDULED" @selected($selectedUnified === 'SCHEDULED')>Terjadwal (Batas Waktu TP Belum Ditentukan)</option>
                                <option value="IN_PROGRESS" @selected(in_array($selectedUnified, ['IN_PROGRESS', 'IN_PROGRESS_TP', 'UNDER_VERIFICATION'], true))>Sedang Berlangsung</option>
                                <option value="SUSPENDED" @selected($selectedUnified === 'SUSPENDED')>Dibekukan (Melewati Batas Waktu TP)</option>
                                <option value="COMPLETED" @selected($selectedUnified === 'COMPLETED')>Selesai (Dinyatakan Memenuhi)</option>
                                <option value="CANCELLED" @selected($selectedUnified === 'CANCELLED')>Dibatalkan</option>
                            </select>
                        </label>
                    </div>

                    <div class="modal-form-grid-2col">
                        <label style="width: 100%; max-width: 100%; min-width: 0;">
                            <span style="font-size: 13px; font-weight: 600; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 4px; margin-bottom: 4px;">
                                <span>Tenggat Waktu TP (KAN)</span>
                                <span id="modal-tp-ext-badge-{{ $assessment->id }}" class="badge-tp badge-tp-info" style="font-size: 10px; display: {{ $modalTpHasExt ? 'inline-flex' : 'none' }}; align-items: center; gap: 3px;">
                                    +1 Bulan (Surat LPK)
                                </span>
                            </span>
                            <input type="date" name="tp_due_date" id="modal_tp_due_date_{{ $assessment->id }}" value="{{ $modalInitTpDueDate }}" data-base-date="{{ $modalBaseTpDate }}" data-is-extended="{{ $modalTpHasExt ? '1' : '0' }}" style="width: 100%; max-width: 100%; min-width: 0; box-sizing: border-box; padding: 8px 12px; border: 1px solid var(--input-border, var(--line)); border-radius: var(--radius-lg, 12px); background: var(--input-bg, #ffffff); color: var(--ink);">
                            <small id="modal_tp_due_date_hint_{{ $assessment->id }}" style="color: var(--muted); font-size: 11px;">
                                @if($modalTpHasExt)
                                    * Otomatis diperpanjang +1 bulan karena ada surat permohonan perpanjangan LPK.
                                @else
                                    Otomatis dihitung +2 atau +3 bulan jika dikosongkan. Ubah jika ada kesepakatan khusus.
                                @endif
                            </small>
                        </label>

                        <label style="width: 100%; max-width: 100%; min-width: 0;">
                            <span style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 4px; color: var(--ink);">Dinyatakan Memenuhi</span>
                            <input type="date" name="tp_satisfied_at" id="modal_tp_satisfied_at_{{ $assessment->id }}" onchange="if(this.value){ const s = document.getElementById('modal_unified_status_select'); if(s) s.value = 'COMPLETED'; }" value="{{ old('tp_satisfied_at', $assessment->tp_satisfied_at?->format('Y-m-d')) }}" style="width: 100%; max-width: 100%; min-width: 0; box-sizing: border-box; padding: 8px 12px; border: 1px solid var(--input-border, var(--line)); border-radius: var(--radius-lg, 12px); background: var(--input-bg, #ffffff); color: var(--ink);">
                            <small style="color: var(--muted); font-size: 11px;">Status TP beralih ke Memenuhi saat tanggal diisi. Lengkapi bagian Rapat Panitia Teknis / SK pada tab di atas untuk menuntaskan siklus asesmen ke Selesai.</small>
                        </label>
                    </div>

                    {{-- Bagian Permohonan Perpanjangan --}}
                    <div style="padding: 12px 14px; background: var(--surface-subtle, #f8fafc); border: 1px solid var(--line); border-radius: var(--radius-lg, 12px); display: grid; gap: 10px; width: 100%; max-width: 100%; min-width: 0; box-sizing: border-box;">
                        <div>
                            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 13px; font-weight: 600; min-height: 44px; color: var(--ink);">
                                <input type="checkbox" name="tp_has_extension" id="modal_tp_has_extension_{{ $assessment->id }}" value="1" @checked($modalTpHasExt) onchange="window.syncModalTpExtension({{ $assessment->id }}, false)" style="width: 18px; height: 18px; min-height: 18px; max-height: 18px; min-width: 18px; max-width: 18px; margin: 0; padding: 0; cursor: pointer; flex-shrink: 0; accent-color: var(--maroon, #e11d48);">
                                <span>Ajukan Perpanjangan Masa Perbaikan (+1 Bulan Sesuai Aturan KAN)</span>
                            </label>
                            <small style="color: var(--muted); font-size: 11.5px; display: block; margin-top: 2px;">
                                Perpanjangan ditolak bila laboratorium belum memulai perbaikan sama sekali dalam batas awal.
                            </small>
                        </div>

                        <div id="extension-fields-{{ $assessment->id }}" style="display: {{ $modalTpHasExt ? 'grid' : 'none' }}; gap: 10px; width: 100%; max-width: 100%; min-width: 0; box-sizing: border-box;">
                            <div class="modal-form-grid-2col">
                                <label style="width: 100%; max-width: 100%; min-width: 0;">
                                    <span style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 2px; color: var(--ink);">
                                        Nomor Surat Resmi LPK <span class="lpk-required-dot modal-ext-req-{{ $assessment->id }}" style="display: {{ $modalTpHasExt ? 'inline' : 'none' }}; color: #ef4444;">*</span>
                                    </span>
                                    <input type="text" name="tp_extension_letter_no" id="modal_tp_ext_letter_no_{{ $assessment->id }}" oninput="window.syncModalTpExtension({{ $assessment->id }}, true)" onchange="window.syncModalTpExtension({{ $assessment->id }}, true)" placeholder="Contoh: 104/LPK-LAB/EXT/IX/2026" value="{{ old('tp_extension_letter_no', $assessment->tp_extension_letter_no) }}" @if($modalTpHasExt) required @endif style="width: 100%; max-width: 100%; min-width: 0; box-sizing: border-box; padding: 7px 10px; border: 1px solid var(--input-border, var(--line)); border-radius: var(--radius-lg, 12px); font-size: 12.5px; background: var(--input-bg, #ffffff); color: var(--ink);">
                                </label>
                                <label style="width: 100%; max-width: 100%; min-width: 0;">
                                    <span style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 2px; color: var(--ink);">
                                        Tanggal Surat <span class="lpk-required-dot modal-ext-req-{{ $assessment->id }}" style="display: {{ $modalTpHasExt ? 'inline' : 'none' }}; color: #ef4444;">*</span>
                                    </span>
                                    <input type="date" name="tp_extension_date" id="modal_tp_ext_date_{{ $assessment->id }}" value="{{ old('tp_extension_date', $assessment->tp_extension_date?->format('Y-m-d')) }}" @if($modalTpHasExt) required @endif style="width: 100%; max-width: 100%; min-width: 0; box-sizing: border-box; padding: 7px 10px; border: 1px solid var(--input-border, var(--line)); border-radius: var(--radius-lg, 12px); font-size: 12.5px; background: var(--input-bg, #ffffff); color: var(--ink);">
                                </label>
                            </div>
                            <label style="width: 100%; max-width: 100%; min-width: 0;">
                                <span style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 2px; color: var(--ink);">Catatan Alasan Perpanjangan</span>
                                <input type="text" name="tp_extension_notes" placeholder="Contoh: Pengadaan bahan acuan standar dan kalibrasi ulang memerlukan waktu tambahan." value="{{ old('tp_extension_notes', $assessment->tp_extension_notes) }}" style="width: 100%; max-width: 100%; min-width: 0; box-sizing: border-box; padding: 7px 10px; border: 1px solid var(--input-border, var(--line)); border-radius: var(--radius-lg, 12px); font-size: 12.5px; background: var(--input-bg, #ffffff); color: var(--ink);">
                            </label>
                        </div>
                    </div>

                    <label style="display: block; width: 100%; max-width: 100%; min-width: 0;">
                        <span style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 4px; color: var(--ink);">Catatan Temuan &amp; Bukti Tindakan Perbaikan</span>
                        <textarea name="tp_notes" rows="3" placeholder="Rangkuman temuan ketidaksesuaian atau status kelengkapan bukti tindakan perbaikan LPK..." style="width: 100%; max-width: 100%; min-width: 0; box-sizing: border-box; padding: 8px 12px; border: 1px solid var(--input-border, var(--line)); border-radius: var(--radius-lg, 12px); background: var(--input-bg, #ffffff); color: var(--ink);">{{ old('tp_notes', $assessment->tp_notes) }}</textarea>
                    </label>
                </div>

                {{-- Panel 2: Laporan Asesmen & Rapat Panitia Teknis --}}
                <div id="modal-tp-panel-eha" class="tp-modal-panel" role="tabpanel" aria-labelledby="tp-modal-tab-btn-eha" style="display: none; gap: 14px; width: 100%; max-width: 100%; min-width: 0; box-sizing: border-box;">
                    <div style="padding: 10px 14px; background: var(--surface-subtle, #f8fafc); border: 1px solid var(--line); border-radius: var(--radius-lg, 12px); display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: wrap;">
                        <span style="font-size: 12px; color: var(--muted);">Status Tindak Lanjut TP Saat Ini:</span>
                        <span class="badge-tp badge-tp-{{ $assessment->tp_status === 'SATISFIED' ? 'satisfied' : ($assessment->is_tp_overdue ? 'overdue' : 'inprogress') }}" style="font-size: 11px;">
                            {{ $assessment->tp_status_label }}
                        </span>
                    </div>

                    <div id="modal-section-eha" style="padding: 12px 14px; background: var(--surface-subtle, var(--surface)); border: 1px solid var(--line); border-radius: var(--radius-lg, 12px); display: grid; gap: 10px; width: 100%; max-width: 100%; min-width: 0; box-sizing: border-box;">
                        <div>
                            <strong style="font-size: 13px; color: var(--ink); display: block;">Laporan Asesmen &amp; Rapat Panitia Teknis</strong>
                            <small style="color: var(--muted); font-size: 11px;">Pencatatan tanggal laporan asesmen tim asesor dan evaluasi oleh panitia teknis KAN.</small>
                        </div>
                        <div class="modal-form-grid-2col">
                            <label style="width: 100%; max-width: 100%; min-width: 0;">
                                <span style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 2px;">Tgl Laporan Asesmen</span>
                                <input type="date" name="report_date" value="{{ old('report_date', $assessment->report_date?->format('Y-m-d')) }}" style="width: 100%; max-width: 100%; min-width: 0; box-sizing: border-box; padding: 7px 10px; border: 1px solid var(--input-border, var(--line)); border-radius: var(--radius-lg, 12px); font-size: 12.5px; background: var(--input-bg, #ffffff); color: var(--ink);">
                            </label>
                            <label style="width: 100%; max-width: 100%; min-width: 0;">
                                <span style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 2px;">Tgl Rapat Panitia Teknis</span>
                                <input type="date" name="eha_date" value="{{ old('eha_date', $assessment->eha_date?->format('Y-m-d')) }}" style="width: 100%; max-width: 100%; min-width: 0; box-sizing: border-box; padding: 7px 10px; border: 1px solid var(--input-border, var(--line)); border-radius: var(--radius-lg, 12px); font-size: 12.5px; background: var(--input-bg, #ffffff); color: var(--ink);">
                            </label>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr; gap: 8px; width: 100%; max-width: 100%; min-width: 0; box-sizing: border-box;">
                            <label style="width: 100%; max-width: 100%; min-width: 0;">
                                <span style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 2px;">Status Hasil Rapat Panitia Teknis</span>
                                <select name="eha_status" style="width: 100%; max-width: 100%; min-width: 0; box-sizing: border-box; text-overflow: ellipsis; padding: 7px 10px; border: 1px solid var(--input-border, var(--line)); border-radius: var(--radius-lg, 12px); font-size: 12.5px; background: var(--input-bg, #ffffff); color: var(--ink);">
                                    @foreach(\App\Models\Assessment::EHA_STATUSES as $ehaVal => $ehaText)
                                        <option value="{{ $ehaVal }}" @selected(old('eha_status', $assessment->eha_status ?: \App\Models\Assessment::EHA_STATUS_BELUM) === $ehaVal)>
                                            {{ $ehaText }}
                                        </option>
                                    @endforeach
                                </select>
                            </label>
                            <label style="width: 100%; max-width: 100%; min-width: 0;">
                                <span style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 2px;">Catatan Hasil Rapat Panitia Teknis</span>
                                <input type="text" name="eha_notes" placeholder="Catatan atau rekomendasi panitia teknis..." value="{{ old('eha_notes', $assessment->eha_notes) }}" style="width: 100%; max-width: 100%; min-width: 0; box-sizing: border-box; padding: 7px 10px; border: 1px solid var(--input-border, var(--line)); border-radius: var(--radius-lg, 12px); font-size: 12.5px; background: var(--input-bg, #ffffff); color: var(--ink);">
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Panel 3: Penetapan SK KAN --}}
                <div id="modal-tp-panel-sk" class="tp-modal-panel" role="tabpanel" aria-labelledby="tp-modal-tab-btn-sk" style="display: none; gap: 14px; width: 100%; max-width: 100%; min-width: 0; box-sizing: border-box;">
                    <div style="padding: 10px 14px; background: var(--surface-subtle, #f8fafc); border: 1px solid var(--line); border-radius: var(--radius-lg, 12px); display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: wrap;">
                        <span style="font-size: 12px; color: var(--muted);">Status Rekomendasi Rapat Panitia Teknis:</span>
                        <span class="badge-tp badge-tp-{{ $assessment->eha_status === 'DIREKOMENDASIKAN' ? 'satisfied' : 'neutral' }}" style="font-size: 11px;">
                            {{ $assessment->eha_status_label ?? 'Belum Ada Rapat Panitia Teknis' }}
                        </span>
                    </div>

                    <div id="modal-section-sk" style="padding: 12px 14px; background: var(--mint, rgba(16, 185, 129, 0.08)); border: 1px solid rgba(16, 185, 129, 0.35); border-radius: var(--radius-lg, 12px); display: grid; gap: 10px; width: 100%; max-width: 100%; min-width: 0; box-sizing: border-box;">
                        <div>
                            <strong style="font-size: 13px; color: var(--green, #10b981); display: block;">Surat Keputusan (SK) Hasil Asesmen KAN</strong>
                            <small style="color: var(--muted); font-size: 11px;">Opsional / Fleksibel: dapat diisi sekarang (jika sudah terbit) atau diisi menyusul setelah dokumen resmi diterima dari KAN.</small>
                        </div>
                        <div class="modal-form-grid-2col">
                            <label style="width: 100%; max-width: 100%; min-width: 0;">
                                <span style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 2px;">Nomor SK KAN</span>
                                <input type="text" name="sk_number" placeholder="Contoh: SK.KAN.042/BSN/IX/2026" value="{{ old('sk_number', $assessment->sk_number) }}" style="width: 100%; max-width: 100%; min-width: 0; box-sizing: border-box; padding: 7px 10px; border: 1px solid var(--input-border, var(--line)); border-radius: var(--radius-lg, 12px); font-size: 12.5px; background: var(--input-bg, #ffffff); color: var(--ink);">
                            </label>
                            <label style="width: 100%; max-width: 100%; min-width: 0;">
                                <span style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 2px;">Tanggal SK</span>
                                <input type="date" name="sk_date" value="{{ old('sk_date', $assessment->sk_date?->format('Y-m-d')) }}" style="width: 100%; max-width: 100%; min-width: 0; box-sizing: border-box; padding: 7px 10px; border: 1px solid var(--input-border, var(--line)); border-radius: var(--radius-lg, 12px); font-size: 12.5px; background: var(--input-bg, #ffffff); color: var(--ink);">
                            </label>
                        </div>
                        @if($assessment->sk_lead_time_label)
                            <div style="font-size: 11.5px; color: var(--green, #10b981); background: rgba(16, 185, 129, 0.16); padding: 5px 10px; border-radius: var(--radius-pill, 9999px); word-break: break-word;">
                                <strong>Rentang Waktu Proses:</strong> {{ $assessment->sk_lead_time_label }}
                                (dari pelaksanaan {{ ($assessment->end_at ?? $assessment->start_at)->format('d M Y') }} s/d SK {{ $assessment->sk_date->format('d M Y') }})
                            </div>
                        @endif
                    </div>
                </div>

            </div>
            <div class="modal-form-actions">
                <button type="button" class="button ghost" data-modal-close onclick="window.closeModal('modal-tp-tracking')">Batal</button>
                <button type="submit" class="button primary">Simpan Pembaruan</button>
            </div>
        </form>
    </div>
</div>

<script>
window.switchModalTpTab = function(tabName) {
    if (typeof window.closeAllCustomPickers === 'function') {
        window.closeAllCustomPickers();
    }
    const tabs = ['tp', 'eha', 'sk'];
    const activeTab = tabs.includes(tabName) ? tabName : 'tp';

    tabs.forEach(function(t) {
        const btn = document.getElementById('tp-modal-tab-btn-' + t);
        const panel = document.getElementById('modal-tp-panel-' + t);
        const isActive = (t === activeTab);

        if (btn) {
            btn.classList.toggle('is-active', isActive);
            btn.setAttribute('aria-selected', isActive ? 'true' : 'false');
        }
        if (panel) {
            panel.style.display = isActive ? 'grid' : 'none';
            panel.classList.toggle('is-active', isActive);
        }
    });

    const modal = document.getElementById('modal-tp-tracking');
    if (modal) {
        const modalBox = modal.querySelector('.simasadi-modal-box');
        if (modalBox) {
            modalBox.scrollTop = 0;
            requestAnimationFrame(() => { modalBox.scrollTop = 0; });
        }
    }
};

window.syncModalTpExtension = function(id, fromLetter) {
    const dueInput = document.getElementById('modal_tp_due_date_' + id);
    const hasExtBox = document.getElementById('modal_tp_has_extension_' + id);
    const letterInput = document.getElementById('modal_tp_ext_letter_no_' + id);
    const extDateInput = document.getElementById('modal_tp_ext_date_' + id);
    const reqDots = document.querySelectorAll('.modal-ext-req-' + id);
    const fieldsBlock = document.getElementById('extension-fields-' + id);
    const badge = document.getElementById('modal-tp-ext-badge-' + id);
    const hint = document.getElementById('modal_tp_due_date_hint_' + id);

    if (!dueInput) return;

    const letterVal = letterInput ? letterInput.value.trim() : '';
    const hasLetter = letterVal.length > 0;

    if (fromLetter && hasLetter && hasExtBox && !hasExtBox.checked) {
        hasExtBox.checked = true;
    }

    const shouldExtend = hasExtBox ? hasExtBox.checked : false;

    if (fieldsBlock) {
        fieldsBlock.style.display = shouldExtend ? 'grid' : 'none';
    }

    if (letterInput) {
        letterInput.required = shouldExtend;
    }
    if (extDateInput) {
        extDateInput.required = shouldExtend;
    }
    reqDots.forEach(function(dot) {
        dot.style.display = shouldExtend ? 'inline' : 'none';
    });

    function addMonth(dStr) {
        if (!dStr) return '';
        const p = dStr.split('-');
        if (p.length !== 3) return dStr;
        let y = parseInt(p[0], 10), m = parseInt(p[1], 10) + 1, d = parseInt(p[2], 10);
        if (m > 12) { y++; m = 1; }
        const max = new Date(y, m, 0).getDate();
        if (d > max) d = max;
        return `${y}-${String(m).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
    }

    function subMonth(dStr) {
        if (!dStr) return '';
        const p = dStr.split('-');
        if (p.length !== 3) return dStr;
        let y = parseInt(p[0], 10), m = parseInt(p[1], 10) - 1, d = parseInt(p[2], 10);
        if (m < 1) { y--; m = 12; }
        const max = new Date(y, m, 0).getDate();
        if (d > max) d = max;
        return `${y}-${String(m).padStart(2, '0')}-${String(d).padStart(2, '0')}`;
    }

    const isExtended = dueInput.dataset.isExtended === '1';

    if (shouldExtend && !isExtended) {
        let base = dueInput.dataset.baseDate || dueInput.value;
        if (base) {
            dueInput.dataset.baseDate = base;
            dueInput.value = addMonth(base);
            dueInput.dataset.isExtended = '1';
            if (badge) badge.style.display = 'inline-flex';
            if (hint) hint.textContent = '* Otomatis diperpanjang +1 bulan karena ada surat permohonan perpanjangan LPK.';
            dueInput.dispatchEvent(new Event('change', { bubbles: true }));
        }
    } else if (!shouldExtend && isExtended) {
        let base = dueInput.dataset.baseDate || (dueInput.value ? subMonth(dueInput.value) : '');
        if (base) {
            dueInput.value = base;
            dueInput.dataset.isExtended = '0';
            if (badge) badge.style.display = 'none';
            if (hint) hint.textContent = 'Otomatis dihitung +2 atau +3 bulan jika dikosongkan. Ubah jika ada kesepakatan khusus.';
            dueInput.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }
};
</script>
