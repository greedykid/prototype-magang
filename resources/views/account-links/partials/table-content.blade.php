@if($lpks->count())
    <div class="table-wrap" style="margin-top: 16px;">
        <div class="table-mobile-scroll-hint" aria-hidden="true">
            <x-icon name="chevron-left" size="14" />
            <span>Geser ke samping untuk melihat kolom lengkap</span>
            <x-icon name="chevron-right" size="14" />
        </div>
        <table class="table-lpks-custom" id="linked-lpks-table">
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
                    <tr class="clickable-row" data-href="{{ route('account-links.lpks.show', [$owner, $lpk]) }}" data-id="{{ $lpk->id }}" tabindex="0" role="link" title="Klik baris untuk melihat detail LPK {{ $lpk->name }}">
                        @php
                            $lpkAlerts = $lpk->getActiveSurveillanceAlerts();
                        @endphp
                        <!-- 1. NO AKREDITASI -->
                        <td class="col-lpk-no" data-sort-value="{{ $lpk->accreditation_number ?: $lpk->registration_number }}">
                            <div class="lpk-card-reg-wrap">
                                <a href="{{ route('account-links.lpks.show', [$owner, $lpk]) }}" class="lpk-reg-link" title="Buka rincian LPK">
                                    {{ $lpk->accreditation_number ?: $lpk->registration_number }}
                                </a>
                            </div>
                        </td>

                        <!-- 2. NAMA LPK -->
                        <td class="col-lpk-name" data-sort-value="{{ $lpk->name }}">
                            <a href="{{ route('account-links.lpks.show', [$owner, $lpk]) }}" class="lpk-name-link" title="Buka rincian LPK">
                                {{ $lpk->name }}
                            </a>
                            <div class="lpk-status-wrap">
                                <x-status :value="$lpk->dynamic_status" />
                                <span class="badge badge-viewer" title="Akses Viewer: Hanya Lihat">
                                    Viewer
                                </span>
                                @if(!empty($lpkAlerts))
                                    @foreach($lpkAlerts as $alt)
                                        <span class="badge lpk-alert-badge {{ $alt['is_urgent'] ? 'is-urgent' : 'is-warning' }}" title="{{ $alt['description'] }}">
                                            {{ $alt['name'] }}
                                        </span>
                                    @endforeach
                                @endif
                            </div>
                        </td>

                        <!-- 3. MASA BERLAKU AKREDITASI -->
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

                        <!-- 4. KETERANGAN -->
                        <td class="col-lpk-notes">
                            <span class="lpk-notes-badge-wrap">
                                <span class="lpk-notes-text">{{ $lpk->dynamic_keterangan }}</span>
                            </span>
                        </td>
                    </tr>
                @endforeach
            </tbody>
        </table>
    </div>

    <div class="table-pagination-wrap" style="margin-top: 16px;">
        {{ $lpks->links() }}
    </div>
@else
    <div style="padding: 48px 16px; text-align: center; background: var(--surface-subtle, var(--surface)); border: 1px dashed var(--line); border-radius: 8px; margin-top: 16px;">
        <div style="display: inline-flex; align-items: center; justify-content: center; width: 48px; height: 48px; border-radius: 9999px; background: var(--neutral-chip-bg, #f1f5f9); color: var(--muted); margin-bottom: 12px;">
            <x-icon name="building" size="24" />
        </div>
        <div style="font-size: 14px; font-weight: 600; color: var(--ink);">Tidak Ada Laboratorium Ditemukan</div>
        <p style="font-size: 12.5px; color: var(--muted); margin: 4px 0 0; line-height: 1.5;">
            @if($search || $status || $surveillance || $expiry)
                Tidak ada laboratorium milik akun {{ $owner->name }} yang sesuai dengan kriteria filter saat ini.
            @else
                Akun {{ $owner->name }} saat ini belum mendaftarkan laboratorium binaan.
            @endif
        </p>
        @if($search || $status || $surveillance || $expiry)
            <div style="margin-top: 14px;">
                <button type="button" class="button secondary" data-role="reset-filter" style="font-size: 12px; padding: 6px 14px; display: inline-flex; align-items: center; gap: 6px;">
                    <x-icon name="x" size="13" />
                    <span>Reset Filter</span>
                </button>
            </div>
        @endif
    </div>
@endif
