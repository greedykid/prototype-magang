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
                        <th data-label="No Akreditasi" style="background-color: #eef4fc; color: #0f172a; font-weight: 700; font-size: 12px; letter-spacing: 0.5px; padding: 13px 14px; border-bottom: 2px solid #cbd5e1; white-space: nowrap;">NO AKREDITASI</th>
                        <th data-label="Nama LPK" style="background-color: #eef4fc; color: #0f172a; font-weight: 700; font-size: 12px; letter-spacing: 0.5px; padding: 13px 14px; border-bottom: 2px solid #cbd5e1; white-space: nowrap;">NAMA LPK</th>
                        <th data-label="Masa Berlaku Akreditasi" style="background-color: #eef4fc; color: #0f172a; font-weight: 700; font-size: 12px; letter-spacing: 0.5px; padding: 13px 14px; border-bottom: 2px solid #cbd5e1; white-space: nowrap;">MASA BERLAKU AKREDITASI (AWAL DAN AKHIR)</th>
                        <th data-label="Keterangan" style="background-color: #eef4fc; color: #0f172a; font-weight: 700; font-size: 12px; letter-spacing: 0.5px; padding: 13px 14px; border-bottom: 2px solid #cbd5e1; white-space: nowrap;">KETERANGAN</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lpks as $lpk)
                        <tr class="clickable-row" data-href="{{ route('lpks.show', $lpk) }}" tabindex="0" role="link" title="Klik baris untuk melihat detail LPK {{ $lpk->name }}">
                            <!-- 1. NO AKREDITASI -->
                            <td class="col-lpk-no" style="white-space: nowrap; vertical-align: top; padding: 12px 14px;">
                                <div class="lpk-card-reg-wrap">
                                    <a href="{{ route('lpks.show', $lpk) }}" style="font-family: ui-monospace, SFMono-Regular, Menlo, Monaco, Consolas, monospace; font-weight: 700; font-size: 13px; color: {{ $lpk->accreditation_number ? '#0f172a' : 'var(--muted)' }}; text-decoration: none;" class="hover-underline lpk-reg-link" title="Buka rincian LPK">
                                        {{ $lpk->accreditation_number ?: $lpk->registration_number }}
                                    </a>
                                </div>
                                @php
                                    $lpkAlerts = $lpk->getActiveSurveillanceAlerts();
                                @endphp
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
                            <td class="col-lpk-name" style="vertical-align: top; min-width: 250px; padding: 12px 14px;">
                                <a href="{{ route('lpks.show', $lpk) }}" style="font-weight: 600; color: #0f172a; text-decoration: none; font-size: 13.5px; line-height: 1.4; display: block;" class="hover-underline lpk-name-link" title="Buka rincian LPK">
                                    {{ $lpk->name }}
                                </a>
                                <div class="lpk-status-wrap" style="margin-top: 5px; display: flex; align-items: center; gap: 6px; flex-wrap: wrap;">
                                    <x-status :value="$lpk->dynamic_status" />
                                    @if($lpk->pic)
                                        <span style="font-size: 11px; color: var(--muted); background: #f1f5f9; padding: 1px 6px; border-radius: 4px;">
                                            PIC: {{ $lpk->pic->name }}
                                        </span>
                                    @endif
                                </div>
                            </td>

                            <!-- 5. MASA BERLAKU AKREDITASI (AWAL DAN AKHIR) -->
                            <td class="col-lpk-validity" style="vertical-align: top; white-space: nowrap; padding: 12px 14px;">
                                @if($lpk->certificate_date || $lpk->expired_at)
                                    <div style="font-size: 13px; line-height: 1.45;">
                                        <div>
                                            <span style="color: #64748b; font-size: 11.5px; display: inline-block; min-width: 42px;">Awal:</span>
                                            <strong style="color: #0f172a;">{{ $lpk->certificate_date ? $lpk->certificate_date->format('d/m/Y') : '-' }}</strong>
                                        </div>
                                        <div style="margin-top: 2px;">
                                            <span style="color: #64748b; font-size: 11.5px; display: inline-block; min-width: 42px;">Akhir:</span>
                                            <strong style="{{ $lpk->isExpired() ? 'color: #dc2626;' : 'color: #0f172a;' }}">{{ $lpk->expired_at ? $lpk->expired_at->format('d/m/Y') : '-' }}</strong>
                                        </div>
                                    </div>
                                    @if($lpk->isExpired())
                                        <span class="status status-danger" style="font-size: 10px; padding: 1px 6px; margin-top: 4px; display: inline-block;">Kedaluwarsa</span>
                                    @elseif($lpk->isExpiringSoon())
                                        <span class="status status-warn" style="font-size: 10px; padding: 1px 6px; margin-top: 4px; display: inline-block;">Mendekati Kedaluwarsa</span>
                                    @endif
                                @else
                                    <span style="color: var(--muted); font-size: 12px;">-</span>
                                @endif
                            </td>

                            <!-- 6. KETERANGAN (OTOMATIS + MANUAL PIC) -->
                            <td class="col-lpk-notes" style="vertical-align: top; min-width: 250px; padding: 12px 14px;">
                                {{-- Status Siklus / Proses Berjalan Otomatis --}}
                                <div class="lpk-auto-status" style="font-size: 12.5px; color: #0f172a; background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 6px; padding: 7px 10px; line-height: 1.45; margin-bottom: {{ $lpk->notes ? '6px' : '0' }};">
                                    @if(str_contains($lpk->dynamic_keterangan, ':'))
                                        @php
                                            [$processName, $statusDetail] = explode(':', $lpk->dynamic_keterangan, 2);
                                        @endphp
                                        <strong style="color: #0f172a; font-weight: 700;">{{ $processName }}:</strong><span style="color: #334155; font-weight: 500;">{{ $statusDetail }}</span>
                                    @else
                                        <strong style="color: #0f172a; font-weight: 700;">{{ $lpk->dynamic_keterangan }}</strong>
                                    @endif
                                </div>

                                {{-- Catatan Manual PIC (Bila Ada) --}}
                                @if($lpk->notes)
                                    <div class="lpk-manual-notes" style="font-size: 11.5px; color: #713f12; background: #fefce8; border: 1px solid #fef08a; border-radius: 6px; padding: 5px 8px; line-height: 1.35;">
                                        <strong style="font-size: 10px; color: #854d0e; text-transform: uppercase; display: block; margin-bottom: 2px;">Catatan PIC:</strong>
                                        <div style="white-space: pre-line;">{{ $lpk->notes }}</div>
                                    </div>
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
