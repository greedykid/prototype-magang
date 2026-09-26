@extends('layouts.app')

@section('title', 'Asesmen | SIMASADI')

@section('content')
@php
    $startAtVal = old('start_at', $assessment->start_at?->format('Y-m-d\TH:i') ?: request('start_at'));
    $endAtVal = old('end_at', $assessment->end_at?->format('Y-m-d\TH:i') ?: request('end_at'));
    $startCarbon = $startAtVal ? \Illuminate\Support\Carbon::parse($startAtVal) : null;
    $endCarbon = $endAtVal ? \Illuminate\Support\Carbon::parse($endAtVal) : null;
    $isOverdueWithoutAction = $endCarbon
        && now()->gt($endCarbon)
        && empty($assessment->sk_number)
        && ($assessment->tp_status === \App\Models\Assessment::TP_STATUS_NONE || empty($assessment->tp_status))
        && empty($assessment->report_date)
        && empty($assessment->eha_date);

    $tpDueDateVal = old('tp_due_date', $assessment->tp_due_date?->format('Y-m-d') ?: ($isOverdueWithoutAction ? null : ($assessment->calculateDefaultTpDueDate()?->format('Y-m-d') ?: null)));
    $tpDueDateCarbon = $tpDueDateVal ? \Illuminate\Support\Carbon::parse($tpDueDateVal) : null;
    $tpHasExtVal = (bool) old('tp_has_extension', $assessment->tp_has_extension);
    $tpExtMonthsVal = (int) old('tp_extension_months', $assessment->tp_extension_months ?: 1);
    $tpSatisfiedVal = old('tp_satisfied_at', $assessment->tp_satisfied_at?->format('Y-m-d'));
    $tpSatisfiedCarbon = $tpSatisfiedVal ? \Illuminate\Support\Carbon::parse($tpSatisfiedVal) : null;
    $typeVal = old('assessment_type', $assessment->assessment_type);

    $initialComputedStatus = \App\Models\Assessment::determineStatusFromDates(
        $startCarbon,
        $endCarbon,
        $assessment->status,
        old('tp_status', $assessment->tp_status),
        old('sk_number', $assessment->sk_number),
        $assessment->report_date,
        $assessment->eha_date,
        $tpDueDateCarbon,
        $tpHasExtVal,
        $tpExtMonthsVal,
        $tpSatisfiedCarbon,
        $typeVal
    );
    $statusLabels = [
        'PLANNED' => 'Direncanakan',
        'SCHEDULED' => 'Terjadwal',
        'IN_PROGRESS' => 'Sedang Berlangsung',
        'SUSPENDED' => 'Dibekukan',
        'COMPLETED' => 'Selesai',
        'CANCELLED' => 'Dibatalkan',
    ];

    $statusDescriptions = [
        'PLANNED' => $isOverdueWithoutAction
            ? 'Otomatis: Tanggal pelaksanaan telah lewat namun belum ada tindakan/pelaporan asesmen (Lewat Jadwal).'
            : 'Otomatis: Agenda asesmen direncanakan.',
        'SCHEDULED' => 'Otomatis: Tanggal mulai di masa mendatang.',
        'IN_PROGRESS' => 'Otomatis: Saat ini dalam periode pelaksanaan atau tindak lanjut asesmen.',
        'SUSPENDED' => 'Otomatis: Melewati batas waktu awal (SLA) belum dinyatakan memenuhi sehingga status dibekukan.',
        'COMPLETED' => 'Otomatis: Tindakan perbaikan memenuhi atau SK KAN telah terbit.',
        'CANCELLED' => 'Asesmen dibatalkan.',
    ];
    $initialComputedDesc = $statusDescriptions[$initialComputedStatus] ?? 'Otomatis ditentukan dari tanggal pelaksanaan & milestone.';

    // Status Tindakan Perbaikan (TP & VTP) Otomatis:
    // Selama tanggal dinyatakan memenuhi belum diinput, statusnya sedang berlangsung.
    // Jika melewati batas waktu awal (SLA) belum ada yang memenuhi, statusnya dibekukan.
    // Jika tanggal dinyatakan memenuhi telah diinput, statusnya memenuhi (selesai).
    $effectiveDueDateCarbon = $tpDueDateCarbon ?: \App\Models\Assessment::calculateDefaultDueDateForType($typeVal, $endCarbon ?: $startCarbon);
    if ($effectiveDueDateCarbon && $tpHasExtVal) {
        $effectiveDueDateCarbon = $effectiveDueDateCarbon->copy()->addMonths(min(1, max(1, $tpExtMonthsVal)));
    }
    $isSlaPassed = $effectiveDueDateCarbon && now()->startOfDay()->gt($effectiveDueDateCarbon->copy()->startOfDay());
    $isTpSatisfied = ! empty($tpSatisfiedCarbon) || old('tp_status', $assessment->tp_status) === \App\Models\Assessment::TP_STATUS_SATISFIED;

    if ($isTpSatisfied) {
        $initialComputedTpStatus = 'SATISFIED';
        $initialComputedTpLabel = 'Dinyatakan Memenuhi (Selesai)';
        $initialComputedTpBadgeClass = 'status-completed';
        $initialComputedTpDesc = 'Otomatis: Tindakan perbaikan telah dinyatakan memenuhi.';
    } elseif ($isOverdueWithoutAction && empty($assessment->tp_due_date)) {
        $initialComputedTpStatus = 'NONE';
        $initialComputedTpLabel = 'Menunggu Pelaksanaan';
        $initialComputedTpBadgeClass = 'status-planned';
        $initialComputedTpDesc = 'Otomatis: Kunjungan asesmen belum terlaksana / belum ada temuan tindakan perbaikan.';
    } elseif ($isSlaPassed) {
        $initialComputedTpStatus = 'IN_PROGRESS';
        $initialComputedTpLabel = 'Dibekukan (Lewat SLA)';
        $initialComputedTpBadgeClass = 'status-suspended';
        $initialComputedTpDesc = 'Otomatis: Melewati batas waktu awal (SLA) belum dinyatakan memenuhi.';
    } else {
        $initialComputedTpStatus = 'IN_PROGRESS';
        $initialComputedTpLabel = 'Sedang Berlangsung';
        $initialComputedTpBadgeClass = 'status-in_progress';
        $initialComputedTpDesc = 'Otomatis: Selama tanggal dinyatakan memenuhi belum diinput, status tindakan perbaikan sedang berlangsung.';
    }
@endphp
<div class="page-heading">
    <div>
        <a class="back-link" href="{{ route('assessments.index') }}">Semua asesmen</a>
        <h1>{{ $assessment->exists ? 'Ubah asesmen' : 'Tambah asesmen' }}</h1>
    </div>
</div>

<form class="panel form-grid" method="POST" action="{{ $assessment->exists ? route('assessments.update', $assessment) : route('assessments.store') }}">
    @csrf
    @if($assessment->exists) @method('PUT') @endif

    @if(!$assessment->exists && ($assessment->lpk_id || request('lpk_id')))
        @php
            $prefilledLpk = $lpks->firstWhere('id', old('lpk_id', $assessment->lpk_id ?: request('lpk_id')));
        @endphp
        @if($prefilledLpk)
            <div class="full" style="background: #f0fdf4; border: 1px solid #86efac; padding: 12px 16px; border-radius: 6px; margin-bottom: 8px; display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 10px;">
                <div style="display: flex; align-items: center; gap: 10px;">
                    <x-icon name="check-circle" size="20" style="color: #16a34a; flex-shrink: 0;" />
                    <div style="font-size: 13px; color: #166534;">
                        <strong>Jadwal Kunjungan Otomatis Disiapkan:</strong> Form telah terisi berdasarkan data <strong>{{ $prefilledLpk->name }}</strong> ({{ $prefilledLpk->registration_number }}). Silakan sesuaikan tanggal dan rincian sebelum disimpan.
                    </div>
                </div>
                <span class="badge" style="background: #dcfce7; color: #15803d; font-size: 11.5px; font-weight: 600; padding: 3px 8px; border-radius: 4px;">
                    Auto Pre-filled
                </span>
            </div>
        @endif
    @endif

    <label class="full">
        LPK
        <select name="lpk_id" required>
            <option value="">Pilih LPK</option>
            @foreach($lpks as $lpk)
                <option value="{{ $lpk->id }}" @selected(old('lpk_id', $assessment->lpk_id ?: request('lpk_id')) == $lpk->id)>{{ $lpk->registration_number }} - {{ $lpk->name }}</option>
            @endforeach
        </select>
    </label>
    <label class="full">
        Judul
        <input name="title" value="{{ old('title', $assessment->title ?: request('title')) }}" required>
    </label>
    <label>
        Jenis asesmen (Standar KAN U-01)
        <select name="assessment_type" required>
            <option value="">Pilih jenis asesmen</option>
            @foreach(\App\Models\Assessment::TYPES as $typeKey => $typeLabel)
                <option value="{{ $typeKey }}" @selected(
                    old('assessment_type', $assessment->assessment_type ?: request('assessment_type')) === $typeKey ||
                    (old('assessment_type') === null && request('assessment_type') === null && (
                        ($typeKey === 'Asesmen Awal' && $assessment->assessment_type === 'INITIAL') ||
                        ($typeKey === 'Surveilen' && $assessment->assessment_type === 'SURVEILLANCE') ||
                        ($typeKey === 'Re-asesmen' && $assessment->assessment_type === 'REASSESSMENT')
                    ))
                )>
                    {{ $typeLabel }}
                </option>
            @endforeach
        </select>
    </label>
    <div>
        <span style="font-size: 13.5px; font-weight: 600; color: var(--text); display: block; margin-bottom: 6px;">
            Status Asesmen
        </span>
        <div style="min-height: 42px; display: flex; align-items: center; justify-content: space-between; padding: 6px 12px; background: var(--surface-subtle, #f8fafc); border: 1px solid var(--line); border-radius: 6px; gap: 8px;">
            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                <span id="status-badge-preview" class="status status-{{ strtolower($initialComputedStatus) }}" style="font-weight: 600; font-size: 12px;">
                    {{ $statusLabels[$initialComputedStatus] ?? $initialComputedStatus }}
                </span>
                <span id="status-desc-preview" style="font-size: 11.5px; color: var(--muted);">
                    {{ $initialComputedDesc }}
                </span>
            </div>
            <input type="hidden" name="status" id="input-auto-status" value="{{ $initialComputedStatus }}">
        </div>
        <small style="font-size: 11px; color: var(--muted); margin-top: 4px; display: block;">
            * Otomatis dihitung berdasarkan tanggal mulai dan selesai yang dimasukkan.
        </small>
    </div>
    <label>
        Mulai
        <input type="datetime-local" name="start_at" value="{{ old('start_at', $assessment->start_at?->format('Y-m-d\TH:i') ?: request('start_at')) }}" required>
    </label>
    <label>
        Selesai
        <input type="datetime-local" name="end_at" value="{{ old('end_at', $assessment->end_at?->format('Y-m-d\TH:i') ?: request('end_at')) }}" required>
    </label>
    <label>
        Lokasi
        <input name="location" value="{{ old('location', $assessment->location ?: request('location')) }}">
    </label>
    <label>
        Lead assessor
        <input name="lead_assessor" value="{{ old('lead_assessor', $assessment->lead_assessor ?: ($assessment->assessment_team ?: request('lead_assessor'))) }}">
    </label>
    <label class="full">
        Tim Asesmen
        <input name="assessment_team" value="{{ old('assessment_team', $assessment->assessment_team ?: $assessment->lead_assessor) }}" placeholder="Contoh: Dr. Ir. Budi (Ketua), Siti Rahma (Asesor), Hendra (Tenaga Ahli)">
    </label>
    <label class="full">
        Catatan
        <textarea name="notes" rows="3">{{ old('notes', $assessment->notes) }}</textarea>
    </label>

    {{-- Section: Tindakan Perbaikan (TP & VTP) Sesuai Ketentuan Brosur KAN --}}
    <div class="full" style="padding: 16px; background: var(--surface-subtle, #f8fafc); border: 1px solid var(--line); border-radius: 8px; margin-top: 6px; display: grid; gap: 14px;">
        <div>
            <h3 style="margin: 0 0 4px; font-size: 15px; font-weight: 700; color: var(--text);">
                Tindakan Perbaikan &amp; Verifikasi (TP &amp; VTP) - Standar KAN
            </h3>
            <p style="margin: 0; font-size: 12.5px; color: var(--muted); line-height: 1.4;">
                Sesuai Brosur Penguji KAN: Akreditasi Awal batas waktu 3 bulan (peringatan di bulan ke-2). Survailen, PRL, STT, dan Reakreditasi batas waktu 2 bulan (peringatan di bulan ke-1). Perpanjangan masa perbaikan maksimal 1 bulan berbasis surat permohonan resmi jika ada progres perbaikan.
            </p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px;">
            <div>
                <span style="font-size: 13.5px; font-weight: 600; color: var(--text); display: block; margin-bottom: 6px;">
                    Status Tindakan Perbaikan
                </span>
                <div style="min-height: 42px; display: flex; align-items: center; justify-content: space-between; padding: 6px 12px; background: #ffffff; border: 1px solid var(--line); border-radius: 6px; gap: 8px;">
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                        <span id="tp-status-badge-preview" class="status {{ $initialComputedTpBadgeClass }}" style="font-weight: 600; font-size: 12px;">
                            {{ $initialComputedTpLabel }}
                        </span>
                        <span id="tp-status-desc-preview" style="font-size: 11.5px; color: var(--muted);">
                            {{ $initialComputedTpDesc }}
                        </span>
                    </div>
                    <input type="hidden" name="tp_status" id="input-auto-tp-status" value="{{ $initialComputedTpStatus }}">
                </div>
                <small style="font-size: 11px; color: var(--muted); margin-top: 4px; display: block;">
                    * Otomatis: Selama tanggal dinyatakan memenuhi belum diinput, status tindakan perbaikan sedang berlangsung.
                </small>
            </div>

            <label>
                Batas waktu awal (SLA)
                <input type="date" name="tp_due_date" value="{{ old('tp_due_date', $assessment->tp_due_date?->format('Y-m-d') ?: ($isOverdueWithoutAction ? '' : ($assessment->calculateDefaultTpDueDate()?->format('Y-m-d') ?: ''))) }}">
                <small style="font-size: 11px; color: var(--muted); margin-top: 4px; display: block;">Kosongkan jika ingin dihitung otomatis sesuai jenis asesmen.</small>
            </label>

            <label>
                Tanggal dinyatakan memenuhi
                <input type="date" name="tp_satisfied_at" value="{{ old('tp_satisfied_at', $assessment->tp_satisfied_at?->format('Y-m-d')) }}">
                <small style="font-size: 11px; color: var(--muted); margin-top: 4px; display: block;">Diisi apabila tindakan perbaikan telah disetujui.</small>
            </label>
        </div>

        <div style="padding: 12px; background: #ffffff; border: 1px solid var(--line); border-radius: 6px; display: grid; gap: 10px;">
            <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 13px; font-weight: 600;">
                <input type="checkbox" name="tp_has_extension" id="input_tp_has_extension" value="1" @checked(old('tp_has_extension', $assessment->tp_has_extension)) onchange="document.getElementById('form-extension-block').style.display = this.checked ? 'grid' : 'none'" style="width: 18px; height: 18px; min-height: 18px; max-height: 18px; min-width: 18px; max-width: 18px; margin: 0; padding: 0; cursor: pointer; flex-shrink: 0; accent-color: var(--maroon, #e11d48);">
                <span>LPK Mengajukan Perpanjangan Masa Perbaikan (+1 Bulan Sesuai Regulasi KAN)</span>
            </label>
            <small style="font-size: 11.5px; color: var(--muted); margin-top: -4px; display: block;">
                * Perpanjangan hanya dapat diajukan jika LPK telah menyampaikan upaya perbaikan (status Penyusunan atau Verifikasi), bukan Nihil/Tanpa Tindakan Perbaikan.
            </small>

            <div id="form-extension-block" style="display: {{ old('tp_has_extension', $assessment->tp_has_extension) ? 'grid' : 'none' }}; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px;">
                <label>
                    Nomor surat permohonan LPK
                    <input type="text" name="tp_extension_letter_no" placeholder="Contoh: 104/LPK-LAB/EXT/IX/2026" value="{{ old('tp_extension_letter_no', $assessment->tp_extension_letter_no) }}">
                </label>
                <label>
                    Tanggal surat
                    <input type="date" name="tp_extension_date" value="{{ old('tp_extension_date', $assessment->tp_extension_date?->format('Y-m-d')) }}">
                </label>
                <label style="grid-column: 1 / -1;">
                    Alasan permohonan perpanjangan
                    <input type="text" name="tp_extension_notes" placeholder="Contoh: Menunggu pengiriman kalibrator eksternal dan uji profisiensi perbaikan." value="{{ old('tp_extension_notes', $assessment->tp_extension_notes) }}">
                </label>
            </div>
        </div>

        <label>
            Catatan temuan &amp; tindakan perbaikan
            <textarea name="tp_notes" rows="3" placeholder="Rangkuman temuan ketidaksesuaian atau tindakan koreksi yang dilakukan LPK...">{{ old('tp_notes', $assessment->tp_notes) }}</textarea>
        </label>
    </div>

    {{-- Section: Milestone Laporan Asesmen & Evaluasi Hasil Asesmen (EHA) --}}
    <div class="full" style="padding: 16px; background: var(--surface-subtle, #f8fafc); border: 1px solid var(--line); border-radius: 8px; margin-top: 6px; display: grid; gap: 14px;">
        <div>
            <h3 style="margin: 0 0 4px; font-size: 15px; font-weight: 700; color: var(--text);">
                Laporan Asesmen &amp; Evaluasi Hasil Asesmen (EHA)
            </h3>
            <p style="margin: 0; font-size: 12.5px; color: var(--muted); line-height: 1.4;">
                Pencatatan tanggal penerbitan laporan dan evaluasi hasil asesmen oleh Panitia Teknis sebelum penerbitan SK.
            </p>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 14px;">
            <label>
                Tanggal penerbitan Laporan Asesmen
                <input type="date" name="report_date" value="{{ old('report_date', $assessment->report_date?->format('Y-m-d')) }}">
            </label>

            <label>
                Tanggal pelaksanaan EHA (Rapat Panitia Teknis)
                <input type="date" name="eha_date" value="{{ old('eha_date', $assessment->eha_date?->format('Y-m-d')) }}">
            </label>

            <label>
                Status hasil EHA
                <select name="eha_status">
                    @foreach(\App\Models\Assessment::EHA_STATUSES as $ehaKey => $ehaLabel)
                        <option value="{{ $ehaKey }}" @selected(old('eha_status', $assessment->eha_status ?: \App\Models\Assessment::EHA_STATUS_BELUM) === $ehaKey)>
                            {{ $ehaLabel }}
                        </option>
                    @endforeach
                </select>
            </label>
        </div>

        <label class="full">
            Catatan / Hasil Evaluasi Asesmen (EHA)
            <textarea name="eha_notes" rows="2" placeholder="Catatan panitia teknis, rekomendasi kelanjutan, atau tindak lanjut hasil evaluasi...">{{ old('eha_notes', $assessment->eha_notes) }}</textarea>
        </label>
    </div>

    {{-- Section: Keputusan Akreditasi / SK KAN --}}
    <div id="form-sk-block" class="full" style="padding: 16px; background: #ffffff; border: 1px solid var(--line); border-radius: 8px; margin-top: 6px; display: grid; gap: 12px;">
        <div style="display: flex; align-items: center; justify-content: space-between; flex-wrap: wrap; gap: 8px;">
            <div>
                <span style="font-size: 14px; font-weight: 700; color: var(--text); display: block;">
                    Surat Keputusan (SK) Hasil Asesmen / Akreditasi KAN
                </span>
                <small style="color: var(--muted); font-size: 11.5px;">Diisi jika tindakan perbaikan telah selesai / memenuhi atau SK kelanjutan/re-akreditasi telah diterbitkan.</small>
            </div>
            <span class="badge-tp badge-tp-success" style="font-size: 11px;">Penyelesaian &amp; SK KAN</span>
        </div>

        <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 12px;">
            <label>
                Nomor SK KAN
                <input type="text" name="sk_number" placeholder="Contoh: SK.KAN.042/BSN/IX/2026" value="{{ old('sk_number', $assessment->sk_number) }}">
            </label>
            <label>
                Tanggal SK
                <input type="date" name="sk_date" value="{{ old('sk_date', $assessment->sk_date?->format('Y-m-d')) }}">
            </label>
        </div>
        @if($assessment->sk_lead_time_label)
            <div style="font-size: 12px; color: #15803d; background: #f0fdf4; border: 1px solid #bbf7d0; padding: 6px 10px; border-radius: 4px;">
                <strong>Rentang Proses:</strong> {{ $assessment->sk_lead_time_label }}
                (dari pelaksanaan {{ ($assessment->end_at ?? $assessment->start_at)->format('d M Y') }} s/d SK {{ $assessment->sk_date->format('d M Y') }})
            </div>
        @endif
    </div>

    <div class="form-actions full">
        <a class="button ghost" href="{{ route('assessments.index') }}">Batal</a>
        <button class="button primary" type="submit">
            <x-icon :name="$assessment->exists ? 'edit' : 'plus'" size="16" />
            <span>{{ $assessment->exists ? 'Simpan perubahan' : 'Simpan asesmen' }}</span>
        </button>
    </div>
</form>

<script>
(function() {
    function initAutoAssessmentStatus() {
        const startInput = document.querySelector('input[name="start_at"]');
        const endInput = document.querySelector('input[name="end_at"]');
        const assessmentTypeSelect = document.querySelector('select[name="assessment_type"]');
        const tpDueDateInput = document.querySelector('input[name="tp_due_date"]');
        const tpHasExtCheckbox = document.getElementById('input_tp_has_extension');
        const tpSatisfiedInput = document.querySelector('input[name="tp_satisfied_at"]');
        const reportDateInput = document.querySelector('input[name="report_date"]');
        const ehaDateInput = document.querySelector('input[name="eha_date"]');
        const skNumberInput = document.querySelector('input[name="sk_number"]');

        const badge = document.getElementById('status-badge-preview');
        const desc = document.getElementById('status-desc-preview');
        const hiddenStatus = document.getElementById('input-auto-status');

        const tpBadge = document.getElementById('tp-status-badge-preview');
        const tpDesc = document.getElementById('tp-status-desc-preview');
        const hiddenTpStatus = document.getElementById('input-auto-tp-status');

        const isCancelled = {{ $assessment->status === 'CANCELLED' ? 'true' : 'false' }};

        if (!startInput || !endInput || !badge) return;

        function updateStatus() {
            if (isCancelled) {
                return;
            }

            const startVal = startInput.value;
            const endVal = endInput.value;
            const skNumberVal = skNumberInput ? skNumberInput.value.trim() : '';
            const tpSatisfiedVal = tpSatisfiedInput ? tpSatisfiedInput.value : '';
            const reportDateVal = reportDateInput ? reportDateInput.value : '';
            const ehaDateVal = ehaDateInput ? ehaDateInput.value : '';
            const tpDueDateVal = tpDueDateInput ? tpDueDateInput.value : '';
            const tpHasExt = tpHasExtCheckbox ? tpHasExtCheckbox.checked : false;

            const now = new Date();
            const startDate = startVal ? new Date(startVal) : null;
            const endDate = endVal ? new Date(endVal) : null;

            // Hitung batas waktu SLA efektif
            let effectiveDueDate = null;
            if (tpDueDateVal) {
                effectiveDueDate = new Date(tpDueDateVal + 'T23:59:59');
            } else if (endDate) {
                const typeVal = (assessmentTypeSelect ? assessmentTypeSelect.value : '') || '';
                const months = (typeVal === 'Akreditasi Awal' || typeVal === 'INITIAL') ? 3 : 2;
                effectiveDueDate = new Date(endDate);
                effectiveDueDate.setMonth(effectiveDueDate.getMonth() + months);
                effectiveDueDate.setHours(23, 59, 59, 999);
            }

            if (effectiveDueDate && tpHasExt) {
                effectiveDueDate.setMonth(effectiveDueDate.getMonth() + 1);
            }

            const isSlaPassed = effectiveDueDate && now > effectiveDueDate;
            const isTpSatisfied = tpSatisfiedVal !== '';
            const hasExecutionProof = reportDateVal !== '' || ehaDateVal !== '';
            const hasCompletedAction = skNumberVal !== '' || isTpSatisfied;
            const isOverdueWithoutAction = endDate && (now > endDate) && !hasExecutionProof && !skNumberVal && !tpDueDateVal && !isTpSatisfied;

            // 1. UPDATE STATUS TINDAKAN PERBAIKAN (TP) OTOMATIS
            let tpStatus = 'IN_PROGRESS';
            let tpLabel = 'Sedang Berlangsung';
            let tpBadgeClass = 'status-in_progress';
            let tpDescription = 'Otomatis: Selama tanggal dinyatakan memenuhi belum diinput, status tindakan perbaikan sedang berlangsung.';

            if (isTpSatisfied) {
                tpStatus = 'SATISFIED';
                tpLabel = 'Dinyatakan Memenuhi (Selesai)';
                tpBadgeClass = 'status-completed';
                tpDescription = 'Otomatis: Tindakan perbaikan telah dinyatakan memenuhi.';
            } else if (isOverdueWithoutAction && !tpDueDateVal) {
                tpStatus = 'NONE';
                tpLabel = 'Menunggu Pelaksanaan';
                tpBadgeClass = 'status-planned';
                tpDescription = 'Otomatis: Kunjungan asesmen belum terlaksana / belum ada temuan tindakan perbaikan.';
            } else if (isSlaPassed) {
                tpStatus = 'IN_PROGRESS';
                tpLabel = 'Dibekukan (Lewat SLA)';
                tpBadgeClass = 'status-suspended';
                tpDescription = 'Otomatis: Melewati batas waktu awal (SLA) belum dinyatakan memenuhi sehingga status dibekukan.';
            }

            if (tpBadge) {
                tpBadge.className = 'status ' + tpBadgeClass;
                tpBadge.textContent = tpLabel;
            }
            if (tpDesc) {
                tpDesc.textContent = tpDescription;
            }
            if (hiddenTpStatus) {
                hiddenTpStatus.value = tpStatus;
            }

            // 2. UPDATE STATUS ASESMEN OTOMATIS
            let status = 'PLANNED';
            let label = 'Direncanakan';
            let badgeClass = 'status-planned';
            let description = 'Otomatis: Agenda asesmen direncanakan.';

            // a. Jika SK KAN sudah terbit atau Tindakan Perbaikan (TP) memenuhi
            if (hasCompletedAction) {
                status = 'COMPLETED';
                label = 'Selesai';
                badgeClass = 'status-completed';
                description = 'Otomatis: SK KAN telah terbit atau tindakan perbaikan (TP) telah memenuhi.';
            }
            // b. Jika tanggal pelaksanaan telah lewat namun belum ada tindakan apapun
            else if (isOverdueWithoutAction && !tpDueDateVal) {
                status = 'PLANNED';
                label = 'Direncanakan';
                badgeClass = 'status-planned';
                description = 'Otomatis: Tanggal pelaksanaan telah lewat namun belum ada tindakan/pelaporan asesmen (Lewat Jadwal).';
            }
            // c. Jika melewati batas waktu awal (SLA) belum ada yang dinyatakan memenuhi -> DIBEKUKAN
            else if (isSlaPassed) {
                status = 'SUSPENDED';
                label = 'Dibekukan';
                badgeClass = 'status-suspended';
                description = 'Otomatis: Melewati batas waktu awal (SLA) belum dinyatakan memenuhi sehingga status dibekukan.';
            }
            // d. Jika dalam masa perbaikan aktif atau kunjungan berjalan
            else if (tpStatus === 'IN_PROGRESS' || hasExecutionProof || (startDate && endDate && now >= startDate && now <= endDate)) {
                status = 'IN_PROGRESS';
                label = 'Sedang Berlangsung';
                badgeClass = 'status-in_progress';
                description = 'Otomatis: Saat ini dalam periode pelaksanaan atau tindak lanjut asesmen.';
            }
            // e. Jika tanggal mulai di masa depan
            else if (startDate && now < startDate) {
                status = 'SCHEDULED';
                label = 'Terjadwal';
                badgeClass = 'status-scheduled';
                description = 'Otomatis: Tanggal pelaksanaan di masa mendatang.';
            }

            badge.className = 'status ' + badgeClass;
            badge.textContent = label;
            if (desc) {
                desc.textContent = description;
            }
            if (hiddenStatus) {
                hiddenStatus.value = status;
            }
        }

        const watchElements = [startInput, endInput, assessmentTypeSelect, tpDueDateInput, tpHasExtCheckbox, tpSatisfiedInput, reportDateInput, ehaDateInput, skNumberInput];
        watchElements.forEach(function(el) {
            if (el) {
                el.addEventListener('input', updateStatus);
                el.addEventListener('change', updateStatus);
            }
        });

        // Jalankan penentuan status otomatis saat form dimuat
        updateStatus();
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initAutoAssessmentStatus);
    } else {
        initAutoAssessmentStatus();
    }
    window.addEventListener('simasadi:page-loaded', initAutoAssessmentStatus);
})();
</script>
@endsection
