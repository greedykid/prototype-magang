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
                                $durationMinutes = max(45, (($endHour - $startHour) * 60) + ($endMinute - $startMinute));
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
                            <div class="gcal-timed-card theme-{{ $ev['color_theme'] }} is-wide {{ $isHighlighted ? 'is-highlight-target' : '' }}"
                                 style="top: {{ $topPct }}%; height: {{ $heightPct }}%; left: {{ $leftStyle }}; width: {{ $widthStyle }}; right: auto;"
                                 data-event-id="{{ $ev['id'] }}"
                                 data-cat="{{ $ev['category'] }}"
                                 data-lpk-id="{{ $ev['lpk_id'] }}"
                                 data-pic-id="{{ $ev['pic_id'] ?? '' }}"
                                 onclick="window.showEventPopover(this, {{ json_encode($ev) }})">
                                <div class="gcal-card-inner">
                                    <div class="gcal-card-time">{{ $ev['start_at']->format('H:i') }} - {{ $ev['end_at']->format('H:i') }} WIB &bull; {{ $ev['category_label'] }}</div>
                                    <strong class="gcal-card-title">{{ $ev['title'] }}</strong>
                                    <span class="gcal-card-lpk">{{ $ev['lpk_name'] }} &bull; {{ $ev['location'] }}</span>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </div>
            </div>