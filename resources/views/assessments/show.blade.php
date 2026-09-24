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
    @if(auth()->user()?->isAdmin())
        <div style="display: flex; gap: 10px; align-items: center; flex-wrap: wrap; margin-top: 14px;">
            <a class="button secondary" href="{{ route('assessments.edit', $assessment) }}" style="display: inline-flex; align-items: center; gap: 6px; font-size: 13px; padding: 7px 16px;">
                <x-icon name="edit" size="16" />
                <span>Ubah asesmen</span>
            </a>
        </div>
    @endif
</div>

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
            @if($assessment->is_submission_overdue)
                <span class="badge-tp badge-tp-danger" style="margin-left: 6px;" title="Toleransi pengisian asesmen (akhir bulan dan tahun yang sama dari waktu kunjungan) telah terlampaui">
                    Lewat Jadwal Asesmen
                </span>
            @endif
        </dd>
    </div>
    @if($assessment->submission_due_date && $assessment->status !== 'COMPLETED')
        <div>
            <dt>Toleransi Pengisian</dt>
            <dd>
                Maksimal {{ $assessment->submission_due_date->format('d M Y') }}
                <small style="color: var(--muted); font-size: 12px;">(akhir bulan dan tahun yang sama dari waktu kunjungan)</small>
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
        <dt>Lead assessor</dt>
        <dd>{{ $assessment->lead_assessor ?: 'Belum diisi' }}</dd>
    </div>
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
