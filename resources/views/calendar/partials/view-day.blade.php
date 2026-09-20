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