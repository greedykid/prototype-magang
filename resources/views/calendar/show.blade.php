@extends('layouts.app')

@section('title', $event->title . ' | SIMASADI')

@section('content')
<div class="page-heading">
    <div>
        <a class="back-link" href="{{ route('calendar.index', ['month' => $event->start_at->format('Y-m')]) }}">Kembali ke kalender</a>
        <h1>{{ $event->title }}</h1>
        <p class="lede">{{ $event->lpk->name }} &middot; Agenda Asesmen Laboratorium</p>
    </div>
    <div class="heading-actions">
        <x-status :value="$event->status" />
        @php
            $gcalStart = $event->start_at->copy()->setTimezone('UTC')->format('Ymd\THis\Z');
            $gcalEnd = $event->end_at->copy()->setTimezone('UTC')->format('Ymd\THis\Z');
            $gcalDetails = "LPK: {$event->lpk->name}\nStatus: {$event->status}\nDeskripsi: " . ($event->description ?: '-') . "\nSIMASADI KAN - Dit. Akreditasi Laboratorium";
            $gcalUrl = 'https://calendar.google.com/calendar/render?action=TEMPLATE'
                . '&text=' . urlencode($event->title)
                . '&dates=' . $gcalStart . '/' . $gcalEnd
                . '&details=' . urlencode($gcalDetails)
                . '&location=' . urlencode($event->location ?? '');
        @endphp
        <a class="button secondary" href="{{ $gcalUrl }}" target="_blank" rel="noopener noreferrer">
            <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/>
                <line x1="16" x2="16" y1="2" y2="6"/>
                <line x1="8" x2="8" y1="2" y2="6"/>
                <line x1="3" x2="21" y1="10" y2="10"/>
            </svg>
            <span>Google Calendar</span>
        </a>
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
