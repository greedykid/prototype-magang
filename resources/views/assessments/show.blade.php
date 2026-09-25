@extends('layouts.app')

@section('title', $assessment->title . ' | SIMASADI')

@section('content')
<div class="page-heading" style="display: block; margin-bottom: 24px;">
    <div>
        <a class="back-link" href="{{ route('assessments.index') }}">Semua asesmen</a>
        <h1 style="margin-top: 8px; margin-bottom: 6px; font-size: 23px; line-height: 1.35; font-weight: 700; word-break: break-word;">
            {{ $assessment->title }}
        </h1>
        <p class="lede" style="margin-bottom: 0;">
            {{ $assessment->lpk->registration_number }} &middot; {{ $assessment->lpk->name }}
        </p>
    </div>
    <div class="heading-actions" style="margin-top: 14px;">
        <a class="button secondary" href="{{ route('assessments.edit', $assessment) }}">
            <x-icon name="edit" size="16" />
            <span>Ubah asesmen</span>
        </a>
    </div>
</div>

@if($assessment->status === 'REVOKED' || $assessment->is_suspension_expired)
    <div style="margin-bottom: 20px; padding: 14px 18px; background: #fef2f2; border: 1.5px solid #fca5a5; border-radius: 8px; display: flex; align-items: center; gap: 14px;">
        <div style="width: 36px; height: 36px; border-radius: 50%; background: #fee2e2; color: #991b1b; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 18px; font-weight: 700;">
            !
        </div>
        <div>
            <strong style="color: #991b1b; font-size: 14px; display: block;">Perhatian: Status Akreditasi Dicabut</strong>
            <span style="color: #b91c1c; font-size: 12.5px;">Telah melewati batas waktu 1 tahun kesempatan penyelesaian masa pembekuan surveilen (batas akhir: {{ $assessment->suspension_resolution_deadline ? $assessment->suspension_resolution_deadline->format('d M Y') : '-' }}) tanpa penyelesaian.</span>
        </div>
    </div>
@elseif($assessment->status === 'SUSPENDED' || $assessment->is_tp_overdue || $assessment->is_submission_overdue)
    <div style="margin-bottom: 20px; padding: 14px 18px; background: #faf5ff; border: 1.5px solid #d8b4fe; border-radius: 8px; display: flex; align-items: center; gap: 14px;">
        <div style="width: 36px; height: 36px; border-radius: 50%; background: #f3e8ff; color: #6b21a8; display: flex; align-items: center; justify-content: center; flex-shrink: 0; font-size: 18px; font-weight: 700;">
            !
        </div>
        <div>
            <strong style="color: #581c87; font-size: 14px; display: block;">Perhatian: Status Asesmen / Akreditasi Dibekukan</strong>
            @if($assessment->is_submission_overdue)
                <span style="color: #6b21a8; font-size: 12.5px;">
                    Toleransi pengisian asesmen ({{ $assessment->submission_due_date ? $assessment->submission_due_date->format('d M Y') : '-' }}) telah terlampaui. LPK diberikan kesempatan 1 tahun untuk menyelesaikan surveilen (batas akhir penyelesaian: <strong>{{ $assessment->suspension_resolution_deadline ? $assessment->suspension_resolution_deadline->format('d M Y') : '-' }}</strong>, sisa {{ $assessment->days_remaining_suspension }} hari). Jika melewati batas waktu tersebut, maka status akreditasi akan dicabut.
                </span>
            @else
                <span style="color: #6b21a8; font-size: 12.5px;">Batas waktu awal (SLA) tindakan perbaikan ({{ $assessment->effective_tp_due_date ? $assessment->effective_tp_due_date->format('d M Y') : '-' }}) telah terlampaui dan belum dinyatakan memenuhi.</span>
            @endif
        </div>
    </div>
@endif

<section class="panel detail-list">
    <div>
        <dt>LPK Terakreditasi</dt>
        <dd>
            <a href="{{ route('lpks.show', $assessment->lpk) }}" style="font-weight: 600; color: var(--primary, #0284c7); display: inline-flex; align-items: center; gap: 6px;">
                <span>{{ $assessment->lpk->registration_number }} &middot; {{ $assessment->lpk->name }}</span>
                <span aria-hidden="true">&rarr;</span>
            </a>
        </dd>
    </div>
    <div>
        <dt>Status</dt>
        <dd>
            <x-status :value="$assessment->status" />
            @if($assessment->status === 'REVOKED' || $assessment->is_suspension_expired)
                <span class="badge-tp badge-tp-danger" style="margin-left: 6px;" title="Telah melewati batas waktu 1 tahun kesempatan penyelesaian pembekuan surveilen">
                    Dicabut (Lewat 1 Tahun)
                </span>
            @elseif($assessment->is_submission_overdue)
                <span class="badge-tp badge-tp-suspended" style="margin-left: 6px;" title="Toleransi pengisian asesmen telah terlampaui, sisa kesempatan penyelesaian 1 tahun">
                    Dibekukan (Toleransi Terlampaui)
                </span>
            @endif
        </dd>
    </div>
    @if($assessment->submission_due_date && $assessment->status !== 'COMPLETED')
        <div>
            <dt>Toleransi Pengisian</dt>
            <dd>
                <div>
                    Maksimal {{ $assessment->submission_due_date->format('d M Y') }}
                    <small style="color: var(--muted); font-size: 12px;">(akhir bulan dan tahun yang sama dari waktu kunjungan)</small>
                </div>
                @if($assessment->status === 'REVOKED' || $assessment->is_suspension_expired)
                    <div style="margin-top: 6px; font-size: 12.5px;">
                        <span style="color: #991b1b; font-weight: 600;">
                            &bull; Batas 1 tahun kesempatan pembekuan ({{ $assessment->suspension_resolution_deadline?->format('d M Y') }}) telah berakhir: Akreditasi dicabut.
                        </span>
                    </div>
                @elseif($assessment->is_submission_overdue || $assessment->status === 'SUSPENDED')
                    <div style="margin-top: 6px; font-size: 12.5px;">
                        <span style="color: #6b21a8; font-weight: 600;">
                            &bull; Kesempatan penyelesaian pembekuan: 1 tahun s/d {{ $assessment->suspension_resolution_deadline?->format('d M Y') }} (sisa {{ $assessment->days_remaining_suspension }} hari).
                        </span>
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
        <dt>Waktu</dt>
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
                <div>
                    <span class="badge-tp badge-tp-neutral">{{ $assessment->eha_status_label }}</span>
                    @if($assessment->eha_date)
                        <span style="color: var(--muted); font-size: 13px; margin-left: 6px;">(Tanggal: {{ $assessment->eha_date->format('d M Y') }})</span>
                    @endif
                </div>
                @if($assessment->eha_notes)
                    <div style="font-size: 13px; color: var(--muted); margin-top: 4px;">{{ $assessment->eha_notes }}</div>
                @endif
            </dd>
        </div>
    @endif
    <div>
        <dt>Catatan</dt>
        <dd>{{ $assessment->notes ?: 'Belum ada catatan.' }}</dd>
    </div>
    @if($assessment->sk_number || $assessment->sk_date)
        <div>
            <dt>Surat Keputusan (SK)</dt>
            <dd>
                @if($assessment->sk_number)
                    <strong style="color: #15803d;">{{ $assessment->sk_number }}</strong>
                @endif
                @if($assessment->sk_date)
                    <span style="color: var(--muted); font-size: 13px;">(Terbit: {{ $assessment->sk_date->format('d M Y') }})</span>
                @endif
                @if($assessment->sk_lead_time_label)
                    <div style="margin-top: 4px;">
                        <span class="badge-tp badge-tp-neutral" style="font-size: 11px;" title="Rentang waktu pelaksanaan asesmen lapangan hingga terbit SK KAN">
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
