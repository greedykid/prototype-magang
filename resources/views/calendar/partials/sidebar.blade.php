    <aside class="gcal-sidebar">
        <button type="button" class="gcal-btn-create" onclick="window.openModal('modal-quick-add-event')">
            <x-icon name="plus" size="18" />
            <span>Buat Agenda Baru</span>
        </button>

        <div class="gcal-sidebar-content" id="gcal-sidebar-content">
            {{-- Mini Month Calendar --}}
            @php
                $miniPrevMonth = $currentMonth->subMonth();
                $miniNextMonth = $currentMonth->addMonth();
            @endphp
            <div class="gcal-mini-cal">
                <div class="gcal-mini-header">
                    <strong>{{ $currentMonth->translatedFormat('F Y') }}</strong>
                    <div class="gcal-mini-nav">
                        <a href="{{ route('calendar.index', ['view' => $viewMode, 'month' => $miniPrevMonth->format('Y-m')]) }}" class="gcal-mini-nav-btn" aria-label="Bulan sebelumnya">
                            <x-icon name="chevron-left" size="14" />
                        </a>
                        <a href="{{ route('calendar.index', ['view' => $viewMode, 'month' => $miniNextMonth->format('Y-m')]) }}" class="gcal-mini-nav-btn" aria-label="Bulan berikutnya">
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
                            $isActive = !empty($selectedDate) && $day->isSameDay($selectedDate);
                            $isToday = $day->isToday();
                            $hasEvents = isset($eventsByDate[$day->toDateString()]) && $eventsByDate[$day->toDateString()]->isNotEmpty();
                        @endphp
                        <a href="{{ route('calendar.index', ['view' => $viewMode, 'date' => $day->toDateString(), 'selected' => 1]) }}"
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
                <label class="gcal-checkbox-row">
                    <input type="checkbox" id="filter-cat-surveillance" checked data-filter-cat="SURVEILEN">
                    <span class="gcal-cat-indicator amber"></span>
                    <span class="gcal-cat-text">Jatuh Tempo Surveilen (S1/S2)</span>
                </label>
                <label class="gcal-checkbox-row">
                    <input type="checkbox" id="filter-cat-expiry" checked data-filter-cat="KEDALUWARSA">
                    <span class="gcal-cat-indicator rose"></span>
                    <span class="gcal-cat-text">Kedaluwarsa Akreditasi</span>
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
        </div>
    </aside>