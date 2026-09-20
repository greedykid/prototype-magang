        <div class="gcal-toolbar">
            <div class="gcal-toolbar-left">
                <a href="{{ route('calendar.index', ['view' => $viewMode, 'date' => now()->toDateString(), 'selected' => 1]) }}" class="button secondary gcal-btn-today">
                    Hari ini
                </a>

                @php
                    $prevMonth = $currentMonth->subMonth();
                    $nextMonth = $currentMonth->addMonth();

                    $prevMonthDate = now()->isSameMonth($prevMonth)
                        ? now()->toDateString()
                        : $prevMonth->setDay(min($activeDate->day, $prevMonth->daysInMonth))->toDateString();

                    $nextMonthDate = now()->isSameMonth($nextMonth)
                        ? now()->toDateString()
                        : $nextMonth->setDay(min($activeDate->day, $nextMonth->daysInMonth))->toDateString();

                    $prevDate = match($viewMode) {
                        'week' => $activeDate->subWeek()->toDateString(),
                        'day' => $activeDate->subDay()->toDateString(),
                        'agenda' => $activeDate->subDays(14)->toDateString(),
                        default => $prevMonthDate,
                    };
                    $nextDate = match($viewMode) {
                        'week' => $activeDate->addWeek()->toDateString(),
                        'day' => $activeDate->addDay()->toDateString(),
                        'agenda' => $activeDate->addDays(14)->toDateString(),
                        default => $nextMonthDate,
                    };
                @endphp

                <div class="gcal-nav-arrows">
                    @if($viewMode === 'month')
                        <a href="{{ route('calendar.index', ['view' => 'month', 'month' => $prevMonth->format('Y-m')]) }}" class="button ghost gcal-arrow-btn" aria-label="Sebelumnya">
                            <x-icon name="chevron-left" size="16" />
                        </a>
                        <a href="{{ route('calendar.index', ['view' => 'month', 'month' => $nextMonth->format('Y-m')]) }}" class="button ghost gcal-arrow-btn" aria-label="Berikutnya">
                            <x-icon name="chevron-right" size="16" />
                        </a>
                    @else
                        <a href="{{ route('calendar.index', ['view' => $viewMode, 'date' => $prevDate]) }}" class="button ghost gcal-arrow-btn" aria-label="Sebelumnya">
                            <x-icon name="chevron-left" size="16" />
                        </a>
                        <a href="{{ route('calendar.index', ['view' => $viewMode, 'date' => $nextDate]) }}" class="button ghost gcal-arrow-btn" aria-label="Berikutnya">
                            <x-icon name="chevron-right" size="16" />
                        </a>
                    @endif
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