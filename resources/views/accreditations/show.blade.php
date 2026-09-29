@extends('layouts.app')

@section('title', 'Detail Akreditasi #' . $accreditation->id . ' | SIMASADI')

@section('content')
<x-page-header
    :backUrl="route('accreditations.index')"
    backText="Semua proses"
    :title="'Proses akreditasi #' . $accreditation->id"
    :subtitle="$accreditation->lpk->name . ' (' . $accreditation->lpk->registration_number . ')'"
>
    <x-status :value="$accreditation->status" />
</x-page-header>

<section class="panel detail-list">
    <div>
        <dt>LPK</dt>
        <dd>{{ $accreditation->lpk->name }}</dd>
    </div>
    <div>
        <dt>Status Proses</dt>
        <dd><x-status :value="$accreditation->status" /></dd>
    </div>
    <div>
        <dt>Tanggal mulai</dt>
        <dd>{{ $accreditation->start_date?->format('d M Y') ?: 'Belum diisi' }}</dd>
    </div>
    <div>
        <dt>Pantek</dt>
        <dd>{{ $accreditation->pantek_at?->format('d M Y') ?: 'Belum diisi' }}</dd>
    </div>
    <div>
        <dt>Target output</dt>
        <dd>{{ $accreditation->target_output_at?->format('d M Y') ?: 'Belum diisi' }}</dd>
    </div>
    <div>
        <dt>Output dirilis</dt>
        <dd>{{ $accreditation->output_released_at?->format('d M Y') ?: 'Belum dirilis' }}</dd>
    </div>
    <div>
        <dt>PIC</dt>
        <dd>{{ $accreditation->pic ?: 'Belum diisi' }}</dd>
    </div>
    <div>
        <dt>Catatan</dt>
        <dd>{{ $accreditation->notes ?: 'Belum ada catatan.' }}</dd>
    </div>
</section>

@include('accreditations.partials.quality-gate')

<section class="panel" style="margin-top: 24px;">
    <div style="display: flex; justify-content: space-between; align-items: center; margin-bottom: 16px;">
        <div>
            <h3 style="margin: 0; font-size: 16px; font-weight: 700; color: var(--ink);">Asesmen Terkait Siklus Akreditasi</h3>
            <p style="margin: 2px 0 0; font-size: 13px; color: var(--muted);">Daftar pelaksanaan asesmen lapangan KAN untuk {{ $accreditation->lpk->name }}.</p>
        </div>
        <a href="{{ route('assessments.create', ['lpk_id' => $accreditation->lpk_id]) }}" class="button secondary">
            <x-icon name="plus" size="14" />
            <span>Tambah Asesmen</span>
        </a>
    </div>

    @if($accreditation->lpk->assessments->count())
        <div class="table-wrap">
            <table>
                <thead>
                    <tr>
                        <th>Judul Asesmen</th>
                        <th>Tipe KAN</th>
                        <th>Tanggal Pelaksanaan</th>
                        <th>Status</th>
                        <th>Lead Assessor</th>
                        <th></th>
                    </tr>
                </thead>
                <tbody>
                    @foreach($accreditation->lpk->assessments as $asm)
                        <tr>
                            <td>
                                <strong>{{ $asm->title }}</strong>
                            </td>
                            <td>{{ $asm->assessment_type_label }}</td>
                            <td>{{ $asm->start_at->isSameDay($asm->end_at) ? $asm->start_at->format('d M Y') : $asm->start_at->format('d M Y') . ' s/d ' . $asm->end_at->format('d M Y') }}</td>
                            <td><x-status :value="$asm->status" /></td>
                            <td>{{ $asm->lead_assessor ?: '-' }}</td>
                            <td><a href="{{ route('assessments.show', $asm) }}">Detail</a></td>
                        </tr>
                    @endforeach
                </tbody>
            </table>
        </div>
    @else
        <div class="empty">
            Belum ada asesmen yang dijadwalkan untuk LPK ini.
        </div>
    @endif
</section>
@endsection
