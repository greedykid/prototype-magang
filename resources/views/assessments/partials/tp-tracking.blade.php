@php
    $tpBadge = $assessment->tp_sla_badge;
    $defaultDueDate = $assessment->calculateDefaultTpDueDate();
    $effectiveDueDate = $assessment->effective_tp_due_date;
    $extMonths = $assessment->tp_extension_months ?: 1;
    if ($assessment->tp_has_extension) {
        if ($assessment->tp_due_date && $effectiveDueDate && $assessment->tp_due_date->lt($effectiveDueDate)) {
            $initialDueDate = $assessment->tp_due_date;
        } elseif ($defaultDueDate && $effectiveDueDate && $defaultDueDate->lt($effectiveDueDate)) {
            $initialDueDate = $defaultDueDate;
        } elseif ($effectiveDueDate) {
            $initialDueDate = $effectiveDueDate->copy()->subMonths($extMonths);
        } else {
            $initialDueDate = $assessment->tp_due_date ?: $defaultDueDate;
        }
    } else {
        $initialDueDate = $assessment->tp_due_date ?: $defaultDueDate;
    }

    $isFieldDone = ($assessment->end_at && now()->gte($assessment->end_at))
        || !empty($assessment->report_date)
        || in_array($assessment->tp_status, [\App\Models\Assessment::TP_STATUS_IN_PROGRESS, \App\Models\Assessment::TP_STATUS_UNDER_VERIFICATION, \App\Models\Assessment::TP_STATUS_SATISFIED], true)
        || $assessment->isPastSurveillanceForActiveLpk()
        || $assessment->status === 'COMPLETED';

    $isTpSatisfied = $assessment->tp_status === \App\Models\Assessment::TP_STATUS_SATISFIED || !empty($assessment->tp_satisfied_at) || $assessment->isPastSurveillanceForActiveLpk();
    $isTpNone = $assessment->tp_status === \App\Models\Assessment::TP_STATUS_NONE;
    $isCompletedWithoutTp = $isTpNone && ($assessment->status === 'COMPLETED' || !empty($assessment->sk_number));
    $isTpDone = $isTpSatisfied || $isCompletedWithoutTp;
    $isTpOverdue = $assessment->is_tp_overdue;
    $isTpWarning = !$isTpDone && ($isTpOverdue || ($assessment->days_remaining_tp !== null && $assessment->days_remaining_tp <= 14));

    $isEhaDone = (in_array($assessment->eha_status, [\App\Models\Assessment::EHA_STATUS_DIREKOMENDASIKAN, 'SUDAH_EHA'], true)
        || (!empty($assessment->eha_date) && !in_array($assessment->eha_status, [\App\Models\Assessment::EHA_STATUS_BELUM, \App\Models\Assessment::EHA_STATUS_PERLU_VERIFIKASI, \App\Models\Assessment::EHA_STATUS_CATATAN_KHUSUS], true)))
        && ($assessment->eha_status !== \App\Models\Assessment::EHA_STATUS_CATATAN_KHUSUS)
        && ($assessment->eha_status !== \App\Models\Assessment::EHA_STATUS_PERLU_VERIFIKASI);
    $isEhaNeedsVerif = $assessment->is_eha_needs_verification || $assessment->is_eha_special_notes;
    $isEhaWaiting = $assessment->is_waiting_eha;

    $isSkDone = !empty($assessment->sk_number);
    $isSkWaiting = $assessment->is_waiting_sk;

    $canManage = !isset($owner) && (! $assessment->lpk || $assessment->lpk->canManage(auth()->user()));
    $canShowEha = $canManage && ($isTpDone || $isEhaDone || !empty($assessment->eha_date) || (!empty($assessment->eha_status) && $assessment->eha_status !== \App\Models\Assessment::EHA_STATUS_BELUM));
    $canShowSk = $canManage && ($isEhaDone || $isSkDone || !empty($assessment->sk_number));
@endphp

{{-- KARTU 3: Tindakan Perbaikan & Verifikasi (TP & VTP) Standar KAN --}}
<div class="lpk-form-card" aria-labelledby="tp-card-heading">
    <div class="lpk-form-card-header">
        <div class="lpk-card-icon-wrap icon-wrap-green">
            <x-icon name="check-circle" size="20" />
        </div>
        <div class="lpk-card-header-text">
            <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; flex-wrap: wrap;">
                <h2 id="tp-card-heading">Tindakan Perbaikan &amp; Verifikasi (TP)</h2>
                @if($isTpSatisfied)
                    <span class="badge-tp badge-tp-satisfied">Verifikasi Tuntas</span>
                @elseif($isCompletedWithoutTp)
                    <span class="badge-tp badge-tp-satisfied">Nol Temuan</span>
                @elseif($isTpNone)
                    <span class="badge-tp badge-tp-neutral">Belum Dimulai</span>
                @else
                    <span class="badge-tp badge-tp-{{ $tpBadge['type'] }}">{{ $tpBadge['label'] }}</span>
                @endif
            </div>
            <p>Ketentuan KAN U-01: AA 3 bulan, Survailen 2 bulan (+1 bulan bersyarat bukti progres nyata).</p>
        </div>
    </div>

    <div style="display: flex; flex-direction: column; gap: 12px;">
        @if($isTpSatisfied)
            <div class="assessment-tp-status-panel is-satisfied">
                <div class="tp-status-panel-head">
                    <span class="tp-status-panel-kicker">Status Verifikasi</span>
                    <span class="badge-tp badge-tp-success" style="font-size: 11px;">Sesuai Ketentuan KAN</span>
                </div>
                <div class="tp-status-panel-title" style="color: var(--green, #16a34a);">
                    Dinyatakan Memenuhi (Selesai)
                </div>
                <p class="tp-status-panel-desc">
                    Seluruh perbaikan ketidaksesuaian telah diverifikasi tuntas oleh tim asesor dan disetujui.
                </p>
                <div class="tp-status-panel-meta">
                    <span>Disetujui: <strong>{{ $assessment->tp_satisfied_at ? $assessment->tp_satisfied_at->translatedFormat('d M Y') : 'Selesai' }}</strong></span>
                    @if($assessment->end_at && $assessment->tp_satisfied_at)
                        @php
                            $durationDays = (int) round(abs($assessment->end_at->diffInDays($assessment->tp_satisfied_at)));
                        @endphp
                        <span style="font-weight: 600; color: var(--green, #16a34a);">
                            Durasi: {{ $durationDays === 0 ? 'Hari yang sama' : $durationDays . ' Hari Kalender' }}
                        </span>
                    @endif
                </div>
            </div>

        @elseif($isCompletedWithoutTp)
            <div class="assessment-tp-status-panel is-satisfied">
                <div class="tp-status-panel-head">
                    <span class="tp-status-panel-kicker">Status Temuan</span>
                    <span class="badge-tp badge-tp-success" style="font-size: 11px;">Nol Temuan</span>
                </div>
                <div class="tp-status-panel-title" style="color: var(--green, #16a34a);">
                    Tanpa Temuan (Selesai)
                </div>
                <p class="tp-status-panel-desc">
                    Seluruh kriteria asesmen dinyatakan memenuhi persyaratan KAN tanpa temuan ketidaksesuaian.
                </p>
                <div class="tp-status-panel-meta">
                    <span style="color: var(--muted);">Tidak ada kewajiban penyerahan dokumen perbaikan TP</span>
                    <span class="badge-tp badge-tp-neutral" style="font-size: 11px;">Langsung ke Sidang SK</span>
                </div>
            </div>

        @elseif($isTpNone)
            <div class="assessment-tp-status-panel">
                <div class="tp-status-panel-head">
                    <span class="tp-status-panel-kicker">Status Saat Ini</span>
                    <span class="badge-tp badge-tp-neutral" style="font-size: 11px;">Menunggu Kunjungan</span>
                </div>
                <div class="tp-status-panel-title">
                    Belum Dimulai
                </div>
                <p class="tp-status-panel-desc">
                    Batas waktu perbaikan akan dihitung otomatis sejak pelaksanaan asesmen lapangan selesai (Maksimal {{ in_array($assessment->assessment_type, ['Asesmen Awal', 'INITIAL'], true) ? '3 bulan' : '2 bulan' }} kalender).
                </p>
                <div class="tp-status-panel-meta">
                    <span>Perkiraan Tenggat: <strong>{{ $defaultDueDate ? $defaultDueDate->translatedFormat('d M Y') : 'Dihitung setelah kunjungan' }}</strong></span>
                    <span style="color: var(--muted); font-size: 11.5px;">Regulasi KAN U-01</span>
                </div>
            </div>

        @else
            {{-- In Progress / Overdue / Warning --}}
            <div class="assessment-tp-status-panel {{ $isTpOverdue ? 'is-overdue' : ($isTpWarning ? 'is-warning' : '') }}">
                <div class="tp-status-panel-head">
                    <span class="tp-status-panel-kicker">Status Saat Ini</span>
                    @if($assessment->tp_has_extension)
                        <span class="badge-tp badge-tp-warning" style="font-size: 10.5px;">+{{ $extMonths }} Bulan Perpanjangan</span>
                    @endif
                </div>

                <div class="tp-status-panel-title" style="color: {{ $isTpOverdue ? '#b91c1c' : 'var(--ink)' }};">
                    {{ $assessment->tp_status_label }}
                </div>

                <div style="font-size: 12.5px; font-weight: 600; color: {{ $isTpOverdue ? '#b91c1c' : ($assessment->days_remaining_tp <= 14 ? '#b45309' : 'var(--primary, #1972f5)') }};">
                    @if($isTpOverdue)
                        &bull; Melewati batas waktu {{ abs($assessment->days_remaining_tp ?? 0) }} hari kalender (Status Dibekukan)
                    @elseif($assessment->days_remaining_tp !== null && $assessment->days_remaining_tp <= 14)
                        &bull; Sisa {{ $assessment->days_remaining_tp }} hari kalender (Mendekati Jatuh Tempo)
                    @elseif($assessment->days_remaining_tp !== null)
                        &bull; Sisa {{ $assessment->days_remaining_tp }} hari kalender
                    @endif
                </div>

                <div class="tp-status-panel-meta">
                    <span>Tenggat Waktu: <strong style="color: {{ $isTpOverdue ? '#b91c1c' : 'var(--ink)' }};">{{ $effectiveDueDate ? $effectiveDueDate->translatedFormat('d M Y') : '-' }}</strong></span>
                    @if($assessment->tp_has_extension && $assessment->tp_extension_letter_no)
                        <span style="font-size: 11.5px; color: var(--muted);">Surat: <strong>{{ $assessment->tp_extension_letter_no }}</strong></span>
                    @endif
                </div>
            </div>
        @endif

        @if($assessment->tp_notes)
            <div style="font-size: 12px; color: var(--muted); padding-top: 6px; border-top: 1px dashed var(--line); line-height: 1.45;">
                <strong style="color: var(--ink);">Catatan Temuan:</strong> {{ $assessment->tp_notes }}
            </div>
        @endif

        @if($canManage && $isFieldDone)
            <div class="compliance-action-row" style="margin-top: 2px;">
                <button type="button" class="button {{ $isTpDone ? 'secondary' : 'primary' }} button-sm" onclick="window.openModal('modal-tp-tracking', 'tp')" title="Buka formulir verifikasi perbaikan temuan asesmen">
                    <x-icon name="check-circle" size="13" />
                    <span>{{ $isTpDone ? 'Perbarui Verifikasi TP' : 'Verifikasi Tindak Lanjut TP' }}</span>
                </button>
            </div>
        @endif
    </div>
</div>

{{-- KARTU 4: Laporan Asesmen, Rapat Panitia Teknis & SK KAN --}}
<div class="lpk-form-card" aria-labelledby="milestone-card-heading">
    <div class="lpk-form-card-header">
        <div class="lpk-card-icon-wrap icon-wrap-rose">
            <x-icon name="file-text" size="20" />
        </div>
        <div class="lpk-card-header-text">
            <h2 id="milestone-card-heading">Laporan, Hasil Sidang &amp; SK KAN</h2>
            <p>Pelaporan tim asesor, rekomendasi Rapat Panitia Teknis, dan penetapan SK akreditasi.</p>
        </div>
    </div>

    <div class="lpk-meta-grid">
        <div class="lpk-meta-item">
            <span class="lpk-meta-label">Laporan Asesmen Lapangan</span>
            <div class="lpk-meta-value">
                @if($assessment->report_date)
                    <div style="display: flex; align-items: center; gap: 6px;">
                        <x-icon name="file-text" size="14" style="color: var(--green);" />
                        <strong style="color: var(--ink);">{{ $assessment->report_date->translatedFormat('d M Y') }}</strong>
                    </div>
                @else
                    <span style="color: var(--muted);">Belum diterbitkan</span>
                @endif
            </div>
        </div>

        <div class="lpk-meta-item">
            <span class="lpk-meta-label">Rapat Panitia Teknis</span>
            <div class="lpk-meta-value">
                @if($isEhaDone)
                    <div style="display: flex; flex-direction: column; gap: 3px;">
                        <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                            <span class="badge-tp badge-tp-satisfied">{{ $assessment->eha_status_label ?: 'Direkomendasikan' }}</span>
                            @if($assessment->eha_date)
                                <span style="color: var(--muted); font-size: 11.5px;">({{ $assessment->eha_date->translatedFormat('d M Y') }})</span>
                            @endif
                        </div>
                        @if($assessment->eha_notes)
                            <div style="font-size: 11.5px; color: var(--muted); margin-top: 2px;">{{ $assessment->eha_notes }}</div>
                        @endif
                    </div>
                @elseif($isEhaNeedsVerif)
                    <div style="display: flex; flex-direction: column; gap: 3px;">
                        <span class="badge-tp badge-tp-warning">{{ $assessment->eha_status_label }}</span>
                        @if($assessment->eha_notes)
                            <div style="font-size: 11.5px; color: var(--muted); margin-top: 2px;">{{ $assessment->eha_notes }}</div>
                        @endif
                    </div>
                @elseif($isEhaWaiting)
                    <div style="display: flex; flex-direction: column; gap: 2px;">
                        <span class="badge-tp badge-tp-warning" style="font-size: 11px;">Menunggu Rapat Panitia Teknis</span>
                        <span style="font-size: 11.5px; color: var(--muted);">(TP telah tuntas diverifikasi)</span>
                    </div>
                @else
                    <span style="color: var(--muted);">Belum dilaksanakan</span>
                @endif
            </div>
        </div>

        <div class="lpk-meta-item full-width">
            <span class="lpk-meta-label">Surat Keputusan (SK) Akreditasi</span>
            <div class="lpk-meta-value">
                @if($assessment->sk_number || $assessment->sk_date)
                    <div style="display: flex; flex-direction: column; gap: 4px;">
                        <div style="display: flex; align-items: center; flex-wrap: wrap; gap: 8px;">
                            @if($assessment->sk_number)
                                <strong style="color: var(--green); font-size: 14px;">{{ $assessment->sk_number }}</strong>
                            @endif
                            @if($assessment->sk_date)
                                <span style="color: var(--muted); font-size: 12.5px;">(Terbit: {{ $assessment->sk_date->translatedFormat('d M Y') }})</span>
                            @endif
                        </div>
                        @if($assessment->sk_lead_time_label)
                            <span class="badge-tp badge-tp-neutral" style="font-size: 11px; align-self: flex-start;" title="Rentang waktu pelaksanaan asesmen lapangan hingga terbit SK KAN">
                                Rentang Proses: {{ $assessment->sk_lead_time_label }}
                            </span>
                        @endif
                    </div>
                @elseif($isSkWaiting)
                    <div style="display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                        <span class="badge-tp badge-tp-warning" style="font-size: 11px;">Menunggu Penetapan SK KAN</span>
                        <span style="font-size: 11.5px; color: var(--muted);">(Rekomendasi sidang panitia teknis telah selesai)</span>
                    </div>
                @else
                    <span style="color: var(--muted);">Belum diterbitkan</span>
                @endif
            </div>
        </div>
    </div>

    @if($canShowEha || $canShowSk)
        <div class="compliance-action-row" style="margin-top: 14px; padding-top: 12px; border-top: 1px solid var(--line);">
            @if($canShowEha)
                <button type="button" class="button {{ $isEhaDone ? 'secondary' : 'primary' }} button-sm" onclick="window.openModal('modal-tp-tracking', 'eha')" title="Catat atau perbarui hasil keputusan rapat panitia teknis">
                    <x-icon name="file-text" size="13" />
                    <span>{{ $isEhaDone ? 'Perbarui Rapat Panitia Teknis' : 'Catat Rekomendasi Panitia Teknis' }}</span>
                </button>
            @endif
            @if($canShowSk)
                <button type="button" class="button {{ $isSkDone ? 'secondary' : 'primary' }} button-sm" onclick="window.openModal('modal-tp-tracking', 'sk')" title="Input atau perbarui nomor dan tanggal SK KAN resmi">
                    <x-icon name="accreditations" size="13" />
                    <span>{{ $isSkDone ? 'Perbarui SK KAN' : 'Input SK KAN Resmi' }}</span>
                </button>
            @endif
        </div>
    @endif
</div>
