@extends('layouts.app')
@section('title', $formTitle.' | SIMASADI')
@section('content')
<div class="page-heading"><div><a class="back-link" href="{{ route('calendar.index') }}">Kembali ke kalender</a><h1>{{ $formTitle }}</h1><p class="lede">Agenda ini hanya tersimpan di database prototype lokal.</p></div></div>
<form class="panel form-grid" method="POST" action="{{ $event->exists ? route('calendar.events.update', $event) : route('calendar.events.store') }}">
    @csrf @if($event->exists) @method('PUT') @endif
    <label class="full">LPK<select name="lpk_id" required><option value="">Pilih LPK</option>@foreach($lpks as $lpk)<option value="{{ $lpk->id }}" @selected(old('lpk_id', $event->lpk_id) == $lpk->id)>{{ $lpk->name }}</option>@endforeach</select></label>
    <label class="full">Judul agenda<input name="title" value="{{ old('title', $event->title) }}" required placeholder="Contoh: Persiapan asesmen awal"></label>
    <label>Tanggal mulai<input type="date" name="start_date" value="{{ old('start_date', $event->start_at?->format('Y-m-d') ?: ($selectedDate ?? null)) }}" required></label>
    <label>Jam mulai<input type="time" name="start_time" value="{{ old('start_time', $event->start_at?->format('H:i')) }}" required></label>
    <label>Tanggal selesai<input type="date" name="end_date" value="{{ old('end_date', $event->end_at?->format('Y-m-d') ?: ($selectedDate ?? null)) }}" required></label>
    <label>Jam selesai<input type="time" name="end_time" value="{{ old('end_time', $event->end_at?->format('H:i')) }}" required></label>
    <label>Lokasi<input name="location" value="{{ old('location', $event->location) }}" placeholder="Ruang atau tautan rapat"></label>
    <label>Status<select name="status"><option value="PLANNED" @selected(old('status', $event->status ?: 'PLANNED') === 'PLANNED')>Direncanakan</option><option value="IN_PROGRESS" @selected(old('status', $event->status) === 'IN_PROGRESS')>Sedang Berlangsung</option><option value="COMPLETED" @selected(old('status', $event->status) === 'COMPLETED')>Selesai</option><option value="CANCELLED" @selected(old('status', $event->status) === 'CANCELLED')>Dibatalkan</option></select></label>
    <label class="full">Deskripsi<textarea name="description" rows="4">{{ old('description', $event->description) }}</textarea></label>
    <label class="full">Catatan<textarea name="notes" rows="3">{{ old('notes', $event->notes) }}</textarea></label>
    <div class="form-actions full">
        <a class="button secondary" href="{{ route('calendar.index') }}">Batal</a>
        <button class="button primary" type="submit">
            <x-icon :name="$event->exists ? 'edit' : 'plus'" size="16" />
            <span>{{ $event->exists ? 'Simpan perubahan' : 'Simpan agenda' }}</span>
        </button>
    </div>
</form>
@endsection
