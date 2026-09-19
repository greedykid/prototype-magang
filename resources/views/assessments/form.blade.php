@extends('layouts.app')

@section('title', 'Asesmen | SIMASADI')

@section('content')
<div class="page-heading">
    <div>
        <a class="back-link" href="{{ route('assessments.index') }}">Semua asesmen</a>
        <h1>{{ $assessment->exists ? 'Ubah asesmen' : 'Tambah asesmen' }}</h1>
    </div>
</div>

<form class="panel form-grid" method="POST" action="{{ $assessment->exists ? route('assessments.update', $assessment) : route('assessments.store') }}">
    @csrf
    @if($assessment->exists) @method('PUT') @endif
    <label class="full">
        LPK
        <select name="lpk_id" required>
            <option value="">Pilih LPK</option>
            @foreach($lpks as $lpk)
                <option value="{{ $lpk->id }}" @selected(old('lpk_id', $assessment->lpk_id) == $lpk->id)>{{ $lpk->name }}</option>
            @endforeach
        </select>
    </label>
    <label class="full">
        Judul
        <input name="title" value="{{ old('title', $assessment->title) }}" required>
    </label>
    <label>
        Jenis asesmen
        <input name="assessment_type" value="{{ old('assessment_type', $assessment->assessment_type ?: 'INITIAL') }}" required>
    </label>
    <label>
        Status
        <select name="status">
            @foreach(['PLANNED', 'SCHEDULED', 'IN_PROGRESS', 'COMPLETED', 'CANCELLED'] as $status)
                <option @selected(old('status', $assessment->status ?: 'PLANNED') === $status)>
                    {{ ["PLANNED" => "Direncanakan", "SCHEDULED" => "Terjadwal", "IN_PROGRESS" => "Sedang Berlangsung", "COMPLETED" => "Selesai", "CANCELLED" => "Dibatalkan", "SUBMITTED" => "Diajukan", "UNDER_REVIEW" => "Sedang Ditinjau", "NEED_REVISION" => "Perlu Perbaikan", "APPROVED" => "Disetujui", "REJECTED" => "Ditolak"][$status] ?? $status }}
                </option>
            @endforeach
        </select>
    </label>
    <label>
        Mulai
        <input type="datetime-local" name="start_at" value="{{ old('start_at', $assessment->start_at?->format('Y-m-d\TH:i')) }}" required>
    </label>
    <label>
        Selesai
        <input type="datetime-local" name="end_at" value="{{ old('end_at', $assessment->end_at?->format('Y-m-d\TH:i')) }}" required>
    </label>
    <label>
        Lokasi
        <input name="location" value="{{ old('location', $assessment->location) }}">
    </label>
    <label>
        Lead assessor
        <input name="lead_assessor" value="{{ old('lead_assessor', $assessment->lead_assessor) }}">
    </label>
    <label class="full">
        Catatan
        <textarea name="notes" rows="4">{{ old('notes', $assessment->notes) }}</textarea>
    </label>
    <div class="form-actions full">
        <a class="button ghost" href="{{ route('assessments.index') }}">Batal</a>
        <button class="button primary" type="submit">
            <x-icon :name="$assessment->exists ? 'edit' : 'plus'" size="16" />
            <span>{{ $assessment->exists ? 'Simpan perubahan' : 'Simpan asesmen' }}</span>
        </button>
    </div>
</form>
@endsection
