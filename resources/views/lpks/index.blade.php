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
        @if(auth()->user()?->hasRole(['admin', 'staf']))
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
                <input name="search" value="{{ $search }}" placeholder="Nama atau nomor registrasi">
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
            <table>
                <thead>
                    <tr>
                        <th>LPK</th>
                        <th>Status</th>
                        <th>Akreditasi</th>
                        <th>Masalah</th>
                        <th><span class="sr-only">Buka</span></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($lpks as $lpk)
                        <tr>
                            <td>
                                <strong>{{ $lpk->name }}</strong>
                                <span>{{ $lpk->registration_number }}</span>
                            </td>
                            <td><x-status :value="$lpk->status" /></td>
                            <td>{{ $lpk->accreditations_count }}</td>
                            <td>{{ $lpk->issues_count }}</td>
                            <td><a href="{{ route('lpks.show', $lpk) }}">Detail</a></td>
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
            @elseif(auth()->user()?->hasRole(['admin', 'staf']))
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
    'columns' => ['ID LPK', 'Nomor Registrasi', 'Nama Lembaga Penilaian Kesesuaian', 'Status Operasional', 'Total Akreditasi', 'Total Isu', 'Tanggal Terdaftar']
])

@if(auth()->user()?->hasRole(['admin', 'staf']))
    @include('lpks.partials.import-modal')
@endif
@endsection
