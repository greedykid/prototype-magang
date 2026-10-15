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
@endphp

<section class="lpk-form-card" aria-labelledby="tp-tracking-heading">
    <div class="lpk-form-card-header tp-tracking-header">
        <div class="tp-tracking-header-top">
            <div class="tp-tracking-header-left">
                <div class="lpk-card-icon-wrap icon-wrap-green">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 22c5.523 0 10-4.477 10-10S17.523 2 12 2 2 6.477 2 12s4.477 10 10 10z"/>
                        <path d="m9 12 2 2 4-4"/>
                    </svg>
                </div>
                <div class="tp-tracking-title-wrap">
                    <div class="tp-tracking-title-row">
                        <h2 id="tp-tracking-heading">
                            Tindakan Perbaikan &amp; Verifikasi (TP &amp; VTP)
                        </h2>
                        <span class="badge-tp badge-tp-{{ $tpBadge['type'] }}">
                            {{ $tpBadge['label'] }}
                        </span>
                    </div>
                </div>
            </div>
            @if(! $assessment->lpk || $assessment->lpk->canManage(auth()->user()))
                <button type="button" class="button secondary tp-manage-btn" onclick="window.openModal('modal-tp-tracking')" aria-controls="modal-tp-tracking">
                    <x-icon name="edit" size="14" />
                    <span>Kelola Status TP</span>
                </button>
            @endif
        </div>
        <p class="tp-tracking-desc">
            Standar KAN: Batas waktu AA 3 bulan, Survailen/PRL/Re-Akreditasi 2 bulan. Perpanjangan maksimal 1 bulan bersyarat ada progres perbaikan nyata.
        </p>
    </div>

    @php
        $isSatisfied = $assessment->tp_status === \App\Models\Assessment::TP_STATUS_SATISFIED || !empty($assessment->tp_satisfied_at) || $assessment->isPastSurveillanceForActiveLpk();
        $isNone = $assessment->tp_status === \App\Models\Assessment::TP_STATUS_NONE;
        $isCompletedWithoutTp = $isNone && ($assessment->status === 'COMPLETED' || !empty($assessment->sk_number));
    @endphp

    <div class="tp-metrics-grid">
        @if($isSatisfied)
            {{-- Kartu 1: Status & Evaluasi Temuan --}}
            <div class="tp-metric-card">
                <div>
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 8px;">
                        <span class="tp-metric-card-kicker">Status Tindakan Perbaikan</span>
                        <span class="badge-tp badge-tp-success" style="font-size: 11px;">
                            Verifikasi Tuntas
                        </span>
                    </div>
                    <div class="tp-metric-card-title" style="color: var(--green, #16a34a);">
                        Dinyatakan Memenuhi (Selesai)
                    </div>
                    <p class="tp-metric-card-desc">
                        {{ $tpBadge['detail'] ?: 'Seluruh perbaikan ketidaksesuaian telah diverifikasi tuntas oleh asesor dan disetujui.' }}
                    </p>
                </div>
                @if($assessment->end_at)
                    <div class="tp-metric-card-footer">
                        Pelaksanaan asesmen: <strong>{{ $assessment->end_at->format('d M Y') }}</strong>
                    </div>
                @endif
            </div>

            {{-- Kartu 2: Waktu Penyelesaian & Batas SLA KAN --}}
            <div class="tp-metric-card">
                <div>
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 8px;">
                        <span class="tp-metric-card-kicker">Waktu Penyelesaian (TP)</span>
                        @if($assessment->tp_has_extension)
                            <span class="badge-tp badge-tp-warning" style="font-size: 10.5px; padding: 2px 8px;">
                                +{{ $extMonths }} Bulan Disetujui
                            </span>
                        @endif
                    </div>
                    <div class="tp-metric-card-title">
                        {{ $assessment->tp_satisfied_at ? $assessment->tp_satisfied_at->format('d M Y') : ($assessment->sk_date ? $assessment->sk_date->format('d M Y') : 'Selesai') }}
                    </div>
                    @if($assessment->end_at && $assessment->tp_satisfied_at)
                        @php
                            $durationDays = (int) round(abs($assessment->end_at->diffInDays($assessment->tp_satisfied_at)));
                        @endphp
                        <div style="font-size: 12.5px; font-weight: 600; color: var(--primary, #0284c7); margin-bottom: 4px;">
                            Durasi:
                            @if($durationDays === 0)
                                Hari yang sama (Tepat Waktu)
                            @elseif($durationDays === 1)
                                1 Hari Kalender (Tepat Waktu)
                            @else
                                {{ $durationDays }} Hari Kalender (Tepat Waktu)
                            @endif
                        </div>
                    @endif
                </div>
                <div class="tp-metric-card-footer">
                    @if($assessment->tp_has_extension)
                        <span>Surat LPK: <strong>{{ $assessment->tp_extension_letter_no ?: 'Ada permohonan resmi' }}</strong></span>
                    @else
                        <span>Standar KAN: <strong>Sesuai Regulasi KAN U-01</strong></span>
                    @endif
                </div>
            </div>

        @elseif($isCompletedWithoutTp)
            {{-- Kartu 1: Status Temuan Asesmen --}}
            <div class="tp-metric-card">
                <div>
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 8px;">
                        <span class="tp-metric-card-kicker">Status Temuan Asesmen</span>
                        <span class="badge-tp badge-tp-success" style="font-size: 11px;">
                            Nol Temuan
                        </span>
                    </div>
                    <div class="tp-metric-card-title" style="color: var(--green, #16a34a);">
                        Tanpa Temuan (Selesai)
                    </div>
                    <p class="tp-metric-card-desc">
                        Seluruh kriteria asesmen dinyatakan memenuhi persyaratan KAN tanpa memerlukan tindakan perbaikan.
                    </p>
                </div>
                @if($assessment->end_at)
                    <div class="tp-metric-card-footer">
                        Pelaksanaan asesmen: <strong>{{ $assessment->end_at->format('d M Y') }}</strong>
                    </div>
                @endif
            </div>

            {{-- Kartu 2: Hasil Evaluasi Asesmen --}}
            <div class="tp-metric-card">
                <div>
                    <span class="tp-metric-card-kicker">Hasil Evaluasi Asesmen</span>
                    <div class="tp-metric-card-title">
                        {{ $assessment->sk_date ? $assessment->sk_date->format('d M Y') : ($assessment->end_at ? $assessment->end_at->format('d M Y') : 'Selesai') }}
                    </div>
                    <div style="font-size: 12.5px; font-weight: 600; color: var(--green, #16a34a); margin-bottom: 4px;">
                        Tidak ada kewajiban unggah dokumen TP
                    </div>
                </div>
                <div class="tp-metric-card-footer">
                    <span>Rekomendasi: <strong>Langsung Penerbitan SK</strong></span>
                </div>
            </div>

        @elseif($isNone)
            {{-- Kartu 1: Status Tindakan Perbaikan --}}
            <div class="tp-metric-card">
                <div>
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 8px;">
                        <span class="tp-metric-card-kicker">Status Tindakan Perbaikan</span>
                        <span class="badge-tp badge-tp-neutral" style="font-size: 11px;">
                            Belum Dimulai
                        </span>
                    </div>
                    <div class="tp-metric-card-title" style="color: var(--muted);">
                        Belum Dimulai
                    </div>
                    <p class="tp-metric-card-desc">
                        @if(in_array($assessment->status, ['PLANNED', 'SCHEDULED'], true))
                            Batas waktu tindakan perbaikan akan dihitung secara otomatis setelah pelaksanaan asesmen lapangan selesai.
                        @else
                            Belum ada penetapan batas waktu tindakan perbaikan untuk agenda ini.
                        @endif
                    </p>
                </div>
                @if($assessment->end_at)
                    <div class="tp-metric-card-footer">
                        Rencana asesmen: <strong>{{ $assessment->end_at->format('d M Y') }}</strong>
                    </div>
                @endif
            </div>

            {{-- Kartu 2: Batas Waktu Tindakan Perbaikan --}}
            <div class="tp-metric-card">
                <div>
                    <span class="tp-metric-card-kicker">Batas Waktu Tindakan Perbaikan</span>
                    <div class="tp-metric-card-title" style="color: var(--muted);">
                        Belum Ditentukan
                    </div>
                    <div style="font-size: 12.5px; font-weight: 500; color: var(--muted); margin-bottom: 4px;">
                        Sesuai regulasi KAN U-01
                    </div>
                </div>
                <div class="tp-metric-card-footer">
                    <span>Dihitung setelah closing meeting asesmen</span>
                </div>
            </div>

        @else
            {{-- Kartu 1: Status & Peringatan Tindakan Perbaikan --}}
            <div class="tp-metric-card {{ $assessment->is_tp_overdue ? 'tp-card-alert' : '' }}">
                <div>
                    <span class="tp-metric-card-kicker">
                        Status Saat Ini
                    </span>
                    <div class="tp-metric-card-title {{ $assessment->is_tp_overdue ? 'text-danger' : '' }}">
                        {{ $assessment->tp_status_label }}
                    </div>
                    <p class="tp-metric-card-desc">
                        {{ $tpBadge['detail'] }}
                    </p>
                </div>
                @if($assessment->end_at)
                    <div class="tp-metric-card-footer">
                        Pelaksanaan asesmen: <strong>{{ $assessment->end_at->format('d M Y') }}</strong>
                    </div>
                @endif
            </div>

            {{-- Kartu 2: Batas Waktu & Sisa Hari --}}
            <div class="tp-metric-card {{ $assessment->is_tp_overdue ? 'tp-card-alert' : '' }}">
                <div>
                    <div style="display: flex; align-items: center; justify-content: space-between; gap: 8px; margin-bottom: 8px;">
                        <span class="tp-metric-card-kicker">
                            Batas Waktu Penyelesaian
                        </span>
                        @if($assessment->tp_has_extension)
                            <span class="badge-tp badge-tp-warning" style="font-size: 10.5px; padding: 2px 8px;">
                                +{{ $extMonths }} Bulan Disetujui
                            </span>
                        @endif
                    </div>

                    @if($assessment->tp_has_extension)
                        <div style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 8px;">
                            <div style="display: flex; align-items: center; justify-content: space-between; font-size: 12px; color: var(--muted);">
                                <span>Batas Waktu Awal:</span>
                                <strong style="color: var(--ink); text-decoration: line-through; text-decoration-color: var(--muted);">
                                    {{ $initialDueDate?->format('d M Y') ?: '-' }}
                                </strong>
                            </div>

                            <div style="background: rgba(225, 29, 72, 0.05); padding: 9px 12px; border-radius: var(--radius-md, 10px); border: 1px solid rgba(225, 29, 72, 0.25);">
                                <div style="font-size: 10.5px; font-weight: 700; color: var(--maroon, #e11d48); text-transform: uppercase; letter-spacing: 0.03em; margin-bottom: 2px;">
                                    Batas Perpanjangan (+{{ $extMonths }} Bulan Efektif)
                                </div>
                                <div style="font-size: 18px; font-weight: 800; color: {{ $assessment->is_tp_overdue ? '#b91c1c' : ($assessment->days_remaining_tp <= 14 ? '#b45309' : 'var(--ink)') }}; letter-spacing: -0.01em;">
                                    {{ $effectiveDueDate ? $effectiveDueDate->format('d M Y') : '-' }}
                                </div>
                            </div>
                        </div>
                    @else
                        <div style="font-size: 18px; font-weight: 800; color: {{ $assessment->is_tp_overdue ? '#b91c1c' : ($assessment->days_remaining_tp <= 14 ? '#b45309' : 'var(--ink)') }}; margin-bottom: 6px; letter-spacing: -0.01em;">
                            {{ $effectiveDueDate ? $effectiveDueDate->format('d M Y') : '-' }}
                        </div>
                    @endif

                    <div style="font-size: 12.5px; font-weight: 600; color: {{ $assessment->is_tp_overdue ? '#b91c1c' : ($assessment->days_remaining_tp <= 14 ? '#b45309' : 'var(--primary, #1e40af)') }};">
                        @if($assessment->is_tp_overdue)
                            Melewati batas {{ abs($assessment->days_remaining_tp ?? 0) }} hari (Status Dibekukan)
                        @elseif($assessment->days_remaining_tp !== null && $assessment->days_remaining_tp <= 14)
                            Sisa {{ $assessment->days_remaining_tp }} hari (Mendekati Jatuh Tempo)
                        @elseif($assessment->days_remaining_tp !== null)
                            Sisa {{ $assessment->days_remaining_tp }} hari kalender
                        @endif
                    </div>
                </div>

                <div class="tp-metric-card-footer">
                    @if($assessment->tp_has_extension)
                        <span style="display: block;">
                            Surat LPK: <strong>{{ $assessment->tp_extension_letter_no ?: 'Ada permohonan resmi' }}</strong>
                            @if($assessment->tp_extension_date)
                                ({{ $assessment->tp_extension_date->format('d M Y') }})
                            @endif
                        </span>
                    @else
                        <span>Standar KAN: <strong>{{ in_array($assessment->assessment_type, ['Asesmen Awal', 'INITIAL'], true) ? '3 bulan sejak asesmen' : '2 bulan sejak asesmen' }}</strong></span>
                    @endif
                </div>
            </div>
        @endif
    </div>

    {{-- SK KAN Hasil Asesmen / Kelanjutan Akreditasi --}}
    @if($assessment->sk_number)
        <div class="tp-sk-banner">
            <div class="tp-sk-banner-left">
                <div class="icon-wrap-emerald" style="width: 40px; height: 40px; border-radius: var(--radius-md, 10px); display: flex; align-items: center; justify-content: center; flex-shrink: 0;" aria-hidden="true">
                    <svg style="width: 22px; height: 22px;" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M9 12l2 2 4-4m6 2a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                </div>
                <div style="min-width: 0;">
                    <span style="font-size: 11px; font-weight: 700; color: var(--green, #15803d); text-transform: uppercase; letter-spacing: 0.04em; display: block; margin-bottom: 2px;">
                        Surat Keputusan (SK) Hasil Asesmen Telah Terbit
                    </span>
                    <strong style="font-size: 15px; color: var(--ink); letter-spacing: 0.01em; word-break: break-word;">{{ $assessment->sk_number }}</strong>
                </div>
            </div>
            @if($assessment->sk_date)
                <div class="tp-sk-banner-right">
                    <span style="font-size: 11.5px; color: var(--muted); display: block;">Tanggal Penerbitan SK</span>
                    <strong style="font-size: 14px; color: var(--ink);">{{ $assessment->sk_date->format('d M Y') }}</strong>
                    @if($assessment->sk_lead_time_label)
                        <span style="display: block; font-size: 11.5px; color: var(--green, #166534); font-weight: 600; margin-top: 3px;" title="Durasi sejak akhir pelaksanaan asesmen hingga tanggal SK">
                            Rentang: {{ $assessment->sk_lead_time_label }}
                        </span>
                    @endif
                </div>
            @endif
        </div>
    @endif

    {{-- Notes & Extension Details --}}
    @if($assessment->tp_notes || ($assessment->tp_has_extension && $assessment->tp_extension_notes))
        <div style="display: grid; gap: 12px; padding-top: 14px; border-top: 1px solid var(--line);">
            @if($assessment->tp_notes)
                <div>
                    <strong style="font-size: 13px; color: var(--ink); display: block; margin-bottom: 4px;">Catatan Temuan &amp; Tindakan Perbaikan:</strong>
                    <p style="margin: 0; font-size: 13px; color: var(--muted); line-height: 1.5; white-space: pre-line;">{{ $assessment->tp_notes }}</p>
                </div>
            @endif
            @if($assessment->tp_has_extension && $assessment->tp_extension_notes)
                <div>
                    <strong style="font-size: 13px; color: var(--ink); display: block; margin-bottom: 4px;">Catatan Surat Permohonan Perpanjangan:</strong>
                    <p style="margin: 0; font-size: 13px; color: var(--muted); line-height: 1.5; white-space: pre-line;">{{ $assessment->tp_extension_notes }}</p>
                </div>
            @endif
        </div>
    @endif
</section>
