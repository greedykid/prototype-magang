@extends('layouts.app')

@section('title', $assessment->title . ' | SIMASADI')

@section('content')
<div class="page-heading">
    <div>
        <a class="back-link" href="{{ route('assessments.index') }}">Semua asesmen</a>
        <h1 style="margin-top: 8px; margin-bottom: 6px; font-size: 23px; line-height: 1.35; font-weight: 700; word-break: break-word;">
            {{ $assessment->title }}
        </h1>
        <p class="lede" style="margin-bottom: 0;">
            {{ $assessment->lpk->registration_number }} &middot; {{ $assessment->lpk->name }}
        </p>
    </div>
    <div class="page-heading-actions" style="display: flex; gap: 8px;">
        <a class="button secondary" href="{{ route('calendar.index', ['view' => 'month', 'date' => $assessment->start_at->toDateString(), 'highlight' => $assessment->id, 'selected' => 1]) }}" title="Lompat ke tanggal agenda di kalender">
            <x-icon name="calendar" size="16" />
            <span>Buka di Kalender</span>
        </a>
        <a class="button secondary" href="{{ route('assessments.edit', $assessment) }}">
            <x-icon name="edit" size="16" />
            <span>Ubah asesmen</span>
        </a>
    </div>
</div>

@if($assessment->status === 'REVOKED' || $assessment->is_suspension_expired)
    <div role="alert" style="margin-bottom: 24px; padding: 16px 20px; background: #fef2f2; border: 1.5px solid #fca5a5; border-radius: 10px; display: flex; align-items: flex-start; gap: 14px;">
        <div style="width: 36px; height: 36px; border-radius: 8px; background: #fee2e2; color: #991b1b; display: flex; align-items: center; justify-content: center; flex-shrink: 0;" aria-hidden="true">
            <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 9v2m0 4h.01m-6.938 4h13.856c1.54 0 2.502-1.667 1.732-3L13.732 4c-.77-1.333-2.694-1.333-3.464 0L3.34 16c-.77 1.333.192 3 1.732 3z"/>
            </svg>
        </div>
        <div>
            <strong style="color: #991b1b; font-size: 14.5px; display: block; margin-bottom: 3px;">Perhatian: Status Akreditasi Dicabut</strong>
            <span style="color: #b91c1c; font-size: 13px; line-height: 1.5; display: block;">
                Telah melewati batas waktu 1 tahun kesempatan penyelesaian masa pembekuan surveilen (batas akhir: <strong>{{ $assessment->suspension_resolution_deadline ? $assessment->suspension_resolution_deadline->format('d M Y') : '-' }}</strong>) tanpa penyelesaian.
            </span>
        </div>
    </div>
@elseif($assessment->status === 'SUSPENDED' || $assessment->is_tp_overdue || $assessment->is_submission_overdue)
    <div role="alert" style="margin-bottom: 24px; padding: 16px 20px; background: #faf5ff; border: 1.5px solid #d8b4fe; border-radius: 10px; display: flex; align-items: flex-start; gap: 14px;">
        <div style="width: 36px; height: 36px; border-radius: 8px; background: #f3e8ff; color: #6b21a8; display: flex; align-items: center; justify-content: center; flex-shrink: 0;" aria-hidden="true">
            <svg width="20" height="20" fill="none" viewBox="0 0 24 24" stroke="currentColor">
                <path stroke-linecap="round" stroke-linejoin="round" stroke-width="2" d="M12 8v4m0 4h.01M21 12a9 9 0 11-18 0 9 9 0 0118 0z"/>
            </svg>
        </div>
        <div>
            <strong style="color: #581c87; font-size: 14.5px; display: block; margin-bottom: 3px;">Perhatian: Status Asesmen / Akreditasi Dibekukan</strong>
            @if($assessment->is_submission_overdue)
                <span style="color: #6b21a8; font-size: 13px; line-height: 1.5; display: block;">
                    Toleransi pengisian dokumen surveilen ({{ $assessment->submission_due_date ? $assessment->submission_due_date->format('d M Y') : '-' }}) telah terlampaui. Laboratorium diberikan masa tenggang toleransi 1 tahun untuk menuntaskan surveilen (batas akhir: <strong>{{ $assessment->suspension_resolution_deadline ? $assessment->suspension_resolution_deadline->format('d M Y') : '-' }}</strong>, sisa <strong>{{ $assessment->days_remaining_suspension }} hari</strong>). Apabila kewajiban tidak dipenuhi dalam 1 tahun, status akreditasi resmi dicabut.
                </span>
            @else
                <span style="color: #6b21a8; font-size: 13px; line-height: 1.5; display: block;">
                    Batas waktu awal (SLA) tindakan perbaikan ({{ $assessment->effective_tp_due_date ? $assessment->effective_tp_due_date->format('d M Y') : '-' }}) telah terlampaui dan belum dinyatakan memenuhi. Laboratorium wajib segera menindaklanjuti temuan atau mengajukan permohonan perpanjangan waktu (+1 bulan) bersyarat ada progres perbaikan nyata.
                </span>
            @endif
        </div>
    </div>
@endif

<section class="panel detail-list">
    <div>
        <dt>LPK Terakreditasi</dt>
        <dd>
            <a href="{{ route('lpks.show', $assessment->lpk) }}" style="font-weight: 600; color: var(--primary, #0284c7); display: inline-flex; align-items: center; gap: 6px; text-decoration: none;">
                <span>{{ $assessment->lpk->registration_number }} &middot; {{ $assessment->lpk->name }}</span>
                <svg width="14" height="14" viewBox="0 0 20 20" fill="currentColor" aria-hidden="true" style="flex-shrink: 0;"><path fill-rule="evenodd" d="M7.21 14.77a.75.75 0 01.02-1.06L11.168 10 7.23 6.29a.75.75 0 111.04-1.08l4.5 4.25a.75.75 0 010 1.08l-4.5 4.25a.75.75 0 01-1.06-.02z" clip-rule="evenodd" /></svg>
            </a>
        </dd>
    </div>
    <div>
        <dt>Status Pelaksanaan</dt>
        <dd style="display: flex; align-items: center; flex-wrap: wrap; gap: 8px;">
            <x-status :value="$assessment->status" />
            @if($assessment->status === 'REVOKED' || $assessment->is_suspension_expired)
                <span class="badge-tp badge-tp-danger" title="Telah melewati batas waktu 1 tahun kesempatan penyelesaian pembekuan surveilen">
                    Dicabut (Lewat 1 Tahun)
                </span>
            @elseif($assessment->is_submission_overdue)
                <span class="badge-tp badge-tp-suspended" title="Toleransi pengisian asesmen telah terlampaui, sisa kesempatan penyelesaian 1 tahun">
                    Dibekukan (Toleransi Terlampaui)
                </span>
            @endif
        </dd>
    </div>
    @if($assessment->submission_due_date && $assessment->status !== 'COMPLETED')
        <div>
            <dt>Toleransi Pengisian</dt>
            <dd>
                <div style="font-size: 13.5px; font-weight: 600; color: var(--text);">
                    Maksimal {{ $assessment->submission_due_date->format('d M Y') }}
                    <span style="color: var(--muted); font-size: 12px; font-weight: normal; margin-left: 4px;">(akhir bulan dari waktu kunjungan)</span>
                </div>
                @if($assessment->status === 'REVOKED' || $assessment->is_suspension_expired)
                    <div style="margin-top: 6px; font-size: 12.5px; color: #991b1b; font-weight: 600;">
                        &bull; Batas 1 tahun kesempatan pembekuan ({{ $assessment->suspension_resolution_deadline?->format('d M Y') }}) telah berakhir: Akreditasi dicabut.
                    </div>
                @elseif($assessment->is_submission_overdue || $assessment->status === 'SUSPENDED')
                    <div style="margin-top: 6px; font-size: 12.5px; color: #6b21a8; font-weight: 600;">
                        &bull; Kesempatan penyelesaian pembekuan: 1 tahun s/d {{ $assessment->suspension_resolution_deadline?->format('d M Y') }} (sisa {{ $assessment->days_remaining_suspension }} hari).
                    </div>
                @endif
            </dd>
        </div>
    @endif
    <div>
        <dt>Jenis (KAN U-01)</dt>
        <dd>{{ $assessment->assessment_type_label }}</dd>
    </div>
    <div>
        <dt>Waktu Pelaksanaan</dt>
        <dd>{{ $assessment->start_at->format('d M Y, H:i') }} sampai {{ $assessment->end_at->format('d M Y, H:i') }} WIB</dd>
    </div>
    <div>
        <dt>Lokasi</dt>
        <dd>{{ $assessment->location ?: 'Belum diisi' }}</dd>
    </div>
    <div>
        <dt>Tim Asesmen</dt>
        <dd>{{ $assessment->assessment_team ?: $assessment->lead_assessor ?: 'Belum diisi' }}</dd>
    </div>
    @if($assessment->report_date)
        <div>
            <dt>Laporan Asesmen</dt>
            <dd>Diterbitkan pada {{ $assessment->report_date->format('d M Y') }}</dd>
        </div>
    @endif
    @if($assessment->eha_date || ($assessment->eha_status && $assessment->eha_status !== 'BELUM_EHA') || $assessment->eha_notes)
        <div>
            <dt>Evaluasi Hasil Asesmen (EHA)</dt>
            <dd>
                <div style="display: flex; align-items: center; flex-wrap: wrap; gap: 8px;">
                    <span class="badge-tp badge-tp-neutral">{{ $assessment->eha_status_label }}</span>
                    @if($assessment->eha_date)
                        <span style="color: var(--muted); font-size: 13px;">(Tanggal: {{ $assessment->eha_date->format('d M Y') }})</span>
                    @endif
                </div>
                @if($assessment->eha_notes)
                    <div style="font-size: 13px; color: var(--muted); margin-top: 4px; line-height: 1.45;">{{ $assessment->eha_notes }}</div>
                @endif
            </dd>
        </div>
    @endif
    <div>
        <dt>Catatan</dt>
        <dd class="pre-line">{{ $assessment->notes ?: 'Belum ada catatan.' }}</dd>
    </div>
    @if($assessment->sk_number || $assessment->sk_date)
        <div>
            <dt>Surat Keputusan (SK)</dt>
            <dd>
                <div style="display: flex; align-items: center; flex-wrap: wrap; gap: 8px;">
                    @if($assessment->sk_number)
                        <strong style="color: #15803d; font-size: 14px;">{{ $assessment->sk_number }}</strong>
                    @endif
                    @if($assessment->sk_date)
                        <span style="color: var(--muted); font-size: 13px;">(Terbit: {{ $assessment->sk_date->format('d M Y') }})</span>
                    @endif
                </div>
                @if($assessment->sk_lead_time_label)
                    <div style="margin-top: 6px;">
                        <span class="badge-tp badge-tp-neutral" style="font-size: 11.5px;" title="Rentang waktu pelaksanaan asesmen lapangan hingga terbit SK KAN">
                            Rentang Proses: {{ $assessment->sk_lead_time_label }}
                        </span>
                    </div>
                @endif
            </dd>
        </div>
    @endif
</section>

@include('assessments.partials.tp-tracking')

@include('assessments.partials.cost-reporting')

@include('assessments.partials.modals')

@endsection
