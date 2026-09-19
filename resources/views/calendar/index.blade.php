@extends('layouts.app')
@section('title', 'Kalender | SIMASADI')
@section('content')
<div class="page-heading">
    <div><h1>Kalender kegiatan</h1><p class="lede">Atur agenda monitoring dan asesmen dalam satu tampilan bulanan.</p></div>
    <a class="button primary" href="{{ route('calendar.events.create') }}"><x-icon name="plus" size="16" /><span>Tambah agenda</span></a>
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
    <div class="mobile-calendar-agenda">
        @foreach($weeks as $day)
            @if($day->month === $currentMonth->month)
                 <section class="mobile-calendar-day {{ $day->isToday() ? 'today' : '' }}">
                     <a class="mobile-calendar-add-day" href="{{ route('calendar.events.create', ['date' => $day->toDateString()]) }}" aria-label="Tambah agenda pada {{ $day->translatedFormat('d F Y') }}"></a>
                    <div class="mobile-calendar-day-heading">
                        <div class="mobile-calendar-date-wrap">
                            <strong class="mobile-calendar-day-name">
                                {{ ['Monday' => 'Senin', 'Tuesday' => 'Selasa', 'Wednesday' => 'Rabu', 'Thursday' => 'Kamis', 'Friday' => 'Jumat', 'Saturday' => 'Sabtu', 'Sunday' => 'Minggu'][$day->format('l')] }}
                                @if($day->isToday())
                                    <span class="badge-today">Hari Ini</span>
                                @endif
                            </strong>
                            <span class="mobile-calendar-date-label">{{ $day->translatedFormat('d F Y') }}</span>
                        </div>
                        <a class="calendar-day-add-btn" href="{{ route('calendar.events.create', ['date' => $day->toDateString()]) }}" aria-label="Tambah agenda pada {{ $day->translatedFormat('d F Y') }}">
                            <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true" focusable="false">
                                <line x1="12" y1="5" x2="12" y2="19"></line>
                                <line x1="5" y1="12" x2="19" y2="12"></line>
                            </svg>
                            <span>Tambah</span>
                        </a>
                    </div>
                    @forelse($events->get($day->toDateString(), collect()) as $event)<a class="mobile-calendar-event status-{{ strtolower($event->status) }}" href="{{ route('calendar.events.show', $event) }}"><strong>{{ $event->start_at->format('H:i') }}</strong><span>{{ $event->title }}</span><small>{{ $event->lpk->name }}</small></a>@empty<span class="mobile-calendar-empty">Belum ada agenda</span>@endforelse
                </section>
            @endif
        @endforeach
    </div>
    @if($events->isEmpty())<div class="empty calendar-empty">Belum ada agenda di rentang bulan ini. <a href="{{ route('calendar.events.create') }}">Tambah agenda pertama</a>.</div>@endif
</section>
@endsection
