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