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
</div>
