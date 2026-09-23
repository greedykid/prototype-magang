@php
    $tpBadge = $assessment->tp_sla_badge;
    $defaultDueDate = $assessment->calculateDefaultTpDueDate();
@endphp

<section class="panel" style="margin-top: 24px;" aria-labelledby="tp-tracking-heading">
    <div style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap; margin-bottom: 20px; padding-bottom: 16px; border-bottom: 1px solid var(--line);">
        <div>
            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                <h3 id="tp-tracking-heading" style="margin: 0; font-size: 17px; font-weight: 700; color: var(--text);">
                    Tindakan Perbaikan &amp; Verifikasi (TP &amp; VTP)
                </h3>
                <span class="badge-tp badge-tp-{{ $tpBadge['type'] }}">
                    {{ $tpBadge['label'] }}
                </span>
            </div>
            <p style="margin: 4px 0 0; font-size: 13px; color: var(--muted); line-height: 1.5;">
                Standar KAN: Batas waktu AA 3 bulan, Survailen/PRL/Reakreditasi 2 bulan. Perpanjangan maksimal 1 bulan melalui surat permohonan resmi.
            </p>
        </div>
        @if(auth()->user()?->isAdmin())
            <button type="button" class="button secondary" onclick="window.openModal('modal-tp-tracking')">
                <x-icon name="edit" size="15" />
                <span>Perbarui status TP</span>
            </button>
        @endif
    </div>

    {{-- SLA Metrics Grid --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 20px;">
        <div style="padding: 14px; background: var(--surface-subtle, #f8fafc); border: 1px solid var(--line); border-radius: 8px;">
            <span style="font-size: 12px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: 0.03em; display: block; margin-bottom: 6px;">
                Status Tindakan Perbaikan
            </span>
            <div style="font-size: 14px; font-weight: 600; color: var(--text); margin-bottom: 4px;">
                {{ $assessment->tp_status_label }}
            </div>
            <small style="color: var(--muted); font-size: 12px; display: block;">
                {{ $tpBadge['detail'] }}
            </small>
        </div>

        <div style="padding: 14px; background: var(--surface-subtle, #f8fafc); border: 1px solid var(--line); border-radius: 8px;">
            <span style="font-size: 12px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: 0.03em; display: block; margin-bottom: 6px;">
                Batas Waktu Awal (SLA KAN)
            </span>
            <div style="font-size: 14px; font-weight: 600; color: var(--text); margin-bottom: 4px;">
                @if($assessment->tp_due_date)
                    {{ $assessment->tp_due_date->format('d M Y') }}
                @elseif($defaultDueDate)
                    {{ $defaultDueDate->format('d M Y') }}
                    <span style="font-size: 11px; color: var(--muted); font-weight: normal;">(Otomatis)</span>
                @else
                    Belum ditentukan
                @endif
            </div>
            <small style="color: var(--muted); font-size: 12px; display: block;">
                {{ in_array($assessment->assessment_type, ['Asesmen Awal', 'INITIAL'], true) ? 'Akreditasi Awal: 3 bulan' : 'Kegiatan lainnya: 2 bulan' }}
            </small>
        </div>

        <div style="padding: 14px; background: var(--surface-subtle, #f8fafc); border: 1px solid var(--line); border-radius: 8px;">
            <span style="font-size: 12px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: 0.03em; display: block; margin-bottom: 6px;">
                Permohonan Perpanjangan
            </span>
            @if($assessment->tp_has_extension)
                <div style="margin-bottom: 4px;">
                    <span class="badge-tp badge-tp-warning">+1 Bulan Disetujui</span>
                </div>
                <small style="color: var(--text); font-size: 12px; display: block;">
                    No. Surat: <strong>{{ $assessment->tp_extension_letter_no ?: 'Ada permohonan' }}</strong>
                </small>
                @if($assessment->tp_extension_date)
                    <small style="color: var(--muted); font-size: 11.5px; display: block;">
                        Tgl Surat: {{ $assessment->tp_extension_date->format('d M Y') }}
                    </small>
                @endif
            @else
                <div style="font-size: 14px; font-weight: 600; color: var(--muted); margin-bottom: 4px;">
                    Tidak ada
                </div>
                <small style="color: var(--muted); font-size: 12px; display: block;">
                    Dapat mengajukan penambahan maks. 1 bulan
                </small>
            @endif
        </div>

        <div style="padding: 14px; background: var(--surface-subtle, #f8fafc); border: 1px solid var(--line); border-radius: 8px;">
            <span style="font-size: 12px; font-weight: 600; color: var(--muted); text-transform: uppercase; letter-spacing: 0.03em; display: block; margin-bottom: 6px;">
                Batas Akhir Efektif / Hasil
            </span>
            @if($assessment->tp_status === \App\Models\Assessment::TP_STATUS_SATISFIED)
                <div style="font-size: 14px; font-weight: 600; color: #166534; margin-bottom: 4px;">
                    Dinyatakan Memenuhi
                </div>
                <small style="color: var(--muted); font-size: 12px; display: block;">
                    Tanggal verifikasi: {{ $assessment->tp_satisfied_at ? $assessment->tp_satisfied_at->format('d M Y') : '-' }}
                </small>
            @elseif($assessment->tp_status === \App\Models\Assessment::TP_STATUS_NONE)
                <div style="font-size: 14px; font-weight: 600; color: var(--muted); margin-bottom: 4px;">
                    Nihil Temuan
                </div>
                <small style="color: var(--muted); font-size: 12px; display: block;">
                    Tidak ada ketidaksesuaian saat asesmen
                </small>
            @else
                <div style="font-size: 14px; font-weight: 700; color: {{ $assessment->is_tp_overdue ? '#b91c1c' : ($assessment->days_remaining_tp <= 14 ? '#b45309' : 'var(--text)') }}; margin-bottom: 4px;">
                    {{ $assessment->effective_tp_due_date ? $assessment->effective_tp_due_date->format('d M Y') : '-' }}
                </div>
                <small style="font-size: 12px; font-weight: 600; color: {{ $assessment->is_tp_overdue ? '#b91c1c' : ($assessment->days_remaining_tp <= 14 ? '#b45309' : '#1e40af') }}; display: block;">
                    @if($assessment->is_tp_overdue)
                        Melewati batas {{ abs($assessment->days_remaining_tp ?? 0) }} hari
                    @else
                        Sisa {{ $assessment->days_remaining_tp ?? 0 }} hari kalender
                    @endif
                </small>
            @endif
        </div>
    </div>

    {{-- Notes & Extension Details --}}
    @if($assessment->tp_notes || ($assessment->tp_has_extension && $assessment->tp_extension_notes))
        <div style="display: grid; gap: 12px; padding-top: 12px; border-top: 1px solid var(--line);">
            @if($assessment->tp_notes)
                <div>
                    <strong style="font-size: 13px; color: var(--text); display: block; margin-bottom: 4px;">Catatan Temuan &amp; Tindakan Perbaikan:</strong>
                    <p style="margin: 0; font-size: 13px; color: var(--muted); line-height: 1.5; white-space: pre-line;">{{ $assessment->tp_notes }}</p>
                </div>
            @endif
            @if($assessment->tp_has_extension && $assessment->tp_extension_notes)
                <div>
                    <strong style="font-size: 13px; color: var(--text); display: block; margin-bottom: 4px;">Catatan Surat Permohonan Perpanjangan:</strong>
                    <p style="margin: 0; font-size: 13px; color: var(--muted); line-height: 1.5; white-space: pre-line;">{{ $assessment->tp_extension_notes }}</p>
                </div>
            @endif
        </div>
    @endif
</section>
