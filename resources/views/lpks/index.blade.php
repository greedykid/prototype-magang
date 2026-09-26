@extends('layouts.app')

@section('title', 'Data LPK | SIMASADI')

@section('content')
<x-page-header
    title="Daftar LPK"
    subtitle="Kelola data profil, siklus pengawasan surveilan, dan masa berlaku akreditasi LPK."
>
    <button type="button" class="button secondary" onclick="window.openModal('modal-sheets-sync-lpks')">
        <x-icon name="sheets" size="16" style="color: #0f9d58;" />
        <span>Google Sheets & Ekspor</span>
    </button>
    @if(auth()->user()?->isAdmin())
        <button type="button" class="button secondary" onclick="window.openModal('modal-import-lpk')">
            <x-icon name="upload" size="16" />
            <span>Impor LPK</span>
        </button>
    @endif
    <a class="button primary" href="{{ route('lpks.create') }}">
        <x-icon name="plus" size="16" />
        <span>Tambah LPK</span>
    </a>
</x-page-header>

<div class="lpk-workbench-tabs" style="display: flex; gap: 8px; margin-bottom: 20px; border-bottom: 1px solid var(--line); padding-bottom: 12px; flex-wrap: wrap;">
    <a href="{{ route('lpks.index') }}" class="button primary" style="font-size: 13px; padding: 6px 14px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
        <x-icon name="lpks" size="15" />
        <span>Master Data LPK</span>
    </a>
    <a href="{{ route('assessments.index') }}" class="button secondary" style="font-size: 13px; padding: 6px 14px; text-decoration: none; display: inline-flex; align-items: center; gap: 6px;">
        <x-icon name="assessments" size="15" />
        <span>Proses yang Sedang Berjalan (S1 &ndash; RA)</span>
    </a>
</div>

<section class="panel">
    <form id="lpk-filter-form" class="table-filters" method="GET" action="{{ route('lpks.index') }}" data-partial-filter="true" data-target="#lpk-table-container">
        <div class="table-filter-grid">
            <label>
                Cari LPK
                <input type="search" name="search" value="{{ $search }}" placeholder="Cari no. akreditasi, nama, lingkup, alamat..." autocomplete="off">
            </label>
            <label>
                Status LPK
                <select name="status">
                    <option value="">Semua status</option>
                    <option value="ACTIVE" @selected($status === 'ACTIVE')>Aktif</option>
                    <option value="SUSPENDED" @selected($status === 'SUSPENDED')>Dibekukan</option>
                    <option value="SURVEILLANCE_OVERDUE" @selected($status === 'SURVEILLANCE_OVERDUE')>Lewat Jadwal Surveilen</option>
                    <option value="SURVEILLANCE_DUE" @selected($status === 'SURVEILLANCE_DUE')>Jatuh Tempo Surveilen</option>
                    <option value="EXPIRED" @selected($status === 'EXPIRED')>Kedaluwarsa</option>
                    <option value="INACTIVE" @selected($status === 'INACTIVE')>Tidak aktif</option>
                </select>
            </label>
            <label>
                Status Pengawasan
                <select name="surveillance">
                    <option value="">Semua pengawasan</option>
                    <option value="NEEDS_ACTION" @selected($surveillance === 'NEEDS_ACTION')>Perlu Tindak Lanjut</option>
                    <option value="DUE_S1" @selected($surveillance === 'DUE_S1')>Jatuh Tempo S1 (Bulan 14)</option>
                    <option value="DUE_S2" @selected($surveillance === 'DUE_S2')>Jatuh Tempo S2 (Bulan 35)</option>
                    <option value="DUE_RA" @selected($surveillance === 'DUE_RA')>Jatuh Tempo Re-Akreditasi (Bulan 1 Sebelum Habis)</option>
                    <option value="OVERDUE" @selected($surveillance === 'OVERDUE')>Melewati Jadwal</option>
                </select>
            </label>
            <label>
                Masa Berlaku
                <select name="expiry">
                    <option value="">Semua masa berlaku</option>
                    <option value="VALID" @selected($expiry === 'VALID')>Masih berlaku</option>
                    <option value="EXPIRING_SOON" @selected($expiry === 'EXPIRING_SOON')>Mendekati kedaluwarsa (&le; 90 hari)</option>
                    <option value="EXPIRED" @selected($expiry === 'EXPIRED')>Kedaluwarsa</option>
                </select>
            </label>
        </div>
        <div class="table-filter-actions" id="lpk-filter-actions" style="margin-top: 8px;">
            <span id="lpk-filter-loading" class="filter-live-indicator" style="display: none; align-items: center; gap: 8px; font-size: 12.5px; color: var(--muted);" aria-live="polite">
                <span class="filter-live-spinner"></span>
                <span>Memperbarui data...</span>
            </span>
        </div>
    </form>

    @php
        $activeFiltersCount = 0;
        if ($search) $activeFiltersCount++;
        if ($status) $activeFiltersCount++;
        if ($surveillance) $activeFiltersCount++;
        if ($expiry) $activeFiltersCount++;
    @endphp

    @if($activeFiltersCount > 0)
        @php
            $statusLabels = [
                'ACTIVE' => 'Aktif',
                'SUSPENDED' => 'Dibekukan',
                'SURVEILLANCE_OVERDUE' => 'Lewat Jadwal Surveilen',
                'SURVEILLANCE_DUE' => 'Jatuh Tempo Surveilen',
                'EXPIRED' => 'Kedaluwarsa',
                'INACTIVE' => 'Tidak aktif',
            ];
            $survLabels = [
                'NEEDS_ACTION' => 'Perlu Tindak Lanjut',
                'DUE_S1' => 'Jatuh Tempo S1 (Bulan 14)',
                'DUE_S2' => 'Jatuh Tempo S2 (Bulan 35)',
                'DUE_RA' => 'Jatuh Tempo Re-Akreditasi',
                'OVERDUE' => 'Melewati Jadwal',
            ];
            $expLabels = [
                'VALID' => 'Masih berlaku',
                'EXPIRING_SOON' => 'Mendekati kedaluwarsa (&le; 90 hari)',
                'EXPIRED' => 'Kedaluwarsa',
            ];
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
                @if($status)
                    <span class="filter-chip" data-field="status" title="Status: {{ $statusLabels[$status] ?? $status }}">
                        <span class="filter-chip-text">Status: {{ $statusLabels[$status] ?? $status }}</span>
                        <button type="button" class="filter-chip-remove" aria-label="Hapus filter Status">&times;</button>
                    </span>
                @endif
                @if($surveillance)
                    <span class="filter-chip" data-field="surveillance" title="Pengawasan: {{ $survLabels[$surveillance] ?? $surveillance }}">
                        <span class="filter-chip-text">Pengawasan: {{ $survLabels[$surveillance] ?? $surveillance }}</span>
                        <button type="button" class="filter-chip-remove" aria-label="Hapus filter Pengawasan">&times;</button>
                    </span>
                @endif
                @if($expiry)
                    <span class="filter-chip" data-field="expiry" title="Masa Berlaku: {{ $expLabels[$expiry] ?? $expiry }}">
                        <span class="filter-chip-text">Masa Berlaku: {{ $expLabels[$expiry] ?? $expiry }}</span>
                        <button type="button" class="filter-chip-remove" aria-label="Hapus filter Masa Berlaku">&times;</button>
                    </span>
                @endif
                <a href="{{ route('lpks.index') }}" class="filter-reset-link" data-role="reset-filter">Reset Filter</a>
            </div>
        </div>
    @endif

    <div id="lpk-table-container" class="lpk-table-container" aria-live="polite">
        @include('lpks.partials.table-content')
    </div>
</section>

@include('partials.sheets-modal', [
    'modalId' => 'modal-sheets-sync-lpks',
    'title' => 'Integrasi Google Sheets: Data Master LPK',
    'subtitle' => 'Sinkronkan data seluruh Lembaga Penilaian Kesesuaian (LPK) terdaftar ke Google Sheets Anda secara langsung.',
    'exportUrl' => route('reports.lpks.export'),
    'feedUrl' => route('feeds.lpks', ['key' => env('SHEETS_FEED_KEY', 'simasadi-live')]),
    'fileName' => 'data-master-lpk-simasadi.csv',
    'columns' => ['ID LPK', 'No. Akreditasi', 'Nama LPK', 'Alamat', 'Telepon / Fax', 'Email', 'Lingkup', 'Masa Berlaku Akreditasi', 'Link Drive Dokumen']
])

@if(auth()->user()?->isAdmin())
    @include('lpks.partials.import-modal')
@endif
@endsection
