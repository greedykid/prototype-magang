    <aside class="gcal-sidebar">
        <div class="gcal-create-dropdown-wrap">
            <button type="button" class="gcal-btn-create" id="gcal-btn-create-toggle" aria-haspopup="true" aria-expanded="false" onclick="window.toggleCreateDropdown(this)">
                <div class="gcal-btn-create-left">
                    <span class="gcal-btn-create-icon">
                        <x-icon name="plus" size="15" />
                    </span>
                    <span>Tambah Agenda Baru</span>
                </div>
                <span class="gcal-btn-create-chevron">
                    <x-icon name="chevron-down" size="14" />
                </span>
            </button>
            <div class="gcal-create-menu" id="gcal-create-menu" role="menu" aria-labelledby="gcal-btn-create-toggle" style="display: none;">
                <div class="gcal-create-menu-header">Pilih Jenis Agenda</div>
                <button type="button" class="gcal-create-item" role="menuitem" onclick="window.openQuickAddWithType('PRL')">
                    <span class="gcal-create-badge prl">PRL</span>
                    <div class="gcal-create-item-text">
                        <strong>Penambahan Ruang Lingkup</strong>
                        <small>Asesmen penambahan ruang lingkup</small>
                    </div>
                </button>
                <button type="button" class="gcal-create-item" role="menuitem" onclick="window.openQuickAddWithType('STT')">
                    <span class="gcal-create-badge stt">STT</span>
                    <div class="gcal-create-item-text">
                        <strong>Surveilen Tidak Terjadwal</strong>
                        <small>Pemantauan khusus lapangan</small>
                    </div>
                </button>
                <div class="gcal-create-menu-divider" role="separator"></div>
                <button type="button" class="gcal-create-item" role="menuitem" onclick="window.openQuickAddWithType('AGENDA_INTERNAL')">
                    <span class="gcal-create-badge agenda">AGENDA</span>
                    <div class="gcal-create-item-text">
                        <strong>Agenda Internal</strong>
                        <small>Rapat atau kegiatan koordinasi internal</small>
                    </div>
                </button>
            </div>
        </div>

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
                    <span class="gcal-cat-indicator emerald"></span>
                    <span class="gcal-cat-text">Pelaksanaan</span>
                </label>
                <label class="gcal-checkbox-row">
                    <input type="checkbox" id="filter-cat-tp" checked data-filter-cat="BATAS_TP">
                    <span class="gcal-cat-indicator cyan"></span>
                    <span class="gcal-cat-text">Batas TP</span>
                </label>
                <label class="gcal-checkbox-row">
                    <input type="checkbox" id="filter-cat-reminder" checked data-filter-cat="REMINDER">
                    <span class="gcal-cat-indicator amber"></span>
                    <span class="gcal-cat-text">Reminder</span>
                </label>
                <label class="gcal-checkbox-row">
                    <input type="checkbox" id="filter-cat-due" checked data-filter-cat="JATUH_TEMPO">
                    <span class="gcal-cat-indicator rose"></span>
                    <span class="gcal-cat-text">Jatuh Tempo</span>
                </label>
                <label class="gcal-checkbox-row">
                    <input type="checkbox" id="filter-cat-agenda" checked data-filter-cat="AGENDA_INTERNAL">
                    <span class="gcal-cat-indicator indigo"></span>
                    <span class="gcal-cat-text">Agenda Internal</span>
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