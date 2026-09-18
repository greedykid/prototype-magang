@extends('layouts.app')
@section('title', 'Kalender | SIMASADI')
@section('content')
<div class="page-heading">
    <div><span class="eyebrow">AGENDA KERJA</span><h1>Kalender kegiatan</h1><p class="lede">Atur agenda monitoring dan asesmen dalam satu tampilan bulanan.</p></div>
    <a class="button primary" href="{{ route('calendar.events.create') }}">Tambah agenda</a>
</div>
<section class="panel calendar-panel">
    <div class="calendar-toolbar"><div><h2>{{ $currentMonth->translatedFormat('F Y') }}</h2><span class="calendar-note">Klik tanggal untuk menambah agenda</span></div><div class="calendar-actions"><a class="button ghost" href="{{ route('calendar.index', ['month' => $currentMonth->subMonth()->format('Y-m')]) }}" aria-label="Bulan sebelumnya">Sebelumnya</a><a class="button secondary" href="{{ route('calendar.index', ['month' => now()->format('Y-m')]) }}">Hari ini</a><a class="button ghost" href="{{ route('calendar.index', ['month' => $currentMonth->addMonth()->format('Y-m')]) }}" aria-label="Bulan berikutnya">Berikutnya</a></div></div>
    <div class="calendar-grid calendar-headings"><span>Senin</span><span>Selasa</span><span>Rabu</span><span>Kamis</span><span>Jumat</span><span>Sabtu</span><span>Minggu</span></div>
    <div class="calendar-grid calendar-days">
        @foreach($weeks as $day)
            <div class="calendar-day {{ $day->month !== $currentMonth->month ? 'outside' : '' }} {{ $day->isToday() ? 'today' : '' }}">
                <a class="calendar-add-day" href="{{ route('calendar.events.create', ['date' => $day->toDateString()]) }}" aria-label="Tambah agenda pada {{ $day->translatedFormat('d F Y') }}"><span class="day-number">{{ $day->day }}</span><span class="calendar-add-label">Tambah</span></a>
                @foreach($events->get($day->toDateString(), collect()) as $event)<a class="calendar-event status-{{ strtolower($event->status) }}" href="{{ route('calendar.events.show', $event) }}"><strong>{{ $event->start_at->format('H:i') }}</strong> {{ $event->title }}</a>@endforeach
            </div>
        @endforeach
    </div>
    @if($events->isEmpty())<div class="empty calendar-empty">Belum ada agenda di rentang bulan ini. <a href="{{ route('calendar.events.create') }}">Tambah agenda pertama</a>.</div>@endif
</section>
@endsection
