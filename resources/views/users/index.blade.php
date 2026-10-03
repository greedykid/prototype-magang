@extends('layouts.app')

@section('title', 'Manajemen Anggota | SIMASADI')

@section('content')
<x-page-header
    title="Manajemen Anggota"
    subtitle="Kelola akun pengguna, hak akses peran Ketua Tim, dan pembagian LPK untuk PIC Laboratorium."
>
    <a class="button primary" href="{{ route('users.create') }}">
        <x-icon name="plus" size="16" />
        <span>Tambah Anggota</span>
    </a>
</x-page-header>

{{-- Kartu Metrik Ringkas --}}
<div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(200px, 1fr)); gap: 16px; margin-bottom: 20px;">
    <div class="panel" style="padding: 16px; display: flex; align-items: center; gap: 14px;">
        <div class="user-stat-icon user-stat-icon-indigo">
            <x-icon name="users" size="22" />
        </div>
        <div>
            <div style="font-size: 11.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: var(--muted);">Total Anggota</div>
            <strong style="font-size: 22px; color: var(--ink); line-height: 1.2;">{{ $totalCount }}</strong>
        </div>
    </div>

    <div class="panel" style="padding: 16px; display: flex; align-items: center; gap: 14px;">
        <div class="user-stat-icon user-stat-icon-purple">
            <x-icon name="shield" size="22" />
        </div>
        <div>
            <div style="font-size: 11.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: var(--muted);">Ketua Tim</div>
            <strong style="font-size: 22px; color: var(--ink); line-height: 1.2;">{{ $adminCount }}</strong>
        </div>
    </div>

    <div class="panel" style="padding: 16px; display: flex; align-items: center; gap: 14px;">
        <div class="user-stat-icon user-stat-icon-blue">
            <x-icon name="user" size="22" />
        </div>
        <div>
            <div style="font-size: 11.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.5px; color: var(--muted);">PIC Laboratorium</div>
            <strong style="font-size: 22px; color: var(--ink); line-height: 1.2;">{{ $picCount }}</strong>
        </div>
    </div>
</div>

<section class="panel table-panel-borderless">
    {{-- Form Filter & Pencarian Live Otomatis --}}
    <form id="user-filter-form" class="table-filters" method="GET" action="{{ route('users.index') }}" data-partial-filter="true" data-target="#user-table-container">
        <input type="hidden" name="per_page" value="{{ $perPage }}">
        <div class="table-filter-grid">
            <label>
                Cari Pengguna
                <input type="search" name="search" value="{{ $search }}" placeholder="Nama atau alamat email..." autocomplete="off">
            </label>

            <label>
                Peran Pengguna
                <select name="role">
                    <option value="">Semua Peran</option>
                    <option value="admin" @selected($role === 'admin')>Ketua Tim</option>
                    <option value="pic" @selected($role === 'pic')>PIC Laboratorium</option>
                </select>
            </label>
        </div>

        <div class="table-filter-actions" id="user-filter-actions" style="margin-top: 8px;">
            <span id="user-filter-loading" class="filter-live-indicator" style="display: none; align-items: center; gap: 8px; font-size: 12.5px; color: var(--muted);" aria-live="polite">
                <span class="filter-live-spinner"></span>
                <span>Memperbarui data...</span>
            </span>
        </div>
    </form>

    @php
        $activeFiltersCount = 0;
        if ($search) $activeFiltersCount++;
        if ($role) $activeFiltersCount++;
        $roleLabels = [
            'admin' => 'Ketua Tim',
            'pic' => 'PIC Laboratorium',
        ];
    @endphp

    @if($activeFiltersCount > 0)
        <div class="active-filters-bar">
            <span class="active-filters-heading">FILTER AKTIF</span>
            <div class="active-filters-chips">
                @if($search)
                    <span class="filter-chip" data-field="search" title="Cari: {{ $search }}">
                        <span class="filter-chip-text">Cari: {{ $search }}</span>
                        <button type="button" class="filter-chip-remove" aria-label="Hapus filter Cari">&times;</button>
                    </span>
                @endif
                @if($role)
                    <span class="filter-chip" data-field="role" title="Peran: {{ $roleLabels[$role] ?? $role }}">
                        <span class="filter-chip-text">Peran: {{ $roleLabels[$role] ?? $role }}</span>
                        <button type="button" class="filter-chip-remove" aria-label="Hapus filter Peran">&times;</button>
                    </span>
                @endif
                <a href="{{ route('users.index') }}" class="filter-reset-link" data-role="reset-filter">Reset Filter</a>
            </div>
        </div>
    @endif

    <div id="user-table-container" class="user-table-container" aria-live="polite">
        @include('users.partials.table-content')
    </div>
</section>
@endsection
