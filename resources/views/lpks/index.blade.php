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
        <a class="button primary" href="{{ route('lpks.create') }}">
            <x-icon name="plus" size="16" />
            <span>Tambah LPK</span>
        </a>
    @endif
</x-page-header>

<section class="panel">
    <form class="table-filters" method="GET">
        <div class="table-filter-grid">
            <label>
                Cari LPK
                <input name="search" value="{{ $search }}" placeholder="Cari no. akreditasi, nama, lingkup, alamat...">
            </label>
            <label>
                Status LPK
                <select name="status">
                    <option value="">Semua status</option>
                    <option value="ACTIVE" @selected($status === 'ACTIVE')>Aktif</option>
                    <option value="SURVEILLANCE_OVERDUE" @selected($status === 'SURVEILLANCE_OVERDUE')>Lewat Jadwal Surveilen</option>
                    <option value="SURVEILLANCE_DUE" @selected($status === 'SURVEILLANCE_DUE')>Jatuh Tempo Surveilen</option>
                    <option value="EXPIRED" @selected($status === 'EXPIRED')>Kedaluwarsa (Expired)</option>
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
        <div class="table-filter-actions">
            <button class="button secondary" type="submit">Terapkan filter</button>
            @if($search || $status || $surveillance || $expiry)
                <a class="button ghost" href="{{ route('lpks.index') }}">Reset</a>
            @endif
        </div>
    </form>

    @if($lpks->count())
        <div class="table-wrap">
            <table class="table-lpks-custom">
                <thead>
                    <tr style="background-color: #eef4fc;">
                        <th data-label="No. Akreditasi" style="background-color: #eef4fc; color: #0f172a; font-weight: 700; font-size: 12px; letter-spacing: 0.5px; padding: 13px 14px; border-bottom: 2px solid #cbd5e1; white-space: nowrap;">NO. AKREDITASI</th>
                        <th data-label="Nama LPK" style="background-color: #eef4fc; color: #0f172a; font-weight: 700; font-size: 12px; letter-spacing: 0.5px; padding: 13px 14px; border-bottom: 2px solid #cbd5e1; white-space: nowrap;">NAMA LPK</th>
                        <th data-label="Alamat" style="background-color: #eef4fc; color: #0f172a; font-weight: 700; font-size: 12px; letter-spacing: 0.5px; padding: 13px 14px; border-bottom: 2px solid #cbd5e1; white-space: nowrap;">ALAMAT</th>
                        <th data-label="Telepon / Fax" style="background-color: #eef4fc; color: #0f172a; font-weight: 700; font-size: 12px; letter-spacing: 0.5px; padding: 13px 14px; border-bottom: 2px solid #cbd5e1; white-space: nowrap;">TELEPON / FAX</th>
                        <th data-label="Email" style="background-color: #eef4fc; color: #0f172a; font-weight: 700; font-size: 12px; letter-spacing: 0.5px; padding: 13px 14px; border-bottom: 2px solid #cbd5e1; white-space: nowrap;">EMAIL</th>
                        <th data-label="Lingkup" style="background-color: #eef4fc; color: #0f172a; font-weight: 700; font-size: 12px; letter-spacing: 0.5px; padding: 13px 14px; border-bottom: 2px solid #cbd5e1; white-space: nowrap;">LINGKUP</th>
                        <th data-label="Masa Berlaku" style="background-color: #eef4fc; color: #0f172a; font-weight: 700; font-size: 12px; letter-spacing: 0.5px; padding: 13px 14px; border-bottom: 2px solid #cbd5e1; white-space: nowrap;">MASA BERLAKU AKREDITASI (EXPIRED)</th>
                        <th data-label="Berkas" style="background-color: #eef4fc; color: #0f172a; font-weight: 700; font-size: 12px; letter-spacing: 0.5px; padding: 13px 14px; border-bottom: 2px solid #cbd5e1; white-space: nowrap; text-align: center;">LINK</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lpks as $lpk)
                        <tr>
                            <!-- 1. NO. AKREDITASI -->
                            <td class="col-lpk-no" style="white-space: nowrap; vertical-align: top; padding: 12px 14px;">
                                <div class="lpk-card-reg-wrap">
                                    <a href="{{ route('lpks.show', $lpk) }}" style="font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-weight: 700; font-size: 13px; color: #1e293b; text-decoration: none;" class="hover-underline lpk-reg-link" title="Buka rincian LPK">
                                        {{ $lpk->registration_number }}
                                    </a>
                                </div>
                                @php($lpkAlerts = $lpk->getActiveSurveillanceAlerts())
                                @if(!empty($lpkAlerts))
                                    <div class="lpk-card-alerts" style="margin-top: 4px; display: flex; gap: 4px; flex-wrap: wrap;">
                                        @foreach($lpkAlerts as $alt)
                                            <span class="badge lpk-alert-badge" style="background-color: {{ $alt['is_urgent'] ? '#fee2e2' : '#fef3c7' }}; color: {{ $alt['is_urgent'] ? '#991b1b' : '#92400e' }}; font-size: 10px; font-weight: 700; padding: 2px 5px; border-radius: 4px;" title="{{ $alt['description'] }}">
                                                ⚠ {{ $alt['name'] }}
                                            </span>
                                        @endforeach
                                    </div>
                                @endif
                            </td>

                            <!-- 2. NAMA LPK -->
                            <td class="col-lpk-name" style="vertical-align: top; min-width: 220px; padding: 12px 14px;">
                                <a href="{{ route('lpks.show', $lpk) }}" style="font-weight: 600; color: #1d4ed8; text-decoration: none; font-size: 13.5px; line-height: 1.4; display: block;" class="hover-underline lpk-name-link" title="Buka rincian LPK">
                                    {{ $lpk->name }}
                                </a>
                                <div class="lpk-status-wrap" style="margin-top: 4px; display: flex; align-items: center; gap: 6px;">
                                    <x-status :value="$lpk->dynamic_status" />
                                </div>
                            </td>

                            <!-- 3. ALAMAT -->
                            <td class="col-lpk-address" style="vertical-align: top; min-width: 180px; max-width: 260px; font-size: 12.5px; color: #334155; line-height: 1.4; padding: 12px 14px;">
                                <div class="lpk-val-address">{{ $lpk->address ?: '-' }}</div>
                            </td>

                            <!-- 4. TELEPON / FAX -->
                            <td class="col-lpk-phone" style="vertical-align: top; white-space: nowrap; font-size: 12.5px; color: #334155; padding: 12px 14px;">
                                @if($lpk->phone)
                                    <a href="tel:{{ $lpk->phone }}" style="color: inherit; text-decoration: none;" title="Hubungi telepon">
                                        {{ $lpk->phone }}
                                    </a>
                                @else
                                    <span style="color: var(--muted);">-</span>
                                @endif
                            </td>

                            <!-- 5. EMAIL -->
                            <td class="col-lpk-email" style="vertical-align: top; white-space: nowrap; font-size: 12.5px; padding: 12px 14px;">
                                @if($lpk->email)
                                    <a href="mailto:{{ $lpk->email }}" style="color: #2563eb; text-decoration: none;" class="hover-underline" title="Kirim email">
                                        {{ $lpk->email }}
                                    </a>
                                @else
                                    <span style="color: var(--muted);">-</span>
                                @endif
                            </td>

                            <!-- 6. LINGKUP -->
                            <td class="col-lpk-scope" style="vertical-align: top; min-width: 260px; max-width: 380px; font-size: 12px; line-height: 1.45; padding: 12px 14px;">
                                @if($lpk->scope)
                                    <div class="lpk-scope-text" style="max-height: 175px; overflow-y: auto; white-space: pre-line; color: #334155; word-break: break-word; padding-right: 6px; margin: 0;" title="{{ $lpk->scope }}">{{ trim($lpk->scope) }}</div>
                                @else
                                    <span style="color: var(--muted);">-</span>
                                @endif
                            </td>

                            <!-- 7. MASA BERLAKU AKREDITASI (EXPIRED) -->
                            <td class="col-lpk-expiry" data-label="Masa Berlaku" style="vertical-align: top; white-space: nowrap; padding: 12px 14px;">
                                @if($lpk->expired_at)
                                    <div class="lpk-expiry-date" style="font-weight: 600; font-size: 13px; {{ $lpk->isExpired() ? 'color: #dc2626;' : 'color: #0f172a;' }}">
                                        {{ $lpk->expired_at->format('d/m/Y') }}
                                    </div>
                                    @if($lpk->certificate_date)
                                        <small class="lpk-cert-date" style="display: block; color: var(--muted); font-size: 11px; margin-top: 1px;">Terbit: {{ $lpk->certificate_date->format('d/m/Y') }}</small>
                                    @endif
                                    @if($lpk->isExpired())
                                        <span class="status status-danger" style="font-size: 10px; padding: 1px 6px; margin-top: 3px; display: inline-block;">Kedaluwarsa</span>
                                    @elseif($lpk->isExpiringSoon())
                                        <span class="status status-warn" style="font-size: 10px; padding: 1px 6px; margin-top: 3px; display: inline-block;">Mendekati Kedaluwarsa</span>
                                    @endif
                                @else
                                    <span style="color: var(--muted); font-size: 12px;">-</span>
                                @endif
                            </td>

                            <!-- 8. LINK -->
                            <td class="col-lpk-link" style="vertical-align: top; text-align: center; white-space: nowrap; padding: 12px 14px;">
                                @if($lpk->drive_url && str_starts_with($lpk->drive_url, 'http'))
                                    <a href="{{ $lpk->drive_url }}" target="_blank" rel="noopener noreferrer" class="button secondary btn-table-drive" style="font-size: 11.5px; padding: 3px 10px; min-height: 28px; display: inline-flex; align-items: center; gap: 5px; text-decoration: none;" title="Buka Berkas Sertifikat, Amandemen & Lampiran di Google Drive">
                                        <span>Drive</span>
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
            {{ $search || $status || $surveillance || $expiry ? 'Tidak ada LPK yang cocok dengan filter.' : 'Belum ada data LPK.' }}
            @if($search || $status || $surveillance || $expiry)
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
