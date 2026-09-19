@extends('layouts.app')

@section('title', 'Amandemen | SIMASADI')

@section('content')
<div class="page-heading">
    <div>
        <a class="back-link" href="{{ route('amendments.index') }}">Semua amandemen</a>
        <h1>{{ $amendment->exists ? 'Ubah amandemen' : 'Catat pengajuan amandemen' }}</h1>
    </div>
</div>

<form class="panel form-grid" method="POST" action="{{ $amendment->exists ? route('amendments.update', $amendment) : route('amendments.store') }}">
    @csrf
    @if($amendment->exists) @method('PUT') @endif
    <label class="full">
        LPK
        <select name="lpk_id" required>
            <option value="">Pilih LPK</option>
            @foreach($lpks as $lpk)
                <option value="{{ $lpk->id }}" @selected(old('lpk_id', $amendment->lpk_id) == $lpk->id)>{{ $lpk->name }}</option>
            @endforeach
        </select>
    </label>
    <label>
        Nomor pengajuan
        <input name="submission_number" value="{{ old('submission_number', $amendment->submission_number) }}" required>
    </label>
    <label>
        Jenis amandemen
        <input name="amendment_type" value="{{ old('amendment_type', $amendment->amendment_type) }}" required placeholder="Contoh: Perubahan lampiran">
    </label>
    <label>
        Tanggal pengajuan
        <input type="date" name="submitted_at" value="{{ old('submitted_at', $amendment->submitted_at?->format('Y-m-d')) }}" required>
    </label>
    <label>
        Status
        <select name="status">
            @foreach(['SUBMITTED', 'UNDER_REVIEW', 'NEED_REVISION', 'APPROVED', 'REJECTED', 'COMPLETED'] as $status)
                <option @selected(old('status', $amendment->status ?: 'SUBMITTED') === $status)>
                    {{ ["PLANNED" => "Direncanakan", "SCHEDULED" => "Terjadwal", "IN_PROGRESS" => "Sedang Berlangsung", "COMPLETED" => "Selesai", "CANCELLED" => "Dibatalkan", "SUBMITTED" => "Diajukan", "UNDER_REVIEW" => "Sedang Ditinjau", "NEED_REVISION" => "Perlu Perbaikan", "APPROVED" => "Disetujui", "REJECTED" => "Ditolak"][$status] ?? $status }}
                </option>
            @endforeach
        </select>
    </label>
    <label>
        Target penyelesaian
        <input type="date" name="target_date" value="{{ old('target_date', $amendment->target_date?->format('Y-m-d')) }}">
    </label>
    <label>
        Tanggal selesai
        <input type="date" name="completed_at" value="{{ old('completed_at', $amendment->completed_at?->format('Y-m-d')) }}">
    </label>
    <label class="full">
        Catatan
        <textarea name="notes" rows="4">{{ old('notes', $amendment->notes) }}</textarea>
    </label>
    <div class="form-actions full">
        <a class="button ghost" href="{{ route('amendments.index') }}">Batal</a>
        <button class="button primary" type="submit">
            <x-icon :name="$amendment->exists ? 'edit' : 'plus'" size="16" />
            <span>{{ $amendment->exists ? 'Simpan perubahan' : 'Simpan amandemen' }}</span>
        </button>
    </div>
</form>
@endsection
