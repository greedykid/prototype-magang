@if($lpks->count())
    <div class="table-wrap">
        <table class="table-lpks-custom">
            <thead>
                <tr>
                    <th data-label="No Akreditasi" class="col-th-no">NO. AKREDITASI</th>
                    <th data-label="Nama LPK" class="col-th-name">NAMA LPK</th>
                    <th data-label="Masa Berlaku" class="col-th-validity">
                        <div class="th-header-dual">
                            <span class="th-title-main">MASA BERLAKU</span>
                            <span class="th-title-sub">AWAL &amp; AKHIR</span>
                        </div>
                    </th>
                    <th data-label="Keterangan" class="col-th-notes" data-sortable="false">KETERANGAN</th>
                </tr>
            </thead>
            <tbody>
                @foreach($lpks as $lpk)
                    <tr class="clickable-row" data-href="{{ route('lpks.show', $lpk) }}" tabindex="0" role="link" title="Klik baris untuk melihat detail LPK {{ $lpk->name }}">
                        <!-- 1. NO AKREDITASI -->
                        <td class="col-lpk-no" data-sort-value="{{ $lpk->accreditation_number ?: $lpk->registration_number }}">
                            <div class="lpk-card-reg-wrap">
                                <a href="{{ route('lpks.show', $lpk) }}" class="hover-underline lpk-reg-link" title="Buka rincian LPK">
                                    {{ $lpk->accreditation_number ?: $lpk->registration_number }}
                                </a>
                            </div>
                            @php
                                $lpkAlerts = $lpk->getActiveSurveillanceAlerts();
                            @endphp
                            @if(!empty($lpkAlerts))
                                <div class="lpk-card-alerts">
                                    @foreach($lpkAlerts as $alt)
                                        <span class="badge lpk-alert-badge {{ $alt['is_urgent'] ? 'is-urgent' : 'is-warning' }}" title="{{ $alt['description'] }}">
                                            ⚠ {{ $alt['name'] }}
                                        </span>
                                    @endforeach
                                </div>
                            @endif
                        </td>

                        <!-- 2. NAMA LPK -->
                        <td class="col-lpk-name" data-sort-value="{{ $lpk->name }}">
                            <a href="{{ route('lpks.show', $lpk) }}" class="hover-underline lpk-name-link" title="Buka rincian LPK">
                                {{ $lpk->name }}
                            </a>
                            <div class="lpk-status-wrap">
                                <x-status :value="$lpk->dynamic_status" />
                                @if($lpk->pic)
                                    <span class="lpk-pic-badge">
                                        PIC: {{ $lpk->pic->name }}
                                    </span>
                                @endif
                            </div>
                        </td>

                        <!-- 3. MASA BERLAKU AKREDITASI (AWAL DAN AKHIR) -->
                        <td class="col-lpk-validity" data-sort-value="{{ $lpk->expired_at ? $lpk->expired_at->format('Y-m-d') : '9999-99-99' }}">
                            @if($lpk->certificate_date || $lpk->expired_at)
                                <div class="lpk-validity-dates">
                                    <div class="lpk-date-row">
                                        <span class="lpk-date-label">Awal:</span>
                                        <strong class="lpk-date-val">{{ $lpk->certificate_date ? $lpk->certificate_date->format('d/m/Y') : '-' }}</strong>
                                    </div>
                                    <div class="lpk-date-row">
                                        <span class="lpk-date-label">Akhir:</span>
                                        <strong class="lpk-date-val {{ $lpk->isExpired() ? 'is-expired' : '' }}">{{ $lpk->expired_at ? $lpk->expired_at->format('d/m/Y') : '-' }}</strong>
                                    </div>
                                </div>
                                @if($lpk->isExpired())
                                    <span class="status status-danger lpk-expiry-badge">Kedaluwarsa</span>
                                @elseif($lpk->isExpiringSoon())
                                    <span class="status status-warn lpk-expiry-badge">Mendekati Kedaluwarsa</span>
                                @endif
                            @else
                                <span class="text-muted">-</span>
                            @endif
                        </td>

                        <!-- 4. KETERANGAN (OTOMATIS + MANUAL PIC) -->
                        <td class="col-lpk-notes" data-sort-value="{{ $lpk->dynamic_keterangan }}">
                            {{-- Status Siklus / Proses Berjalan Otomatis --}}
                            @if($lpk->dynamic_keterangan)
                                @php
                                    $ketLower = strtolower($lpk->dynamic_keterangan);
                                    if (str_contains($ketLower, 'jatuh tempo') || str_contains($ketLower, 'terlampaui') || str_contains($ketLower, 'lewat jadwal') || str_contains($ketLower, 'kedaluwarsa') || str_contains($ketLower, 'dicabut')) {
                                        $theme = 'rose';
                                        $catLabel = 'Jatuh Tempo';
                                    } elseif (str_contains($ketLower, 'segera berakhir') || str_contains($ketLower, 'reminder') || str_contains($ketLower, 'mendekati kedaluwarsa') || str_contains($ketLower, 'masa berlaku berakhir')) {
                                        $theme = 'amber';
                                        $catLabel = 'Reminder';
                                    } elseif (str_contains($ketLower, 'batas tp') || str_contains($ketLower, 'penyusunan tp') || str_contains($ketLower, 'verifikasi tp') || str_contains($ketLower, 'perpanjangan tp')) {
                                        $theme = 'cyan';
                                        $catLabel = 'Batas TP';
                                    } elseif (str_contains($ketLower, 'dibekukan')) {
                                        $theme = 'rose';
                                        $catLabel = 'Jatuh Tempo';
                                    } else {
                                        $theme = 'emerald';
                                        $catLabel = 'Pelaksanaan';
                                    }
                                @endphp
                                <div class="lpk-auto-status theme-{{ $theme }}">
                                    @if(str_contains($lpk->dynamic_keterangan, ':'))
                                        @php
                                            [$processName, $statusDetail] = explode(':', $lpk->dynamic_keterangan, 2);
                                        @endphp
                                        <div class="lpk-note-header">
                                            <span class="lpk-note-badge">
                                                {{ trim($processName) }}
                                            </span>
                                            <span class="lpk-cat-tag">
                                                <span class="lpk-dot {{ $theme }}"></span>
                                                {{ $catLabel }}
                                            </span>
                                        </div>
                                        <div class="lpk-note-body">
                                            {{ trim($statusDetail) }}
                                        </div>
                                    @else
                                        <div class="lpk-note-header">
                                            <span class="lpk-cat-tag">
                                                <span class="lpk-dot {{ $theme }}"></span>
                                                {{ $catLabel }}
                                            </span>
                                        </div>
                                        <div class="lpk-note-body font-semibold">
                                            {{ $lpk->dynamic_keterangan }}
                                        </div>
                                    @endif
                                </div>
                            @endif

                            {{-- Catatan Manual PIC (Bila Ada) --}}
                            @if($lpk->notes)
                                <div class="lpk-manual-notes">
                                    <div class="lpk-manual-notes-tag">CATATAN PIC</div>
                                    <div class="lpk-manual-notes-body">{{ $lpk->notes }}</div>
                                </div>
                            @endif
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>
    {{ $lpks->links() }}
@else
    <div class="empty">
        {{ $search || $status || $surveillance || $expiry ? 'Tidak ada LPK yang cocok dengan filter.' : 'Belum ada data LPK.' }}
        @if($search || $status || $surveillance || $expiry)
            <button type="button" class="button ghost empty-action" id="empty-reset-filter-btn" data-role="reset-filter">Reset filter</button>
        @elseif(auth()->user()?->isAdmin())
            <a href="{{ route('lpks.create') }}">Tambah LPK pertama</a>.
        @endif
    </div>
@endif
