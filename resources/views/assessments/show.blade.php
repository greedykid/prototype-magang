@extends('layouts.app')

@section('title', $assessment->display_title . ' | SIMASADI')

@section('content')
<div class="lpk-show-container">
    {{-- Tombol Navigasi Kembali (di Luar Container Card) --}}
    <div class="lpk-header-back-wrap" style="margin-bottom: -6px;">
        <a href="{{ route('assessments.index') }}" class="lpk-back-btn">
            <x-icon name="chevron-left" size="14" />
            <span>Semua asesmen</span>
        </a>
    </div>

    {{-- Header Card dengan Judul, Badges & Tombol Aksi --}}
    <div class="lpk-show-header">
        <div class="lpk-header-row">
            <div class="lpk-header-title-group">
                <h1>{{ $assessment->display_title }}</h1>
                <div class="lpk-header-badges">
                    <a href="{{ route('lpks.show', $assessment->lpk) }}" class="lpk-badge-reg" style="text-decoration: none; color: var(--ink);" title="Buka detail LPK">
                        {{ $assessment->lpk->registration_number }} - {{ $assessment->lpk->name }}
                    </a>
                    <span class="lpk-badge-type">{{ $assessment->assessment_type_label }}</span>
                    @if($assessment->status === 'REVOKED' || $assessment->is_suspension_expired)
                        <span class="status status-revoked" title="Telah melewati batas waktu 1 tahun kesempatan penyelesaian pembekuan surveilen">
                            Dicabut (Lewat 1 Tahun)
                        </span>
                    @elseif($assessment->is_submission_overdue)
                        <span class="status status-suspended" title="Toleransi pengisian asesmen telah terlampaui, sisa kesempatan penyelesaian 1 tahun">
                            Dibekukan (Toleransi Terlampaui)
                        </span>
                    @else
                        <x-status :value="$assessment->status" />
                    @endif
                </div>
            </div>

            <div class="lpk-header-actions">
                <a class="button secondary" href="{{ route('calendar.index', ['view' => 'month', 'date' => $assessment->start_at->toDateString(), 'highlight' => $assessment->id, 'selected' => 1]) }}" title="Lompat ke tanggal agenda di kalender">
                    <x-icon name="calendar" size="14" />
                    <span>Buka di Kalender</span>
                </a>
                @if(! $assessment->lpk || $assessment->lpk->canManage(auth()->user()))
                    <a class="button secondary" href="{{ route('assessments.edit', $assessment) }}">
                        <x-icon name="edit" size="14" />
                        <span>Ubah asesmen</span>
                    </a>
                @endif
            </div>
        </div>
    </div>

    {{-- Banner Notifikasi Status Pembekuan / Pencabutan --}}
    @if($assessment->status === 'REVOKED' || $assessment->is_suspension_expired)
        <div class="lpk-alert-callout">
            <div class="lpk-alert-callout-content">
                <div class="lpk-alert-callout-head">
                    <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
                    </svg>
                    <strong>Perhatian: Status Akreditasi Dicabut</strong>
                </div>
                <p class="lpk-alert-callout-desc">
                    Telah melewati batas waktu 1 tahun kesempatan penyelesaian masa pembekuan surveilen (batas akhir: <strong>{{ $assessment->suspension_resolution_deadline ? $assessment->suspension_resolution_deadline->format('d M Y') : '-' }}</strong>) tanpa penyelesaian.
                </p>
            </div>
        </div>
    @elseif($assessment->status === 'SUSPENDED' || $assessment->is_tp_overdue || $assessment->is_submission_overdue)
        <div class="lpk-alert-callout is-suspended">
            <div class="lpk-alert-callout-content">
                <div class="lpk-alert-callout-head">
                    <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor" aria-hidden="true">
                        <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
                    </svg>
                    <strong>Perhatian: Status Asesmen / Akreditasi Dibekukan</strong>
                </div>
                <p class="lpk-alert-callout-desc">
                    @if($assessment->is_submission_overdue)
                        Toleransi pengisian dokumen surveilen ({{ $assessment->submission_due_date ? $assessment->submission_due_date->format('d M Y') : '-' }}) telah terlampaui. Laboratorium diberikan masa tenggang toleransi 1 tahun untuk menuntaskan surveilen (batas akhir: <strong>{{ $assessment->suspension_resolution_deadline ? $assessment->suspension_resolution_deadline->format('d M Y') : '-' }}</strong>, sisa <strong>{{ $assessment->days_remaining_suspension }} hari</strong>). Apabila kewajiban tidak dipenuhi dalam 1 tahun, status akreditasi resmi dicabut.
                    @else
                        Batas waktu awal tindakan perbaikan ({{ $assessment->effective_tp_due_date ? $assessment->effective_tp_due_date->format('d M Y') : '-' }}) telah terlampaui dan belum dinyatakan memenuhi. Laboratorium wajib segera menindaklanjuti temuan atau mengajukan permohonan perpanjangan waktu (+1 bulan) bersyarat ada progres perbaikan nyata.
                    @endif
                </p>
            </div>
        </div>
    @endif

    {{-- 2-Column Responsive Layout --}}
    <div class="lpk-show-grid">
        {{-- KOLOM KIRI: Informasi Pelaksanaan, Tim Asesor, Evaluasi EHA & SK KAN --}}
        <div class="lpk-show-col">
            {{-- KARTU 1: Detail Pelaksanaan Asesmen --}}
            <div class="lpk-form-card">
                <div class="lpk-form-card-header">
                    <div class="lpk-card-icon-wrap icon-wrap-blue">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/>
                            <line x1="16" y1="2" x2="16" y2="6"/>
                            <line x1="8" y1="2" x2="8" y2="6"/>
                            <line x1="3" y1="10" x2="21" y2="10"/>
                        </svg>
                    </div>
                    <div class="lpk-card-header-text">
                        <h2>Informasi Agenda &amp; Pelaksanaan</h2>
                        <p>Tanggal pelaksanaan, lokasi kunjungan, dan status pelaksanaan asesmen.</p>
                    </div>
                </div>

                <div class="lpk-meta-grid">
                    <div class="lpk-meta-item full-width">
                        <span class="lpk-meta-label">LPK Terakreditasi</span>
                        <div class="lpk-meta-value">
                            <a href="{{ route('lpks.show', $assessment->lpk) }}" style="font-weight: 600; color: var(--primary, #0284c7); display: inline-flex; align-items: center; flex-wrap: wrap; word-break: break-word; gap: 4px; text-decoration: none;">
                                <span>{{ $assessment->lpk->registration_number }} &middot; {{ $assessment->lpk->name }}</span>
                                <svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" style="flex-shrink: 0;"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" /></svg>
                            </a>
                        </div>
                    </div>

                    <div class="lpk-meta-item">
                        <span class="lpk-meta-label">Status Pelaksanaan</span>
                        <div class="lpk-meta-value" style="display: flex; align-items: center; flex-wrap: wrap; gap: 6px;">
                            <x-status :value="$assessment->status" />
                        </div>
                    </div>

                    <div class="lpk-meta-item">
                        <span class="lpk-meta-label">Jenis Asesmen (KAN U-01)</span>
                        <div class="lpk-meta-value">
                            {{ $assessment->assessment_type_label }}
                        </div>
                    </div>

                    <div class="lpk-meta-item full-width">
                        <span class="lpk-meta-label">Tanggal Pelaksanaan</span>
                        <div class="lpk-meta-value">
                            @if($assessment->start_at->isSameDay($assessment->end_at))
                                <strong>{{ $assessment->start_at->format('d M Y') }}</strong>
                            @else
                                <strong>{{ $assessment->start_at->format('d M Y') }}</strong> sampai <strong>{{ $assessment->end_at->format('d M Y') }}</strong>
                            @endif
                        </div>
                    </div>

                    <div class="lpk-meta-item">
                        <span class="lpk-meta-label">Lokasi Kunjungan</span>
                        <div class="lpk-meta-value">
                            {{ $assessment->location ?: 'Belum diisi' }}
                        </div>
                    </div>

                    <div class="lpk-meta-item">
                        <span class="lpk-meta-label">Tim Asesmen</span>
                        <div class="lpk-meta-value">
                            {{ $assessment->assessment_team ?: $assessment->lead_assessor ?: 'Belum diisi' }}
                        </div>
                    </div>

                    @if($assessment->submission_due_date && $assessment->status !== 'COMPLETED')
                        <div class="lpk-meta-item full-width" style="border-top: 1px dashed var(--line); padding-top: 10px; margin-top: 4px;">
                            <span class="lpk-meta-label">Toleransi Pengisian</span>
                            <div class="lpk-meta-value">
                                <strong>Maksimal {{ $assessment->submission_due_date->format('d M Y') }}</strong>
                                <span style="color: var(--muted); font-size: 12px; margin-left: 4px;">(masa pengisian bulan {{ str_contains(strtolower($assessment->assessment_type ?: ''), 's2') || str_contains(strtolower($assessment->title ?: ''), 's2') ? '36-39' : '15-18' }} siklus KAN)</span>
                                @if($assessment->status === 'REVOKED' || $assessment->is_suspension_expired)
                                    <div style="margin-top: 4px; font-size: 12px; color: var(--danger-text, #ef4444); font-weight: 600;">
                                        &bull; Batas 1 tahun kesempatan pembekuan ({{ $assessment->suspension_resolution_deadline?->format('d M Y') }}) telah berakhir: Akreditasi dicabut.
                                    </div>
                                @elseif($assessment->is_submission_overdue || $assessment->status === 'SUSPENDED')
                                    <div style="margin-top: 4px; font-size: 12px; color: var(--terracotta, #c084fc); font-weight: 600;">
                                        &bull; Kesempatan penyelesaian pembekuan: 1 tahun s/d {{ $assessment->suspension_resolution_deadline?->format('d M Y') }} (sisa {{ $assessment->days_remaining_suspension }} hari).
                                    </div>
                                @endif
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- KARTU 2: Laporan Asesmen, Evaluasi Hasil Asesmen (EHA) & SK KAN --}}
            <div class="lpk-form-card">
                <div class="lpk-form-card-header">
                    <div class="lpk-card-icon-wrap icon-wrap-rose">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                            <line x1="16" y1="17" x2="8" y2="17"/>
                        </svg>
                    </div>
                    <div class="lpk-card-header-text">
                        <h2>Laporan Asesmen, Evaluasi EHA &amp; SK KAN</h2>
                        <p>Dokumen pelaporan asesmen, evaluasi panitia teknis, dan keputusan akreditasi.</p>
                    </div>
                </div>

                <div class="lpk-meta-grid">
                    <div class="lpk-meta-item">
                        <span class="lpk-meta-label">Laporan Asesmen</span>
                        <div class="lpk-meta-value">
                            @if($assessment->report_date)
                                Diterbitkan pada <strong>{{ $assessment->report_date->format('d M Y') }}</strong>
                            @else
                                <span style="color: var(--muted);">Belum diterbitkan</span>
                            @endif
                        </div>
                    </div>

                    <div class="lpk-meta-item">
                        <span class="lpk-meta-label">Evaluasi Hasil Asesmen (EHA)</span>
                        <div class="lpk-meta-value">
                            @if($assessment->eha_date || ($assessment->eha_status && $assessment->eha_status !== 'BELUM_EHA') || $assessment->eha_notes)
                                <div style="display: flex; align-items: center; flex-wrap: wrap; gap: 6px;">
                                    <span class="badge-tp badge-tp-neutral">{{ $assessment->eha_status_label }}</span>
                                    @if($assessment->eha_date)
                                        <span style="color: var(--muted); font-size: 12px;">(Tanggal: {{ $assessment->eha_date->format('d M Y') }})</span>
                                    @endif
                                </div>
                                @if($assessment->eha_notes)
                                    <div style="font-size: 12.5px; color: var(--muted); margin-top: 4px; line-height: 1.45;">{{ $assessment->eha_notes }}</div>
                                @endif
                            @else
                                <span style="color: var(--muted);">Belum EHA</span>
                            @endif
                        </div>
                    </div>

                    <div class="lpk-meta-item full-width" style="border-top: 1px dashed var(--line); padding-top: 10px; margin-top: 4px;">
                        <span class="lpk-meta-label">Surat Keputusan (SK)</span>
                        <div class="lpk-meta-value">
                            @if($assessment->sk_number || $assessment->sk_date)
                                <div style="display: flex; align-items: center; flex-wrap: wrap; gap: 8px;">
                                    @if($assessment->sk_number)
                                        <strong style="color: var(--green); font-size: 14px;">{{ $assessment->sk_number }}</strong>
                                    @endif
                                    @if($assessment->sk_date)
                                        <span style="color: var(--muted); font-size: 12.5px;">(Terbit: {{ $assessment->sk_date->format('d M Y') }})</span>
                                    @endif
                                    @if($assessment->sk_lead_time_label)
                                        <span class="badge-tp badge-tp-neutral" style="font-size: 11px;" title="Rentang waktu pelaksanaan asesmen lapangan hingga terbit SK KAN">
                                            Rentang Proses: {{ $assessment->sk_lead_time_label }}
                                        </span>
                                    @endif
                                </div>
                            @else
                                <span style="color: var(--muted);">Belum terbit</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- KARTU 3: Catatan Pelaksanaan --}}
            <div class="lpk-form-card">
                <div class="lpk-form-card-header">
                    <div class="lpk-card-icon-wrap icon-wrap-slate">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M21 15a2 2 0 0 1-2 2H7l-4 4V5a2 2 0 0 1 2-2h14a2 2 0 0 1 2 2z"/>
                        </svg>
                    </div>
                    <div class="lpk-card-header-text">
                        <h2>Catatan</h2>
                        <p>Catatan khusus pelaksanaan agenda asesmen di lapangan.</p>
                    </div>
                </div>

                <div style="background: var(--surface-subtle, #f8fafc); border: 1px solid var(--line); border-radius: 8px; padding: 12px 14px; font-size: 13px; color: var(--ink); line-height: 1.55; white-space: pre-line;">{{ $assessment->notes ?: 'Belum ada catatan.' }}</div>
            </div>
        </div>

        {{-- KOLOM KANAN: Tindakan Perbaikan & Verifikasi (TP & VTP) Standar KAN --}}
        <div class="lpk-show-col">
            @include('assessments.partials.tp-tracking')
        </div>
    </div>
</div>

@include('assessments.partials.modal-tp-tracking')
@endsection
