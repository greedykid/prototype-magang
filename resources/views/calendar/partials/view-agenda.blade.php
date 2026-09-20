            <div class="gcal-view-container gcal-agenda-wrap">
                @php
                    $sortedDates = $eventsByDate->keys()->sort();
                @endphp

                @forelse($sortedDates as $dateKey)
                    @php
                        $dateObj = \Carbon\Carbon::parse($dateKey)->locale('id');
                        $evs = $eventsByDate[$dateKey];
                        $indonesianDays = [
                            'Sunday' => 'Minggu',
                            'Monday' => 'Senin',
                            'Tuesday' => 'Selasa',
                            'Wednesday' => 'Rabu',
                            'Thursday' => 'Kamis',
                            'Friday' => 'Jumat',
                            'Saturday' => 'Sabtu',
                        ];
                        $indonesianMonths = [
                            1 => 'Januari', 2 => 'Februari', 3 => 'Maret', 4 => 'April',
                            5 => 'Mei', 6 => 'Juni', 7 => 'Juli', 8 => 'Agustus',
                            9 => 'September', 10 => 'Oktober', 11 => 'November', 12 => 'Desember'
                        ];
                        $dayName = $indonesianDays[$dateObj->format('l')] ?? $dateObj->translatedFormat('l');
                        $monthYear = ($indonesianMonths[$dateObj->month] ?? $dateObj->translatedFormat('F')) . ' ' . $dateObj->year;
                    @endphp
                    <div class="gcal-agenda-group" data-date="{{ $dateKey }}">
                        <div class="gcal-agenda-date-head">
                            <div class="gcal-agenda-date-left">
                                <span class="gcal-agenda-day-num">{{ $dateObj->day }}</span>
                                <div>
                                    <strong class="gcal-agenda-day-name">{{ $dayName }}</strong>
                                    <small>{{ $monthYear }}</small>
                                </div>
                            </div>
                            @if($dateObj->isToday())
                                <span class="badge-today">Hari Ini</span>
                            @endif
                        </div>

                        <div class="gcal-agenda-items">
                            @foreach($evs as $ev)
                                <div class="gcal-agenda-row theme-{{ $ev['color_theme'] }}"
                                     data-event-id="{{ $ev['id'] }}"
                                     data-cat="{{ $ev['category'] }}"
                                     data-lpk-id="{{ $ev['lpk_id'] }}">
                                    <div class="gcal-agenda-time-col">
                                        <strong>{{ $ev['start_at']->format('H:i') }}</strong>
                                        <small>{{ $ev['end_at']->format('H:i') }} WIB</small>
                                    </div>

                                    <div class="gcal-agenda-info-col">
                                        <div class="gcal-agenda-title-row">
                                            <strong class="gcal-agenda-title">{{ $ev['title'] }}</strong>
                                            <span class="gcal-badge-pill theme-{{ $ev['color_theme'] }}">{{ $ev['category_label'] }}</span>
                                        </div>
                                        <div class="gcal-agenda-meta">
                                            <span><strong>LPK:</strong> {{ $ev['lpk_name'] }}</span>
                                            <span><strong>Lokasi:</strong> {{ $ev['location'] }}</span>
                                            @if($ev['lead'])
                                                <span><strong>Lead Asesor:</strong> {{ $ev['lead'] }}</span>
                                            @endif
                                        </div>
                                    </div>

                                    <div class="gcal-agenda-actions">
                                        <button type="button" class="button secondary btn-sm" onclick="window.showEventPopover(this, {{ json_encode($ev) }})">
                                            Pratinjau
                                        </button>
                                        <a href="{{ $ev['url'] }}" class="button ghost btn-sm">
                                            <span>Detail</span>
                                            <x-icon name="chevron-right" size="14" />
                                        </a>
                                    </div>
                                </div>
                            @endforeach
                        </div>
                    </div>
                @empty
                    <div class="empty">Belum ada agenda atau asesmen yang tercatat pada rentang ini.</div>
                @endforelse
            </div>