@extends('layouts.app')

@section('title', $event->title . ' | SIMASADI')

@section('content')
<div class="page-heading">
    <div>
        <a class="back-link" href="{{ route('calendar.index', ['month' => $event->start_at->format('Y-m')]) }}">Kembali ke kalender</a>
        <h1>{{ $event->title }}</h1>
        <p class="lede">{{ $event->lpk->name }} &middot; agenda prototype lokal</p>
    </div>
    <div class="heading-actions">
        <x-status :value="$event->status" />
        <a class="button secondary" href="{{ route('calendar.events.edit', $event) }}">
            <x-icon name="edit" size="16" />
            <span>Ubah agenda</span>
        </a>
    </div>
</div>

<section class="panel detail-list">
    <div>
        <dt>LPK</dt>
        <dd>{{ $event->lpk->name }}</dd>
    </div>
    <div>
        <dt>Status</dt>
        <dd><x-status :value="$event->status" /></dd>
    </div>
    <div>
        <dt>Waktu</dt>
        <dd>{{ $event->start_at->format('d M Y, H:i') }} sampai {{ $event->end_at->format('d M Y, H:i') }} WIB</dd>
    </div>
    <div>
        <dt>Lokasi</dt>
        <dd>{{ $event->location ?: 'Belum diisi' }}</dd>
    </div>
    <div>
        <dt>Deskripsi</dt>
        <dd>{{ $event->description ?: 'Belum ada deskripsi.' }}</dd>
    </div>
    <div>
        <dt>Catatan</dt>
        <dd>{{ $event->notes ?: 'Belum ada catatan.' }}</dd>
    </div>
</section>
@endsection
