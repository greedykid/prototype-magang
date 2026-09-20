@extends('layouts.app')

@section('title', $assessment->title . ' | SIMASADI')

@section('content')
<x-page-header
    :backUrl="route('assessments.index')"
    backText="Semua asesmen"
    :title="$assessment->title"
    :subtitle="$assessment->lpk->name"
>
    @if(auth()->user()?->hasRole(['admin', 'staf']))
        <a class="button secondary" href="{{ route('assessments.edit', $assessment) }}">
            <x-icon name="edit" size="16" />
            <span>Ubah asesmen</span>
        </a>
    @endif
</x-page-header>

<section class="panel detail-list">
    <div>
        <dt>Status</dt>
        <dd><x-status :value="$assessment->status" /></dd>
    </div>
    <div>
        <dt>Jenis</dt>
        <dd>{{ $assessment->assessment_type }}</dd>
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
</section>

@include('assessments.partials.cost-reporting')

@include('assessments.partials.modals')

@endsection
