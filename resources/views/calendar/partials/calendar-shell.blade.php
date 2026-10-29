@php
    $indonesianMonths = [
        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
    ];
    $calendarMonthLabel = ($indonesianMonths[$currentMonth->month] ?? $currentMonth->format('F')) . ' ' . $currentMonth->format('Y');
@endphp
<div class="gcal-shell" data-active-date="{{ $activeDate->toDateString() }}" data-month-label="{{ $calendarMonthLabel }}" data-highlight-id="{{ $highlightId ?? '' }}">
    @include('calendar.partials.sidebar')

    <main class="gcal-main">
        @include('calendar.partials.toolbar')

        @if($viewMode === 'month')
            @include('calendar.partials.view-month')
        @elseif($viewMode === 'week')
            @include('calendar.partials.view-week')
        @elseif($viewMode === 'day')
            @include('calendar.partials.view-day')
        @else
            @include('calendar.partials.view-agenda')
        @endif
    </main>

    {{-- Instant Hover Micro-Tooltip --}}
    <div id="gcal-hover-tooltip" class="gcal-hover-tooltip" role="tooltip" aria-hidden="true" style="display: none;">
        <div class="gcal-tt-header">
            <span class="gcal-tt-badge theme-indigo" id="gcal-tt-cat">Agenda</span>
            <span class="gcal-tt-duration" id="gcal-tt-duration" style="display: none;"></span>
        </div>
        <div class="gcal-tt-body">
            <div class="gcal-tt-title-wrap">
                <strong class="gcal-tt-lpk" id="gcal-tt-lpk">Nama LPK</strong>
                <span class="gcal-tt-acc" id="gcal-tt-acc" style="display: none;"></span>
            </div>
            <div class="gcal-tt-row" id="gcal-tt-date-wrap">
                <x-icon name="calendar" size="13" />
                <span id="gcal-tt-date">Tanggal</span>
            </div>
            <div class="gcal-tt-row" id="gcal-tt-loc-wrap" style="display: none;">
                <x-icon name="map-pin" size="13" />
                <span id="gcal-tt-loc"></span>
            </div>
        </div>
        <div class="gcal-tt-footer">
            <span>Klik untuk rincian lengkap &rarr;</span>
        </div>
    </div>
</div>
