@extends('layouts.app')

@section('title', 'Kalender Kegiatan | SIMASADI')

@section('content')
<div class="page-heading">
    <div>
        <h1>Kalender kegiatan</h1>
        <p class="lede">Atur jadwal asesmen lapangan dan agenda kegiatan KAN dalam tata letak Google Calendar.</p>
    </div>
</div>

{{-- Google Calendar Main Shell --}}
<div class="gcal-shell">
    {{-- Left Sidebar: Mini Calendar & Category Filters --}}
    <aside class="gcal-sidebar">
        <button type="button" class="gcal-btn-create" onclick="window.openModal('modal-quick-add-event')">
            <x-icon name="plus" size="18" />
            <span>Buat Agenda Baru</span>
        </button>

        {{-- Mini Month Calendar --}}
        <div class="gcal-mini-cal">
            <div class="gcal-mini-header">
                <strong>{{ $currentMonth->translatedFormat('F Y') }}</strong>
                <div class="gcal-mini-nav">
                    <a href="{{ route('calendar.index', ['view' => $viewMode, 'date' => $currentMonth->subMonth()->startOfMonth()->toDateString()]) }}" class="gcal-mini-nav-btn" aria-label="Bulan sebelumnya">
                        <x-icon name="chevron-left" size="14" />
                    </a>
                    <a href="{{ route('calendar.index', ['view' => $viewMode, 'date' => $currentMonth->addMonth()->startOfMonth()->toDateString()]) }}" class="gcal-mini-nav-btn" aria-label="Bulan berikutnya">
                        <x-icon name="chevron-right" size="14" />
                    </a>
                </div>
            </div>

            <div class="gcal-mini-grid-days">
                <span>S</span><span>S</span><span>R</span><span>K</span><span>J</span><span>S</span><span>M</span>
            </div>

            <div class="gcal-mini-grid-cells">
                @foreach($miniWeeks as $day)
                    @php
                        $isCurrentMonth = $day->month === $currentMonth->month;
                        $isActive = $day->isSameDay($activeDate);
                        $isToday = $day->isToday();
                        $hasEvents = isset($eventsByDate[$day->toDateString()]) && $eventsByDate[$day->toDateString()]->isNotEmpty();
                    @endphp
                    <a href="{{ route('calendar.index', ['view' => $viewMode, 'date' => $day->toDateString()]) }}"
                       class="gcal-mini-cell {{ !$isCurrentMonth ? 'outside' : '' }} {{ $isActive ? 'active' : '' }} {{ $isToday ? 'today' : '' }} {{ $hasEvents ? 'has-events' : '' }}"
                       title="{{ $day->translatedFormat('d F Y') }}">
                        <span>{{ $day->day }}</span>
                        @if($hasEvents)
                            <i class="gcal-mini-dot"></i>
                        @endif
                    </a>
                @endforeach
            </div>
        </div>

        {{-- Filter Categories --}}
        <div class="gcal-filters-group">
            <span class="gcal-filter-title">Kategori Kalender</span>
            <label class="gcal-checkbox-row">
                <input type="checkbox" id="filter-cat-assessment" checked data-filter-cat="ASESMEN_LAPANGAN">
                <span class="gcal-cat-indicator purple"></span>
                <span class="gcal-cat-text">Asesmen Lapangan KAN</span>
            </label>
            <label class="gcal-checkbox-row">
                <input type="checkbox" id="filter-cat-agenda" checked data-filter-cat="AGENDA_INTERNAL">
                <span class="gcal-cat-indicator indigo"></span>
                <span class="gcal-cat-text">Agenda Internal SIMASADI</span>
            </label>
        </div>

        {{-- Filter LPK --}}
        <div class="gcal-filters-group">
            <span class="gcal-filter-title">Filter Lembaga (LPK)</span>
            <select id="gcal-filter-lpk" class="gcal-lpk-select">
                <option value="">Semua LPK</option>
                @foreach($lpks as $lpk)
                    <option value="{{ $lpk->id }}">{{ $lpk->name }}</option>
                @endforeach
            </select>
        </div>
    </aside>

    {{-- Main Calendar View Area --}}
    <main class="gcal-main">
        {{-- Top Toolbar --}}
        <div class="gcal-toolbar">
            <div class="gcal-toolbar-left">
                <a href="{{ route('calendar.index', ['view' => $viewMode, 'date' => now()->toDateString()]) }}" class="button secondary gcal-btn-today">
                    Hari ini
                </a>

                @php
                    $prevDate = match($viewMode) {
                        'week' => $activeDate->subWeek()->toDateString(),
                        'day' => $activeDate->subDay()->toDateString(),
                        'agenda' => $activeDate->subDays(14)->toDateString(),
                        default => $currentMonth->subMonth()->startOfMonth()->toDateString(),
                    };
                    $nextDate = match($viewMode) {
                        'week' => $activeDate->addWeek()->toDateString(),
                        'day' => $activeDate->addDay()->toDateString(),
                        'agenda' => $activeDate->addDays(14)->toDateString(),
                        default => $currentMonth->addMonth()->startOfMonth()->toDateString(),
                    };
                @endphp

                <div class="gcal-nav-arrows">
                    <a href="{{ route('calendar.index', ['view' => $viewMode, 'date' => $prevDate]) }}" class="button ghost gcal-arrow-btn" aria-label="Sebelumnya">
                        <x-icon name="chevron-left" size="16" />
                    </a>
                    <a href="{{ route('calendar.index', ['view' => $viewMode, 'date' => $nextDate]) }}" class="button ghost gcal-arrow-btn" aria-label="Berikutnya">
                        <x-icon name="chevron-right" size="16" />
                    </a>
                </div>

                <h2 class="gcal-title-label">
                    @if($viewMode === 'month')
                        {{ $currentMonth->translatedFormat('F Y') }}
                    @elseif($viewMode === 'week')
                        {{ $weekStart->translatedFormat('d M') }} &ndash; {{ $weekEnd->translatedFormat('d M Y') }}
                    @elseif($viewMode === 'day')
                        {{ $activeDate->translatedFormat('l, d F Y') }}
                    @else
                        Agenda &bull; {{ $activeDate->translatedFormat('F Y') }}
                    @endif
                </h2>
            </div>

            <div class="gcal-toolbar-right">
                <div class="gcal-view-switcher" role="tablist">
                    <a href="{{ route('calendar.index', ['view' => 'month', 'date' => $activeDate->toDateString()]) }}"
                       class="gcal-view-tab {{ $viewMode === 'month' ? 'is-active' : '' }}" role="tab">
                        Bulan
                    </a>
                    <a href="{{ route('calendar.index', ['view' => 'week', 'date' => $activeDate->toDateString()]) }}"
                       class="gcal-view-tab {{ $viewMode === 'week' ? 'is-active' : '' }}" role="tab">
                        Minggu
                    </a>
                    <a href="{{ route('calendar.index', ['view' => 'day', 'date' => $activeDate->toDateString()]) }}"
                       class="gcal-view-tab {{ $viewMode === 'day' ? 'is-active' : '' }}" role="tab">
                        Hari
                    </a>
                    <a href="{{ route('calendar.index', ['view' => 'agenda', 'date' => $activeDate->toDateString()]) }}"
                       class="gcal-view-tab {{ $viewMode === 'agenda' ? 'is-active' : '' }}" role="tab">
                        Agenda
                    </a>
                </div>
            </div>
        </div>

        {{-- VIEW 1: MONTH VIEW (BULAN) --}}
        @if($viewMode === 'month')
            <div class="gcal-view-container gcal-month-wrap">
                <div class="gcal-month-headings">
                    <span>Senin</span><span>Selasa</span><span>Rabu</span><span>Kamis</span><span>Jumat</span><span>Sabtu</span><span>Minggu</span>
                </div>

                <div class="gcal-month-grid">
                    @foreach($monthWeeks as $day)
                        @php
                            $isCurrentMonth = $day->month === $currentMonth->month;
                            $isToday = $day->isToday();
                            $dayKey = $day->toDateString();
                            $dayEvents = $eventsByDate->get($dayKey, collect());
                        @endphp
                        <div class="gcal-month-cell {{ !$isCurrentMonth ? 'outside' : '' }} {{ $isToday ? 'today' : '' }}" data-date="{{ $dayKey }}">
                            <div class="gcal-cell-top">
                                <a href="{{ route('calendar.index', ['view' => 'day', 'date' => $dayKey]) }}" class="gcal-day-badge {{ $isToday ? 'is-today' : '' }}">
                                    {{ $day->day }}
                                </a>
                                <button type="button" class="gcal-cell-add-btn" onclick="window.quickAddAt('{{ $dayKey }}', '09:00')" title="Tambah agenda pada {{ $day->translatedFormat('d M Y') }}">
                                    <x-icon name="plus" size="12" />
                                </button>
                            </div>

                            <div class="gcal-cell-events">
                                @foreach($dayEvents->take(3) as $ev)
                                    <button type="button"
                                            class="gcal-event-chip theme-{{ $ev['color_theme'] }}"
                                            data-event-id="{{ $ev['id'] }}"
                                            data-event-source="{{ $ev['source'] }}"
                                            data-cat="{{ $ev['category'] }}"
                                            data-lpk-id="{{ $ev['lpk_id'] }}"
                                            onclick="window.showEventPopover(this, {{ json_encode($ev) }})">
                                        <span class="gcal-chip-time">{{ $ev['start_at']->format('H:i') }}</span>
                                        <span class="gcal-chip-title">{{ $ev['title'] }}</span>
                                    </button>
                                @endforeach

                                @if($dayEvents->count() > 3)
                                    <a href="{{ route('calendar.index', ['view' => 'day', 'date' => $dayKey]) }}" class="gcal-more-chip">
                                        +{{ $dayEvents->count() - 3 }} lainnya
                                    </a>
                                @endif
                            </div>
                        </div>
                    @endforeach
                </div>
            </div>

        {{-- VIEW 2: WEEK VIEW (MINGGU) --}}
        @elseif($viewMode === 'week')
            <div class="gcal-view-container gcal-week-wrap">
                <div class="gcal-week-header-row">
                    <div class="gcal-time-corner">
                        <small>WIB</small>
                    </div>
                    @foreach($weekDays as $day)
                        @php
                            $isToday = $day->isToday();
                            $isActive = $day->isSameDay($activeDate);
                        @endphp
                        <div class="gcal-week-col-head {{ $isToday ? 'today' : '' }}">
                            <span class="gcal-col-dayname">{{ ['Monday' => 'SEN', 'Tuesday' => 'SEL', 'Wednesday' => 'RAB', 'Thursday' => 'KAM', 'Friday' => 'JUM', 'Saturday' => 'SAB', 'Sunday' => 'MIN'][$day->format('l')] }}</span>
                            <a href="{{ route('calendar.index', ['view' => 'day', 'date' => $day->toDateString()]) }}" class="gcal-col-daynum {{ $isToday ? 'is-today' : '' }}">
                                {{ $day->day }}
                            </a>
                        </div>
                    @endforeach
                </div>

                <div class="gcal-week-body">
                    {{-- Time labels column --}}
                    <div class="gcal-time-gutter">
                        @foreach($hoursRange as $hour)
                            <div class="gcal-time-mark">
                                <span>{{ sprintf('%02d:00', $hour) }}</span>
                            </div>
                        @endforeach
                    </div>

                    {{-- 7 Day columns --}}
                    <div class="gcal-week-grid-cols">
                        @foreach($weekDays as $day)
                            @php
                                $isToday = $day->isToday();
                                $dayKey = $day->toDateString();
                                $dayEvents = $eventsByDate->get($dayKey, collect());
                            @endphp
                            <div class="gcal-week-col {{ $isToday ? 'today' : '' }}" data-date="{{ $dayKey }}">
                                @foreach($hoursRange as $hour)
                                    <div class="gcal-hour-slot" onclick="window.quickAddAt('{{ $dayKey }}', '{{ sprintf('%02d:00', $hour) }}')"></div>
                                @endforeach

                                @if($isToday)
                                    {{-- Live Red Line Time Indicator --}}
                                    <div class="gcal-current-time-line" id="gcal-live-time-line" title="Waktu saat ini (WIB)">
                                        <span class="gcal-time-dot"></span>
                                    </div>
                                @endif

                                {{-- Events placed in this day --}}
                                @foreach($dayEvents as $ev)
                                    @php
                                        $startHour = (int)$ev['start_at']->format('H');
                                        $startMinute = (int)$ev['start_at']->format('i');
                                        $endHour = (int)$ev['end_at']->format('H');
                                        $endMinute = (int)$ev['end_at']->format('i');

                                        $topMinutes = max(0, ($startHour - 7) * 60 + $startMinute);
                                        $durationMinutes = max(35, (($endHour - $startHour) * 60) + ($endMinute - $startMinute));
                                        $totalDayMinutes = (count($hoursRange)) * 60;

                                        $topPct = ($topMinutes / $totalDayMinutes) * 100;
                                        $heightPct = min(100 - $topPct, ($durationMinutes / $totalDayMinutes) * 100);
                                    @endphp
                                    <div class="gcal-timed-card theme-{{ $ev['color_theme'] }}"
                                         style="top: {{ $topPct }}%; height: {{ $heightPct }}%;"
                                         data-event-id="{{ $ev['id'] }}"
                                         data-cat="{{ $ev['category'] }}"
                                         data-lpk-id="{{ $ev['lpk_id'] }}"
                                         onclick="window.showEventPopover(this, {{ json_encode($ev) }})">
                                        <div class="gcal-card-inner">
                                            <div class="gcal-card-time">{{ $ev['start_at']->format('H:i') }} &ndash; {{ $ev['end_at']->format('H:i') }} WIB</div>
                                            <strong class="gcal-card-title">{{ $ev['title'] }}</strong>
                                            <span class="gcal-card-lpk">{{ $ev['lpk_name'] }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

        {{-- VIEW 3: DAY VIEW (HARI) --}}
        @elseif($viewMode === 'day')
            <div class="gcal-view-container gcal-day-wrap">
                <div class="gcal-day-header">
                    <div class="gcal-day-header-info">
                        <strong class="gcal-day-header-title">{{ $activeDate->translatedFormat('l, d F Y') }}</strong>
                        @if($activeDate->isToday())
                            <span class="badge-today">Hari Ini (WIB)</span>
                        @endif
                    </div>
                    <button type="button" class="button secondary" onclick="window.quickAddAt('{{ $activeDate->toDateString() }}', '09:00')">
                        <x-icon name="plus" size="14" />
                        <span>Tambah Agenda Hari Ini</span>
                    </button>
                </div>

                <div class="gcal-day-body">
                    <div class="gcal-time-gutter">
                        @foreach($hoursRange as $hour)
                            <div class="gcal-time-mark">
                                <span>{{ sprintf('%02d:00', $hour) }}</span>
                            </div>
                        @endforeach
                    </div>

                    <div class="gcal-single-day-col {{ $activeDate->isToday() ? 'today' : '' }}" data-date="{{ $activeDate->toDateString() }}">
                        @foreach($hoursRange as $hour)
                            <div class="gcal-hour-slot" onclick="window.quickAddAt('{{ $activeDate->toDateString() }}', '{{ sprintf('%02d:00', $hour) }}')"></div>
                        @endforeach

                        @if($activeDate->isToday())
                            <div class="gcal-current-time-line" id="gcal-live-time-line" title="Waktu saat ini (WIB)">
                                <span class="gcal-time-dot"></span>
                            </div>
                        @endif

                        @php
                            $dayEvents = $eventsByDate->get($activeDate->toDateString(), collect());
                        @endphp
                        @foreach($dayEvents as $ev)
                            @php
                                $startHour = (int)$ev['start_at']->format('H');
                                $startMinute = (int)$ev['start_at']->format('i');
                                $endHour = (int)$ev['end_at']->format('H');
                                $endMinute = (int)$ev['end_at']->format('i');

                                $topMinutes = max(0, ($startHour - 7) * 60 + $startMinute);
                                $durationMinutes = max(45, (($endHour - $startHour) * 60) + ($endMinute - $startMinute));
                                $totalDayMinutes = (count($hoursRange)) * 60;

                                $topPct = ($topMinutes / $totalDayMinutes) * 100;
                                $heightPct = min(100 - $topPct, ($durationMinutes / $totalDayMinutes) * 100);
                            @endphp
                            <div class="gcal-timed-card theme-{{ $ev['color_theme'] }} is-wide"
                                 style="top: {{ $topPct }}%; height: {{ $heightPct }}%;"
                                 data-event-id="{{ $ev['id'] }}"
                                 data-cat="{{ $ev['category'] }}"
                                 data-lpk-id="{{ $ev['lpk_id'] }}"
                                 onclick="window.showEventPopover(this, {{ json_encode($ev) }})">
                                <div class="gcal-card-inner">
                                    <div class="gcal-card-time">{{ $ev['start_at']->format('H:i') }} &ndash; {{ $ev['end_at']->format('H:i') }} WIB &bull; {{ $ev['category_label'] }}</div>
                                    <strong class="gcal-card-title">{{ $ev['title'] }}</strong>
                                    <span class="gcal-card-lpk">{{ $ev['lpk_name'] }} &bull; {{ $ev['location'] }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>

        {{-- VIEW 4: AGENDA / LIST VIEW (DAFTAR) --}}
        @elseif($viewMode === 'agenda')
            <div class="gcal-view-container gcal-agenda-wrap">
                @php
                    $sortedDates = $eventsByDate->keys()->sort();
                @endphp

                @forelse($sortedDates as $dateKey)
                    @php
                        $dateObj = \Carbon\Carbon::parse($dateKey);
                        $evs = $eventsByDate[$dateKey];
                    @endphp
                    <div class="gcal-agenda-group" data-date="{{ $dateKey }}">
                        <div class="gcal-agenda-date-head">
                            <div class="gcal-agenda-date-left">
                                <span class="gcal-agenda-day-num">{{ $dateObj->day }}</span>
                                <div>
                                    <strong class="gcal-agenda-day-name">{{ $dateObj->translatedFormat('l') }}</strong>
                                    <small>{{ $dateObj->translatedFormat('F Y') }}</small>
                                </div>
                            </div>
                            @if($dateObj->isToday())
                                <span class="badge-today">Hari Ini</span>
                            @endif
                        </div>

                        <div class="gcal-agenda-items">
                            @foreach($evs as $ev)
                                <div class="gcal-agenda-row theme-{{ $ev['color_theme'] }}"
                                     data-event-id="{{ $ev['id'] }}"
                                     data-cat="{{ $ev['category'] }}"
                                     data-lpk-id="{{ $ev['lpk_id'] }}">
                                    <div class="gcal-agenda-time-col">
                                        <strong>{{ $ev['start_at']->format('H:i') }}</strong>
                                        <small>{{ $ev['end_at']->format('H:i') }} WIB</small>
                                    </div>

                                    <div class="gcal-agenda-info-col">
                                        <div class="gcal-agenda-title-row">
                                            <strong class="gcal-agenda-title">{{ $ev['title'] }}</strong>
                                            <span class="gcal-badge-pill theme-{{ $ev['color_theme'] }}">{{ $ev['category_label'] }}</span>
                                        </div>
                                        <div class="gcal-agenda-meta">
                                            <span><strong>LPK:</strong> {{ $ev['lpk_name'] }}</span>
                                            <span><strong>Lokasi:</strong> {{ $ev['location'] }}</span>
                                            @if($ev['lead'])
                                                <span><strong>Lead Asesor:</strong> {{ $ev['lead'] }}</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="gcal-agenda-actions">
                                        <button type="button" class="button secondary btn-sm" onclick="window.showEventPopover(this, {{ json_encode($ev) }})">
                                            Pratinjau
                                        </button>
                                        <a href="{{ $ev['url'] }}" class="button ghost btn-sm">
                                            Detail &rarr;
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="empty">Belum ada agenda atau asesmen yang tercatat pada rentang ini.</div>
                @endforelse
            </div>
        @endif
    </main>
</div>

{{-- Quick Event Popover Card (Google Calendar Float Card) --}}
<div class="gcal-popover" id="gcal-event-popover" role="dialog" aria-hidden="true" style="display: none;">
    <div class="gcal-popover-card">
        <div class="gcal-popover-head">
            <span class="gcal-popover-cat-pill" id="popover-cat-badge">Agenda</span>
            <button type="button" class="gcal-popover-close" onclick="window.closeEventPopover()" aria-label="Tutup">
                <x-icon name="x" size="16" />
            </button>
        </div>

        <div class="gcal-popover-body">
            <h3 id="popover-title">Judul Agenda</h3>
            <div class="gcal-popover-row">
                <x-icon name="calendar" size="16" />
                <span id="popover-time">Senin, 21 September 2026 &bull; 09:00 - 11:00 WIB</span>
            </div>
            <div class="gcal-popover-row">
                <x-icon name="lpks" size="16" />
                <span id="popover-lpk">Nama Lembaga Pemohon</span>
            </div>
            <div class="gcal-popover-row" id="popover-location-wrap">
                <x-icon name="services" size="16" />
                <span id="popover-location">Lokasi Asesmen</span>
            </div>
            <div class="gcal-popover-notes" id="popover-notes-wrap" style="display: none;">
                <p id="popover-notes"></p>
            </div>
        </div>

        <div class="gcal-popover-footer">
            <a href="#" target="_blank" rel="noopener noreferrer" class="button secondary btn-sm gcal-btn-gcal" id="popover-gcal-link">
                <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                    <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/>
                    <line x1="16" x2="16" y1="2" y2="6"/>
                    <line x1="8" x2="8" y1="2" y2="6"/>
                    <line x1="3" x2="21" y1="10" y2="10"/>
                    <path d="M8 14h.01M12 14h.01M16 14h.01M8 18h.01M12 18h.01M16 18h.01"/>
                </svg>
                <span>Google Calendar</span>
            </a>
            <div class="gcal-popover-actions-right">
                <a href="#" class="button secondary btn-sm" id="popover-edit-link">
                    <x-icon name="edit" size="14" />
                    <span>Ubah</span>
                </a>
                <a href="#" class="button primary btn-sm" id="popover-detail-link">
                    <span>Detail Lengkap</span>
                </a>
            </div>
        </div>
    </div>
</div>

{{-- Quick Add Modal (Google Calendar Quick Event Creation) --}}
<div class="simasadi-modal" id="modal-quick-add-event" role="dialog" aria-modal="true" aria-labelledby="quick-add-title">
    <div class="simasadi-modal-box" style="max-width: 580px;">
        <div class="simasadi-modal-head">
            <div>
                <h4 id="quick-add-title" style="margin: 0; font-size: 18px; font-weight: 700;">Buat Agenda Kegiatan Baru</h4>
                <p style="margin: 4px 0 0 0; font-size: 13px; color: var(--muted);">Jadwalkan kegiatan internal atau koordinasi monitoring akreditasi.</p>
            </div>
            <button type="button" class="simasadi-modal-close" data-modal-close onclick="window.closeModal('modal-quick-add-event')" aria-label="Tutup modal">&times;</button>
        </div>

        <form method="POST" action="{{ route('calendar.events.store') }}" style="display: grid; gap: 14px; margin-top: 14px;">
            @csrf
            <div class="form-grid" style="gap: 12px;">
                <label class="full">
                    Judul Kegiatan
                    <input type="text" name="title" id="quick-input-title" required placeholder="Contoh: Rapat Komite Akreditasi Laboratorium">
                </label>

                <label class="full">
                    Lembaga Terkait (LPK)
                    <select name="lpk_id" id="quick-input-lpk" required data-no-custom="true">
                        <option value="">Pilih Lembaga Penilaian Kesesuaian</option>
                        @foreach($lpks as $lpk)
                            <option value="{{ $lpk->id }}">{{ $lpk->name }}</option>
                        @endforeach
                    </select>
                </label>

                <label>
                    Tanggal Mulai
                    <input type="date" name="start_date" id="quick-input-start-date" value="{{ $activeDate->toDateString() }}" required>
                </label>

                <label>
                    Jam Mulai (WIB)
                    <input type="time" name="start_time" id="quick-input-start-time" value="09:00" required>
                </label>

                <label>
                    Tanggal Selesai
                    <input type="date" name="end_date" id="quick-input-end-date" value="{{ $activeDate->toDateString() }}" required>
                </label>

                <label>
                    Jam Selesai (WIB)
                    <input type="time" name="end_time" id="quick-input-end-time" value="11:00" required>
                </label>

                <label class="full">
                    Lokasi / Tautan Rapat
                    <input type="text" name="location" id="quick-input-location" placeholder="Contoh: Gedung BSN Lt. 3 / Zoom Meeting">
                </label>

                <label class="full">
                    Status
                    <select name="status" data-no-custom="true">
                        <option value="PLANNED" selected>Direncanakan</option>
                        <option value="IN_PROGRESS">Sedang Berlangsung</option>
                        <option value="COMPLETED">Selesai</option>
                        <option value="CANCELLED">Dibatalkan</option>
                    </select>
                </label>
            </div>

            <div class="modal-form-actions">
                <button type="button" class="button secondary" data-modal-close onclick="window.closeModal('modal-quick-add-event')">
                    Batal
                </button>
                <button type="submit" class="button primary">
                    <x-icon name="plus" size="16" />
                    <span>Simpan ke Kalender</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endsection
