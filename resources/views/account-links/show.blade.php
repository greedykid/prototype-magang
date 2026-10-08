@extends('layouts.app')

@section('title', 'Data Akun Tertaut: ' . $owner->name . ' | SIMASADI')

@section('content')
<div class="lpk-header-back-wrap">
    <a href="{{ route('account-links.index') }}" class="lpk-back-btn" title="Kembali ke Tautan Akun">
        <x-icon name="chevron-left" size="16" />
        <span>Tautan Akun</span>
    </a>
</div>

{{-- Header Halaman --}}
<div class="page-heading" style="display: flex; justify-content: space-between; align-items: flex-start; gap: 16px; flex-wrap: wrap; margin-bottom: 20px;">
    <div>
        <div style="display: flex; align-items: center; gap: 10px; margin-bottom: 6px; flex-wrap: wrap;">
            <h1 style="margin: 0; font-size: 22px; font-weight: 700; color: var(--ink);">Data Akun Tertaut</h1>
            <span class="badge" style="background: var(--mint, #d1fae5); color: var(--green, #065f46); border: 1px solid rgba(16, 185, 129, 0.3); font-size: 11.5px; font-weight: 600; padding: 3px 10px; border-radius: 5px;">
                Mode Pemantauan (Viewer)
            </span>
        </div>
        <p class="lede" style="margin: 0; font-size: 13.5px; color: var(--muted); max-width: 800px; line-height: 1.5;">
            Data laboratorium binaan dan agenda asesmen milik akun <strong>{{ $owner->name }}</strong> ({{ $owner->email }}). Seluruh data disajikan secara terpisah dengan hak akses pemantauan hanya baca (Viewer).
        </p>
    </div>

    <div style="display: flex; align-items: center; gap: 8px;">
        <form method="POST" action="{{ route('account-links.destroy', $owner) }}"
              data-confirm-delete
              data-confirm-title="Lepaskan Akses Pemantauan?"
              data-confirm-text="Apakah Anda yakin ingin melepaskan akses pemantauan dari akun {{ $owner->name }}? Seluruh data LPK dan agenda asesmen akun ini tidak akan dapat diakses lagi."
              data-confirm-btn="Ya, Lepas Akses"
              style="margin: 0;">
            @csrf
            @method('DELETE')
            <button type="submit" class="button secondary" style="font-size: 12.5px; padding: 7px 12px; color: #dc2626; border-color: rgba(239, 68, 68, 0.3);">
                <x-icon name="unlink" size="14" />
                <span>Lepas Akses Akun</span>
            </button>
        </form>
    </div>
</div>

{{-- Tab Navigasi Data Akun Tertaut --}}
<div style="display: flex; gap: 4px; margin-bottom: 20px; border-bottom: 1px solid var(--line); padding-bottom: 0;">
    <a href="{{ route('account-links.show', [$owner, 'tab' => 'lpks']) }}"
       style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; font-size: 13.5px; font-weight: 600; text-decoration: none; border-bottom: 2px solid {{ $tab === 'lpks' ? 'var(--primary, #0284c7)' : 'transparent' }}; color: {{ $tab === 'lpks' ? 'var(--primary, #0284c7)' : 'var(--muted)' }}; transition: all 120ms ease;">
        <x-icon name="building" size="16" />
        <span>Daftar Laboratorium ({{ $totalLpkCount }})</span>
    </a>
    <a href="{{ route('account-links.show', [$owner, 'tab' => 'assessments']) }}"
       style="display: inline-flex; align-items: center; gap: 8px; padding: 10px 18px; font-size: 13.5px; font-weight: 600; text-decoration: none; border-bottom: 2px solid {{ $tab === 'assessments' ? 'var(--primary, #0284c7)' : 'transparent' }}; color: {{ $tab === 'assessments' ? 'var(--primary, #0284c7)' : 'var(--muted)' }}; transition: all 120ms ease;">
        <x-icon name="assessments" size="16" />
        <span>Program Asesmen ({{ $totalAssessmentCount }})</span>
    </a>
</div>

@if($tab === 'lpks')
    {{-- Kartu Ringkasan LPK --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(240px, 1fr)); gap: 16px; margin-bottom: 24px;">
        {{-- Info Pemilik --}}
        <div class="panel" style="margin: 0; padding: 16px; display: flex; align-items: center; gap: 14px;">
            <div style="width: 44px; height: 44px; border-radius: 9999px; background: var(--mint, #ecfdf5); color: var(--green, #047857); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 16px; flex-shrink: 0; border: 1px solid rgba(16, 185, 129, 0.3);">
                {{ $owner->initials }}
            </div>
            <div style="min-width: 0;">
                <div style="font-size: 11.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted);">Pemilik Laboratorium</div>
                <div style="font-size: 14px; font-weight: 700; color: var(--ink); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $owner->name }}</div>
                <div style="font-size: 12px; color: var(--muted); white-space: nowrap; overflow: hidden; text-overflow: ellipsis;">{{ $owner->email }}</div>
            </div>
        </div>

        {{-- Total LPK --}}
        <div class="panel" style="margin: 0; padding: 16px; display: flex; align-items: center; gap: 14px;">
            <div style="width: 44px; height: 44px; border-radius: 10px; background: var(--info-bg, #e0f2fe); color: var(--info-text, #0284c7); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 16px; flex-shrink: 0; border: 1px solid var(--info-border);">
                <x-icon name="building" size="20" />
            </div>
            <div>
                <div style="font-size: 11.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted);">Total Laboratorium</div>
                <div style="font-size: 20px; font-weight: 800; color: var(--ink);">{{ $totalLpkCount }} <span style="font-size: 13px; font-weight: 500; color: var(--muted);">LPK</span></div>
            </div>
        </div>

        {{-- LPK Aktif --}}
        <div class="panel" style="margin: 0; padding: 16px; display: flex; align-items: center; gap: 14px;">
            <div style="width: 44px; height: 44px; border-radius: 10px; background: var(--mint, #ecfdf5); color: var(--green, #047857); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 16px; flex-shrink: 0; border: 1px solid rgba(16, 185, 129, 0.3);">
                <x-icon name="shield-check" size="20" />
            </div>
            <div>
                <div style="font-size: 11.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted);">Laboratorium Aktif</div>
                <div style="font-size: 20px; font-weight: 800; color: var(--green, #047857);">{{ $activeLpkCount }} <span style="font-size: 13px; font-weight: 500; color: var(--muted);">Aktif</span></div>
            </div>
        </div>
    </div>

    {{-- Panel Tabel LPK Akun Tertaut --}}
    <section class="panel table-panel-borderless">
        <form id="linked-lpk-filter-form" class="table-filters" method="GET" action="{{ route('account-links.show', $owner) }}" data-partial-filter="true" data-target="#linked-lpk-table-container">
            <input type="hidden" name="tab" value="lpks">
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
            <div class="table-filter-actions" id="linked-lpk-filter-actions" style="margin-top: 8px;">
                <span id="linked-lpk-filter-loading" class="filter-live-indicator" style="display: none; align-items: center; gap: 8px; font-size: 12.5px; color: var(--muted);" aria-live="polite">
                    <span class="filter-live-spinner"></span>
                    <span>Memperbarui data...</span>
                </span>
            </div>
        </form>

        @php
            $activeLpkFiltersCount = 0;
            if ($search) $activeLpkFiltersCount++;
            if ($status) $activeLpkFiltersCount++;
            if ($surveillance) $activeLpkFiltersCount++;
            if ($expiry) $activeLpkFiltersCount++;
        @endphp

        @if($activeLpkFiltersCount > 0)
            @php
                $statusLabels = [
                    'ACTIVE' => 'Aktif',
                    'GRACE_PERIOD' => 'Masa Tenggang (6 Bln)',
                    'SUSPENDED' => 'Dibekukan',
                    'REVOKED' => 'Dicabut',
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
                    'EXPIRING_SOON' => 'Mendekati kedaluwarsa (<= 90 hari)',
                    'EXPIRED' => 'Kedaluwarsa',
                ];
            @endphp
            <div class="active-filters-bar">
                <span class="active-filters-heading">FILTER AKTIF</span>
                <div class="active-filters-chips">
                    @if($search)
                        <span class="filter-chip" data-field="search" title="Cari: {{ $search }}">
                            <span class="filter-chip-text">Cari: {{ $search }}</span>
                            <button type="button" class="filter-chip-remove" aria-label="Hapus filter pencarian">&times;</button>
                        </span>
                    @endif
                    @if($status)
                        <span class="filter-chip" data-field="status" title="Status: {{ $statusLabels[$status] ?? $status }}">
                            <span class="filter-chip-text">Status: {{ $statusLabels[$status] ?? $status }}</span>
                            <button type="button" class="filter-chip-remove" aria-label="Hapus filter status">&times;</button>
                        </span>
                    @endif
                    @if($surveillance)
                        <span class="filter-chip" data-field="surveillance" title="Pengawasan: {{ $survLabels[$surveillance] ?? $surveillance }}">
                            <span class="filter-chip-text">Pengawasan: {{ $survLabels[$surveillance] ?? $surveillance }}</span>
                            <button type="button" class="filter-chip-remove" aria-label="Hapus filter pengawasan">&times;</button>
                        </span>
                    @endif
                    @if($expiry)
                        <span class="filter-chip" data-field="expiry" title="Masa Berlaku: {{ $expLabels[$expiry] ?? $expiry }}">
                            <span class="filter-chip-text">Masa Berlaku: {{ $expLabels[$expiry] ?? $expiry }}</span>
                            <button type="button" class="filter-chip-remove" aria-label="Hapus filter masa berlaku">&times;</button>
                        </span>
                    @endif
                    <a href="{{ route('account-links.show', [$owner, 'tab' => 'lpks']) }}" class="filter-reset-link" data-role="reset-filter">Reset Filter</a>
                </div>
            </div>
        @endif

        {{-- Kontainer Parsial Tabel LPK --}}
        <div id="linked-lpk-table-container" class="lpk-table-container" aria-live="polite">
            @include('account-links.partials.table-content')
        </div>
    </section>

@else
    {{-- Kartu Ringkasan Program Asesmen --}}
    <div style="display: grid; grid-template-columns: repeat(auto-fit, minmax(220px, 1fr)); gap: 16px; margin-bottom: 24px;">
        {{-- Total Asesmen --}}
        <div class="panel" style="margin: 0; padding: 16px; display: flex; align-items: center; gap: 14px;">
            <div style="width: 44px; height: 44px; border-radius: var(--radius-md, 12px); background: var(--info-bg); color: var(--info-text); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 16px; flex-shrink: 0; border: 1px solid var(--info-border);">
                <x-icon name="assessments" size="20" />
            </div>
            <div>
                <div style="font-size: 11.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted);">Total Asesmen</div>
                <div style="font-size: 20px; font-weight: 800; color: var(--ink);">{{ $totalAssessmentCount }} <span style="font-size: 13px; font-weight: 500; color: var(--muted);">Agenda</span></div>
            </div>
        </div>

        {{-- Asesmen Terjadwal & Berjalan --}}
        <div class="panel" style="margin: 0; padding: 16px; display: flex; align-items: center; gap: 14px;">
            <div style="width: 44px; height: 44px; border-radius: var(--radius-md, 12px); background: var(--warning-bg); color: var(--warning-text); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 16px; flex-shrink: 0; border: 1px solid var(--warning-border);">
                <x-icon name="calendar" size="20" />
            </div>
            <div>
                <div style="font-size: 11.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted);">Terjadwal & Berjalan</div>
                <div style="font-size: 20px; font-weight: 800; color: var(--warning-text);">{{ $activeAssessmentCount }} <span style="font-size: 13px; font-weight: 500; color: var(--muted);">Agenda</span></div>
            </div>
        </div>

        {{-- Asesmen Selesai --}}
        <div class="panel" style="margin: 0; padding: 16px; display: flex; align-items: center; gap: 14px;">
            <div style="width: 44px; height: 44px; border-radius: var(--radius-md, 12px); background: var(--success-bg); color: var(--success-text); display: flex; align-items: center; justify-content: center; font-weight: 700; font-size: 16px; flex-shrink: 0; border: 1px solid var(--success-border);">
                <x-icon name="check-circle" size="20" />
            </div>
            <div>
                <div style="font-size: 11.5px; font-weight: 600; text-transform: uppercase; letter-spacing: 0.05em; color: var(--muted);">Asesmen Selesai</div>
                <div style="font-size: 20px; font-weight: 800; color: var(--success-text);">{{ $completedAssessmentCount }} <span style="font-size: 13px; font-weight: 500; color: var(--muted);">Selesai</span></div>
            </div>
        </div>
    </div>

    {{-- Panel Tabel Asesmen Akun Tertaut --}}
    <section class="panel table-panel-borderless">
        <form id="linked-asm-filter-form" class="table-filters" method="GET" action="{{ route('account-links.show', $owner) }}" data-partial-filter="true" data-target="#linked-assessment-table-container">
            <input type="hidden" name="tab" value="assessments">
            <div class="table-filter-grid">
                <label for="filter-asm-search">
                    Cari Agenda
                    <input id="filter-asm-search" type="search" name="search" value="{{ $search }}" placeholder="Judul agenda atau no. SK..." autocomplete="off">
                </label>
                <label for="filter-asm-lpk">
                    LPK Terpilih
                    <select id="filter-asm-lpk" name="lpk_id">
                        <option value="">Semua LPK Milik Akun</option>
                        @foreach($ownerLpks as $lpk)
                            <option value="{{ $lpk->id }}" @selected((string)$lpkId === (string)$lpk->id)>
                                {{ $lpk->registration_number }} - {{ $lpk->name }}
                            </option>
                        @endforeach
                    </select>
                </label>
                <label for="filter-asm-type">
                    Jenis Asesmen
                    <select id="filter-asm-type" name="assessment_type">
                        <option value="">Semua jenis (KAN U-01)</option>
                        @foreach($assessmentTypes as $key => $label)
                            <option value="{{ $key }}" @selected($assessmentType === $key)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label for="filter-asm-status">
                    Status Pelaksanaan
                    <select id="filter-asm-status" name="status">
                        <option value="">Semua status</option>
                        @foreach(['PLANNED' => 'Direncanakan', 'SCHEDULED' => 'Terjadwal', 'IN_PROGRESS' => 'Sedang Berlangsung', 'SUSPENDED' => 'Dibekukan', 'COMPLETED' => 'Selesai', 'CANCELLED' => 'Dibatalkan'] as $value => $label)
                            <option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>
                        @endforeach
                    </select>
                </label>
                <label for="filter-asm-tp-status">
                    Status TP (Batas Waktu)
                    <select id="filter-asm-tp-status" name="tp_status">
                        <option value="">Semua status TP</option>
                        <option value="NONE" @selected(($tpFilter ?? '') === 'NONE')>Nihil / Belum Ada Temuan</option>
                        <option value="ACTIVE" @selected(($tpFilter ?? '') === 'ACTIVE')>Sedang Perbaikan / Verifikasi</option>
                        <option value="DUE_SOON" @selected(($tpFilter ?? '') === 'DUE_SOON')>Jatuh Tempo (<= 14 Hari)</option>
                        <option value="OVERDUE" @selected(($tpFilter ?? '') === 'OVERDUE')>Melewati Batas Waktu (Dibekukan)</option>
                        <option value="SATISFIED" @selected(($tpFilter ?? '') === 'SATISFIED')>Dinyatakan Memenuhi</option>
                    </select>
                </label>
            </div>
            <div class="table-filter-actions" id="linked-asm-filter-actions" style="margin-top: 8px;">
                <span id="linked-asm-filter-loading" class="filter-live-indicator" style="display: none; align-items: center; gap: 8px; font-size: 12.5px; color: var(--muted);" aria-live="polite">
                    <span class="filter-live-spinner"></span>
                    <span>Memperbarui data...</span>
                </span>
            </div>
        </form>

        @php
            $activeAsmFiltersCount = 0;
            if ($search) $activeAsmFiltersCount++;
            if ($lpkId) $activeAsmFiltersCount++;
            if ($assessmentType) $activeAsmFiltersCount++;
            if ($status) $activeAsmFiltersCount++;
            if (!empty($tpFilter)) $activeAsmFiltersCount++;
        @endphp

        @if($activeAsmFiltersCount > 0)
            @php
                $asmStatusLabels = [
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
                    'DUE_SOON' => 'Jatuh Tempo (<= 14 Hari)',
                    'OVERDUE' => 'Melewati Batas Waktu (Dibekukan)',
                    'SATISFIED' => 'Dinyatakan Memenuhi',
                ];
            @endphp
            <div class="active-filters-bar">
                <span class="active-filters-heading">FILTER AKTIF</span>
                <div class="active-filters-chips">
                    @if($search)
                        <span class="filter-chip" data-field="search" title="Cari: {{ $search }}">
                            <span class="filter-chip-text">Cari: {{ $search }}</span>
                            <button type="button" class="filter-chip-remove" aria-label="Hapus filter pencarian">&times;</button>
                        </span>
                    @endif
                    @if($lpkId)
                        @php $selLpk = $ownerLpks->firstWhere('id', (int)$lpkId); @endphp
                        <span class="filter-chip" data-field="lpk_id" title="LPK: {{ $selLpk?->name ?? $lpkId }}">
                            <span class="filter-chip-text">LPK: {{ $selLpk?->name ?? $lpkId }}</span>
                            <button type="button" class="filter-chip-remove" aria-label="Hapus filter LPK">&times;</button>
                        </span>
                    @endif
                    @if($assessmentType)
                        <span class="filter-chip" data-field="assessment_type" title="Jenis: {{ $assessmentTypes[$assessmentType] ?? $assessmentType }}">
                            <span class="filter-chip-text">Jenis: {{ $assessmentTypes[$assessmentType] ?? $assessmentType }}</span>
                            <button type="button" class="filter-chip-remove" aria-label="Hapus filter jenis asesmen">&times;</button>
                        </span>
                    @endif
                    @if($status)
                        <span class="filter-chip" data-field="status" title="Status: {{ $asmStatusLabels[$status] ?? $status }}">
                            <span class="filter-chip-text">Status: {{ $asmStatusLabels[$status] ?? $status }}</span>
                            <button type="button" class="filter-chip-remove" aria-label="Hapus filter status">&times;</button>
                        </span>
                    @endif
                    @if(!empty($tpFilter))
                        <span class="filter-chip" data-field="tp_status" title="Status TP: {{ $tpLabels[$tpFilter] ?? $tpFilter }}">
                            <span class="filter-chip-text">TP: {{ $tpLabels[$tpFilter] ?? $tpFilter }}</span>
                            <button type="button" class="filter-chip-remove" aria-label="Hapus filter status TP">&times;</button>
                        </span>
                    @endif
                    <a href="{{ route('account-links.show', [$owner, 'tab' => 'assessments']) }}" class="filter-reset-link" data-role="reset-filter">Reset Filter</a>
                </div>
            </div>
        @endif

        {{-- Kontainer Parsial Tabel Asesmen --}}
        <div id="linked-assessment-table-container" class="assessment-table-container" aria-live="polite">
            @include('account-links.partials.assessments-table-content')
        </div>
    </section>
@endif
@endsection
