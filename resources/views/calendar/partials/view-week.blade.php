            <div class="gcal-view-container gcal-week-wrap">
                <div class="gcal-week-header-row">
                    <div class="gcal-time-corner">
                        <small>WIB</small>
                    </div>
                    @foreach($weekDays as $day)
                        @php
                            $isToday = $day->isToday();
                            $isActive = $day->isSameDay($activeDate);
                        @endphp
                        <div class="gcal-week-col-head {{ $isToday ? 'today' : '' }}">
                            <span class="gcal-col-dayname">{{ ['Monday' => 'SEN', 'Tuesday' => 'SEL', 'Wednesday' => 'RAB', 'Thursday' => 'KAM', 'Friday' => 'JUM', 'Saturday' => 'SAB', 'Sunday' => 'MIN'][$day->format('l')] }}</span>
                            <a href="{{ route('calendar.index', ['view' => 'day', 'date' => $day->toDateString()]) }}" class="gcal-col-daynum {{ $isToday ? 'is-today' : '' }}">
                                {{ $day->day }}
                            </a>
                        </div>
                    @endforeach
                </div>

                <div class="gcal-week-body">
                    {{-- Time labels column --}}
                    <div class="gcal-time-gutter">
                        @foreach($hoursRange as $hour)
                            <div class="gcal-time-mark">
                                <span>{{ sprintf('%02d:00', $hour) }}</span>
                            </div>
                        @endforeach
                    </div>

                    {{-- 7 Day columns --}}
                    <div class="gcal-week-grid-cols">
                        @foreach($weekDays as $day)
                            @php
                                $isToday = $day->isToday();
                                $dayKey = $day->toDateString();
                                $dayEvents = $eventsByDate->get($dayKey, collect());
                            @endphp
                            <div class="gcal-week-col {{ $isToday ? 'today' : '' }}" data-date="{{ $dayKey }}">
                                @foreach($hoursRange as $hour)
                                    <div class="gcal-hour-slot" onclick="window.quickAddAt('{{ $dayKey }}', '{{ sprintf('%02d:00', $hour) }}')"></div>
                                @endforeach

                                @if($isToday)
                                    {{-- Live Red Line Time Indicator --}}
                                    <div class="gcal-current-time-line" id="gcal-live-time-line" title="Waktu saat ini (WIB)">
                                        <span class="gcal-time-dot"></span>
                                    </div>
                                @endif

                                 {{-- Events placed in this day --}}
                                @foreach($dayEvents as $ev)
                                    @php
                                        $startHour = (int)$ev['start_at']->format('H');
                                        $startMinute = (int)$ev['start_at']->format('i');
                                        $endHour = (int)$ev['end_at']->format('H');
                                        $endMinute = (int)$ev['end_at']->format('i');

                                        $topMinutes = max(0, ($startHour - 7) * 60 + $startMinute);
                                        $durationMinutes = max(35, (($endHour - $startHour) * 60) + ($endMinute - $startMinute));
                                        $totalDayMinutes = (count($hoursRange)) * 60;

                                        $topPct = ($topMinutes / $totalDayMinutes) * 100;
                                        $heightPct = min(100 - $topPct, ($durationMinutes / $totalDayMinutes) * 100);

                                        $rawH = !empty($highlightId) ? preg_replace('/^assessment-/', '', $highlightId) : null;
                                        $isHighlighted = $rawH && (
                                            (string)$ev['id'] === (string)$highlightId ||
                                            (string)$ev['id'] === (string)$rawH ||
                                            (string)$ev['id'] === 'assessment-' . $rawH
                                        );
                                    @endphp
                                    <div class="gcal-timed-card theme-{{ $ev['color_theme'] }} {{ $isHighlighted ? 'is-highlight-target' : '' }}"
                                         style="top: {{ $topPct }}%; height: {{ $heightPct }}%;"
                                         data-event-id="{{ $ev['id'] }}"
                                         data-cat="{{ $ev['category'] }}"
                                         data-lpk-id="{{ $ev['lpk_id'] }}"
                                         onclick="window.showEventPopover(this, {{ json_encode($ev) }})">
                                        <div class="gcal-card-inner">
                                            <div class="gcal-card-time">{{ $ev['start_at']->format('H:i') }} &ndash; {{ $ev['end_at']->format('H:i') }} WIB</div>
                                            <strong class="gcal-card-title">{{ $ev['title'] }}</strong>
                                            <span class="gcal-card-lpk">{{ $ev['lpk_name'] }}</span>
                                        </div>
                                    </div>
                                @endforeach
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>