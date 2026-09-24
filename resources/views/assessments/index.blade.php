@extends('layouts.app')

@section('title', 'Program Asesmen | SIMASADI')

@section('content')
<x-page-header
    title="Program asesmen"
    subtitle="Jadwal dan progres asesmen semua LPK."
>
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
</x-page-header>
<section class="panel">
    <form class="table-filters" method="GET">
        <div class="table-filter-grid">
            <label>Cari agenda<input name="search" value="{{ $search }}" placeholder="Judul agenda"></label>
            <label>LPK<select name="lpk_id"><option value="">Semua LPK</option>@foreach($lpks as $lpk)<option value="{{ $lpk->id }}" @selected($lpkId === $lpk->id)>{{ $lpk->registration_number }} - {{ $lpk->name }}</option>@endforeach</select></label>
            <label>Jenis<select name="assessment_type"><option value="">Semua jenis (KAN U-01)</option>@foreach($assessmentTypes as $key => $label)<option value="{{ $key }}" @selected($assessmentType === $key)>{{ $label }}</option>@endforeach</select></label>
            <label>Status<select name="status"><option value="">Semua status</option>@foreach(['PLANNED' => 'Direncanakan', 'SCHEDULED' => 'Terjadwal', 'IN_PROGRESS' => 'Berjalan', 'COMPLETED' => 'Selesai', 'CANCELLED' => 'Dibatalkan'] as $value => $label)<option value="{{ $value }}" @selected($status === $value)>{{ $label }}</option>@endforeach</select></label>
            <label>Status TP (SLA KAN)
                <select name="tp_status">
                    <option value="">Semua status TP</option>
                    <option value="NONE" @selected(($tpFilter ?? '') === 'NONE')>Nihil / Belum Ada Temuan</option>
                    <option value="ACTIVE" @selected(($tpFilter ?? '') === 'ACTIVE')>Sedang Perbaikan / Verifikasi</option>
                    <option value="DUE_SOON" @selected(($tpFilter ?? '') === 'DUE_SOON')>Jatuh Tempo (&le; 14 Hari)</option>
                    <option value="OVERDUE" @selected(($tpFilter ?? '') === 'OVERDUE')>Melewati Batas Waktu (Overdue)</option>
                    <option value="SATISFIED" @selected(($tpFilter ?? '') === 'SATISFIED')>Dinyatakan Memenuhi</option>
                </select>
            </label>
            <label>Mulai dari<input type="date" name="start_from" value="{{ $startFrom }}"></label>
            <label>Mulai sampai<input type="date" name="start_to" value="{{ $startTo }}"></label>
        </div>
        <div class="table-filter-actions">
            <button class="button secondary" type="submit">Terapkan filter</button>
            @if($search || $lpkId || $assessmentType || $status || ($tpFilter ?? null) || $startFrom || $startTo)
                <a class="button ghost" href="{{ route('assessments.index') }}">Reset</a>
            @endif
        </div>
    </form>
    @if($assessments->count())
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Agenda</th>
                        <th>LPK</th>
                        <th>Waktu</th>
                        <th>Tindakan Perbaikan (TP)</th>
                        <th>Biaya Asesor</th>
                        <th>Status Asesmen</th>
                        <th>Aksi</th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($assessments as $assessment)
                        <tr>
                            <td>
                                <strong>{{ $assessment->title }}</strong>
                                <span>{{ $assessment->assessment_type_label }}</span>
                                @if($assessment->sk_number)
                                    <div style="margin-top: 3px;">
                                        <span class="badge-tp badge-tp-success" style="font-size: 10px; display: inline-block;" title="SK: {{ $assessment->sk_number }} {{ $assessment->sk_date ? '(' . $assessment->sk_date->format('d/m/Y') . ')' : '' }} {{ $assessment->sk_lead_time_label ? '• Rentang: ' . $assessment->sk_lead_time_label : '' }}">
                                            SK: {{ $assessment->sk_number }}
                                            @if($assessment->sk_lead_time_days !== null)
                                                ({{ $assessment->sk_lead_time_days }} hr)
                                            @endif
                                        </span>
                                    </div>
                                @endif
                            </td>
                            <td>
                                <strong>{{ $assessment->lpk->name }}</strong>
                                <span style="display: block; font-size: 11.5px; color: var(--muted); font-weight: 500;">{{ $assessment->lpk->registration_number }}</span>
                            </td>
                            <td>{{ $assessment->start_at->format('d M Y, H:i') }} WIB</td>
                            <td>
                                @php $badge = $assessment->tp_sla_badge; @endphp
                                <div style="display: flex; flex-direction: column; gap: 3px;">
                                    <span class="badge-tp badge-tp-{{ $badge['type'] }}" title="{{ $badge['detail'] }}">
                                        {{ $badge['label'] }}
                                    </span>
                                    @if($assessment->effective_tp_due_date && $assessment->tp_status !== \App\Models\Assessment::TP_STATUS_NONE)
                                        <small style="color: var(--muted); font-size: 11px;">
                                            Batas: {{ $assessment->effective_tp_due_date->format('d/m/Y') }}
                                        </small>
                                    @endif
                                </div>
                            </td>
                            <td><x-status :value="$assessment->expense ? $assessment->expense->status : 'BELUM_DILAPORKAN'" /></td>
                            <td>
                                <x-status :value="$assessment->status" />
                                @if($assessment->is_submission_overdue)
                                    <div style="margin-top: 3px;">
                                        <span class="badge-tp badge-tp-danger" style="font-size: 10px; display: inline-block;" title="Toleransi pengisian asesmen (akhir bulan dan tahun yang sama dari waktu kunjungan) telah terlampaui">
                                            Lewat Jadwal Asesmen
                                        </span>
                                    </div>
                                @endif
                            </td>
                            <td><a href="{{ route('assessments.show', $assessment) }}">Detail</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
        {{ $assessments->links() }}
    @else
        <div class="empty">
            {{ $search || $lpkId || $assessmentType || $status || ($tpFilter ?? null) || $startFrom || $startTo ? 'Tidak ada program asesmen yang cocok dengan filter.' : 'Belum ada program asesmen.' }}
            @if($search || $lpkId || $assessmentType || $status || ($tpFilter ?? null) || $startFrom || $startTo)
                <a class="button ghost empty-action" href="{{ route('assessments.index') }}">Reset filter</a>
            @endif
        </div>
    @endif
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
