@extends('layouts.app')

@section('title', 'Data LPK | SIMASADI')

@section('content')
<x-page-header
    title="Daftar LPK"
    subtitle="Kelola catatan dasar lembaga pengujian yang digunakan di prototype."
>
    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
        <button type="button" class="button secondary" onclick="window.openModal('modal-sheets-sync-lpks')">
            <x-icon name="sheets" size="16" style="color: #0f9d58;" />
            <span>Google Sheets & Ekspor</span>
        </button>
        @if(auth()->user()?->isAdmin())
            <button type="button" class="button secondary" onclick="window.openModal('modal-import-lpk')">
                <x-icon name="upload" size="16" />
                <span>Impor LPK</span>
            </button>
            <a class="button primary" href="{{ route('lpks.create') }}">
                <x-icon name="plus" size="16" />
                <span>Tambah LPK</span>
            </a>
        @endif
    </div>
</x-page-header>

<section class="panel">
    <form class="table-filters" method="GET">
        <div class="table-filter-grid">
            <label>
                Cari LPK
                <input name="search" value="{{ $search }}" placeholder="Cari no. akreditasi, nama, lingkup, alamat...">
            </label>
            <label>
                Status
                <select name="status">
                    <option value="">Semua status</option>
                    <option value="ACTIVE" @selected($status === 'ACTIVE')>Aktif</option>
                    <option value="INACTIVE" @selected($status === 'INACTIVE')>Tidak aktif</option>
                </select>
            </label>
        </div>
        <div class="table-filter-actions">
            <button class="button secondary" type="submit">Terapkan filter</button>
            @if($search || $status)
                <a class="button ghost" href="{{ route('lpks.index') }}">Reset</a>
            @endif
        </div>
    </form>

    @if($lpks->count())
        <div class="table-wrap">
            <table class="table-lpks-custom">
                <thead>
                    <tr style="background-color: #eef4fc;">
                        <th style="background-color: #eef4fc; color: #0f172a; font-weight: 700; font-size: 12px; letter-spacing: 0.5px; padding: 13px 14px; border-bottom: 2px solid #cbd5e1; white-space: nowrap;">NO. AKREDITASI</th>
                        <th style="background-color: #eef4fc; color: #0f172a; font-weight: 700; font-size: 12px; letter-spacing: 0.5px; padding: 13px 14px; border-bottom: 2px solid #cbd5e1; white-space: nowrap;">NAMA LPK</th>
                        <th style="background-color: #eef4fc; color: #0f172a; font-weight: 700; font-size: 12px; letter-spacing: 0.5px; padding: 13px 14px; border-bottom: 2px solid #cbd5e1; white-space: nowrap;">ALAMAT</th>
                        <th style="background-color: #eef4fc; color: #0f172a; font-weight: 700; font-size: 12px; letter-spacing: 0.5px; padding: 13px 14px; border-bottom: 2px solid #cbd5e1; white-space: nowrap;">TELEPON / FAX</th>
                        <th style="background-color: #eef4fc; color: #0f172a; font-weight: 700; font-size: 12px; letter-spacing: 0.5px; padding: 13px 14px; border-bottom: 2px solid #cbd5e1; white-space: nowrap;">EMAIL</th>
                        <th style="background-color: #eef4fc; color: #0f172a; font-weight: 700; font-size: 12px; letter-spacing: 0.5px; padding: 13px 14px; border-bottom: 2px solid #cbd5e1; white-space: nowrap;">LINGKUP</th>
                        <th style="background-color: #eef4fc; color: #0f172a; font-weight: 700; font-size: 12px; letter-spacing: 0.5px; padding: 13px 14px; border-bottom: 2px solid #cbd5e1; white-space: nowrap;">MASA BERLAKU AKREDITASI (EXPIRED)</th>
                        <th style="background-color: #eef4fc; color: #0f172a; font-weight: 700; font-size: 12px; letter-spacing: 0.5px; padding: 13px 14px; border-bottom: 2px solid #cbd5e1; white-space: nowrap; text-align: center;">LINK</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lpks as $lpk)
                        <tr>
                            <!-- 1. NO. AKREDITASI -->
                            <td style="white-space: nowrap; vertical-align: top; padding: 12px 14px;">
                                <div>
                                    <a href="{{ route('lpks.show', $lpk) }}" style="font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-weight: 700; font-size: 13px; color: #1e293b; text-decoration: none;" class="hover-underline" title="Buka rincian LPK">
                                        {{ $lpk->registration_number }}
                                    </a>
                                </div>
                                @php($lpkAlerts = $lpk->getActiveSurveillanceAlerts())
                                @if(!empty($lpkAlerts))
                                    <div style="margin-top: 4px; display: flex; gap: 4px; flex-wrap: wrap;">
                                        @foreach($lpkAlerts as $alt)
                                            <span class="badge" style="background-color: {{ $alt['is_urgent'] ? '#fee2e2' : '#fef3c7' }}; color: {{ $alt['is_urgent'] ? '#991b1b' : '#92400e' }}; font-size: 10px; font-weight: 700; padding: 2px 5px; border-radius: 4px;" title="{{ $alt['description'] }}">
                                                ⚠ {{ $alt['name'] }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>

                            <!-- 2. NAMA LPK -->
                            <td style="vertical-align: top; min-width: 220px; padding: 12px 14px;">
                                <a href="{{ route('lpks.show', $lpk) }}" style="font-weight: 600; color: #1d4ed8; text-decoration: none; font-size: 13.5px; line-height: 1.4; display: block;" class="hover-underline" title="Buka rincian LPK">
                                    {{ $lpk->name }}
                                </a>
                                <div style="margin-top: 4px; display: flex; align-items: center; gap: 6px;">
                                    <x-status :value="$lpk->status" />
                                </div>
                            </td>

                            <!-- 3. ALAMAT -->
                            <td style="vertical-align: top; min-width: 180px; max-width: 260px; font-size: 12.5px; color: #334155; line-height: 1.4; padding: 12px 14px;">
                                {{ $lpk->address ?: '-' }}
                            </td>

                            <!-- 4. TELEPON / FAX -->
                            <td style="vertical-align: top; white-space: nowrap; font-size: 12.5px; color: #334155; padding: 12px 14px;">
                                @if($lpk->phone)
                                    <a href="tel:{{ $lpk->phone }}" style="color: inherit; text-decoration: none;" title="Hubungi telepon">
                                        {{ $lpk->phone }}
                                    </a>
                                @else
                                    <span style="color: var(--muted);">-</span>
                                @endif
                            </td>

                            <!-- 5. EMAIL -->
                            <td style="vertical-align: top; white-space: nowrap; font-size: 12.5px; padding: 12px 14px;">
                                @if($lpk->email)
                                    <a href="mailto:{{ $lpk->email }}" style="color: #2563eb; text-decoration: none;" class="hover-underline" title="Kirim email">
                                        {{ $lpk->email }}
                                    </a>
                                @else
                                    <span style="color: var(--muted);">-</span>
                                @endif
                            </td>

                            <!-- 6. LINGKUP -->
                            <td style="vertical-align: top; min-width: 260px; max-width: 380px; font-size: 12px; line-height: 1.45; padding: 12px 14px;">
                                @if($lpk->scope)
                                    <div style="max-height: 120px; overflow-y: auto; white-space: pre-line; color: #334155; word-break: break-word; padding-right: 6px;" title="{{ $lpk->scope }}">
                                        {{ $lpk->scope }}
                                    </div>
                                @else
                                    <span style="color: var(--muted);">-</span>
                                @endif
                            </td>

                            <!-- 7. MASA BERLAKU AKREDITASI (EXPIRED) -->
                            <td style="vertical-align: top; white-space: nowrap; padding: 12px 14px;">
                                @if($lpk->expired_at)
                                    <div style="font-weight: 600; font-size: 13px; {{ $lpk->isExpired() ? 'color: #dc2626;' : 'color: #0f172a;' }}">
                                        {{ $lpk->expired_at->format('d/m/Y') }}
                                    </div>
                                    @if($lpk->certificate_date)
                                        <small style="display: block; color: var(--muted); font-size: 11px; margin-top: 1px;">Terbit: {{ $lpk->certificate_date->format('d/m/Y') }}</small>
                                    @endif
                                    @if($lpk->isExpired())
                                        <span class="status status-danger" style="font-size: 10px; padding: 1px 6px; margin-top: 3px; display: inline-block;">Kedaluwarsa</span>
                                    @elseif($lpk->isExpiringSoon())
                                        <span class="status status-warn" style="font-size: 10px; padding: 1px 6px; margin-top: 3px; display: inline-block;">Mendekati Expired</span>
                                    @endif
                                @else
                                    <span style="color: var(--muted); font-size: 12px;">-</span>
                                @endif
                            </td>

                            <!-- 8. LINK -->
                            <td style="vertical-align: top; text-align: center; white-space: nowrap; padding: 12px 14px;">
                                @if($lpk->drive_url)
                                    <a href="{{ $lpk->drive_url }}" target="_blank" rel="noopener noreferrer" class="button secondary" style="font-size: 11.5px; padding: 3px 10px; min-height: 28px; display: inline-flex; align-items: center; gap: 5px; text-decoration: none;" title="Buka Berkas Sertifikat, Amandemen & Lampiran di Google Drive">
                                        <x-icon name="sheets" size="13" style="color: #0f9d58;" />
                                        <span>Drive &rarr;</span>
                                    </a>
                                @else
                                    <span style="color: var(--muted); font-size: 12px;">-</span>
                                @endif
                            </td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $lpks->links() }}
    @else
        <div class="empty">
            {{ $search || $status ? 'Tidak ada LPK yang cocok dengan filter.' : 'Belum ada data LPK.' }}
            @if($search || $status)
                <a class="button ghost empty-action" href="{{ route('lpks.index') }}">Reset filter</a>
            @elseif(auth()->user()?->isAdmin())
                <a href="{{ route('lpks.create') }}">Tambah LPK pertama</a>.
            @endif
        </div>
    @endif
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
