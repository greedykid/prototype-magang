{{-- Modal: Pembaruan Tindak Lanjut Asesmen (TP, Rapat Panitia Teknis & SK) KAN --}}
<div class="simasadi-modal" id="modal-tp-tracking" role="dialog" aria-modal="true" aria-labelledby="modal-tp-tracking-heading">
    <div class="simasadi-modal-box" style="max-width: min(580px, 100%); width: 100%; box-sizing: border-box;">
        <div class="simasadi-modal-head">
            <div class="modal-title-group">
                <div class="modal-title-icon-badge badge-purple" aria-hidden="true">
                    <x-icon name="assessments" size="17" />
                </div>
                <h4 id="modal-tp-tracking-heading">Pembaruan Tindak Lanjut Asesmen (TP, Rapat Panitia Teknis &amp; SK)</h4>
            </div>
            <button type="button" class="simasadi-modal-close" data-modal-close onclick="window.closeModal('modal-tp-tracking')" aria-label="Tutup modal">&times;</button>
        </div>
        <form method="POST" action="{{ route('assessments.tp.update', $assessment) }}" style="width: 100%; max-width: 100%; min-width: 0; box-sizing: border-box;">
            @csrf
            <input type="hidden" name="unified_status" id="modal_unified_status_select" value="{{ old('unified_status', $assessment->status === 'COMPLETED' ? 'COMPLETED' : ($assessment->tp_status === 'UNDER_VERIFICATION' ? 'UNDER_VERIFICATION' : ($assessment->status === 'SUSPENDED' ? 'SUSPENDED' : 'IN_PROGRESS'))) }}">
            <div class="simasadi-modal-body">
                <div style="display: grid; gap: 14px; width: 100%; max-width: 100%; min-width: 0; box-sizing: border-box;">

                @php
                    $selectedTpStatus = old('tp_status', $assessment->tp_status ?: (!empty($assessment->tp_satisfied_at) ? \App\Models\Assessment::TP_STATUS_SATISFIED : \App\Models\Assessment::TP_STATUS_IN_PROGRESS));

                    $modalIsTpSatisfied = $assessment->tp_status === \App\Models\Assessment::TP_STATUS_SATISFIED || !empty($assessment->tp_satisfied_at);
                    $modalIsEhaDone = (in_array($assessment->eha_status, [\App\Models\Assessment::EHA_STATUS_DIREKOMENDASIKAN, 'SUDAH_EHA'], true)
                        || (!empty($assessment->eha_date) && !in_array($assessment->eha_status, [\App\Models\Assessment::EHA_STATUS_BELUM, \App\Models\Assessment::EHA_STATUS_PERLU_VERIFIKASI, \App\Models\Assessment::EHA_STATUS_CATATAN_KHUSUS], true)))
                        && ($assessment->eha_status !== \App\Models\Assessment::EHA_STATUS_CATATAN_KHUSUS)
                        && ($assessment->eha_status !== \App\Models\Assessment::EHA_STATUS_PERLU_VERIFIKASI);

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

                {{-- Navigasi Tab Seksi Regulasi KAN U-01 --}}
                <div class="modal-tp-nav-tabs" role="tablist" aria-label="Navigasi Seksi Tindak Lanjut Asesmen">
                    <button type="button" class="tp-modal-tab-btn is-active" id="tp-modal-tab-btn-tp" role="tab" aria-selected="true" aria-controls="modal-tp-panel-tp" onclick="window.switchModalTpTab('tp')">
                        <span>1. Tindakan Perbaikan</span>
                    </button>
                    <button type="button" class="tp-modal-tab-btn" id="tp-modal-tab-btn-eha" role="tab" aria-selected="false" aria-controls="modal-tp-panel-eha" onclick="window.switchModalTpTab('eha')" title="2. Rapat Panitia Teknis">
                        <span>2. Rapat Panitia Teknis</span>
                    </button>
                    <button type="button" class="tp-modal-tab-btn" id="tp-modal-tab-btn-sk" role="tab" aria-selected="false" aria-controls="modal-tp-panel-sk" onclick="window.switchModalTpTab('sk')" title="3. SK KAN">
                        <span>3. SK KAN</span>
                    </button>
                </div>

                {{-- Panel 1: Tindakan Perbaikan (TP & VTP) --}}
                <div id="modal-tp-panel-tp" class="tp-modal-panel is-active" role="tabpanel" aria-labelledby="tp-modal-tab-btn-tp" style="display: grid; gap: 14px; width: 100%; max-width: 100%; min-width: 0; box-sizing: border-box;">
                    <div id="modal-section-tp-{{ $assessment->id }}" style="scroll-margin-top: 14px; width: 100%; max-width: 100%; min-width: 0; position: relative;">
                        <label style="display: block; width: 100%; max-width: 100%; min-width: 0;">
                            <span style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 4px; color: var(--ink);">Status Tindakan Perbaikan (TP)</span>
                            <select name="tp_status" id="modal_tp_status_select" required style="width: 100%; max-width: 100%; min-width: 0; box-sizing: border-box; text-overflow: ellipsis; padding: 8px 12px; border: 1px solid var(--input-border, var(--line)); border-radius: var(--radius-lg, 12px); background: var(--input-bg, #ffffff); color: var(--ink); font-size: 13px;">
                                <option value="SATISFIED" @selected($selectedTpStatus === 'SATISFIED')>Dinyatakan Memenuhi (Selesai)</option>
                                <option value="UNDER_VERIFICATION" @selected($selectedTpStatus === 'UNDER_VERIFICATION')>Dalam Verifikasi Tim Asesor</option>
                                <option value="IN_PROGRESS" @selected($selectedTpStatus === 'IN_PROGRESS')>Penyusunan Perbaikan oleh LPK</option>
                                <option value="NONE" @selected($selectedTpStatus === 'NONE')>Belum Ada Temuan / Belum Ditentukan</option>
                            </select>
                        </label>
                    </div>

                    <div class="modal-form-grid-2col">
                        <div style="width: 100%; max-width: 100%; min-width: 0;">
                            <span style="font-size: 13px; font-weight: 600; display: flex; align-items: center; min-height: 20px; margin-bottom: 4px; color: var(--ink);">
                                <span>Tenggat Waktu TP (KAN)</span>
                            </span>
                            <input type="date" name="tp_due_date" id="modal_tp_due_date_{{ $assessment->id }}" value="{{ $modalInitTpDueDate }}" data-base-date="{{ $modalBaseTpDate }}" data-is-extended="{{ $modalTpHasExt ? '1' : '0' }}" style="width: 100%; max-width: 100%; min-width: 0; box-sizing: border-box; padding: 8px 12px; border: 1px solid var(--input-border, var(--line)); border-radius: var(--radius-lg, 12px); background: var(--input-bg, #ffffff); color: var(--ink);">
                            <div style="margin-top: 3px; display: flex; align-items: center; gap: 5px; flex-wrap: wrap;">
                                <span id="modal-tp-ext-badge-{{ $assessment->id }}" class="badge-tp badge-tp-info" style="font-size: 10px; display: {{ $modalTpHasExt ? 'inline-flex' : 'none' }}; align-items: center; gap: 3px;">
                                    +1 Bulan (Surat LPK)
                                </span>
                                <small id="modal_tp_due_date_hint_{{ $assessment->id }}" style="color: var(--muted); font-size: 11px; line-height: 1.4;">
                                    @if($modalTpHasExt)
                                        * Diperpanjang sesuai permohonan resmi LPK.
                                    @else
                                        Batas akhir penyerahan perbaikan LPK sesuai aturan KAN.
                                    @endif
                                </small>
                            </div>
                        </div>

                        <div style="width: 100%; max-width: 100%; min-width: 0;">
                            <span style="font-size: 13px; font-weight: 600; display: flex; align-items: center; min-height: 20px; margin-bottom: 4px; color: var(--ink);">
                                <span>Tanggal Memenuhi (Selesai TP)</span>
                            </span>
                            <input type="date" name="tp_satisfied_at" id="modal_tp_satisfied_at_{{ $assessment->id }}" value="{{ old('tp_satisfied_at', $assessment->tp_satisfied_at?->format('Y-m-d')) }}" style="width: 100%; max-width: 100%; min-width: 0; box-sizing: border-box; padding: 8px 12px; border: 1px solid var(--input-border, var(--line)); border-radius: var(--radius-lg, 12px); background: var(--input-bg, #ffffff); color: var(--ink);">
                            <small style="color: var(--muted); font-size: 11px; display: block; margin-top: 3px; line-height: 1.4;">Tanggal verifikasi saat seluruh temuan perbaikan tuntas.</small>
                            <div id="modal_tp_satisfied_error_{{ $assessment->id }}" style="display: none; margin-top: 6px; padding: 7px 10px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; color: #b91c1c; font-size: 11.5px; line-height: 1.4;">
                                <strong>Peringatan KAN U-01 Rev.3 (Klausul 2.11.12):</strong>
                                <span style="display: block; margin-top: 2px;">Tanggal memenuhi melewati batas waktu perbaikan. Penyelesaian melewati batas waktu tidak dapat dinyatakan memenuhi dan berakibat pembekuan status akreditasi LPK.</span>
                            </div>
                            @error('tp_satisfied_at')
                                <div style="margin-top: 6px; padding: 7px 10px; background: #fef2f2; border: 1px solid #fecaca; border-radius: 8px; color: #b91c1c; font-size: 11.5px; line-height: 1.4;">
                                    {{ $message }}
                                </div>
                            @enderror
                        </div>
                    </div>

                    {{-- Bagian Permohonan Perpanjangan --}}
                    <div style="padding: 12px 14px; background: var(--surface-subtle, #f8fafc); border: 1px solid var(--line); border-radius: var(--radius-lg, 12px); display: grid; gap: 10px; width: 100%; max-width: 100%; min-width: 0; box-sizing: border-box;">
                        <div>
                            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 13px; font-weight: 600; min-height: 44px; color: var(--ink);">
                                <input type="hidden" name="tp_has_extension" value="0">
                                <input type="checkbox" name="tp_has_extension" id="modal_tp_has_extension_{{ $assessment->id }}" value="1" @checked($modalTpHasExt) onchange="window.syncModalTpExtension({{ $assessment->id }}, false)" style="width: 18px; height: 18px; min-height: 18px; max-height: 18px; min-width: 18px; max-width: 18px; margin: 0; padding: 0; cursor: pointer; flex-shrink: 0; accent-color: var(--maroon, #e11d48);">
                                <span>Ajukan Perpanjangan Masa Perbaikan (+1 Bulan Sesuai Aturan KAN)</span>
                            </label>
                            <small style="color: var(--muted); font-size: 11.5px; display: block; margin-top: 2px;">
                                Perpanjangan ditolak bila laboratorium belum memulai perbaikan sama sekali dalam batas awal.
                            </small>
                        </div>

                        <div id="extension-fields-{{ $assessment->id }}" class="modal-tp-extension-collapse {{ $modalTpHasExt ? 'is-expanded is-overflow-visible' : '' }}" @if(!$modalTpHasExt) inert @endif>
                            <div class="modal-tp-extension-inner">
                                <div class="modal-form-grid-2col">
                                    <label style="width: 100%; max-width: 100%; min-width: 0;">
                                        <span style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 2px; color: var(--ink);">
                                            Nomor Surat Resmi LPK <span class="lpk-required-dot modal-ext-req-{{ $assessment->id }}" style="display: {{ $modalTpHasExt ? 'inline' : 'none' }}; color: #ef4444;">*</span>
                                        </span>
                                        <input type="text" name="tp_extension_letter_no" id="modal_tp_ext_letter_no_{{ $assessment->id }}" oninput="window.syncModalTpExtension({{ $assessment->id }}, true)" onchange="window.syncModalTpExtension({{ $assessment->id }}, true)" maxlength="80" placeholder="Contoh: 104/LPK-LAB/EXT/IX/2026" value="{{ old('tp_extension_letter_no', $assessment->tp_extension_letter_no) }}" @if($modalTpHasExt) required @else disabled @endif style="width: 100%; max-width: 100%; min-width: 0; box-sizing: border-box; padding: 7px 10px; border: 1px solid var(--input-border, var(--line)); border-radius: var(--radius-lg, 12px); font-size: 12.5px; background: var(--input-bg, #ffffff); color: var(--ink);">
                                    </label>
                                    <div style="width: 100%; max-width: 100%; min-width: 0;">
                                        <span style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 2px; color: var(--ink);">
                                            Tanggal Surat <span class="lpk-required-dot modal-ext-req-{{ $assessment->id }}" style="display: {{ $modalTpHasExt ? 'inline' : 'none' }}; color: #ef4444;">*</span>
                                        </span>
                                        <input type="date" name="tp_extension_date" id="modal_tp_ext_date_{{ $assessment->id }}" value="{{ old('tp_extension_date', $assessment->tp_extension_date?->format('Y-m-d')) }}" @if($modalTpHasExt) required @else disabled @endif style="width: 100%; max-width: 100%; min-width: 0; box-sizing: border-box; padding: 7px 10px; border: 1px solid var(--input-border, var(--line)); border-radius: var(--radius-lg, 12px); font-size: 12.5px; background: var(--input-bg, #ffffff); color: var(--ink);">
                                    </div>
                                </div>
                                <label style="width: 100%; max-width: 100%; min-width: 0;">
                                    <span style="font-size: 12px; font-weight: 600; display: block; margin-bottom: 2px; color: var(--ink);">Catatan Alasan Perpanjangan</span>
                                    <input type="text" name="tp_extension_notes" maxlength="1000" placeholder="Contoh: Pengadaan bahan acuan standar dan kalibrasi ulang memerlukan waktu tambahan." value="{{ old('tp_extension_notes', $assessment->tp_extension_notes) }}" @if(!$modalTpHasExt) disabled @endif style="width: 100%; max-width: 100%; min-width: 0; box-sizing: border-box; padding: 7px 10px; border: 1px solid var(--input-border, var(--line)); border-radius: var(--radius-lg, 12px); font-size: 12.5px; background: var(--input-bg, #ffffff); color: var(--ink);">
                                </label>
                            </div>
                        </div>
                    </div>

                    <label style="display: block; width: 100%; max-width: 100%; min-width: 0;">
                        <span style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 4px; color: var(--ink);">Catatan Temuan &amp; Bukti Tindakan Perbaikan</span>
                        <textarea name="tp_notes" rows="3" maxlength="5000" placeholder="Rangkuman temuan ketidaksesuaian atau status kelengkapan bukti tindakan perbaikan LPK..." style="width: 100%; max-width: 100%; min-width: 0; box-sizing: border-box; padding: 8px 12px; border: 1px solid var(--input-border, var(--line)); border-radius: var(--radius-lg, 12px); background: var(--input-bg, #ffffff); color: var(--ink);">{{ old('tp_notes', $assessment->tp_notes) }}</textarea>
                    </label>
                </div>

                {{-- Panel 2: Laporan Asesmen & Rapat Panitia Teknis --}}
                <div id="modal-tp-panel-eha" class="tp-modal-panel" role="tabpanel" aria-labelledby="tp-modal-tab-btn-eha" style="display: none; gap: 14px; width: 100%; max-width: 100%; min-width: 0; box-sizing: border-box;">
                    <div style="padding: 10px 14px; background: var(--surface-subtle, #f8fafc); border: 1px solid var(--line); border-radius: var(--radius-lg, 12px); display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: wrap;">
                        <span style="font-size: 13px; font-weight: 500; color: var(--muted);">Status Tindak Lanjut TP Saat Ini:</span>
                        @php
                            $tpType = match(true) {
                                $assessment->tp_status === 'SATISFIED' => 'success',
                                $assessment->is_tp_overdue => 'danger',
                                $assessment->tp_status === 'UNDER_VERIFICATION' => 'info',
                                $assessment->tp_status === 'IN_PROGRESS' => 'warning',
                                default => 'neutral',
                            };
                            $badgeStyles = match($tpType) {
                                'success' => 'background: #ecfdf5; border: 1px solid #a7f3d0; color: #047857;',
                                'danger' => 'background: #fef2f2; border: 1px solid #fecaca; color: #b91c1c;',
                                'info' => 'background: #eff6ff; border: 1px solid #bfdbfe; color: #1d4ed8;',
                                'warning' => 'background: #fffbeb; border: 1px solid #fde68a; color: #b45309;',
                                default => 'background: #f1f5f9; border: 1px solid #cbd5e1; color: #475569;',
                            };
                        @endphp
                        <span id="modal-tp-current-badge-{{ $assessment->id }}" class="badge badge-pill badge-tp badge-tp-{{ $tpType }}" style="font-size: 12.5px; font-weight: 600; padding: 4px 12px; border-radius: 9999px; {{ $badgeStyles }}">
                            {{ $assessment->tp_status_label }}
                        </span>
                    </div>

                    {{-- Smart Assist 1: Nihil Temuan / Tanpa Perbaikan --}}
                    @php
                        $isTpNoneOrEmpty = in_array($selectedTpStatus, [\App\Models\Assessment::TP_STATUS_NONE, null, ''], true) && !$modalIsTpSatisfied;
                    @endphp
                    <div id="smart-assist-nihil-card-{{ $assessment->id }}" class="smart-assist-card emerald" style="display: {{ $isTpNoneOrEmpty ? 'flex' : 'none' }};">
                        <div class="smart-assist-icon">
                            <x-icon name="check-circle" size="18" />
                        </div>
                        <div class="smart-assist-content">
                            <div class="smart-assist-title">Asesmen Lapangan Tanpa Temuan (Nihil)?</div>
                            <p class="smart-assist-desc">Jika asesmen lapangan tidak memiliki temuan ketidaksesuaian, Anda dapat langsung mengesahkan status perbaikan tanpa harus kembali ke Tab 1.</p>
                            <button type="button" class="button secondary btn-xs smart-assist-btn" onclick="window.applySmartAssistNihil({{ $assessment->id }}, '{{ $assessment->end_at?->format('Y-m-d') ?: ($assessment->start_at?->format('Y-m-d') ?: now()->toDateString()) }}')">
                                <x-icon name="check" size="14" />
                                <span>Sahkan Nihil Temuan &amp; Tuntaskan TP</span>
                            </button>
                        </div>
                    </div>
                    <div id="smart-assist-nihil-applied-{{ $assessment->id }}" class="smart-assist-success" style="display: none;">
                        <x-icon name="check-circle" size="15" />
                        <span>TP otomatis disahkan berstatus Nihil Temuan (Memenuhi). Anda dapat melanjutkan pengisian Rapat Panitia Teknis.</span>
                    </div>

                    <div id="modal-tp-panel-eha-warning-{{ $assessment->id }}" style="padding: 10px 14px; background: #fffbeb; border: 1px solid #fde68a; border-radius: var(--radius-lg, 12px); font-size: 13px; color: #92400e; display: {{ !$modalIsTpSatisfied ? 'flex' : 'none' }}; align-items: flex-start; gap: 10px; line-height: 1.5;">
                        <x-icon name="alert-triangle" size="16" style="flex-shrink: 0; margin-top: 2px; color: #d97706;" />
                        <span><strong>Perhatian:</strong> Tindakan perbaikan (TP) belum berstatus memenuhi. Catatan rapat tetap dapat disimpan, namun rekomendasi baru berlaku setelah TP tuntas diverifikasi.</span>
                    </div>

                    <div id="modal-section-eha" style="padding: 12px 14px; background: var(--surface-subtle, var(--surface)); border: 1px solid var(--line); border-radius: var(--radius-lg, 12px); display: grid; gap: 10px; width: 100%; max-width: 100%; min-width: 0; box-sizing: border-box;">
                        <div>
                            <strong style="font-size: 13px; color: var(--ink); display: block;">Laporan Asesmen &amp; Rapat Panitia Teknis</strong>
                            <small style="color: var(--muted); font-size: 11px;">Pencatatan tanggal laporan asesmen tim asesor dan evaluasi oleh panitia teknis KAN.</small>
                        </div>
                        <div class="modal-form-grid-2col">
                            <div class="modal-field-label">
                                <span>Tgl Laporan Asesmen</span>
                                <input type="date" name="report_date" value="{{ old('report_date', $assessment->report_date?->format('Y-m-d')) }}" class="modal-form-input">
                            </div>
                            <div class="modal-field-label">
                                <span>Tgl Rapat Panitia Teknis</span>
                                <input type="date" name="eha_date" id="modal_eha_date_{{ $assessment->id }}" value="{{ old('eha_date', $assessment->eha_date?->format('Y-m-d')) }}" class="modal-form-input" onchange="window.syncEhaStatusFromDate({{ $assessment->id }})" oninput="window.syncEhaStatusFromDate({{ $assessment->id }})">
                            </div>
                        </div>
                        <div style="display: grid; grid-template-columns: 1fr; gap: 8px; width: 100%; max-width: 100%; min-width: 0; box-sizing: border-box;">
                            <label class="modal-field-label">
                                <span>Status Hasil Rapat Panitia Teknis</span>
                                <select name="eha_status" id="modal_eha_status_select" class="modal-form-input" onchange="window.checkEhaConflict({{ $assessment->id }})">
                                    @foreach(\App\Models\Assessment::EHA_STATUSES as $ehaVal => $ehaText)
                                        <option value="{{ $ehaVal }}" @selected(old('eha_status', $assessment->eha_status ?: \App\Models\Assessment::EHA_STATUS_BELUM) === $ehaVal)>
                                            {{ $ehaText }}
                                        </option>
                                    @endforeach
                                </select>
                            </label>

                            {{-- Smart Assist 2: Resolusi Konflik Rekomendasi vs TP Belum Selesai --}}
                            <div id="smart-assist-eha-conflict-{{ $assessment->id }}" class="smart-assist-alert amber" style="display: none;">
                                <div class="smart-assist-icon">
                                    <x-icon name="alert-triangle" size="16" />
                                </div>
                                <div class="smart-assist-content">
                                    <div class="smart-assist-title">Tindakan Perbaikan (TP) Belum Dinyatakan Memenuhi</div>
                                    <p class="smart-assist-desc">Rekomendasi RPT idealnya diberikan setelah perbaikan LPK tuntas diverifikasi. Pilih mode penyelesaian:</p>
                                    <div class="smart-assist-actions">
                                        <button type="button" class="button primary btn-xs" onclick="window.resolveTpAndEhaTogether({{ $assessment->id }}, '{{ now()->toDateString() }}')">
                                            <x-icon name="check" size="13" />
                                            <span>Verifikasi TP Selesai Hari Ini &amp; Simpan Rekomendasi</span>
                                        </button>
                                        <button type="button" class="button secondary btn-xs" onclick="window.keepEhaAsDraft({{ $assessment->id }})">
                                            <span>Tetap Simpan Sebagai Draf Rapat (TP Berjalan)</span>
                                        </button>
                                    </div>
                                </div>
                            </div>
                            <div id="smart-assist-eha-draft-notice-{{ $assessment->id }}" class="smart-assist-desc" style="display: none; color: var(--muted); margin-top: 4px; font-style: italic;">
                                * Rekomendasi panitia teknis akan disimpan sebagai draf; proses perbaikan TP tetap berlangsung sampai diverifikasi tuntas.
                            </div>

                            <label class="modal-field-label">
                                <span>Catatan Hasil Rapat Panitia Teknis</span>
                                <input type="text" name="eha_notes" maxlength="2000" placeholder="Catatan atau rekomendasi panitia teknis..." value="{{ old('eha_notes', $assessment->eha_notes) }}" class="modal-form-input">
                            </label>
                        </div>
                    </div>
                </div>

                {{-- Panel 3: Penetapan SK KAN --}}
                <div id="modal-tp-panel-sk" class="tp-modal-panel" role="tabpanel" aria-labelledby="tp-modal-tab-btn-sk" style="display: none; gap: 14px; width: 100%; max-width: 100%; min-width: 0; box-sizing: border-box;">
                    <div style="padding: 10px 14px; background: var(--surface-subtle, #f8fafc); border: 1px solid var(--line); border-radius: var(--radius-lg, 12px); display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: wrap;">
                        <span style="font-size: 13px; font-weight: 500; color: var(--muted);">Status Rekomendasi Rapat Panitia Teknis:</span>
                        @php
                            $ehaType = $assessment->eha_status === 'DIREKOMENDASIKAN' ? 'success' : 'neutral';
                            $ehaBadgeStyles = $ehaType === 'success'
                                ? 'background: #ecfdf5; border: 1px solid #a7f3d0; color: #047857;'
                                : 'background: #f1f5f9; border: 1px solid #cbd5e1; color: #475569;';
                        @endphp
                        <span class="badge badge-pill badge-tp badge-tp-{{ $ehaType }}" style="font-size: 12.5px; font-weight: 600; padding: 4px 12px; border-radius: 9999px; {{ $ehaBadgeStyles }}">
                            {{ $assessment->eha_status_label ?? 'Belum Ada Rapat Panitia Teknis' }}
                        </span>
                    </div>

                    @if(!$modalIsTpSatisfied || !$modalIsEhaDone)
                        <div id="modal-tp-panel-sk-info-{{ $assessment->id }}" style="padding: 10px 14px; background: #eff6ff; border: 1px solid #bfdbfe; border-radius: var(--radius-lg, 12px); font-size: 13px; color: #1e40af; display: flex; align-items: flex-start; gap: 10px; line-height: 1.5;">
                            <x-icon name="info" size="16" style="flex-shrink: 0; margin-top: 2px; color: #2563eb;" />
                            <span><strong>Info:</strong> Anda sudah bisa mengisi nomor dan tanggal SK sekarang. Status asesmen akan otomatis berubah menjadi <strong>Selesai</strong> setelah tindakan perbaikan (TP) dan rapat panitia teknis selesai.</span>
                        </div>
                    @endif

                    {{-- Smart Assist 3: Fast-Track SK Resmi Terbit --}}
                    <div id="smart-assist-sk-fasttrack-{{ $assessment->id }}" class="smart-assist-card emerald" style="display: none;">
                        <div class="smart-assist-icon">
                            <x-icon name="check-circle" size="18" />
                        </div>
                        <div class="smart-assist-content">
                            <div class="smart-assist-title">SK Akreditasi Resmi Terbit</div>
                            <p class="smart-assist-desc">Karena SK KAN telah terbit resmi, seluruh tahapan perbaikan dan evaluasi panitia teknis secara de facto telah selesai dilalui.</p>
                            <button type="button" class="button secondary btn-xs smart-assist-btn" onclick="window.applySmartAssistSkFastTrack({{ $assessment->id }}, '{{ now()->toDateString() }}')">
                                <x-icon name="check" size="14" />
                                <span>Sahkan Seluruh Tahapan &amp; Selesaikan Asesmen</span>
                            </button>
                        </div>
                    </div>
                    <div id="smart-assist-sk-applied-{{ $assessment->id }}" class="smart-assist-success" style="display: none;">
                        <x-icon name="check-circle" size="15" />
                        <span>Seluruh tahapan perbaikan dan evaluasi panitia teknis otomatis disahkan memenuhi sesuai SK resmi KAN.</span>
                    </div>

                    <div id="modal-section-sk" style="padding: 12px 14px; background: var(--mint, rgba(16, 185, 129, 0.08)); border: 1px solid rgba(16, 185, 129, 0.35); border-radius: var(--radius-lg, 12px); display: grid; gap: 10px; width: 100%; max-width: 100%; min-width: 0; box-sizing: border-box; position: relative;">
                        <div>
                            <strong style="font-size: 13px; color: var(--green, #10b981); display: block;">Surat Keputusan (SK) Hasil Asesmen KAN</strong>
                            <small style="color: var(--muted); font-size: 11px;">Opsional / Fleksibel: dapat diisi sekarang (jika sudah terbit) atau diisi menyusul setelah dokumen resmi diterima dari KAN.</small>
                        </div>
                        <div class="modal-form-grid-2col">
                            <label class="modal-field-label">
                                <span>Nomor SK KAN</span>
                                <input type="text" name="sk_number" id="modal_sk_number_{{ $assessment->id }}" maxlength="80" placeholder="Contoh: SK.KAN.042/BSN/IX/2026" value="{{ old('sk_number', $assessment->sk_number) }}" class="modal-form-input" oninput="window.checkSkFastTrack({{ $assessment->id }})" onchange="window.checkSkFastTrack({{ $assessment->id }})">
                            </label>
                            <div class="modal-field-label">
                                <span>Tanggal SK</span>
                                <input type="date" name="sk_date" id="modal_sk_date_{{ $assessment->id }}" value="{{ old('sk_date', $assessment->sk_date?->format('Y-m-d')) }}" class="modal-form-input" oninput="window.checkSkFastTrack({{ $assessment->id }})" onchange="window.checkSkFastTrack({{ $assessment->id }})">
                            </div>
                        </div>
                        @if($assessment->sk_lead_time_label)
                            <div style="font-size: 11.5px; color: var(--green, #10b981); background: rgba(16, 185, 129, 0.16); padding: 5px 10px; border-radius: var(--radius-pill, 9999px); word-break: break-word;">
                                <strong>Rentang Waktu Proses:</strong> {{ $assessment->sk_lead_time_label }}
                                (dari pelaksanaan {{ ($assessment->end_at ?? $assessment->start_at)->translatedFormat('d M Y') }} s/d SK {{ $assessment->sk_date->translatedFormat('d M Y') }})
                            </div>
                        @endif
                    </div>
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
window.syncEhaStatusFromDate = function(id) {
    const ehaDateInput = document.getElementById('modal_eha_date_' + id);
    const ehaStatusSelect = document.getElementById('modal_eha_status_select');
    if (!ehaDateInput || !ehaStatusSelect) return;

    if (ehaDateInput.value) {
        if (ehaStatusSelect.value === 'BELUM_EHA' || !ehaStatusSelect.value) {
            ehaStatusSelect.value = 'DIREKOMENDASIKAN';
            ehaStatusSelect.dispatchEvent(new Event('change', { bubbles: true }));
        }
    } else {
        if (ehaStatusSelect.value === 'DIREKOMENDASIKAN') {
            ehaStatusSelect.value = 'BELUM_EHA';
            ehaStatusSelect.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }
    if (window.checkEhaConflict) {
        window.checkEhaConflict(id);
    }
};

window.applySmartAssistNihil = function(id, defaultDate) {
    const tpStatusSelect = document.getElementById('modal_tp_status_select');
    const tpSatisfiedInput = document.getElementById('modal_tp_satisfied_at_' + id);
    const nihilCard = document.getElementById('smart-assist-nihil-card-' + id);
    const nihilApplied = document.getElementById('smart-assist-nihil-applied-' + id);
    const warning = document.getElementById('modal-tp-panel-eha-warning-' + id);
    const badge = document.getElementById('modal-tp-current-badge-' + id);

    if (tpStatusSelect) {
        tpStatusSelect.value = 'SATISFIED';
        tpStatusSelect.dispatchEvent(new Event('change', { bubbles: true }));
    }
    if (tpSatisfiedInput) {
        tpSatisfiedInput.value = defaultDate || new Date().toISOString().split('T')[0];
        tpSatisfiedInput.dispatchEvent(new Event('change', { bubbles: true }));
    }
    if (nihilCard) nihilCard.style.display = 'none';
    if (nihilApplied) nihilApplied.style.display = 'flex';
    if (warning) warning.style.display = 'none';
    if (badge) {
        badge.textContent = 'Dinyatakan Memenuhi (Nihil Temuan)';
        badge.className = 'badge badge-pill badge-tp badge-tp-success';
        badge.style.background = '#ecfdf5';
        badge.style.border = '1px solid #a7f3d0';
        badge.style.color = '#047857';
    }
};

window.checkEhaConflict = function(id) {
    const ehaStatusSelect = document.getElementById('modal_eha_status_select');
    const tpStatusSelect = document.getElementById('modal_tp_status_select');
    const tpSatisfiedInput = document.getElementById('modal_tp_satisfied_at_' + id);
    const conflictAlert = document.getElementById('smart-assist-eha-conflict-' + id);
    const draftNotice = document.getElementById('smart-assist-eha-draft-notice-' + id);

    if (!ehaStatusSelect || !conflictAlert) return;

    const isEhaRecommended = ehaStatusSelect.value === 'DIREKOMENDASIKAN';
    const isTpSatisfied = tpStatusSelect && tpStatusSelect.value === 'SATISFIED' && tpSatisfiedInput && tpSatisfiedInput.value;

    if (isEhaRecommended && !isTpSatisfied) {
        conflictAlert.style.display = 'flex';
        if (draftNotice) draftNotice.style.display = 'none';
    } else {
        conflictAlert.style.display = 'none';
    }
};

window.resolveTpAndEhaTogether = function(id, todayDate) {
    const tpStatusSelect = document.getElementById('modal_tp_status_select');
    const tpSatisfiedInput = document.getElementById('modal_tp_satisfied_at_' + id);
    const conflictAlert = document.getElementById('smart-assist-eha-conflict-' + id);
    const warning = document.getElementById('modal-tp-panel-eha-warning-' + id);
    const badge = document.getElementById('modal-tp-current-badge-' + id);

    if (tpStatusSelect) {
        tpStatusSelect.value = 'SATISFIED';
        tpStatusSelect.dispatchEvent(new Event('change', { bubbles: true }));
    }
    if (tpSatisfiedInput) {
        tpSatisfiedInput.value = todayDate || new Date().toISOString().split('T')[0];
        tpSatisfiedInput.dispatchEvent(new Event('change', { bubbles: true }));
    }
    if (conflictAlert) conflictAlert.style.display = 'none';
    if (warning) warning.style.display = 'none';
    if (badge) {
        badge.textContent = 'Dinyatakan Memenuhi (Selesai)';
        badge.className = 'badge badge-pill badge-tp badge-tp-success';
        badge.style.background = '#ecfdf5';
        badge.style.border = '1px solid #a7f3d0';
        badge.style.color = '#047857';
    }
};

window.keepEhaAsDraft = function(id) {
    const conflictAlert = document.getElementById('smart-assist-eha-conflict-' + id);
    const draftNotice = document.getElementById('smart-assist-eha-draft-notice-' + id);
    if (conflictAlert) conflictAlert.style.display = 'none';
    if (draftNotice) draftNotice.style.display = 'block';
};

window.checkSkFastTrack = function(id) {
    const skNumInput = document.getElementById('modal_sk_number_' + id);
    const skDateInput = document.getElementById('modal_sk_date_' + id);
    const tpStatusSelect = document.getElementById('modal_tp_status_select');
    const skCard = document.getElementById('smart-assist-sk-fasttrack-' + id);
    const skInfo = document.getElementById('modal-tp-panel-sk-info-' + id);

    if (!skNumInput || !skDateInput || !skCard) return;

    const hasSk = skNumInput.value.trim().length > 0 && skDateInput.value.trim().length > 0;
    const isTpNotSatisfied = !tpStatusSelect || tpStatusSelect.value !== 'SATISFIED';

    if (hasSk && isTpNotSatisfied) {
        skCard.style.display = 'flex';
        if (skInfo) skInfo.style.display = 'none';
    } else {
        skCard.style.display = 'none';
    }
};

window.applySmartAssistSkFastTrack = function(id, todayDate) {
    const tpStatusSelect = document.getElementById('modal_tp_status_select');
    const tpSatisfiedInput = document.getElementById('modal_tp_satisfied_at_' + id);
    const ehaStatusSelect = document.getElementById('modal_eha_status_select');
    const skCard = document.getElementById('smart-assist-sk-fasttrack-' + id);
    const skApplied = document.getElementById('smart-assist-sk-applied-' + id);

    if (tpStatusSelect && tpStatusSelect.value !== 'SATISFIED') {
        tpStatusSelect.value = 'SATISFIED';
        tpStatusSelect.dispatchEvent(new Event('change', { bubbles: true }));
    }
    if (tpSatisfiedInput && !tpSatisfiedInput.value) {
        tpSatisfiedInput.value = todayDate || new Date().toISOString().split('T')[0];
        tpSatisfiedInput.dispatchEvent(new Event('change', { bubbles: true }));
    }
    if (ehaStatusSelect && ehaStatusSelect.value === 'BELUM_EHA') {
        ehaStatusSelect.value = 'DIREKOMENDASIKAN';
    }
    if (skCard) skCard.style.display = 'none';
    if (skApplied) skApplied.style.display = 'flex';
};

window.syncModalTpTabGates = function() {
    // No-op: tab dibuka bebas tanpa sistem kunci
};

window.switchModalTpTab = function(tabName) {
    if (typeof window.closeAllCustomPickers === 'function') {
        window.closeAllCustomPickers();
    }
    if (typeof window.closeAllCustomSelects === 'function') {
        window.closeAllCustomSelects();
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
            if (isActive) {
                const wasActive = panel.classList.contains('is-active') && panel.style.display !== 'none';
                panel.style.display = 'grid';
                if (!wasActive) {
                    panel.classList.remove('is-active');
                    void panel.offsetWidth; // Force layout reflow so animation restarts reliably
                    panel.classList.add('is-active');
                }
            } else {
                panel.style.display = 'none';
                panel.classList.remove('is-active');
            }
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
        if (shouldExtend) {
            fieldsBlock.inert = false;
            fieldsBlock.classList.add('is-expanded');
            clearTimeout(fieldsBlock._overflowTimer);
            fieldsBlock._overflowTimer = setTimeout(function() {
                if (fieldsBlock.classList.contains('is-expanded')) {
                    fieldsBlock.classList.add('is-overflow-visible');
                }
            }, 280);
        } else {
            clearTimeout(fieldsBlock._overflowTimer);
            fieldsBlock.classList.remove('is-overflow-visible');
            fieldsBlock.classList.remove('is-expanded');
            fieldsBlock.inert = true;
            if (typeof window.closeAllCustomPickers === 'function') {
                window.closeAllCustomPickers();
            }
        }
    }

    if (letterInput) {
        letterInput.required = shouldExtend;
        letterInput.disabled = !shouldExtend;
    }
    if (extDateInput) {
        extDateInput.required = shouldExtend;
        extDateInput.disabled = !shouldExtend;
    }
    const notesInput = document.querySelector('#extension-fields-' + id + ' input[name="tp_extension_notes"]');
    if (notesInput) {
        notesInput.disabled = !shouldExtend;
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
            if (hint) hint.textContent = '* Diperpanjang sesuai permohonan resmi LPK.';
            dueInput.dispatchEvent(new Event('change', { bubbles: true }));
        }
    } else if (!shouldExtend && isExtended) {
        let base = dueInput.dataset.baseDate || (dueInput.value ? subMonth(dueInput.value) : '');
        if (base) {
            dueInput.value = base;
            dueInput.dataset.isExtended = '0';
            if (badge) badge.style.display = 'none';
            if (hint) hint.textContent = 'Batas akhir penyerahan perbaikan LPK sesuai aturan KAN.';
            dueInput.dispatchEvent(new Event('change', { bubbles: true }));
        }
    }
};

(function() {
    function initTpTrackingAutoSync() {
        const tpStatusSelect = document.getElementById('modal_tp_status_select');
        const tpSatisfiedInput = document.getElementById('modal_tp_satisfied_at_{{ $assessment->id }}');
        const tpDueDateInput = document.getElementById('modal_tp_due_date_{{ $assessment->id }}');
        const unifiedStatusInput = document.getElementById('modal_unified_status_select');
        const errorBox = document.getElementById('modal_tp_satisfied_error_{{ $assessment->id }}');
        const form = document.querySelector('#modal-tp-tracking form');
        const submitBtn = form ? form.querySelector('button[type="submit"]') : null;

        if (!tpSatisfiedInput || !tpStatusSelect) return;

        let isSyncing = false;

        function validateAndSyncTpStatus() {
            if (isSyncing) return;
            isSyncing = true;
            try {
                const satisfiedVal = (tpSatisfiedInput.value || '').trim();
                const dueVal = tpDueDateInput ? (tpDueDateInput.value || '').trim() : '';

                const isPastDue = satisfiedVal && dueVal && satisfiedVal > dueVal;

                if (isPastDue) {
                    if (errorBox) errorBox.style.display = 'block';
                    tpSatisfiedInput.style.borderColor = '#ef4444';

                    // Batalkan status SATISFIED jika tanggal memenuhi lewat batas waktu KAN
                    if (tpStatusSelect.value === 'SATISFIED') {
                        tpStatusSelect.value = 'IN_PROGRESS';
                        tpStatusSelect.dispatchEvent(new Event('change', { bubbles: true }));
                    }
                    if (unifiedStatusInput && unifiedStatusInput.value === 'COMPLETED') {
                        unifiedStatusInput.value = 'IN_PROGRESS';
                    }
                    if (submitBtn) {
                        submitBtn.disabled = true;
                        submitBtn.style.opacity = '0.6';
                        submitBtn.style.cursor = 'not-allowed';
                        submitBtn.title = 'Tanggal memenuhi melewati batas waktu perbaikan (KAN U-01 Rev.3)';
                    }
                } else {
                    if (errorBox) errorBox.style.display = 'none';
                    tpSatisfiedInput.style.borderColor = '';
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.style.opacity = '';
                        submitBtn.style.cursor = '';
                        submitBtn.removeAttribute('title');
                    }

                    if (satisfiedVal) {
                        if (tpStatusSelect.value !== 'SATISFIED') {
                            tpStatusSelect.value = 'SATISFIED';
                            tpStatusSelect.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                        if (unifiedStatusInput) {
                            unifiedStatusInput.value = 'COMPLETED';
                        }
                    } else {
                        if (tpStatusSelect.value === 'SATISFIED') {
                            tpStatusSelect.value = 'IN_PROGRESS';
                            tpStatusSelect.dispatchEvent(new Event('change', { bubbles: true }));
                        }
                        if (unifiedStatusInput && unifiedStatusInput.value === 'COMPLETED') {
                            unifiedStatusInput.value = (tpStatusSelect.value === 'UNDER_VERIFICATION') ? 'UNDER_VERIFICATION' : 'IN_PROGRESS';
                        }
                    }
                }
            } finally {
                isSyncing = false;
            }
        }

        tpSatisfiedInput.addEventListener('change', validateAndSyncTpStatus);
        tpSatisfiedInput.addEventListener('input', validateAndSyncTpStatus);
        if (tpDueDateInput) {
            tpDueDateInput.addEventListener('change', validateAndSyncTpStatus);
            tpDueDateInput.addEventListener('input', validateAndSyncTpStatus);
        }

        if (form) {
            form.addEventListener('submit', function(e) {
                const satisfiedVal = (tpSatisfiedInput.value || '').trim();
                const dueVal = tpDueDateInput ? (tpDueDateInput.value || '').trim() : '';
                if (satisfiedVal && dueVal && satisfiedVal > dueVal) {
                    e.preventDefault();
                    if (errorBox) errorBox.style.display = 'block';
                    tpSatisfiedInput.style.borderColor = '#ef4444';
                    tpSatisfiedInput.focus();
                    return false;
                }
            });
        }

        tpStatusSelect.addEventListener('change', function() {
            if (isSyncing) return;
            isSyncing = true;
            try {
                const dueVal = tpDueDateInput ? (tpDueDateInput.value || '').trim() : '';
                if (tpStatusSelect.value === 'SATISFIED') {
                    if (!tpSatisfiedInput.value) {
                        const today = new Date().toISOString().split('T')[0];
                        if (dueVal && today > dueVal) {
                            // Melewati batas waktu perbaikan
                            if (errorBox) errorBox.style.display = 'block';
                            tpSatisfiedInput.style.borderColor = '#ef4444';
                            tpStatusSelect.value = 'IN_PROGRESS';
                            tpStatusSelect.dispatchEvent(new Event('change', { bubbles: true }));
                            if (submitBtn) {
                                submitBtn.disabled = true;
                                submitBtn.style.opacity = '0.6';
                                submitBtn.style.cursor = 'not-allowed';
                                submitBtn.title = 'Tanggal memenuhi melewati batas waktu perbaikan (KAN U-01 Rev.3)';
                            }
                            return;
                        }
                        tpSatisfiedInput.value = today;
                        tpSatisfiedInput.dispatchEvent(new Event('change', { bubbles: true }));
                        tpSatisfiedInput.dispatchEvent(new Event('input', { bubbles: true }));
                    } else if (dueVal && tpSatisfiedInput.value > dueVal) {
                        if (errorBox) errorBox.style.display = 'block';
                        tpSatisfiedInput.style.borderColor = '#ef4444';
                        tpStatusSelect.value = 'IN_PROGRESS';
                        tpStatusSelect.dispatchEvent(new Event('change', { bubbles: true }));
                        if (submitBtn) {
                            submitBtn.disabled = true;
                            submitBtn.style.opacity = '0.6';
                            submitBtn.style.cursor = 'not-allowed';
                            submitBtn.title = 'Tanggal memenuhi melewati batas waktu perbaikan (KAN U-01 Rev.3)';
                        }
                        return;
                    }
                    if (unifiedStatusInput) {
                        unifiedStatusInput.value = 'COMPLETED';
                    }
                } else {
                    if (tpSatisfiedInput.value) {
                        tpSatisfiedInput.value = '';
                        tpSatisfiedInput.dispatchEvent(new Event('change', { bubbles: true }));
                        tpSatisfiedInput.dispatchEvent(new Event('input', { bubbles: true }));
                    }
                    if (errorBox) errorBox.style.display = 'none';
                    tpSatisfiedInput.style.borderColor = '';
                    if (submitBtn) {
                        submitBtn.disabled = false;
                        submitBtn.style.opacity = '';
                        submitBtn.style.cursor = '';
                        submitBtn.removeAttribute('title');
                    }
                    if (unifiedStatusInput) {
                        if (tpStatusSelect.value === 'NONE') {
                            unifiedStatusInput.value = 'PLANNED';
                        } else if (tpStatusSelect.value === 'UNDER_VERIFICATION') {
                            unifiedStatusInput.value = 'UNDER_VERIFICATION';
                        } else if (tpStatusSelect.value === 'SATISFIED') {
                            unifiedStatusInput.value = 'COMPLETED';
                        } else {
                            unifiedStatusInput.value = 'IN_PROGRESS';
                        }
                    }
                }
            } finally {
                isSyncing = false;
            }
        });

        // Validasi awal saat modal dimuat
        validateAndSyncTpStatus();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTpTrackingAutoSync);
    } else {
        initTpTrackingAutoSync();
    }
})();
</script>
