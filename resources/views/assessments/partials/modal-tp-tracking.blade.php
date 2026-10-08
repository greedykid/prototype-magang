{{-- Modal 1: Kelola Tindakan Perbaikan (TP & VTP) KAN --}}
<div class="simasadi-modal" id="modal-tp-tracking" role="dialog" aria-modal="true" aria-labelledby="modal-tp-tracking-heading">
    <div class="simasadi-modal-box" style="max-width: 580px;">
        <div class="simasadi-modal-head">
            <h4 id="modal-tp-tracking-heading">Kelola Tindakan Perbaikan (TP &amp; VTP)</h4>
            <button type="button" class="simasadi-modal-close" data-modal-close onclick="window.closeModal('modal-tp-tracking')" aria-label="Tutup modal">&times;</button>
        </div>
        <form method="POST" action="{{ route('assessments.tp.update', $assessment) }}">
            @csrf
            <div style="display: grid; gap: 14px;">
                <div class="modal-tp-info-callout">
                    <strong style="display: block; margin-bottom: 4px;">Ketentuan Waktu Tindakan Perbaikan (Dokumen KAN U-01):</strong>
                    <ul style="margin: 0; padding-left: 18px; line-height: 1.5;">
                        <li><strong>Asesmen Awal (AA):</strong> Batas waktu penyelesaian 3 bulan kalender.</li>
                        <li><strong>Survailen, PRL, STT, dan Re-Akreditasi:</strong> Batas waktu penyelesaian 2 bulan kalender.</li>
                        <li><strong>Perpanjangan (+1 Bulan):</strong> Berbasis surat permohonan resmi LPK, hanya diberikan jika ada bukti progres perbaikan nyata.</li>
                    </ul>
                </div>

                @php
                    $currentUnifiedStatus = 'SCHEDULED';
                    if ($assessment->status === 'CANCELLED') {
                        $currentUnifiedStatus = 'CANCELLED';
                    } elseif ($assessment->status === 'COMPLETED' || $assessment->tp_status === 'SATISFIED' || !empty($assessment->sk_number)) {
                        $currentUnifiedStatus = 'COMPLETED';
                    } elseif ($assessment->status === 'SUSPENDED' || $assessment->is_tp_overdue || $assessment->is_submission_overdue) {
                        $currentUnifiedStatus = 'SUSPENDED';
                    } elseif ($assessment->tp_status === 'UNDER_VERIFICATION') {
                        $currentUnifiedStatus = 'UNDER_VERIFICATION';
                    } elseif ($assessment->tp_status === 'IN_PROGRESS' || $assessment->status === 'IN_PROGRESS') {
                        $currentUnifiedStatus = 'IN_PROGRESS_TP';
                    } elseif ($assessment->status === 'PLANNED') {
                        $currentUnifiedStatus = 'PLANNED';
                    }
                    $selectedUnified = old('unified_status', $currentUnifiedStatus);
                @endphp

                <div>
                    <label style="display: block;">
                        <span style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 4px; color: var(--ink);">Status Asesmen</span>
                        <select name="unified_status" id="modal_unified_status_select" required onchange="const satInput = document.getElementById('modal_tp_satisfied_at_{{ $assessment->id }}'); if(this.value === 'COMPLETED' && satInput && !satInput.value){ satInput.value = new Date().toISOString().split('T')[0]; }" style="width: 100%; padding: 8px 12px; border: 1px solid var(--input-border, var(--line)); border-radius: 6px; background: var(--input-bg, #ffffff); color: var(--ink); font-size: 13px;">
                            <option value="SCHEDULED" @selected($selectedUnified === 'SCHEDULED')>Terjadwal (Nihil / Belum Ada Temuan)</option>
                            <option value="IN_PROGRESS_TP" @selected($selectedUnified === 'IN_PROGRESS_TP')>Sedang Berlangsung: Penyusunan Perbaikan oleh LPK</option>
                            <option value="UNDER_VERIFICATION" @selected($selectedUnified === 'UNDER_VERIFICATION')>Sedang Berlangsung: Dalam Verifikasi Tim Asesor</option>
                            <option value="SUSPENDED" @selected($selectedUnified === 'SUSPENDED')>Dibekukan (Melewati Batas Waktu TP)</option>
                            <option value="COMPLETED" @selected($selectedUnified === 'COMPLETED')>Selesai (Dinyatakan Memenuhi)</option>
                            <option value="CANCELLED" @selected($selectedUnified === 'CANCELLED')>Dibatalkan</option>
                        </select>
                        <small style="color: var(--muted); font-size: 11px; display: block; margin-top: 3px;">
                            Status pelaksanaan agenda dan tindakan perbaikan otomatis tersinkronisasi.
                        </small>
                    </label>
                </div>

@php
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
                <div class="modal-form-grid-2col">
                    <label>
                        <span style="font-size: 13px; font-weight: 600; display: flex; align-items: center; gap: 6px; margin-bottom: 4px;">
                            <span>Batas Waktu Awal (KAN)</span>
                            <span id="modal-tp-ext-badge-{{ $assessment->id }}" class="badge-tp badge-tp-info" style="font-size: 10px; display: {{ $modalTpHasExt ? 'inline-flex' : 'none' }}; align-items: center; gap: 3px;">
                                +1 Bulan (Surat LPK)
                            </span>
                        </span>
                        <input type="date" name="tp_due_date" id="modal_tp_due_date_{{ $assessment->id }}" value="{{ $modalInitTpDueDate }}" data-base-date="{{ $modalBaseTpDate }}" data-is-extended="{{ $modalTpHasExt ? '1' : '0' }}" style="width: 100%; padding: 8px 12px; border: 1px solid var(--input-border, var(--line)); border-radius: 6px; background: var(--input-bg, #ffffff); color: var(--ink);">
                        <small id="modal_tp_due_date_hint_{{ $assessment->id }}" style="color: var(--muted); font-size: 11px;">
                            @if($modalTpHasExt)
                                * Otomatis diperpanjang +1 bulan karena ada surat permohonan perpanjangan LPK.
                            @else
                                Otomatis +2 atau +3 bulan jika dikosongkan.
                            @endif
                        </small>
                    </label>

                    <label>
                        <span style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 4px; color: var(--ink);">Tanggal Dinyatakan Memenuhi</span>
                        <input type="date" name="tp_satisfied_at" id="modal_tp_satisfied_at_{{ $assessment->id }}" onchange="if(this.value){ const s = document.getElementById('modal_unified_status_select'); if(s) s.value = 'COMPLETED'; }" value="{{ old('tp_satisfied_at', $assessment->tp_satisfied_at?->format('Y-m-d')) }}" style="width: 100%; padding: 8px 12px; border: 1px solid var(--input-border, var(--line)); border-radius: 6px; background: var(--input-bg, #ffffff); color: var(--ink);">
                        <small style="color: var(--muted); font-size: 11px;">Status otomatis beralih ke Selesai saat tanggal diisi.</small>
                    </label>
                </div>

                {{-- Bagian Permohonan Perpanjangan --}}
                <div style="padding: 12px 14px; background: var(--surface-subtle, #f8fafc); border: 1px solid var(--line); border-radius: 6px; display: grid; gap: 10px;">
                    <div>
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 13px; font-weight: 600; min-height: 44px; color: var(--ink);">
                            <input type="checkbox" name="tp_has_extension" id="modal_tp_has_extension_{{ $assessment->id }}" value="1" @checked($modalTpHasExt) onchange="window.syncModalTpExtension({{ $assessment->id }}, false)" style="width: 18px; height: 18px; min-height: 18px; max-height: 18px; min-width: 18px; max-width: 18px; margin: 0; padding: 0; cursor: pointer; flex-shrink: 0; accent-color: var(--maroon, #e11d48);">
                            <span>Ajukan Perpanjangan Masa Perbaikan (+1 Bulan Sesuai Aturan KAN)</span>
                        </label>
                        <small style="color: var(--muted); font-size: 11.5px; display: block; margin-top: 2px;">
                            Perpanjangan ditolak bila laboratorium belum memulai perbaikan sama sekali dalam batas awal.
                        </small>
                    </div>

                    <div id="extension-fields-{{ $assessment->id }}" style="display: {{ $modalTpHasExt ? 'grid' : 'none' }}; gap: 10px;">
                        <div class="modal-form-grid-2col">
                            <label>
                                <span style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 2px; color: var(--ink);">Nomor Surat Resmi LPK</span>
                                <input type="text" name="tp_extension_letter_no" id="modal_tp_ext_letter_no_{{ $assessment->id }}" oninput="window.syncModalTpExtension({{ $assessment->id }}, true)" onchange="window.syncModalTpExtension({{ $assessment->id }}, true)" placeholder="Contoh: 104/LPK-LAB/EXT/IX/2026" value="{{ old('tp_extension_letter_no', $assessment->tp_extension_letter_no) }}" style="width: 100%; padding: 7px 10px; border: 1px solid var(--input-border, var(--line)); border-radius: 4px; font-size: 12.5px; background: var(--input-bg, #ffffff); color: var(--ink);">
                            </label>
                            <label>
                                <span style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 2px; color: var(--ink);">Tanggal Surat</span>
                                <input type="date" name="tp_extension_date" value="{{ old('tp_extension_date', $assessment->tp_extension_date?->format('Y-m-d')) }}" style="width: 100%; padding: 7px 10px; border: 1px solid var(--input-border, var(--line)); border-radius: 4px; font-size: 12.5px; background: var(--input-bg, #ffffff); color: var(--ink);">
                            </label>
                        </div>
                        <label>
                            <span style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 2px; color: var(--ink);">Catatan Alasan Perpanjangan</span>
                            <input type="text" name="tp_extension_notes" placeholder="Contoh: Pengadaan bahan acuan standar dan kalibrasi ulang memerlukan waktu tambahan." value="{{ old('tp_extension_notes', $assessment->tp_extension_notes) }}" style="width: 100%; padding: 7px 10px; border: 1px solid var(--input-border, var(--line)); border-radius: 4px; font-size: 12.5px; background: var(--input-bg, #ffffff); color: var(--ink);">
                        </label>
                    </div>
                </div>

                {{-- Bagian Laporan Asesmen & EHA --}}
                <div style="padding: 10px 12px; background: var(--surface-subtle, var(--surface)); border: 1px solid var(--line); border-radius: 6px; display: grid; gap: 8px;">
                    <div>
                        <strong style="font-size: 12.5px; color: var(--ink); display: block;">Laporan Asesmen &amp; Evaluasi Hasil Asesmen (EHA)</strong>
                        <small style="color: var(--muted); font-size: 11px;">Pencatatan tanggal laporan dan evaluasi panitia teknis.</small>
                    </div>
                    <div class="modal-form-grid-2col">
                        <label>
                            <span style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 2px;">Tgl Laporan Asesmen</span>
                            <input type="date" name="report_date" value="{{ old('report_date', $assessment->report_date?->format('Y-m-d')) }}" style="width: 100%; padding: 7px 10px; border: 1px solid var(--input-border, var(--line)); border-radius: 4px; font-size: 12.5px; background: var(--input-bg, #ffffff); color: var(--ink);">
                        </label>
                        <label>
                            <span style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 2px;">Tgl EHA (Panitia Teknis)</span>
                            <input type="date" name="eha_date" value="{{ old('eha_date', $assessment->eha_date?->format('Y-m-d')) }}" style="width: 100%; padding: 7px 10px; border: 1px solid var(--input-border, var(--line)); border-radius: 4px; font-size: 12.5px; background: var(--input-bg, #ffffff); color: var(--ink);">
                        </label>
                    </div>
                    <div style="display: grid; grid-template-columns: 1fr; gap: 8px;">
                        <label>
                            <span style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 2px;">Status Hasil EHA</span>
                            <select name="eha_status" style="width: 100%; padding: 7px 10px; border: 1px solid var(--input-border, var(--line)); border-radius: 4px; font-size: 12.5px; background: var(--input-bg, #ffffff); color: var(--ink);">
                                @foreach(\App\Models\Assessment::EHA_STATUSES as $ehaVal => $ehaText)
                                    <option value="{{ $ehaVal }}" @selected(old('eha_status', $assessment->eha_status ?: \App\Models\Assessment::EHA_STATUS_BELUM) === $ehaVal)>
                                        {{ $ehaText }}
                                    </option>
                                @endforeach
                            </select>
                        </label>
                        <label>
                            <span style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 2px;">Catatan Hasil EHA</span>
                            <input type="text" name="eha_notes" placeholder="Catatan atau rekomendasi panitia teknis..." value="{{ old('eha_notes', $assessment->eha_notes) }}" style="width: 100%; padding: 7px 10px; border: 1px solid var(--input-border, var(--line)); border-radius: 4px; font-size: 12.5px; background: var(--input-bg, #ffffff); color: var(--ink);">
                        </label>
                    </div>
                </div>

                {{-- Bagian SK Hasil Asesmen --}}
                <div style="padding: 10px 12px; background: var(--mint, rgba(16, 185, 129, 0.08)); border: 1px solid rgba(16, 185, 129, 0.35); border-radius: 6px; display: grid; gap: 8px;">
                    <div>
                        <strong style="font-size: 12.5px; color: var(--green, #10b981); display: block;">Surat Keputusan (SK) Hasil Asesmen KAN</strong>
                        <small style="color: var(--muted); font-size: 11px;">Diisi jika tindakan perbaikan telah selesai / dinyatakan memenuhi dan terbit SK KAN.</small>
                    </div>
                    <div class="modal-form-grid-2col">
                        <label>
                            <span style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 2px;">Nomor SK KAN</span>
                            <input type="text" name="sk_number" placeholder="Contoh: SK.KAN.042/BSN/IX/2026" value="{{ old('sk_number', $assessment->sk_number) }}" style="width: 100%; padding: 7px 10px; border: 1px solid var(--input-border, var(--line)); border-radius: 4px; font-size: 12.5px; background: var(--input-bg, #ffffff); color: var(--ink);">
                        </label>
                        <label>
                            <span style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 2px;">Tanggal SK</span>
                            <input type="date" name="sk_date" value="{{ old('sk_date', $assessment->sk_date?->format('Y-m-d')) }}" style="width: 100%; padding: 7px 10px; border: 1px solid var(--input-border, var(--line)); border-radius: 4px; font-size: 12.5px; background: var(--input-bg, #ffffff); color: var(--ink);">
                        </label>
                    </div>
                    @if($assessment->sk_lead_time_label)
                        <div style="font-size: 11.5px; color: var(--green, #10b981); background: rgba(16, 185, 129, 0.16); padding: 4px 8px; border-radius: 4px;">
                            <strong>Rentang Waktu Proses:</strong> {{ $assessment->sk_lead_time_label }}
                            (dari pelaksanaan {{ ($assessment->end_at ?? $assessment->start_at)->format('d M Y') }} s/d SK {{ $assessment->sk_date->format('d M Y') }})
                        </div>
                    @endif
                </div>

                <label style="display: block;">
                    <span style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 4px; color: var(--ink);">Catatan Temuan &amp; Bukti Tindakan Perbaikan</span>
                    <textarea name="tp_notes" rows="3" placeholder="Rangkuman temuan ketidaksesuaian atau status kelengkapan bukti tindakan perbaikan LPK..." style="width: 100%; padding: 8px 12px; border: 1px solid var(--input-border, var(--line)); border-radius: 6px; background: var(--input-bg, #ffffff); color: var(--ink);">{{ old('tp_notes', $assessment->tp_notes) }}</textarea>
                </label>
            </div>
            <div class="modal-form-actions">
                <button type="button" class="button ghost" data-modal-close onclick="window.closeModal('modal-tp-tracking')">Batal</button>
                <button type="submit" class="button primary">Simpan Status TP</button>
            </div>
        </form>
    </div>
</div>

<script>
window.syncModalTpExtension = function(id, fromLetter) {
    const dueInput = document.getElementById('modal_tp_due_date_' + id);
    const hasExtBox = document.getElementById('modal_tp_has_extension_' + id);
    const letterInput = document.getElementById('modal_tp_ext_letter_no_' + id);
    const fieldsBlock = document.getElementById('extension-fields-' + id);
    const badge = document.getElementById('modal-tp-ext-badge-' + id);
    const hint = document.getElementById('modal_tp_due_date_hint_' + id);

    if (!dueInput) return;

    const letterVal = letterInput ? letterInput.value.trim() : '';
    const hasLetter = letterVal.length > 0;

    if (fromLetter && hasLetter && hasExtBox && !hasExtBox.checked) {
        hasExtBox.checked = true;
    }

    const shouldExtend = hasLetter || (hasExtBox ? hasExtBox.checked : false);

    if (fieldsBlock) {
        fieldsBlock.style.display = shouldExtend ? 'grid' : 'none';
    }

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
            if (hint) hint.textContent = 'Otomatis +2 atau +3 bulan jika dikosongkan.';
            dueInput.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }
};
</script>

