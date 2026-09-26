@extends('layouts.app')

@section('title', 'Program Asesmen | SIMASADI')

@section('content')
<x-page-header
    title="Program asesmen"
    subtitle="Jadwal dan progres asesmen semua LPK."
>
    <button type="button" class="button secondary" onclick="window.openModal('modal-import-assessments')">
        <x-icon name="upload" size="16" />
        <span>Import Asesmen</span>
    </button>
    <button type="button" class="button secondary" onclick="window.openModal('modal-sheets-sync-assessments')">
        <x-icon name="sheets" size="16" style="color: #0f9d58;" />
        <span>Google Sheets & Ekspor</span>
    </button>
    <a class="button primary" href="{{ route('assessments.create') }}">
        <x-icon name="plus" size="16" />
        <span>Tambah asesmen</span>
    </a>
</x-page-header>

<div class="assessment-workbench-tabs" style="display: flex; gap: 8px; margin-bottom: 20px; border-bottom: 1px solid var(--line); padding-bottom: 12px; flex-wrap: wrap;">
    <a href="{{ route('lpks.index') }}" class="button secondary" style="font-size: 13px; padding: 6px 14px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
        <x-icon name="lpks" size="15" />
        <span>Master Data LPK</span>
    </a>
    <a href="{{ route('assessments.index') }}" class="button primary" style="font-size: 13px; padding: 6px 14px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
        <x-icon name="assessments" size="15" />
        <span>Proses yang Sedang Berjalan (S1 sampai RA)</span>
    </a>
</div>

<section class="panel">
    <form id="assessment-filter-form" class="table-filters" method="GET" action="{{ route('assessments.index') }}" data-partial-filter="true" data-target="#assessment-table-container">
        <div class="table-filter-grid">
            <label>Cari agenda<input type="search" name="search" value="{{ $search }}" placeholder="Judul agenda" autocomplete="off"></label>
            <label>LPK<select name="lpk_id"><option value="">Semua LPK</option>@foreach($lpks as $lpk)<option value="{{ $lpk->id }}" @selected($lpkId === $lpk->id)>{{ $lpk->registration_number }} - {{ $lpk->name }}</option>@endforeach</select></label>
            <label>Jenis<select name="assessment_type"><option value="">Semua jenis (KAN U-01)</option>@foreach($assessmentTypes as $key => $label)<option value="{{ $key }}" @selected($assessmentType === $key)>{{ $label }}</option>@endforeach</select></label>
            <label>Status Pelaksanaan<select name="status"><option value="">Semua status</option>@foreach(['PLANNED' => 'Direncanakan', 'SCHEDULED' => 'Terjadwal', 'IN_PROGRESS' => 'Sedang Berlangsung', 'SUSPENDED' => 'Dibekukan', 'COMPLETED' => 'Selesai', 'CANCELLED' => 'Dibatalkan'] as $value => $label)<option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>@endforeach</select></label>
            <label>Status TP (SLA KAN)
                <select name="tp_status">
                    <option value="">Semua status TP</option>
                    <option value="NONE" @selected(($tpFilter ?? '') === 'NONE')>Nihil / Belum Ada Temuan</option>
                    <option value="ACTIVE" @selected(($tpFilter ?? '') === 'ACTIVE')>Sedang Perbaikan / Verifikasi</option>
                    <option value="DUE_SOON" @selected(($tpFilter ?? '') === 'DUE_SOON')>Jatuh Tempo (&le; 14 Hari)</option>
                    <option value="OVERDUE" @selected(($tpFilter ?? '') === 'OVERDUE')>Melewati Batas SLA (Dibekukan)</option>
                    <option value="SATISFIED" @selected(($tpFilter ?? '') === 'SATISFIED')>Dinyatakan Memenuhi</option>
                </select>
            </label>
            <label>Mulai dari<input type="date" name="start_from" value="{{ $startFrom }}"></label>
            <label>Mulai sampai<input type="date" name="start_to" value="{{ $startTo }}"></label>
        </div>
        <div class="table-filter-actions" id="assessment-filter-actions" style="margin-top: 8px;">
            <span id="assessment-filter-loading" class="filter-live-indicator" style="display: none; align-items: center; gap: 8px; font-size: 12.5px; color: var(--muted);" aria-live="polite">
                <span class="filter-live-spinner"></span>
                <span>Memperbarui data...</span>
            </span>
        </div>
    </form>

    @php
        $activeFiltersCount = 0;
        if ($search) $activeFiltersCount++;
        if ($lpkId) $activeFiltersCount++;
        if ($assessmentType) $activeFiltersCount++;
        if ($status) $activeFiltersCount++;
        if (!empty($tpFilter)) $activeFiltersCount++;
        if ($startFrom) $activeFiltersCount++;
        if ($startTo) $activeFiltersCount++;
    @endphp

    @if($activeFiltersCount > 0)
        @php
            $statusLabels = [
                'PLANNED' => 'Direncanakan',
                'SCHEDULED' => 'Terjadwal',
                'IN_PROGRESS' => 'Sedang Berlangsung',
                'SUSPENDED' => 'Dibekukan',
                'COMPLETED' => 'Selesai',
                'CANCELLED' => 'Dibatalkan',
            ];
            $tpLabels = [
                'NONE' => 'Nihil / Belum Ada Temuan',
                'ACTIVE' => 'Sedang Perbaikan / Verifikasi',
                'DUE_SOON' => 'Jatuh Tempo (≤ 14 Hari)',
                'OVERDUE' => 'Melewati Batas SLA (Dibekukan)',
                'SATISFIED' => 'Dinyatakan Memenuhi',
            ];
            $selectedLpk = $lpkId ? $lpks->firstWhere('id', $lpkId) : null;
        @endphp
        <div class="active-filters-bar">
            <span class="active-filters-heading">FILTER AKTIF</span>
            <div class="active-filters-chips">
                @if($search)
                    <span class="filter-chip" data-field="search" title="Cari: {{ $search }}">
                        <span class="filter-chip-text">Cari: {{ $search }}</span>
                        <button type="button" class="filter-chip-remove" aria-label="Hapus filter Cari">&times;</button>
                    </span>
                @endif
                @if($selectedLpk)
                    <span class="filter-chip" data-field="lpk_id" title="LPK: {{ $selectedLpk->registration_number }} - {{ $selectedLpk->name }}">
                        <span class="filter-chip-text">LPK: {{ $selectedLpk->registration_number }} - {{ $selectedLpk->name }}</span>
                        <button type="button" class="filter-chip-remove" aria-label="Hapus filter LPK">&times;</button>
                    </span>
                @endif
                @if($assessmentType)
                    <span class="filter-chip" data-field="assessment_type" title="Jenis: {{ $assessmentTypes[$assessmentType] ?? $assessmentType }}">
                        <span class="filter-chip-text">Jenis: {{ $assessmentTypes[$assessmentType] ?? $assessmentType }}</span>
                        <button type="button" class="filter-chip-remove" aria-label="Hapus filter Jenis">&times;</button>
                    </span>
                @endif
                @if($status)
                    <span class="filter-chip" data-field="status" title="Status: {{ $statusLabels[$status] ?? $status }}">
                        <span class="filter-chip-text">Status: {{ $statusLabels[$status] ?? $status }}</span>
                        <button type="button" class="filter-chip-remove" aria-label="Hapus filter Status">&times;</button>
                    </span>
                @endif
                @if(!empty($tpFilter))
                    <span class="filter-chip" data-field="tp_status" title="Status TP: {{ $tpLabels[$tpFilter] ?? $tpFilter }}">
                        <span class="filter-chip-text">Status TP: {{ $tpLabels[$tpFilter] ?? $tpFilter }}</span>
                        <button type="button" class="filter-chip-remove" aria-label="Hapus filter Status TP">&times;</button>
                    </span>
                @endif
                @if($startFrom)
                    <span class="filter-chip" data-field="start_from" title="Mulai Dari: {{ \Illuminate\Support\Carbon::parse($startFrom)->translatedFormat('d M Y') }}">
                        <span class="filter-chip-text">Mulai Dari: {{ \Illuminate\Support\Carbon::parse($startFrom)->translatedFormat('d M Y') }}</span>
                        <button type="button" class="filter-chip-remove" aria-label="Hapus filter Mulai Dari">&times;</button>
                    </span>
                @endif
                @if($startTo)
                    <span class="filter-chip" data-field="start_to" title="Mulai Sampai: {{ \Illuminate\Support\Carbon::parse($startTo)->translatedFormat('d M Y') }}">
                        <span class="filter-chip-text">Mulai Sampai: {{ \Illuminate\Support\Carbon::parse($startTo)->translatedFormat('d M Y') }}</span>
                        <button type="button" class="filter-chip-remove" aria-label="Hapus filter Mulai Sampai">&times;</button>
                    </span>
                @endif
                <a href="{{ route('assessments.index') }}" class="filter-reset-link" data-role="reset-filter">Reset Filter</a>
            </div>
        </div>
    @endif

    <div id="assessment-table-container" class="assessment-table-container lpk-table-container" aria-live="polite">
        @include('assessments.partials.table-content')
    </div>
</section>

@include('partials.sheets-modal', [
    'modalId' => 'modal-sheets-sync-assessments',
    'title' => 'Integrasi Google Sheets: Data Program Asesmen',
    'subtitle' => 'Sinkronkan jadwal, tahapan, dan progres proses asesmen (S1 sampai RA) secara langsung ke Google Sheets Anda.',
    'exportUrl' => route('reports.assessments.export'),
    'feedUrl' => route('feeds.assessments', ['key' => env('SHEETS_FEED_KEY', 'simasadi-live')]),
    'fileName' => 'program-asesmen-simasadi.csv',
    'columns' => [
        'ID Asesmen', 'Judul Agenda Asesmen', 'Nama LPK', 'Jenis Asesmen',
        'Waktu Mulai', 'Waktu Selesai', 'Status Asesmen'
    ]
])

@include('assessments.partials.import-modal')
@endsection
