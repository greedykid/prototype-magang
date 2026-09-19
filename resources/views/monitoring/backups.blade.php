@extends('layouts.app')

@section('title', 'Backup | SIMASADI')

@section('content')
<div class="page-heading"><div><h1>Riwayat backup</h1><p class="lede">Catatan status backup manual. Halaman ini tidak menjalankan backup nyata.</p></div></div>
<section class="panel">
    <form class="table-filters" method="GET">
        <div class="table-filter-grid"><label>Sistem<input name="system" value="{{ $system }}" placeholder="Nama sistem"></label><label>Status<select name="status"><option value="">Semua status</option><option value="SUCCESS" @selected($status === 'SUCCESS')>Berhasil</option><option value="FAILED" @selected($status === 'FAILED')>Gagal</option><option value="UNKNOWN" @selected($status === 'UNKNOWN')>Tidak diketahui</option></select></label><label>Dicatat oleh<select name="recorded_by"><option value="">Semua pencatat</option>@foreach($users as $user)<option value="{{ $user->id }}" @selected($recordedBy === $user->id)>{{ $user->name }}</option>@endforeach</select></label><label>Selesai dari<input type="date" name="finished_from" value="{{ $finishedFrom }}"></label><label>Selesai sampai<input type="date" name="finished_to" value="{{ $finishedTo }}"></label></div>
        <div class="table-filter-actions"><button class="button secondary" type="submit">Terapkan filter</button>@if($system || $status || $recordedBy || $finishedFrom || $finishedTo)<a class="button ghost" href="{{ route('monitoring.backups') }}">Reset</a>@endif</div>
    </form>
    @if($backups->count())<div class="table-wrap"><table><thead><tr><th>Sistem</th><th>Status</th><th>Selesai</th><th>Ukuran</th><th>Dicatat oleh</th></tr></thead><tbody>@foreach($backups as $backup)<tr><td>{{ $backup->system }}</td><td><x-status :value="$backup->status" /></td><td>{{ $backup->finished_at?->format('d M Y, H:i') ? $backup->finished_at->format('d M Y, H:i').' WIB' : 'Berjalan' }}</td><td>{{ $backup->size ?: 'Belum dicatat' }}</td><td>{{ $backup->recorder?->name ?: 'Sistem' }}</td></tr>@endforeach</tbody></table></div>{{ $backups->links() }}@else<div class="empty">{{ $system || $status || $recordedBy || $finishedFrom || $finishedTo ? 'Tidak ada histori backup yang cocok dengan filter.' : 'Belum ada histori backup.' }} @if($system || $status || $recordedBy || $finishedFrom || $finishedTo)<a class="button ghost empty-action" href="{{ route('monitoring.backups') }}">Reset filter</a>@endif</div>@endif
</section>
@endsection
