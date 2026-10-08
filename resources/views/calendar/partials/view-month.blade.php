            <div class="gcal-view-container gcal-month-wrap">
                <div class="gcal-month-headings">
                    <span>Senin</span><span>Selasa</span><span>Rabu</span><span>Kamis</span><span>Jumat</span><span>Sabtu</span><span>Minggu</span>
                </div>

                <div class="gcal-month-grid">
                    @foreach($monthWeeks as $day)
                        @php
                            $isCurrentMonth = $day->month === $currentMonth->month;
                            $isToday = $day->isToday();
                            $isSelected = $selectedDate && $day->isSameDay($selectedDate);
                            $dayKey = $day->toDateString();
                            $dayEvents = $eventsByDate->get($dayKey, collect());

                            if (!empty($highlightId)) {
                                $rawH = preg_replace('/^assessment-/', '', $highlightId);
                                $dayEvents = $dayEvents->sortByDesc(fn ($ev) =>
                                    (string)$ev['id'] === (string)$highlightId ||
                                    (string)$ev['id'] === (string)$rawH ||
                                    (string)$ev['id'] === 'assessment-' . $rawH
                                );
                            }
                        @endphp
                        <div class="gcal-month-cell {{ !$isCurrentMonth ? 'outside' : '' }} {{ $isToday ? 'today' : '' }} {{ $isSelected ? 'is-selected' : '' }}" data-date="{{ $dayKey }}">
                            <div class="gcal-cell-top">
                                <a href="{{ route('calendar.index', ['view' => 'day', 'date' => $dayKey]) }}" class="gcal-day-badge {{ $isToday ? 'is-today' : '' }} {{ $isSelected && !$isToday ? 'is-selected' : '' }}">
                                    {{ $day->day }}
                                </a>
                                <button type="button" class="gcal-cell-add-btn" onclick="window.quickAddAt('{{ $dayKey }}', '09:00')" title="Tambah agenda pada {{ $day->translatedFormat('d M Y') }}">
                                    <x-icon name="plus" size="12" />
                                </button>
                            </div>

                            <div class="gcal-cell-events">
                                @foreach($dayEvents->take(3) as $ev)
                                    @php
                                        $rawH = !empty($highlightId) ? preg_replace('/^assessment-/', '', $highlightId) : null;
                                        $isHighlighted = $rawH && (
                                            (string)$ev['id'] === (string)$highlightId ||
                                            (string)$ev['id'] === (string)$rawH ||
                                            (string)$ev['id'] === 'assessment-' . $rawH
                                        );
                                    @endphp
                                    <button type="button"
                                            class="gcal-event-chip theme-{{ $ev['color_theme'] }} {{ $isHighlighted ? 'is-highlight-target' : '' }} {{ !empty($ev['is_multi_day']) ? 'gcal-chip-multiday' : '' }} {{ !empty($ev['is_range_start']) ? 'gcal-chip-start' : '' }} {{ !empty($ev['is_range_end']) ? 'gcal-chip-end' : '' }} {{ empty($ev['is_range_start']) && empty($ev['is_range_end']) && !empty($ev['is_multi_day']) ? 'gcal-chip-mid' : '' }}"
                                            data-event-id="{{ $ev['id'] }}"
                                            data-event-source="{{ $ev['source'] }}"
                                            data-cat="{{ $ev['category'] }}"
                                            data-lpk-id="{{ $ev['lpk_id'] }}"
                                            data-pic-id="{{ $ev['pic_id'] ?? '' }}"
                                            title="{{ $ev['title'] }}{{ !empty($ev['is_multi_day']) ? ' (Hari ke-' . $ev['range_day_index'] . ' dari ' . $ev['range_total_days'] . ')' : '' }}{{ !empty($ev['lpk_name']) ? ' &bull; ' . $ev['lpk_name'] : '' }}"
                                            onclick="window.showEventPopover(this, {{ json_encode($ev) }})">
                                        <span class="gcal-chip-title">
                                            @if(!empty($ev['is_multi_day']) && empty($ev['is_range_start']))
                                                <span class="gcal-chip-arrow">&rarr;</span>
                                            @endif
                                            {{ $ev['title'] }}
                                            @if(!empty($ev['is_multi_day']))
                                                <span class="gcal-chip-day-count">({{ $ev['range_day_index'] }}/{{ $ev['range_total_days'] }})</span>
                                            @endif
                                        </span>
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