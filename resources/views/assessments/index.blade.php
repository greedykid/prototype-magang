@extends('layouts.app')

@section('title', 'Program Asesmen | SIMASADI')

@section('content')
<div class="page-heading">
    <div>
        <h1>Program asesmen</h1>
        <p class="lede">Jadwal dan progres asesmen semua LPK.</p>
    </div>
    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
        <button type="button" class="button secondary" onclick="window.openModal('modal-sheets-sync-assessments')">
            <x-icon name="sheets" size="16" style="color: #0f9d58;" />
            <span>Google Sheets & Ekspor</span>
        </button>
        @if(auth()->user()?->isAdmin())
            <a class="button primary" href="{{ route('assessments.create') }}">
                <x-icon name="plus" size="16" />
                <span>Tambah asesmen</span>
            </a>
        @endif
    </div>
</div>
<section class="panel">
    <form class="table-filters" method="GET">
        <div class="table-filter-grid"><label>Cari agenda<input name="search" value="{{ $search }}" placeholder="Judul agenda"></label><label>LPK<select name="lpk_id"><option value="">Semua LPK</option>@foreach($lpks as $lpk)<option value="{{ $lpk->id }}" @selected($lpkId === $lpk->id)>{{ $lpk->name }}</option>@endforeach</select></label><label>Jenis<select name="assessment_type"><option value="">Semua jenis (KAN U-01)</option>@foreach($assessmentTypes as $key => $label)<option value="{{ $key }}" @selected($assessmentType === $key)>{{ $label }}</option>@endforeach</select></label><label>Status<select name="status"><option value="">Semua status</option>@foreach(['PLANNED' => 'Direncanakan', 'SCHEDULED' => 'Terjadwal', 'IN_PROGRESS' => 'Berjalan', 'COMPLETED' => 'Selesai', 'CANCELLED' => 'Dibatalkan'] as $value => $label)<option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>@endforeach</select></label><label>Mulai dari<input type="date" name="start_from" value="{{ $startFrom }}"></label><label>Mulai sampai<input type="date" name="start_to" value="{{ $startTo }}"></label></div>
        <div class="table-filter-actions"><button class="button secondary" type="submit">Terapkan filter</button>@if($search || $lpkId || $assessmentType || $status || $startFrom || $startTo)<a class="button ghost" href="{{ route('assessments.index') }}">Reset</a>@endif</div>
    </form>
    @if($assessments->count())<div class="table-wrap"><table><thead><tr><th>Agenda</th><th>LPK</th><th>Waktu</th><th>Biaya Asesor</th><th>Status</th><th></th></tr></thead><tbody>@foreach($assessments as $assessment)<tr><td><strong>{{ $assessment->title }}</strong><span>{{ $assessment->assessment_type_label }}</span></td><td>{{ $assessment->lpk->name }}</td><td>{{ $assessment->start_at->format('d M Y, H:i') }} WIB</td><td><x-status :value="$assessment->expense ? $assessment->expense->status : 'BELUM_DILAPORKAN'" /></td><td><x-status :value="$assessment->status" /></td><td><a href="{{ route('assessments.show',$assessment) }}">Detail</a></td></tr>@endforeach</tbody></table></div>{{ $assessments->links() }}@else<div class="empty">{{ $search || $lpkId || $assessmentType || $status || $startFrom || $startTo ? 'Tidak ada program asesmen yang cocok dengan filter.' : 'Belum ada program asesmen.' }} @if($search || $lpkId || $assessmentType || $status || $startFrom || $startTo)<a class="button ghost empty-action" href="{{ route('assessments.index') }}">Reset filter</a>@endif</div>@endif
</section>

@include('partials.sheets-modal', [
    'modalId' => 'modal-sheets-sync-assessments',
    'title' => 'Integrasi Google Sheets: Rekapitulasi Biaya SBM Asesor',
    'subtitle' => 'Sinkronkan data realisasi biaya transportasi, akomodasi, dan uang harian asesor (SBM Kemenkeu) ke Google Sheets Anda secara live.',
    'exportUrl' => route('reports.expenses.export'),
    'feedUrl' => route('feeds.expenses', ['key' => env('SHEETS_FEED_KEY', 'simasadi-live')]),
    'fileName' => 'rekap-biaya-sbm-simasadi.csv',
    'columns' => [
        'ID Asesmen', 'Judul Agenda', 'Nama LPK', 'Nomor Registrasi LPK', 'Jenis Asesmen',
        'Waktu Pelaksanaan', 'Status Asesmen', 'Uang Harian (Rp)', 'Transportasi (Rp)',
        'Akomodasi (Rp)', 'Paket Data (Rp)', 'Total Biaya (Rp)', 'Status SBM', 'No. Kwitansi / SPPD',
        'Catatan Verifikator KAN', 'Waktu Verifikasi'
    ]
])
@endsection
