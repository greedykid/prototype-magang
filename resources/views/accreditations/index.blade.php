@extends('layouts.app')

@section('title', 'Akreditasi | SIMASADI')

@section('content')
<x-page-header
    title="Proses akreditasi"
    subtitle="Lihat status proses akreditasi yang terhubung dengan LPK."
/>

<section class="panel">
    <form class="table-filters" method="GET">
        <div class="table-filter-grid">
            <label>
                LPK
                <select name="lpk_id">
                    <option value="">Semua LPK</option>
                    @foreach($lpks as $lpk)
                        <option value="{{ $lpk->id }}" @selected($lpkId === $lpk->id)>{{ $lpk->name }}</option>
                    @endforeach
                </select>
            </label>
            <label>
                Status
                <select name="status">
                    <option value="">Semua status</option>
                    <option value="NOT_STARTED" @selected($status === 'NOT_STARTED')>Belum mulai</option>
                    <option value="IN_PROGRESS" @selected($status === 'IN_PROGRESS')>Berjalan</option>
                    <option value="COMPLETED" @selected($status === 'COMPLETED')>Selesai</option>
                </select>
            </label>
            <label>
                Mulai dari
                <input type="date" name="start_from" value="{{ $startFrom }}">
            </label>
            <label>
                Mulai sampai
                <input type="date" name="start_to" value="{{ $startTo }}">
            </label>
            <label>
                Target dari
                <input type="date" name="target_from" value="{{ $targetFrom }}">
            </label>
            <label>
                Target sampai
                <input type="date" name="target_to" value="{{ $targetTo }}">
            </label>
        </div>
        <div class="table-filter-actions">
            <button class="button secondary" type="submit">Terapkan filter</button>
            @if($lpkId || $status || $startFrom || $startTo || $targetFrom || $targetTo)
                <a class="button ghost" href="{{ route('accreditations.index') }}">Reset</a>
            @endif
        </div>
    </form>

    @if($accreditations->count())
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>LPK</th>
                        <th>Status</th>
                        <th>Billing PNBP</th>
                        <th>TTE BSrE</th>
                        <th>Mulai</th>
                        <th>Target</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($accreditations as $item)
                        <tr>
                            <td>
                                <strong>{{ $item->lpk->name }}</strong>
                                <span>{{ $item->lpk->registration_number }}</span>
                            </td>
                            <td><x-status :value="$item->status" /></td>
                            <td><x-status :value="$item->latestBilling ? $item->latestBilling->status : 'UNPAID'" /></td>
                            <td><x-status :value="$item->signature && $item->signature->is_signed ? 'SIGNED' : 'UNSIGNED'" /></td>
                            <td>{{ $item->start_date?->format('d M Y') ?: 'Belum diisi' }}</td>
                            <td>{{ $item->target_date?->format('d M Y') ?: 'Belum diisi' }}</td>
                            <td><a href="{{ route('accreditations.show', $item) }}">Detail</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $accreditations->links() }}
    @else
        <div class="empty">
            {{ $lpkId || $status || $startFrom || $startTo || $targetFrom || $targetTo ? 'Tidak ada proses akreditasi yang cocok dengan filter.' : 'Belum ada proses akreditasi untuk ditampilkan.' }}
            @if($lpkId || $status || $startFrom || $startTo || $targetFrom || $targetTo)
                <a class="button ghost empty-action" href="{{ route('accreditations.index') }}">Reset filter</a>
            @endif
        </div>
    @endif
</section>
@endsection
