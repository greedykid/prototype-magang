@if($lpks->count())
    <div class="table-wrap">
        <div class="table-mobile-scroll-hint" aria-hidden="true">
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="15 18 9 12 15 6"></polyline>
            </svg>
            <span>Geser ke samping untuk melihat kolom lengkap</span>
            <svg width="14" height="14" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round">
                <polyline points="9 18 15 12 9 6"></polyline>
            </svg>
        </div>
        <table class="table-lpks-custom" id="lpks-table">
            <thead>
                <tr>
                    <th class="col-th-checkbox" data-sortable="false" data-no-row-click="true">
                        <input type="checkbox" class="table-select-all" data-table-id="lpks-table" aria-label="Pilih semua LPK di halaman ini">
                    </th>
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
                    <tr class="clickable-row" data-href="{{ route('lpks.show', $lpk) }}" data-id="{{ $lpk->id }}" tabindex="0" role="link" title="Klik baris untuk melihat detail LPK {{ $lpk->name }}">
                        @php
                            $lpkAlerts = $lpk->getActiveSurveillanceAlerts();
                        @endphp
                        <td class="col-td-checkbox" data-no-row-click="true">
                            <input type="checkbox" class="table-row-select" data-table-id="lpks-table" value="{{ $lpk->id }}" data-item-name="{{ $lpk->name }}" aria-label="Pilih LPK {{ $lpk->name }}">
                        </td>
                        <!-- 1. NO AKREDITASI -->
                        <td class="col-lpk-no" data-sort-value="{{ $lpk->accreditation_number ?: $lpk->registration_number }}">
                            <div class="lpk-card-reg-wrap">
                                <a href="{{ route('lpks.show', $lpk) }}" class="hover-underline lpk-reg-link" title="Buka rincian LPK">
                                    {{ $lpk->accreditation_number ?: $lpk->registration_number }}
                                </a>
                            </div>
                        </td>

                        <!-- 2. NAMA LPK -->
                        <td class="col-lpk-name" data-sort-value="{{ $lpk->name }}">
                            <a href="{{ route('lpks.show', $lpk) }}" class="hover-underline lpk-name-link" title="Buka rincian LPK">
                                {{ $lpk->name }}
                            </a>
                            <div class="lpk-status-wrap">
                                <x-status :value="$lpk->dynamic_status" />
                                @if(auth()->check() && $lpk->isViewerPic(auth()->user()))
                                    <span class="badge" style="background: #f1f5f9; color: #475569; border: 1px solid #cbd5e1; font-size: 10px; font-weight: 600; padding: 1px 6px; border-radius: 9999px;" title="Anda terhubung sebagai Viewer (Hanya Lihat)">
                                        Viewer
                                    </span>
                                @endif
                                @if(!empty($lpkAlerts))
                                    @foreach($lpkAlerts as $alt)
                                        <span class="badge lpk-alert-badge {{ $alt['is_urgent'] ? 'is-urgent' : 'is-warning' }}" title="{{ $alt['description'] }}">
                                            {{ $alt['name'] }}
                                        </span>
                                    @endforeach
                                @endif
                            </div>
                        </td>

                        <!-- 3. MASA BERLAKU AKREDITASI (AWAL DAN AKHIR) -->
                        <td class="col-lpk-validity" data-sort-value="{{ $lpk->expired_at ? $lpk->expired_at->format('Y-m-d') : '9999-99-99' }}">
                            <div class="lpk-validity-content-wrap">
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
                                    @if($lpk->isRevocationOverdue())
                                        @if(! $lpk->hasReaccreditationInFlight())
                                            <span class="status status-danger lpk-expiry-badge" title="Siklus akreditasi berakhir tanpa pelaksanaan asesmen akreditasi ulang">Dicabut (Tanpa RA)</span>
                                        @else
                                            <span class="status status-danger lpk-expiry-badge" title="Melewati batas 6 bulan masa tenggang tanpa keputusan baru">Dicabut (Lewat Toleransi)</span>
                                        @endif
                                    @elseif($lpk->isInGracePeriod())
                                        <span class="status status-warn lpk-expiry-badge" title="Masa tenggang toleransi s/d {{ $lpk->grace_period_deadline?->format('d/m/Y') }}. Hak penggunaan simbol KAN dibekukan sementara.">Masa Tenggang (6 Bln)</span>
                                    @elseif($lpk->isExpired())
                                        <span class="status status-danger lpk-expiry-badge">Kedaluwarsa</span>
                                    @elseif($lpk->isExpiringSoon())
                                        <span class="status status-warn lpk-expiry-badge">Mendekati Kedaluwarsa</span>
                                    @endif
                                @else
                                    <span class="text-muted">-</span>
                                @endif
                            </div>
                        </td>

                        <!-- 4. KETERANGAN (OTOMATIS + MANUAL PIC) -->
                        @php
                            $dynamicKet = $lpk->dynamic_keterangan;
                        @endphp
                        <td class="col-lpk-notes" data-sort-value="{{ $dynamicKet }}">
                            {{-- Status Siklus / Proses Berjalan Otomatis --}}
                            @if($dynamicKet)
                                @php
                                    $ketLower = strtolower($dynamicKet);
                                    if ($lpk->isRevocationOverdue() || str_contains($ketLower, 'akreditasi dicabut') || str_contains($ketLower, 'status dicabut')) {
                                        $theme = 'rose';
                                        $catLabel = 'Dicabut';
                                    } elseif ($lpk->isInGracePeriod() || str_contains($ketLower, 'masa tenggang') || str_contains($ketLower, 'toleransi')) {
                                        $theme = 'amber';
                                        $catLabel = 'Toleransi 6 Bln';
                                    } elseif (str_contains($ketLower, 'jatuh tempo') || str_contains($ketLower, 'terlampaui') || str_contains($ketLower, 'lewat jadwal') || str_contains($ketLower, 'kedaluwarsa')) {
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
                                    @if(str_contains($dynamicKet, ':'))
                                        @php
                                            [$processName, $statusDetail] = explode(':', $dynamicKet, 2);
                                        @endphp
                                        <div class="lpk-note-header">
                                            <span class="lpk-note-badge">
                                                {{ trim($processName) }}
                                            </span>
                                            <span class="lpk-cat-tag">
                                                {{ $catLabel }}
                                            </span>
                                        </div>
                                        <div class="lpk-note-body">
                                            {{ trim($statusDetail) }}
                                        </div>
                                    @else
                                        <div class="lpk-note-header">
                                            <span class="lpk-cat-tag">
                                                {{ $catLabel }}
                                            </span>
                                        </div>
                                        <div class="lpk-note-body font-semibold">
                                            {{ $dynamicKet }}
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
