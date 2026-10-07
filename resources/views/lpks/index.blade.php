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
    <button type="button" class="button secondary" onclick="window.openModal('modal-import-lpk')">
        <x-icon name="import" size="16" />
        <span>Impor LPK</span>
    </button>
    <a class="button primary" href="{{ route('lpks.create') }}">
        <x-icon name="plus" size="16" />
        <span>Tambah LPK</span>
    </a>
</x-page-header>

@if(!empty($linkedOwnersCount) && $linkedOwnersCount > 0)
    <div style="background: var(--info-bg, #e0f2fe); border: 1px solid var(--info-border, #bae6fd); border-radius: 8px; padding: 10px 16px; margin-bottom: 16px; display: flex; align-items: center; justify-content: space-between; gap: 12px; flex-wrap: wrap;">
        <div style="display: flex; align-items: center; gap: 10px;">
            <div style="color: var(--info-text, #0284c7); display: inline-flex;">
                <x-icon name="link" size="18" />
            </div>
            <div style="font-size: 13px; color: var(--ink);">
                Anda memiliki <strong>{{ $linkedOwnersCount }} akun tertaut</strong> yang membagikan data laboratorium. LPK tersebut tersimpan terpisah di detail akun masing-masing.
            </div>
        </div>
        <a href="{{ route('account-links.index') }}" class="button secondary" style="font-size: 12px; padding: 4px 12px; background: var(--surface); border-color: var(--info-border, #bae6fd); color: var(--info-text, #0284c7); font-weight: 600; text-decoration: none;">
            <span>Buka Akun Tertaut</span>
            <x-icon name="chevron-right" size="13" />
        </a>
    </div>
@endif

<section class="panel table-panel-borderless">
    <form id="lpk-filter-form" class="table-filters" method="GET" action="{{ route('lpks.index') }}" data-partial-filter="true" data-target="#lpk-table-container">
        <div class="table-filter-grid">
            <label for="filter-lpk-search">
                Cari LPK
                <input id="filter-lpk-search" type="search" name="search" value="{{ $search }}" placeholder="Cari no. akreditasi, nama, lingkup, alamat..." autocomplete="off">
            </label>
            <label for="filter-lpk-status">
                Status LPK
                <select id="filter-lpk-status" name="status">
                    <option value="">Semua status</option>
                    <option value="ACTIVE" @selected($status === 'ACTIVE')>Aktif</option>
                    <option value="GRACE_PERIOD" @selected($status === 'GRACE_PERIOD')>Masa Tenggang (6 Bln)</option>
                    <option value="SUSPENDED" @selected($status === 'SUSPENDED')>Dibekukan</option>
                    <option value="REVOKED" @selected($status === 'REVOKED')>Dicabut</option>
                    <option value="SURVEILLANCE_OVERDUE" @selected($status === 'SURVEILLANCE_OVERDUE')>Lewat Jadwal Surveilen</option>
                    <option value="SURVEILLANCE_DUE" @selected($status === 'SURVEILLANCE_DUE')>Jatuh Tempo Surveilen</option>
                    <option value="EXPIRED" @selected($status === 'EXPIRED')>Kedaluwarsa</option>
                    <option value="INACTIVE" @selected($status === 'INACTIVE')>Tidak aktif</option>
                </select>
            </label>
            <label for="filter-lpk-surveillance">
                Status Pengawasan
                <select id="filter-lpk-surveillance" name="surveillance">
                    <option value="">Semua pengawasan</option>
                    <option value="NEEDS_ACTION" @selected($surveillance === 'NEEDS_ACTION')>Perlu Tindak Lanjut</option>
                    <option value="DUE_S1" @selected($surveillance === 'DUE_S1')>Jatuh Tempo S1 (Bulan 14)</option>
                    <option value="DUE_S2" @selected($surveillance === 'DUE_S2')>Jatuh Tempo S2 (Bulan 35)</option>
                    <option value="DUE_RA" @selected($surveillance === 'DUE_RA')>Jatuh Tempo Re-Akreditasi (Bulan 1 Sebelum Habis)</option>
                    <option value="OVERDUE" @selected($surveillance === 'OVERDUE')>Melewati Jadwal</option>
                </select>
            </label>
            <label for="filter-lpk-expiry">
                Masa Berlaku
                <select id="filter-lpk-expiry" name="expiry">
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
                        <button type="button" class="filter-chip-remove" aria-label="Hapus filter pencarian: {{ $search }}">&times;</button>
                    </span>
                @endif
                @if($status)
                    <span class="filter-chip" data-field="status" title="Status: {{ $statusLabels[$status] ?? $status }}">
                        <span class="filter-chip-text">Status: {{ $statusLabels[$status] ?? $status }}</span>
                        <button type="button" class="filter-chip-remove" aria-label="Hapus filter status LPK: {{ $statusLabels[$status] ?? $status }}">&times;</button>
                    </span>
                @endif
                @if($surveillance)
                    <span class="filter-chip" data-field="surveillance" title="Pengawasan: {{ $survLabels[$surveillance] ?? $surveillance }}">
                        <span class="filter-chip-text">Pengawasan: {{ $survLabels[$surveillance] ?? $surveillance }}</span>
                        <button type="button" class="filter-chip-remove" aria-label="Hapus filter pengawasan: {{ $survLabels[$surveillance] ?? $surveillance }}">&times;</button>
                    </span>
                @endif
                @if($expiry)
                    <span class="filter-chip" data-field="expiry" title="Masa Berlaku: {{ $expLabels[$expiry] ?? $expiry }}">
                        <span class="filter-chip-text">Masa Berlaku: {{ $expLabels[$expiry] ?? $expiry }}</span>
                        <button type="button" class="filter-chip-remove" aria-label="Hapus filter masa berlaku: {{ $expLabels[$expiry] ?? $expiry }}">&times;</button>
                    </span>
                @endif
                <a href="{{ route('lpks.index') }}" class="filter-reset-link" data-role="reset-filter">Reset Filter</a>
            </div>
        </div>
    @endif

    <x-bulk-action-bar
        table-id="lpks-table"
        :export-url="route('reports.lpks.export')"
        :delete-url="route('lpks.bulk-destroy')"
        entity-name="LPK"
        :can-delete="auth()->user()?->isAdmin() || auth()->user()?->isPic()"
    />

    <div id="lpk-table-container" class="lpk-table-container" aria-live="polite">
        @include('lpks.partials.table-content')
    </div>
</section>

<x-sheets-modal
    modal-id="modal-sheets-sync-lpks"
    title="Integrasi Google Sheets: Data Master LPK"
    subtitle="Sinkronkan data seluruh Lembaga Penilaian Kesesuaian (LPK) terdaftar ke Google Sheets Anda secara langsung."
    :export-url="route('reports.lpks.export')"
    :feed-url="route('feeds.lpks', ['key' => env('SHEETS_FEED_KEY', 'simasadi-live')])"
    file-name="data-master-lpk-simasadi.csv"
    :columns="['ID LPK', 'No. Akreditasi', 'Nama LPK', 'Alamat', 'Telepon / Fax', 'Email', 'Lingkup', 'Masa Berlaku Akreditasi', 'Link Drive Dokumen']"
/>

@include('lpks.partials.import-modal')
@endsection
