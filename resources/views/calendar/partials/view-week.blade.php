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
                                @php
                                    $timedEvents = $dayEvents->values();
                                    $eventColumns = [];
                                    $eventTotalCols = [];

                                    if ($timedEvents->isNotEmpty()) {
                                        $clusters = [];
                                        foreach ($timedEvents as $idx => $event) {
                                            $placed = false;
                                            foreach ($clusters as &$cluster) {
                                                $overlapsCluster = false;
                                                foreach ($cluster as $cIdx) {
                                                    $other = $timedEvents[$cIdx];
                                                    if ($event['start_at'] < $other['end_at'] && $event['end_at'] > $other['start_at']) {
                                                        $overlapsCluster = true;
                                                        break;
                                                    }
                                                }
                                                if ($overlapsCluster) {
                                                    $cluster[] = $idx;
                                                    $placed = true;
                                                    break;
                                                }
                                            }
                                            unset($cluster);
                                            if (!$placed) {
                                                $clusters[] = [$idx];
                                            }
                                        }

                                        foreach ($clusters as $cluster) {
                                            $cols = [];
                                            foreach ($cluster as $idx) {
                                                $event = $timedEvents[$idx];
                                                $assignedCol = -1;
                                                foreach ($cols as $colIdx => $colEnd) {
                                                    if ($event['start_at'] >= $colEnd) {
                                                        $assignedCol = $colIdx;
                                                        $cols[$colIdx] = $event['end_at'];
                                                        break;
                                                    }
                                                }
                                                if ($assignedCol === -1) {
                                                    $cols[] = $event['end_at'];
                                                    $assignedCol = count($cols) - 1;
                                                }
                                                $eventColumns[$idx] = $assignedCol;
                                            }
                                            $totalInCluster = max(1, count($cols));
                                            foreach ($cluster as $idx) {
                                                $eventTotalCols[$idx] = $totalInCluster;
                                            }
                                        }
                                    }
                                @endphp
                                @foreach($timedEvents as $idx => $ev)
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

                                        $colIndex = $eventColumns[$idx] ?? 0;
                                        $totalCols = $eventTotalCols[$idx] ?? 1;

                                        $widthStyle = $totalCols > 1
                                            ? "calc((100% - 6px) / {$totalCols} - 2px)"
                                            : "calc(100% - 6px)";
                                        $leftStyle = $totalCols > 1
                                            ? "calc(3px + ({$colIndex} * (100% - 6px) / {$totalCols}))"
                                            : "3px";

                                        $rawH = !empty($highlightId) ? preg_replace('/^assessment-/', '', $highlightId) : null;
                                        $isHighlighted = $rawH && (
                                            (string)$ev['id'] === (string)$highlightId ||
                                            (string)$ev['id'] === (string)$rawH ||
                                            (string)$ev['id'] === 'assessment-' . $rawH
                                        );
                                    @endphp
                                    <div class="gcal-timed-card theme-{{ $ev['color_theme'] }} {{ $isHighlighted ? 'is-highlight-target' : '' }}"
                                         style="top: {{ $topPct }}%; height: {{ $heightPct }}%; left: {{ $leftStyle }}; width: {{ $widthStyle }}; right: auto;"
                                         data-event-id="{{ $ev['id'] }}"
                                         data-cat="{{ $ev['category'] }}"
                                         data-lpk-id="{{ $ev['lpk_id'] }}"
                                         data-pic-id="{{ $ev['pic_id'] ?? '' }}"
                                         onclick="window.showEventPopover(this, {{ json_encode($ev) }})">
                                        <div class="gcal-card-inner">
                                            <div class="gcal-card-time">{{ $ev['start_at']->format('H:i') }} - {{ $ev['end_at']->format('H:i') }} WIB</div>
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