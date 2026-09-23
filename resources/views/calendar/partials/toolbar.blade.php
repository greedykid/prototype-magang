        <div class="gcal-toolbar">
            <div class="gcal-toolbar-left">
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

                <div class="gcal-nav-group">
                    <a href="{{ route('calendar.index', ['view' => $viewMode, 'date' => now()->toDateString(), 'selected' => 1]) }}" class="button secondary gcal-btn-today">
                        Hari ini
                    </a>

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
                </div>

                @php
                    $indonesianMonths = [
                        1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                        5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                        9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                    ];
                    $currentYear = (int) $currentMonth->format('Y');
                    $minLpkDate = \App\Models\Lpk::whereNotNull('certificate_date')->min('certificate_date');
                    $minLpkYear = $minLpkDate ? (int) substr($minLpkDate, 0, 4) : 2020;
                    $maxLpkDate = \App\Models\Lpk::whereNotNull('expired_at')->max('expired_at');
                    $maxLpkYear = $maxLpkDate ? (int) substr($maxLpkDate, 0, 4) : 2035;

                    $minYear = min(2020, $currentYear - 2, $minLpkYear);
                    $maxYear = max(2035, $currentYear + 5, $maxLpkYear);
                    $availableYears = range($minYear, $maxYear);
                @endphp

                <div class="gcal-title-picker-wrap">
                    @if($viewMode === 'month')
                        <div class="gcal-title-selects" role="group" aria-label="Pilih bulan dan tahun">
                            <label class="sr-only" for="gcal-select-month">Pilih bulan</label>
                            <div class="gcal-select-wrapper gcal-select-wrapper-month">
                                <select id="gcal-select-month" class="gcal-nav-select gcal-select-month" aria-label="Pilih bulan" data-no-search="true" data-calendar-base-url="{{ route('calendar.index') }}">
                                    @foreach($indonesianMonths as $num => $name)
                                        <option value="{{ sprintf('%02d', $num) }}"{{ $currentMonth->month === $num ? ' selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <label class="sr-only" for="gcal-select-year">Pilih tahun</label>
                            <div class="gcal-select-wrapper gcal-select-wrapper-year">
                                <select id="gcal-select-year" class="gcal-nav-select gcal-select-year" aria-label="Pilih tahun" data-no-search="true" data-calendar-base-url="{{ route('calendar.index') }}">
                                    @foreach($availableYears as $year)
                                        <option value="{{ $year }}"{{ $currentYear === $year ? ' selected' : '' }}>{{ $year }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @elseif($viewMode === 'week')
                        <h2 class="gcal-title-label">
                            {{ $weekStart->translatedFormat('d M') }} &ndash; {{ $weekEnd->translatedFormat('d M Y') }}
                        </h2>
                        <div class="gcal-title-selects is-compact" role="group" aria-label="Lompat ke bulan dan tahun">
                            <label class="sr-only" for="gcal-select-month">Lompat bulan</label>
                            <div class="gcal-select-wrapper gcal-select-wrapper-month">
                                <select id="gcal-select-month" class="gcal-nav-select gcal-select-month" aria-label="Lompat bulan" data-no-search="true" data-calendar-base-url="{{ route('calendar.index') }}">
                                    @foreach($indonesianMonths as $num => $name)
                                        <option value="{{ sprintf('%02d', $num) }}"{{ $currentMonth->month === $num ? ' selected' : '' }}>{{ substr($name, 0, 3) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <label class="sr-only" for="gcal-select-year">Lompat tahun</label>
                            <div class="gcal-select-wrapper gcal-select-wrapper-year">
                                <select id="gcal-select-year" class="gcal-nav-select gcal-select-year" aria-label="Lompat tahun" data-no-search="true" data-calendar-base-url="{{ route('calendar.index') }}">
                                    @foreach($availableYears as $year)
                                        <option value="{{ $year }}"{{ $currentYear === $year ? ' selected' : '' }}>{{ $year }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @elseif($viewMode === 'day')
                        <h2 class="gcal-title-label">
                            {{ $activeDate->translatedFormat('l, d F Y') }}
                        </h2>
                        <div class="gcal-title-selects is-compact" role="group" aria-label="Lompat ke bulan dan tahun">
                            <label class="sr-only" for="gcal-select-month">Lompat bulan</label>
                            <div class="gcal-select-wrapper gcal-select-wrapper-month">
                                <select id="gcal-select-month" class="gcal-nav-select gcal-select-month" aria-label="Lompat bulan" data-no-search="true" data-calendar-base-url="{{ route('calendar.index') }}">
                                    @foreach($indonesianMonths as $num => $name)
                                        <option value="{{ sprintf('%02d', $num) }}"{{ $currentMonth->month === $num ? ' selected' : '' }}>{{ substr($name, 0, 3) }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <label class="sr-only" for="gcal-select-year">Lompat tahun</label>
                            <div class="gcal-select-wrapper gcal-select-wrapper-year">
                                <select id="gcal-select-year" class="gcal-nav-select gcal-select-year" aria-label="Lompat tahun" data-no-search="true" data-calendar-base-url="{{ route('calendar.index') }}">
                                    @foreach($availableYears as $year)
                                        <option value="{{ $year }}"{{ $currentYear === $year ? ' selected' : '' }}>{{ $year }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @else
                        <h2 class="gcal-title-label">
                            Agenda &bull;
                        </h2>
                        <div class="gcal-title-selects" role="group" aria-label="Pilih bulan dan tahun agenda">
                            <label class="sr-only" for="gcal-select-month">Pilih bulan</label>
                            <div class="gcal-select-wrapper gcal-select-wrapper-month">
                                <select id="gcal-select-month" class="gcal-nav-select gcal-select-month" aria-label="Pilih bulan agenda" data-no-search="true" data-calendar-base-url="{{ route('calendar.index') }}">
                                    @foreach($indonesianMonths as $num => $name)
                                        <option value="{{ sprintf('%02d', $num) }}"{{ $currentMonth->month === $num ? ' selected' : '' }}>{{ $name }}</option>
                                    @endforeach
                                </select>
                            </div>

                            <label class="sr-only" for="gcal-select-year">Pilih tahun</label>
                            <div class="gcal-select-wrapper gcal-select-wrapper-year">
                                <select id="gcal-select-year" class="gcal-nav-select gcal-select-year" aria-label="Pilih tahun agenda" data-no-search="true" data-calendar-base-url="{{ route('calendar.index') }}">
                                    @foreach($availableYears as $year)
                                        <option value="{{ $year }}"{{ $currentYear === $year ? ' selected' : '' }}>{{ $year }}</option>
                                    @endforeach
                                </select>
                            </div>
                        </div>
                    @endif
                </div>
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