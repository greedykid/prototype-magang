@extends('layouts.app')

@section('title', ($assessment->exists ? 'Ubah Asesmen' : 'Tambah Asesmen') . ' | SIMASADI')

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
        'SUSPENDED' => 'Otomatis: Melewati batas waktu awal belum dinyatakan memenuhi sehingga status dibekukan.',
        'COMPLETED' => 'Otomatis: Tindakan perbaikan memenuhi atau SK KAN telah terbit.',
        'CANCELLED' => 'Asesmen dibatalkan.',
    ];
    $initialComputedDesc = $statusDescriptions[$initialComputedStatus] ?? 'Otomatis ditentukan dari tanggal pelaksanaan & milestone.';

    // Status Tindakan Perbaikan (TP & VTP) Otomatis:
    // Selama tanggal dinyatakan memenuhi belum diinput, statusnya sedang berlangsung.
    // Jika melewati batas waktu awal belum ada yang memenuhi, statusnya dibekukan.
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
        $initialComputedTpLabel = 'Dibekukan (Lewat Batas Waktu)';
        $initialComputedTpBadgeClass = 'status-suspended';
        $initialComputedTpDesc = 'Otomatis: Melewati batas waktu awal belum dinyatakan memenuhi.';
    } else {
        $initialComputedTpStatus = 'IN_PROGRESS';
        $initialComputedTpLabel = 'Sedang Berlangsung';
        $initialComputedTpBadgeClass = 'status-in_progress';
        $initialComputedTpDesc = 'Otomatis: Selama tanggal dinyatakan memenuhi belum diinput, status tindakan perbaikan sedang berlangsung.';
    }
@endphp

<div class="lpk-form-container">
    {{-- Header Card dengan Breadcrumb, Judul & Tombol Cepat --}}
    <div class="lpk-form-header">
        <div class="lpk-header-back-wrap">
            <a href="{{ route('assessments.index') }}" class="lpk-back-btn">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M19 12H5"/><path d="m12 19-7-7 7-7"/></svg>
                <span>Semua asesmen</span>
            </a>
        </div>

        <div class="lpk-header-row">
            <div class="lpk-header-title-group">
                <h1>{{ $assessment->exists ? 'Ubah asesmen' : 'Tambah asesmen' }}</h1>
                <p class="lpk-form-desc">
                    Penjadwalan agenda kunjungan asesmen, penetapan tim asesor, dan pemantauan tindak lanjut perbaikan (TP/VTP) standar KAN.
                </p>

                @if($assessment->exists)
                    <div class="lpk-header-badges">
                        <span class="lpk-badge-reg">{{ $assessment->lpk->registration_number ?? '-' }}</span>
                        <span class="lpk-badge-type">{{ $assessment->assessment_type_label }}</span>
                        <x-status :value="$assessment->status" />
                    </div>
                @endif
            </div>

            <div class="lpk-header-actions">
                <a class="button ghost" href="{{ route('assessments.index') }}">
                    Batal
                </a>
                <button class="button primary" type="submit" form="assessment-main-form">
                    <x-icon :name="$assessment->exists ? 'check' : 'plus'" size="16" />
                    <span>{{ $assessment->exists ? 'Simpan perubahan' : 'Simpan asesmen' }}</span>
                </button>
            </div>
        </div>
    </div>

    {{-- Banner Notifikasi Jika Jadwal Terisi Otomatis --}}
    @if(!$assessment->exists && ($assessment->lpk_id || request('lpk_id')))
        @php
            $prefilledLpk = $lpks->firstWhere('id', old('lpk_id', $assessment->lpk_id ?: request('lpk_id')));
        @endphp
        @if($prefilledLpk)
            <div class="lpk-alert-callout" style="background: #f0fdf4; border-color: #86efac; color: #166534; box-shadow: 0 1px 3px rgba(22, 101, 52, 0.05);">
                <div class="lpk-alert-callout-content">
                    <div class="lpk-alert-callout-head" style="color: #166534;">
                        <x-icon name="check-circle" size="18" style="color: #16a34a; flex-shrink: 0;" />
                        <strong>Jadwal Kunjungan Otomatis Disiapkan</strong>
                    </div>
                    <p class="lpk-alert-callout-desc" style="color: #15803d;">
                        Form telah terisi otomatis berdasarkan data <strong>{{ $prefilledLpk->name }}</strong> ({{ $prefilledLpk->registration_number }}). Silakan sesuaikan tanggal dan rincian sebelum disimpan.
                    </p>
                </div>
                <span class="badge" style="background: #dcfce7; color: #15803d; font-size: 11.5px; font-weight: 700; padding: 4px 10px; border-radius: 6px;">
                    Auto Pre-filled
                </span>
            </div>
        @endif
    @endif

    {{-- Main Multi-Card Form --}}
    <form method="POST" action="{{ $assessment->exists ? route('assessments.update', $assessment) : route('assessments.store') }}" id="assessment-main-form">
        @csrf
        @if($assessment->exists)
            @method('PUT')
        @endif

        <div class="lpk-form-layout">
            {{-- KOLOM KIRI: Informasi Utama Asesmen & Tim Pelaksana --}}
            <div class="lpk-form-column">
                {{-- KARTU 1: Informasi Lembaga & Judul Agenda --}}
                <div class="lpk-form-card">
                    <div class="lpk-form-card-header">
                        <div class="lpk-card-icon-wrap" style="background: #eff6ff; color: #1d4ed8;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect width="16" height="20" x="4" y="2" rx="2" ry="2"/>
                                <path d="M9 22v-4h6v4"/>
                                <path d="M8 6h.01"/>
                                <path d="M16 6h.01"/>
                                <path d="M8 10h.01"/>
                                <path d="M16 10h.01"/>
                            </svg>
                        </div>
                        <div class="lpk-card-header-text">
                            <h2>Informasi Lembaga &amp; Judul Agenda</h2>
                            <p>Pilih laboratorium uji / kalibrasi sasaran dan tentukan judul penugasan asesmen.</p>
                        </div>
                    </div>

                    <div class="lpk-field">
                        <label class="lpk-label" for="assessment-lpk-id">
                            <span>LPK / Laboratorium Sasaran</span>
                            <span class="lpk-required-dot">*</span>
                        </label>
                        <select id="assessment-lpk-id" name="lpk_id" required>
                            <option value="">Pilih LPK</option>
                            @foreach($lpks as $lpk)
                                <option value="{{ $lpk->id }}" @selected(old('lpk_id', $assessment->lpk_id ?: request('lpk_id')) == $lpk->id)>
                                    {{ $lpk->registration_number }} - {{ $lpk->name }}
                                </option>
                            @endforeach
                        </select>
                        <span class="lpk-field-hint">Lembaga Penilaian Kesesuaian yang terdaftar di Komite Akreditasi Nasional.</span>
                    </div>

                    <div class="lpk-field">
                        <label class="lpk-label" for="assessment-title">
                            <span>Judul Agenda Asesmen</span>
                            <span class="lpk-required-dot">*</span>
                        </label>
                        <input id="assessment-title" name="title" value="{{ old('title', $assessment->title ?: request('title')) }}" required placeholder="Contoh: Asesmen Surveilen 1 (S1) - Balai Besar Logam dan Mesin">
                        <span class="lpk-field-hint">Nama penugasan yang tercantum pada surat tugas dan kalender asesmen.</span>
                    </div>

                    <div class="lpk-field-row">
                        <div class="lpk-field">
                            <label class="lpk-label" for="assessment-type-select">
                                <span>Jenis Asesmen (Standar KAN U-01)</span>
                                <span class="lpk-required-dot">*</span>
                            </label>
                            <select id="assessment-type-select" name="assessment_type" required>
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
                            <span class="lpk-field-hint">Jenis siklus akreditasi KAN (Asesmen Awal, Surveilen, Re-asesmen, STT/PRL).</span>
                        </div>

                        <div class="lpk-field">
                            <label class="lpk-label">
                                <span>Status Asesmen</span>
                            </label>
                            <div class="lpk-status-preview-box">
                                <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                    <span id="status-badge-preview" class="status status-{{ strtolower($initialComputedStatus) }}" style="font-weight: 600; font-size: 12px;">
                                        {{ $statusLabels[$initialComputedStatus] ?? $initialComputedStatus }}
                                    </span>
                                    <span id="status-desc-preview" style="font-size: 11.5px; color: var(--muted); line-height: 1.4;">
                                        {{ $initialComputedDesc }}
                                    </span>
                                </div>
                                <input type="hidden" name="status" id="input-auto-status" value="{{ $initialComputedStatus }}">
                            </div>
                            <span class="lpk-field-hint">* Otomatis dihitung berdasarkan tanggal pelaksanaan &amp; milestone perbaikan.</span>
                        </div>
                    </div>
                </div>

                {{-- KARTU 2: Jadwal Pelaksanaan & Personil Asesor --}}
                <div class="lpk-form-card">
                    <div class="lpk-form-card-header">
                        <div class="lpk-card-icon-wrap" style="background: #f0fdf4; color: #166534;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                        </div>
                        <div class="lpk-card-header-text">
                            <h2>Waktu Pelaksanaan &amp; Tim Asesor</h2>
                            <p>Rentang tanggal kunjungan lapangan, lokasi, dan personil tim penilai KAN.</p>
                        </div>
                    </div>

                    <div class="lpk-field-row">
                        <div class="lpk-field">
                            <label class="lpk-label" for="assessment-start-at">
                                <span>Mulai Pelaksanaan</span>
                                <span class="lpk-required-dot">*</span>
                            </label>
                            <input id="assessment-start-at" type="datetime-local" name="start_at" value="{{ old('start_at', $assessment->start_at?->format('Y-m-d\TH:i') ?: request('start_at')) }}" required>
                            <span class="lpk-field-hint">Waktu pembukaan opening meeting asesmen.</span>
                        </div>

                        <div class="lpk-field">
                            <label class="lpk-label" for="assessment-end-at">
                                <span>Selesai Pelaksanaan</span>
                                <span class="lpk-required-dot">*</span>
                            </label>
                            <input id="assessment-end-at" type="datetime-local" name="end_at" value="{{ old('end_at', $assessment->end_at?->format('Y-m-d\TH:i') ?: request('end_at')) }}" required>
                            <span class="lpk-field-hint">Waktu penutupan closing meeting asesmen.</span>
                        </div>
                    </div>

                    <div class="lpk-field-row">
                        <div class="lpk-field">
                            <label class="lpk-label" for="assessment-location">
                                <span>Lokasi Kunjungan / Audit</span>
                            </label>
                            <input id="assessment-location" name="location" value="{{ old('location', $assessment->location ?: request('location')) }}" placeholder="Contoh: Gedung Laboratorium Kimia & Fisika, Bandung">
                            <span class="lpk-field-hint">Lokasi fisik pengujian atau audit lapangan.</span>
                        </div>

                        <div class="lpk-field">
                            <label class="lpk-label" for="assessment-lead-assessor">
                                <span>Lead Assessor (Asesor Kepala)</span>
                            </label>
                            <input id="assessment-lead-assessor" name="lead_assessor" value="{{ old('lead_assessor', $assessment->lead_assessor ?: ($assessment->assessment_team ?: request('lead_assessor'))) }}" placeholder="Nama asesor kepala">
                            <span class="lpk-field-hint">Penanggung jawab tim asesmen KAN.</span>
                        </div>
                    </div>

                    <div class="lpk-field">
                        <label class="lpk-label" for="assessment-team">
                            <span>Susunan Tim Asesmen (Asesor &amp; Tenaga Ahli)</span>
                        </label>
                        <input id="assessment-team" name="assessment_team" value="{{ old('assessment_team', $assessment->assessment_team ?: $assessment->lead_assessor) }}" placeholder="Contoh: Dr. Ir. Budi (Ketua), Siti Rahma (Asesor), Hendra (Tenaga Ahli)">
                        <span class="lpk-field-hint">Daftar nama lengkap asesor dan tenaga ahli yang ditugaskan.</span>
                    </div>

                    <div class="lpk-field">
                        <label class="lpk-label" for="assessment-notes">
                            <span>Catatan Pelaksanaan / Lingkup Khusus</span>
                        </label>
                        <textarea id="assessment-notes" name="notes" rows="3" placeholder="Catatan internal pengawasan, agenda khusus, atau arahan logistik tim asesmen...">{{ old('notes', $assessment->notes) }}</textarea>
                    </div>
                </div>
            </div>

            {{-- KOLOM KANAN: Standar KAN (TP/VTP, Evaluasi EHA, dan Keputusan SK) --}}
            <div class="lpk-form-column">
                {{-- KARTU 3: Tindakan Perbaikan & Verifikasi (TP & VTP) - Standar KAN --}}
                <div class="lpk-form-card">
                    <div class="lpk-form-card-header">
                        <div class="lpk-card-icon-wrap" style="background: #fffbeb; color: #b45309;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                <path d="m9 12 2 2 4-4"/>
                            </svg>
                        </div>
                        <div class="lpk-card-header-text">
                            <h2>Tindakan Perbaikan &amp; Verifikasi (TP &amp; VTP)</h2>
                            <p>Batas waktu perbaikan ketidaksesuaian sesuai Brosur KAN (Akreditasi Awal 3 bulan, Surveilen/Re-asesmen 2 bulan).</p>
                        </div>
                    </div>

                    <div class="lpk-field">
                        <label class="lpk-label">
                            <span>Status Tindakan Perbaikan</span>
                        </label>
                        <div class="lpk-status-preview-box">
                            <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                                <span id="tp-status-badge-preview" class="status {{ $initialComputedTpBadgeClass }}" style="font-weight: 600; font-size: 12px;">
                                    {{ $initialComputedTpLabel }}
                                </span>
                                <span id="tp-status-desc-preview" style="font-size: 11.5px; color: var(--muted); line-height: 1.4;">
                                    {{ $initialComputedTpDesc }}
                                </span>
                            </div>
                            <input type="hidden" name="tp_status" id="input-auto-tp-status" value="{{ $initialComputedTpStatus }}">
                        </div>
                        <span class="lpk-field-hint">* Otomatis: Selama tanggal dinyatakan memenuhi belum diinput, status tindakan perbaikan aktif berlangsung.</span>
                    </div>

                    <div class="lpk-field-row">
                        <div class="lpk-field">
                            <label class="lpk-label" for="assessment-tp-due-date">
                                <span>Batas Waktu Awal</span>
                            </label>
                            <input id="assessment-tp-due-date" type="date" name="tp_due_date" value="{{ old('tp_due_date', $assessment->tp_due_date?->format('Y-m-d') ?: ($isOverdueWithoutAction ? '' : ($assessment->calculateDefaultTpDueDate()?->format('Y-m-d') ?: ''))) }}">
                            <span class="lpk-field-hint">Kosongkan jika ingin dihitung otomatis sesuai jenis asesmen.</span>
                        </div>

                        <div class="lpk-field">
                            <label class="lpk-label" for="assessment-tp-satisfied-at">
                                <span>Tanggal Dinyatakan Memenuhi</span>
                            </label>
                            <input id="assessment-tp-satisfied-at" type="date" name="tp_satisfied_at" value="{{ old('tp_satisfied_at', $assessment->tp_satisfied_at?->format('Y-m-d')) }}">
                            <span class="lpk-field-hint">Diisi saat verifikasi tindakan perbaikan telah disetujui.</span>
                        </div>
                    </div>

                    {{-- Kotak Perpanjangan Masa Perbaikan --}}
                    <div class="lpk-checkbox-card">
                        <label style="display: flex; align-items: center; gap: 10px; cursor: pointer; font-size: 13px; font-weight: 600; color: var(--ink);">
                            <input type="checkbox" name="tp_has_extension" id="input_tp_has_extension" value="1" @checked(old('tp_has_extension', $assessment->tp_has_extension)) onchange="document.getElementById('form-extension-block').style.display = this.checked ? 'grid' : 'none'" style="width: 18px; height: 18px; min-height: 18px; max-height: 18px; min-width: 18px; max-width: 18px; margin: 0; padding: 0; cursor: pointer; flex-shrink: 0; accent-color: var(--maroon, #e11d48);">
                            <span>LPK Mengajukan Perpanjangan Masa Perbaikan (+1 Bulan Sesuai Regulasi KAN)</span>
                        </label>
                        <span style="font-size: 11.5px; color: var(--muted); margin-top: -4px; line-height: 1.4;">
                            * Perpanjangan hanya dapat diajukan jika LPK telah menyampaikan upaya perbaikan (status Penyusunan atau Verifikasi), bukan Nihil/Tanpa Tindakan Perbaikan.
                        </span>

                        <div id="form-extension-block" style="display: {{ old('tp_has_extension', $assessment->tp_has_extension) ? 'grid' : 'none' }}; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 12px; border-top: 1px dashed var(--line); padding-top: 12px; margin-top: 4px;">
                            <div class="lpk-field">
                                <label class="lpk-label" for="assessment-ext-letter-no">
                                    <span>Nomor Surat Permohonan</span>
                                </label>
                                <input id="assessment-ext-letter-no" type="text" name="tp_extension_letter_no" placeholder="Contoh: 104/LPK-LAB/EXT/IX/2026" value="{{ old('tp_extension_letter_no', $assessment->tp_extension_letter_no) }}">
                            </div>

                            <div class="lpk-field">
                                <label class="lpk-label" for="assessment-ext-date">
                                    <span>Tanggal Surat</span>
                                </label>
                                <input id="assessment-ext-date" type="date" name="tp_extension_date" value="{{ old('tp_extension_date', $assessment->tp_extension_date?->format('Y-m-d')) }}">
                            </div>

                            <div class="lpk-field" style="grid-column: 1 / -1;">
                                <label class="lpk-label" for="assessment-ext-notes">
                                    <span>Alasan Permohonan Perpanjangan</span>
                                </label>
                                <input id="assessment-ext-notes" type="text" name="tp_extension_notes" placeholder="Contoh: Menunggu pengiriman kalibrator eksternal dan uji profisiensi perbaikan." value="{{ old('tp_extension_notes', $assessment->tp_extension_notes) }}">
                            </div>
                        </div>
                    </div>

                    <div class="lpk-field">
                        <label class="lpk-label" for="assessment-tp-notes">
                            <span>Catatan Temuan &amp; Tindakan Perbaikan</span>
                        </label>
                        <textarea id="assessment-tp-notes" name="tp_notes" rows="3" placeholder="Rangkuman temuan ketidaksesuaian atau tindakan koreksi yang dilakukan LPK...">{{ old('tp_notes', $assessment->tp_notes) }}</textarea>
                    </div>
                </div>

                {{-- KARTU 4: Laporan Asesmen & Evaluasi Hasil Asesmen (EHA) --}}
                <div class="lpk-form-card">
                    <div class="lpk-form-card-header">
                        <div class="lpk-card-icon-wrap" style="background: #fdf2f8; color: #be185d;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                                <path d="M14 2v6h6"/>
                                <line x1="16" y1="13" x2="8" y2="13"/>
                                <line x1="16" y1="17" x2="8" y2="17"/>
                                <line x1="10" y1="9" x2="8" y2="9"/>
                            </svg>
                        </div>
                        <div class="lpk-card-header-text">
                            <h2>Laporan Asesmen &amp; Evaluasi (EHA)</h2>
                            <p>Pencatatan laporan akhir dan evaluasi hasil asesmen oleh Panitia Teknis sebelum penerbitan SK.</p>
                        </div>
                    </div>

                    <div class="lpk-field-row">
                        <div class="lpk-field">
                            <label class="lpk-label" for="assessment-report-date">
                                <span>Penerbitan Laporan Asesmen</span>
                            </label>
                            <input id="assessment-report-date" type="date" name="report_date" value="{{ old('report_date', $assessment->report_date?->format('Y-m-d')) }}">
                        </div>

                        <div class="lpk-field">
                            <label class="lpk-label" for="assessment-eha-date">
                                <span>Pelaksanaan EHA (Panitia Teknis)</span>
                            </label>
                            <input id="assessment-eha-date" type="date" name="eha_date" value="{{ old('eha_date', $assessment->eha_date?->format('Y-m-d')) }}">
                        </div>

                        <div class="lpk-field">
                            <label class="lpk-label" for="assessment-eha-status">
                                <span>Status Hasil EHA</span>
                            </label>
                            <select id="assessment-eha-status" name="eha_status">
                                @foreach(\App\Models\Assessment::EHA_STATUSES as $ehaKey => $ehaLabel)
                                    <option value="{{ $ehaKey }}" @selected(old('eha_status', $assessment->eha_status ?: \App\Models\Assessment::EHA_STATUS_BELUM) === $ehaKey)>
                                        {{ $ehaLabel }}
                                    </option>
                                @endforeach
                            </select>
                        </div>
                    </div>

                    <div class="lpk-field">
                        <label class="lpk-label" for="assessment-eha-notes">
                            <span>Catatan / Rekomendasi Hasil Evaluasi (EHA)</span>
                        </label>
                        <textarea id="assessment-eha-notes" name="eha_notes" rows="2" placeholder="Catatan panitia teknis, rekomendasi kelanjutan, atau tindak lanjut hasil evaluasi...">{{ old('eha_notes', $assessment->eha_notes) }}</textarea>
                    </div>
                </div>

                {{-- KARTU 5: Keputusan Akreditasi / SK KAN --}}
                <div id="form-sk-block" class="lpk-form-card">
                    <div class="lpk-form-card-header">
                        <div class="lpk-card-icon-wrap" style="background: #f0fdf4; color: #15803d;">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <circle cx="12" cy="8" r="6"/>
                                <path d="M15.477 12.89 17 22l-5-3-5 3 1.523-9.11"/>
                            </svg>
                        </div>
                        <div class="lpk-card-header-text" style="flex: 1;">
                            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                                <div>
                                    <h2>Surat Keputusan (SK) Hasil Asesmen / Akreditasi KAN</h2>
                                    <p>Diisi jika tindakan perbaikan telah selesai / memenuhi atau SK kelanjutan/re-akreditasi telah diterbitkan.</p>
                                </div>
                                <span class="badge-tp badge-tp-success" style="font-size: 11px;">Penyelesaian &amp; SK KAN</span>
                            </div>
                        </div>
                    </div>

                    <div class="lpk-field-row">
                        <div class="lpk-field">
                            <label class="lpk-label" for="assessment-sk-number">
                                <span>Nomor SK KAN</span>
                            </label>
                            <input id="assessment-sk-number" type="text" name="sk_number" placeholder="Contoh: SK.KAN.042/BSN/IX/2026" value="{{ old('sk_number', $assessment->sk_number) }}">
                        </div>

                        <div class="lpk-field">
                            <label class="lpk-label" for="assessment-sk-date">
                                <span>Tanggal SK</span>
                            </label>
                            <input id="assessment-sk-date" type="date" name="sk_date" value="{{ old('sk_date', $assessment->sk_date?->format('Y-m-d')) }}">
                        </div>
                    </div>

                    @if($assessment->sk_lead_time_label)
                        <div style="font-size: 12px; color: #15803d; background: #f0fdf4; border: 1px solid #bbf7d0; padding: 8px 12px; border-radius: 6px;">
                            <strong>Rentang Proses:</strong> {{ $assessment->sk_lead_time_label }}
                            (dari pelaksanaan {{ ($assessment->end_at ?? $assessment->start_at)->format('d M Y') }} s/d SK {{ $assessment->sk_date->format('d M Y') }})
                        </div>
                    @endif
                </div>
            </div>
        </div>
    </form>
</div>

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

            // Hitung batas waktu perbaikan efektif
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
                tpLabel = 'Dibekukan (Lewat Batas Waktu)';
                tpBadgeClass = 'status-suspended';
                tpDescription = 'Otomatis: Melewati batas waktu awal belum dinyatakan memenuhi sehingga status dibekukan.';
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
            // c. Jika melewati batas waktu awal belum ada yang dinyatakan memenuhi -> DIBEKUKAN
            else if (isSlaPassed) {
                status = 'SUSPENDED';
                label = 'Dibekukan';
                badgeClass = 'status-suspended';
                description = 'Otomatis: Melewati batas waktu awal belum dinyatakan memenuhi sehingga status dibekukan.';
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
