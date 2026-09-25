    <aside class="gcal-sidebar">
        <div class="gcal-create-dropdown-wrap" style="position: relative; margin-bottom: 16px;">
            <button type="button" class="gcal-btn-create" id="gcal-btn-create-toggle" aria-haspopup="true" aria-expanded="false" onclick="window.toggleCreateDropdown(this)" style="display: flex; align-items: center; justify-content: space-between; width: 100%;">
                <div style="display: flex; align-items: center; gap: 8px;">
                    <x-icon name="plus" size="18" />
                    <span>Tambah Agenda Baru</span>
                </div>
                <x-icon name="chevron-down" size="14" />
            </button>
            <div class="gcal-create-menu" id="gcal-create-menu" style="display: none; position: absolute; top: calc(100% + 6px); left: 0; width: 100%; min-width: 230px; background: #ffffff; border: 1px solid var(--line, #cbd5e1); border-radius: 8px; box-shadow: 0 10px 25px -5px rgba(0,0,0,0.12), 0 8px 10px -6px rgba(0,0,0,0.08); z-index: 100; padding: 6px; box-sizing: border-box;">
                <button type="button" class="gcal-create-item" onclick="window.openQuickAddWithType('PRL')" style="display: flex; align-items: center; gap: 10px; width: 100%; padding: 8px 10px; border: none; background: transparent; border-radius: 6px; text-align: left; cursor: pointer; transition: background 150ms ease;">
                    <span style="background: #ecfdf5; color: #047857; font-weight: 700; font-size: 11px; padding: 3px 6px; border-radius: 4px; border: 1px solid #a7f3d0;">PRL</span>
                    <div>
                        <strong style="font-size: 13px; color: var(--ink, #0f172a); display: block;">PRL</strong>
                        <small style="font-size: 11px; color: var(--muted, #64748b);">Penambahan Ruang Lingkup</small>
                    </div>
                </button>
                <button type="button" class="gcal-create-item" onclick="window.openQuickAddWithType('STT')" style="display: flex; align-items: center; gap: 10px; width: 100%; padding: 8px 10px; border: none; background: transparent; border-radius: 6px; text-align: left; cursor: pointer; transition: background 150ms ease;">
                    <span style="background: #ecfdf5; color: #047857; font-weight: 700; font-size: 11px; padding: 3px 6px; border-radius: 4px; border: 1px solid #a7f3d0;">STT</span>
                    <div>
                        <strong style="font-size: 13px; color: var(--ink, #0f172a); display: block;">STT</strong>
                        <small style="font-size: 11px; color: var(--muted, #64748b);">Surveilen Tidak Terjadwal</small>
                    </div>
                </button>
                <div style="border-top: 1px solid #f1f5f9; margin: 4px 0;"></div>
                <button type="button" class="gcal-create-item" onclick="window.openQuickAddWithType('AGENDA_INTERNAL')" style="display: flex; align-items: center; gap: 10px; width: 100%; padding: 8px 10px; border: none; background: transparent; border-radius: 6px; text-align: left; cursor: pointer; transition: background 150ms ease;">
                    <span style="background: #eef2ff; color: #4338ca; font-weight: 700; font-size: 11px; padding: 3px 6px; border-radius: 4px; border: 1px solid #c7d2fe;">AGENDA</span>
                    <div>
                        <strong style="font-size: 13px; color: var(--ink, #0f172a); display: block;">Agenda Umum</strong>
                        <small style="font-size: 11px; color: var(--muted, #64748b);">Rapat atau kegiatan internal</small>
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