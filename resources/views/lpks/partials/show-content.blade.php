<div class="lpk-show-container">
    {{-- Navigasi Breadcrumb Kembali (Notion Style) --}}
    <div class="lpk-header-back-wrap">
        @if(isset($owner))
            <a href="{{ route('account-links.show', [$owner, 'tab' => 'lpks']) }}" class="lpk-back-btn" title="Kembali ke Daftar LPK Akun Tertaut">
                <x-icon name="chevron-left" size="16" />
                <span>Daftar LPK Akun: {{ $owner->name }}</span>
            </a>
        @elseif(auth()->check() && $lpk->pic_id && auth()->user()->isViewerFor($lpk->pic_id))
            <a href="{{ route('account-links.show', $lpk->pic_id) }}" class="lpk-back-btn" title="Kembali ke Daftar LPK Akun Tertaut">
                <x-icon name="chevron-left" size="16" />
                <span>LPK Akun Tertaut ({{ $lpk->pic?->name }})</span>
            </a>
        @else
            <a href="{{ route('lpks.index') }}" class="lpk-back-btn" title="Kembali ke Data Laboratorium">
                <x-icon name="chevron-left" size="16" />
                <span>Data Laboratorium</span>
            </a>
        @endif
    </div>

    {{-- Header Card dengan Nama LPK, Identitas & Tombol Aksi --}}
    <div class="lpk-show-header">
        <div class="lpk-header-row">
            <div class="lpk-header-title-group">
                <h1>{{ $lpk->name }}</h1>

                <div class="lpk-header-badges">
                    <span class="lpk-badge-reg">No Reg: {{ $lpk->no_reg ?: ($lpk->registration_number ?: '-') }}</span>
                    @if($lpk->accreditation_number)
                        <span class="lpk-badge-acc">No Akreditasi: {{ $lpk->accreditation_number }}</span>
                    @else
                        <span class="lpk-badge-acc">Asesmen Awal</span>
                    @endif
                    @if($lpk->accreditation_type)
                        <span class="lpk-badge-type">{{ $lpk->accreditation_type }}</span>
                    @endif
                    <x-status :value="$lpk->dynamic_status" />
                    @if(isset($owner))
                        <span class="badge" style="background: var(--mint, #ecfdf5); color: var(--green, #047857); border: 1px solid rgba(16, 185, 129, 0.3); font-size: 11.5px; font-weight: 600; padding: 2px 8px; border-radius: var(--radius-pill, 9999px);">
                            Mode Pemantauan (Viewer) - Milik Akun: {{ $owner->name }}
                        </span>
                    @elseif(auth()->check() && $lpk->pic_id && auth()->user()->isViewerFor($lpk->pic_id))
                        <span class="badge" style="background: var(--mint, #ecfdf5); color: var(--green, #047857); border: 1px solid rgba(16, 185, 129, 0.3); font-size: 11.5px; font-weight: 600; padding: 2px 8px; border-radius: var(--radius-pill, 9999px);">
                            Akun Tertaut: {{ $lpk->pic?->name }} (Viewer)
                        </span>
                    @elseif(auth()->check() && $lpk->isViewerPic(auth()->user()))
                        <span class="badge badge-pic-viewer">
                            Akses: Viewer (Hanya Lihat)
                        </span>
                    @endif
                </div>
            </div>

            @if(!isset($owner) && (auth()->user()?->isAdmin() || (auth()->user()?->isPic() && $lpk->isManagedBy(auth()->user()))))
                <div class="lpk-header-actions">
                    <a class="button secondary" href="{{ route('lpks.edit', $lpk) }}" style="display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px; padding: 6px 14px;">
                        <x-icon name="edit" size="14" />
                        <span>Ubah data</span>
                    </a>
                    <form method="POST" action="{{ route('lpks.destroy', $lpk) }}" class="form-delete-lpk" data-lpk-name="{{ $lpk->name }}" data-lpk-reg="{{ $lpk->registration_number }}" style="margin: 0; display: inline-block;">
                        @csrf
                        @method('DELETE')
                        <button type="submit" class="button danger" style="display: inline-flex; align-items: center; gap: 6px; font-size: 12.5px; padding: 6px 14px;">
                            <x-icon name="trash" size="14" />
                            <span>Hapus LPK</span>
                        </button>
                    </form>
                </div>
            @endif
        </div>
    </div>

    @php
        $milestones = $lpk->surveillance_milestones;
        $activeAlerts = $lpk->getActiveSurveillanceAlerts();
        $s1 = $milestones['s1'];
        $s2 = $milestones['s2'];
        $ra = $milestones['ra'];
        $cycleEnd = $lpk->expired_at ?: ($lpk->certificate_date ? $lpk->certificate_date->copy()->addYears(5) : null);
        $cycleStart = $lpk->certificate_date ?: ($cycleEnd ? $cycleEnd->copy()->subYears(5) : null);

        $linkedS1 = $lpk->assessments->first(function ($a) use ($s1, $cycleStart) {
            $inCycle = ! $cycleStart || ($a->start_at && $a->start_at->gte($cycleStart->copy()->subMonths(2)) && $a->start_at->lte($cycleStart->copy()->addMonths(26)));

            return $inCycle && (
                str_contains(strtolower($a->title), 's1')
                || str_contains(strtolower($a->assessment_type), 'surveilen 1')
                || $a->assessment_type === \App\Models\Assessment::TYPE_SURVEILEN_1
                || (str_contains(strtolower($a->assessment_type), 'survei') && $a->start_at && $s1['target_date'] && abs($a->start_at->diffInMonths($s1['target_date'])) <= 6)
            );
        });
        $linkedS2 = $lpk->assessments->first(function ($a) use ($s2, $cycleStart, $linkedS1) {
            $inCycle = ! $cycleStart || ($a->start_at && $a->start_at->gte($cycleStart->copy()->addMonths(24)) && $a->start_at->lte($cycleStart->copy()->addMonths(46)));

            return $inCycle
                && $a->id !== ($linkedS1?->id ?? null)
                && (
                    str_contains(strtolower($a->title), 's2')
                    || str_contains(strtolower($a->assessment_type), 'surveilen 2')
                    || $a->assessment_type === \App\Models\Assessment::TYPE_SURVEILEN_2
                    || (str_contains(strtolower($a->assessment_type), 'survei') && $a->start_at && $s2['target_date'] && abs($a->start_at->diffInMonths($s2['target_date'])) <= 6)
                );
        });
        $linkedRA = $lpk->assessments->first(function ($a) use ($ra, $cycleStart, $cycleEnd) {
            $inCycle = ! $cycleStart || ($a->start_at && $a->start_at->gte($cycleStart->copy()->addMonths(42)) && (! $cycleEnd || $a->start_at->lte($cycleEnd->copy()->addMonths(6))));

            return $inCycle && (
                str_contains(strtolower($a->title), 're-akreditasi')
                || str_contains(strtolower($a->title), 'reakreditasi')
                || str_contains(strtolower($a->title), '(ra)')
                || str_contains(strtolower($a->title), ' ra ')
                || in_array($a->assessment_type, [\App\Models\Assessment::TYPE_RE_AKREDITASI, 'Re-Akreditasi', 'Re-asesmen', 'REASSESSMENT'], true)
                || ($ra['target_date'] && $a->start_at && abs($a->start_at->diffInMonths($ra['target_date'])) <= 6)
            );
        });

        $resolveMilestoneCardState = function (?App\Models\Assessment $linked, array $milestone, string $typeCode): array {
            // 1. DIBEKUKAN (Biru) - Cek pembekuan asesmen atau milestone siklus
            if (($linked && $linked->status === 'SUSPENDED') || ($milestone['status'] ?? null) === 'SUSPENDED') {
                return [
                    'card_class' => 'is-dibekukan is-suspended',
                    'badge_class' => 'status-dibekukan status-suspended',
                    'badge_label' => 'Dibekukan',
                ];
            }

            // 2. BATAS TP DAN VTP (Cyan) - Cek tindakan perbaikan aktif
            if ($linked && $linked->tp_status && ! in_array($linked->tp_status, [\App\Models\Assessment::TP_STATUS_NONE, \App\Models\Assessment::TP_STATUS_SATISFIED], true)) {
                return [
                    'card_class' => 'is-batas-tp',
                    'badge_class' => 'status-batas-tp',
                    'badge_label' => 'Batas TP',
                ];
            }

            // 3. JT S1 - RA (Merah) - Jatuh tempo, lewat jadwal, atau dicabut
            if ($linked && $linked->status === 'REVOKED') {
                return [
                    'card_class' => 'is-jt is-overdue',
                    'badge_class' => 'status-jt status-danger',
                    'badge_label' => 'Dicabut',
                ];
            }
            if ($linked && $linked->status === 'SCHEDULED' && $linked->start_at && $linked->start_at->isPast()) {
                return [
                    'card_class' => 'is-jt is-overdue',
                    'badge_class' => 'status-jt status-danger',
                    'badge_label' => 'Lewat Jadwal',
                ];
            }
            if (($milestone['status'] ?? null) === 'OVERDUE') {
                return [
                    'card_class' => 'is-jt is-overdue',
                    'badge_class' => 'status-jt status-danger',
                    'badge_label' => 'Lewat Jadwal',
                ];
            }
            if (($milestone['status'] ?? null) === 'EXPIRED') {
                return [
                    'card_class' => 'is-jt is-overdue',
                    'badge_class' => 'status-jt status-danger',
                    'badge_label' => 'Sertifikat Kedaluwarsa',
                ];
            }

            // 4. PELAKSANAAN S1 - RA, PRL DAN STT (Hijau)
            // Semua asesmen pelaksanaan resmi KAN (Selesai, Sedang Berlangsung, Terjadwal, maupun Direncanakan)
            if ($linked) {
                if ($linked->status === 'COMPLETED') {
                    return [
                        'card_class' => 'is-pelaksanaan is-completed',
                        'badge_class' => 'status-pelaksanaan status-completed',
                        'badge_label' => 'Selesai',
                    ];
                }
                if ($linked->status === 'IN_PROGRESS') {
                    return [
                        'card_class' => 'is-pelaksanaan is-in-progress',
                        'badge_class' => 'status-pelaksanaan status-in_progress',
                        'badge_label' => 'Sedang Berlangsung',
                    ];
                }
                if ($linked->status === 'SCHEDULED') {
                    return [
                        'card_class' => 'is-pelaksanaan is-scheduled',
                        'badge_class' => 'status-pelaksanaan status-scheduled',
                        'badge_label' => 'Terjadwal',
                    ];
                }
                if ($linked->status === 'PLANNED') {
                    return [
                        'card_class' => 'is-upcoming',
                        'badge_class' => 'status-planned',
                        'badge_label' => 'Akan Datang',
                    ];
                }
            }

            if (($milestone['status'] ?? null) === 'COMPLETED_OR_SCHEDULED') {
                return [
                    'card_class' => 'is-pelaksanaan is-completed',
                    'badge_class' => 'status-pelaksanaan status-completed',
                    'badge_label' => 'Terealisasi',
                ];
            }

            // 5. REMINDER S1 - RA (Kuning)
            if (($milestone['status'] ?? null) === 'DUE') {
                $lbl = match ($typeCode) {
                    'S1' => 'Reminder S1 (Bulan 14)',
                    'S2' => 'Reminder S2 (Bulan 35)',
                    default => 'Reminder RA (1 Bulan Sebelum Habis)',
                };
                return [
                    'card_class' => 'is-reminder is-due',
                    'badge_class' => 'status-reminder status-warn',
                    'badge_label' => $lbl,
                ];
            }

            // 6. Netral / Belum Ada Agenda (Akan Datang)
            return [
                'card_class' => 'is-upcoming',
                'badge_class' => 'status-planned',
                'badge_label' => 'Akan Datang',
            ];
        };

        $s1CardState = $resolveMilestoneCardState($linkedS1, $s1, 'S1');
        $s2CardState = $resolveMilestoneCardState($linkedS2, $s2, 'S2');
        $raCardState = $resolveMilestoneCardState($linkedRA, $ra, 'RA');

        $isMilestoneResolved = function (array $state): bool {
            return in_array($state['badge_label'], ['Selesai', 'Terealisasi'], true);
        };

        $currentFocusCode = null;
        if (! $isMilestoneResolved($s1CardState)) {
            $currentFocusCode = 'S1';
        } elseif (! $isMilestoneResolved($s2CardState)) {
            $currentFocusCode = 'S2';
        } else {
            $currentFocusCode = 'RA';
        }
    @endphp

    {{-- Alert Masa Tenggang 6 Bulan & Auto-Revocation --}}
    @if($lpk->isInGracePeriod())
        <div class="lpk-alert-callout is-warning">
            <div class="lpk-alert-callout-content">
                <div class="lpk-alert-callout-head">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                    <strong>Masa Tenggang Akreditasi Ulang &amp; Peringatan Penggunaan Simbol KAN</strong>
                </div>
                <p class="lpk-alert-callout-desc" style="margin-bottom: 6px;">
                    Sertifikat akreditasi telah habis masa berlakunya pada <strong>{{ $lpk->expired_at?->format('d M Y') }}</strong>. Karena asesmen akreditasi ulang telah dilaksanakan sebelum siklus berakhir, laboratorium berada dalam masa tenggang toleransi maksimal 6 bulan (sisa waktu: <strong>{{ $lpk->days_remaining_grace_period }} hari</strong> s/d <strong>{{ $lpk->grace_period_deadline?->format('d M Y') }}</strong>) untuk menunggu keputusan akreditasi KAN.
                </p>
                <div class="lpk-grace-warning-box">
                    <strong>Peringatan Penggunaan Simbol KAN:</strong> Selama masa akreditasi telah habis dan keputusan akreditasi ulang belum ditetapkan, LPK <u>dilarang</u> menggunakan simbol akreditasi KAN dan/atau membuat pernyataan akreditasi pada sertifikat, laporan pengujian/kalibrasi, kop surat, maupun media publikasi lainnya.
                </div>
            </div>
            @if($lpk->canManage(auth()->user()))
                <div class="lpk-alert-callout-actions">
                    @if($linkedRA)
                        <a href="{{ route('assessments.edit', $linkedRA) }}#form-sk-block" class="button primary button-sm lpk-alert-action-btn lpk-alert-action-btn-warning" title="Ubah asesmen reakreditasi dan langsung menuju pengisian nomor &amp; tanggal SK KAN">
                            <x-icon name="check-circle" size="14" />
                            <span>Input SK Akreditasi KAN</span>
                        </a>
                    @else
                        <a href="{{ route('assessments.create', ['lpk_id' => $lpk->id, 'alert_code' => 'RA', 'target_date' => $ra['target_date']?->format('Y-m-d')]) }}" class="button primary button-sm lpk-alert-action-btn lpk-alert-action-btn-warning" title="Catat agenda asesmen akreditasi ulang">
                            <x-icon name="plus" size="14" />
                            <span>Catat Reakreditasi</span>
                        </a>
                    @endif
                    <a href="{{ route('lpks.edit', $lpk) }}" class="button secondary button-sm lpk-alert-action-btn" title="Perbarui sertifikat dan masa berlaku akreditasi LPK">
                        <x-icon name="edit" size="14" />
                        <span>Perbarui Sertifikat</span>
                    </a>
                </div>
            @endif
        </div>
    @elseif($lpk->isRevocationOverdue())
        <div class="lpk-alert-callout">
            <div class="lpk-alert-callout-content">
                <div class="lpk-alert-callout-head">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="15" y1="9" x2="9" y2="15"/><line x1="9" y1="9" x2="15" y2="15"/></svg>
                    <strong>Status Akreditasi Dicabut (Revoked)</strong>
                </div>
                <p class="lpk-alert-callout-desc">
                    @if(! $lpk->hasReaccreditationInFlight())
                        Siklus akreditasi telah berakhir pada <strong>{{ $lpk->expired_at?->format('d M Y') }}</strong> tanpa pelaksanaan asesmen akreditasi ulang. Status akreditasi laboratorium resmi dicabut. Laboratorium dapat mengajukan akreditasi kembali sebagai pemohon akreditasi awal dengan nomor akreditasi baru.
                    @else
                        Keputusan akreditasi ulang belum ditetapkan sampai dengan batas 6 bulan masa tenggang pada <strong>{{ $lpk->grace_period_deadline?->format('d M Y') }}</strong>. Proses akreditasi ulang dihentikan dan status akreditasi resmi dicabut. Laboratorium dapat mengajukan akreditasi kembali sebagai pemohon akreditasi awal dengan nomor akreditasi baru.
                    @endif
                </p>
            </div>
            @if($lpk->canManage(auth()->user()))
                <div class="lpk-alert-callout-actions">
                    <a href="{{ route('assessments.create', ['lpk_id' => $lpk->id, 'assessment_type' => 'INITIAL', 'title' => 'Akreditasi Awal - ' . $lpk->name]) }}" class="button primary button-sm lpk-alert-action-btn lpk-alert-action-btn-danger" title="Daftarkan permohonan asesmen akreditasi awal baru">
                        <x-icon name="plus" size="14" />
                        <span>Ajukan Akreditasi Awal</span>
                    </a>
                    <a href="{{ route('lpks.edit', $lpk) }}" class="button secondary button-sm lpk-alert-action-btn" title="Perbarui data dan status laboratorium">
                        <x-icon name="edit" size="14" />
                        <span>Perbarui Data LPK</span>
                    </a>
                </div>
            @endif
        </div>
    @endif

    {{-- Alert Persisten Pengawasan KAN --}}
    @if(!empty($activeAlerts))
        <div class="lpk-alert-callout">
            <div class="lpk-alert-callout-content">
                <div class="lpk-alert-callout-head">
                    <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="#e11d48" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" style="flex-shrink: 0;" aria-hidden="true"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                    <strong>Peringatan Persisten: LPK Memasuki Masa Jatuh Tempo Pengawasan KAN</strong>
                </div>
                <p class="lpk-alert-callout-desc">
                    Notifikasi aktif untuk 
                    @foreach($activeAlerts as $a)
                        <span class="lpk-alert-tag">{{ $a['name'] }} ({{ $a['status_label'] }})</span>@if(!$loop->last) @endif
                    @endforeach
                    . Peringatan ini aktif sampai agenda kunjungan asesmen tercatat.
                </p>
                @if($lpk->last_surveillance_notified_at)
                    <div class="lpk-alert-callout-meta">
                        <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" aria-hidden="true"><circle cx="12" cy="12" r="10"/><polyline points="12 6 12 12 16 14"/></svg>
                        <span>Email pemberitahuan terakhir dikirim ke PIC Lab: <strong>{{ $lpk->last_surveillance_notified_at->format('d M Y') }}</strong></span>
                    </div>
                @endif
            </div>
            <div class="lpk-alert-callout-actions">
                @if($lpk->canManage(auth()->user()))
                    @foreach($activeAlerts as $a)
                        @php
                            $alertCode = strtoupper($a['code'] ?? 'S1');
                            $alertTarget = ($a['visit_target_date'] ?? $a['target_date'])?->format('Y-m-d');
                            $linkedAssessment = match($alertCode) {
                                'S1' => $linkedS1,
                                'S2' => $linkedS2,
                                'RA' => $linkedRA,
                                default => null,
                            };
                        @endphp
                        @if($linkedAssessment)
                            @if($linkedAssessment->start_at)
                                <a href="{{ route('assessments.show', $linkedAssessment) }}" class="button primary button-sm lpk-alert-action-btn lpk-alert-action-btn-danger" title="Buka detail agenda asesmen {{ $a['name'] }}">
                                    <x-icon name="calendar" size="14" />
                                    <span>Buka {{ $a['name'] }}</span>
                                </a>
                            @else
                                <a href="{{ route('assessments.edit', $linkedAssessment) }}" class="button primary button-sm lpk-alert-action-btn lpk-alert-action-btn-danger" title="Lengkapi jadwal kunjungan asesmen {{ $a['name'] }}">
                                    <x-icon name="calendar" size="14" />
                                    <span>Input Tanggal {{ $a['name'] }}</span>
                                </a>
                            @endif
                        @else
                            <a href="{{ route('assessments.create', ['lpk_id' => $lpk->id, 'alert_code' => $alertCode, 'target_date' => $alertTarget]) }}" class="button primary button-sm lpk-alert-action-btn lpk-alert-action-btn-danger" title="Jadwalkan agenda kunjungan asesmen {{ $a['name'] }} sekarang">
                                <x-icon name="plus" size="14" />
                                <span>Jadwalkan {{ $a['name'] }}</span>
                            </a>
                        @endif
                    @endforeach
                @endif
                @if(auth()->user()?->isAdmin())
                    <form method="POST" action="{{ route('lpks.surveillance.remind', $lpk) }}" style="margin: 0; flex-shrink: 0;">
                        @csrf
                        <button type="submit" class="button secondary button-sm lpk-alert-action-btn" title="Kirim notifikasi email pengingat pengawasan ke PIC Lab">
                            <x-icon name="mail" size="14" />
                            <span>Kirim Notifikasi Email PIC Lab</span>
                        </button>
                    </form>
                @endif
            </div>
        </div>
    @endif

    {{-- Siklus KAN U-01: Roadmap & Milestone Card --}}
    <div class="lpk-form-card">
        <div class="lpk-form-card-header" style="border-bottom: 1px solid var(--line); padding-bottom: 14px; margin-bottom: 16px;">
            <div class="lpk-card-icon-wrap icon-wrap-slate">
                <x-icon name="clock" size="20" />
            </div>
            <div class="lpk-card-header-text" style="flex: 1;">
                <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 10px;">
                    <div>
                        <h2 style="font-size: 16px; margin: 0; display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                            <span>Siklus Pengawasan &amp; Re-Akreditasi</span>
                            @if($cycleStart && $cycleEnd)
                                <span style="font-size: 13px; font-weight: 500; color: var(--muted); margin-left: 4px;">(Periode {{ $cycleStart->format('Y') }} - {{ $cycleEnd->format('Y') }})</span>
                            @endif
                            @php
                                $focusLabel = match($currentFocusCode) {
                                    'S1' => 'Surveilen 1',
                                    'S2' => 'Surveilen 2',
                                    default => 'Re-Akreditasi',
                                };
                            @endphp
                            <span style="font-size: 11px; font-weight: 700; padding: 2.5px 9px; border-radius: var(--radius-pill, 9999px); background: var(--neutral-chip-bg); color: var(--ink); border: 1px solid var(--line); display: inline-flex; align-items: center; letter-spacing: 0.02em;">
                                Fokus Siklus: {{ $focusLabel }}
                            </span>
                        </h2>
                    </div>
                    @if(auth()->user()?->isAdmin())
                        <a href="{{ route('assessments.create', ['lpk_id' => $lpk->id]) }}" class="button secondary" style="font-size: 12px; padding: 5px 12px; min-height: 32px; display: inline-flex; align-items: center; gap: 6px;">
                            <x-icon name="plus" size="14" />
                            <span>Jadwalkan Asesmen Kunjungan</span>
                        </a>
                    @endif
                </div>
            </div>
        </div>

        {{-- Stepper Timeline Horizontal KAN U-01 --}}
        <div class="lpk-cycle-timeline-bar" aria-label="Alur Tahapan Siklus KAN U-01">
            <div class="lpk-cycle-step-item {{ $currentFocusCode === 'S1' ? 'is-current' : ($isMilestoneResolved($s1CardState) ? 'is-completed' : 'is-upcoming') }}">
                <div class="lpk-step-bullet">
                    @if($isMilestoneResolved($s1CardState))
                        <x-icon name="check" size="13" />
                    @else
                        <span>1</span>
                    @endif
                </div>
                <div class="lpk-step-label-group">
                    <span class="lpk-step-num">Tahap 1</span>
                    <strong class="lpk-step-name">Surveilen 1</strong>
                    <span class="lpk-step-timing">Bulan ke-15</span>
                </div>
            </div>

            <div class="lpk-cycle-step-line {{ in_array($currentFocusCode, ['S2', 'RA']) || $isMilestoneResolved($s1CardState) ? 'is-passed' : '' }}"></div>

            <div class="lpk-cycle-step-item {{ $currentFocusCode === 'S2' ? 'is-current' : ($isMilestoneResolved($s2CardState) ? 'is-completed' : 'is-upcoming') }}">
                <div class="lpk-step-bullet">
                    @if($isMilestoneResolved($s2CardState))
                        <x-icon name="check" size="13" />
                    @else
                        <span>2</span>
                    @endif
                </div>
                <div class="lpk-step-label-group">
                    <span class="lpk-step-num">Tahap 2</span>
                    <strong class="lpk-step-name">Surveilen 2</strong>
                    <span class="lpk-step-timing">Bulan ke-36</span>
                </div>
            </div>

            <div class="lpk-cycle-step-line {{ $currentFocusCode === 'RA' || $isMilestoneResolved($s2CardState) ? 'is-passed' : '' }}"></div>

            <div class="lpk-cycle-step-item {{ $currentFocusCode === 'RA' ? 'is-current' : ($isMilestoneResolved($raCardState) ? 'is-completed' : 'is-upcoming') }}">
                <div class="lpk-step-bullet">
                    @if($isMilestoneResolved($raCardState))
                        <x-icon name="check" size="13" />
                    @else
                        <span>3</span>
                    @endif
                </div>
                <div class="lpk-step-label-group">
                    <span class="lpk-step-num">Tahap 3</span>
                    <strong class="lpk-step-name">Re-Akreditasi</strong>
                    <span class="lpk-step-timing">Bulan ke-54 &bull; Akhir Siklus</span>
                </div>
            </div>
        </div>

        <div class="lpk-milestones-grid">
            <!-- S1 Card -->
            <div class="lpk-milestone-card {{ $s1CardState['card_class'] }} {{ $currentFocusCode === 'S1' ? 'is-current-focus' : '' }}">
                <div class="lpk-milestone-card-top">
                    <div class="lpk-milestone-header">
                        <div class="lpk-milestone-heading-left">
                            <h3 class="lpk-milestone-title">{{ $s1['name'] }}</h3>
                        </div>
                        <div class="lpk-milestone-badges">
                            @if($currentFocusCode === 'S1')
                                <span class="badge-focus">Fokus Siklus</span>
                            @endif
                            <span class="status {{ $s1CardState['badge_class'] }}">
                                {{ $s1CardState['badge_label'] }}
                            </span>
                        </div>
                    </div>
                    <p class="lpk-milestone-desc">Target Kunjungan Bulan ke-15 &bull; Toleransi jatuh tempo s/d Bulan ke-24</p>

                    {{-- Primary Target Date --}}
                    <div class="lpk-milestone-primary-target">
                        <span class="lpk-target-label">Target Kunjungan (Bulan 15)</span>
                        <span class="lpk-target-value">{{ ($s1['visit_target_date'] ?? null) ? $s1['visit_target_date']->format('d M Y') : '-' }}</span>
                    </div>

                    {{-- Supporting Metadata Dates --}}
                    <div class="lpk-milestone-subdates">
                        <div class="lpk-subdate-row">
                            <span class="lpk-subdate-label">Tanggal Notifikasi:</span>
                            <strong class="lpk-subdate-val">{{ $s1['notice_date'] ? $s1['notice_date']->format('d M Y') : '-' }}</strong>
                        </div>
                        <div class="lpk-subdate-row">
                            <span class="lpk-subdate-label">Batas Jatuh Tempo (Bulan 18):</span>
                            <strong class="lpk-subdate-val">{{ $s1['target_date'] ? $s1['target_date']->format('d M Y') : '-' }}</strong>
                        </div>
                    </div>

                    @if($lpk->s1_delay_penalty_months > 0)
                        <div class="lpk-delay-penalty-banner">
                            <span style="font-weight: 600;">Penyempitan Jarak S1:</span> Terlambat RA {{ $lpk->s1_delay_penalty_months }} bulan pada siklus lalu. Jarak persiapan menuju S1 menyempit menjadi <strong>{{ $lpk->s1_prep_remaining_months }} bulan</strong>.
                        </div>
                    @endif

                    {{-- Agenda Asesmen Strip --}}
                    <div class="lpk-milestone-agenda-strip">
                        <span class="lpk-agenda-caption">Agenda Asesmen:</span>
                        <div class="lpk-agenda-actions">
                            @if($linkedS1)
                                <a href="{{ route('assessments.show', $linkedS1) }}" class="lpk-agenda-chip" title="Buka detail asesmen">
                                    <span>{{ $linkedS1->status === 'PLANNED' ? 'Rencana (Bulan 15)' : $linkedS1->status_label }}</span>
                                    <x-icon name="chevron-right" size="12" />
                                </a>
                            @elseif(!empty($s1['target_date']))
                                <span class="lpk-agenda-unassigned">Belum Dijadwalkan</span>
                            @endif

                            @if($linkedS1)
                                <a href="{{ route('calendar.index', ['view' => 'month', 'date' => $linkedS1->start_at->toDateString(), 'highlight' => $linkedS1->id, 'selected' => 1]) }}"
                                   class="assessment-cal-shortcut"
                                   title="Lihat agenda S1 di kalender">
                                    <x-icon name="calendar" size="13" />
                                    <span>Kalender</span>
                                </a>
                            @elseif(!empty($s1['target_date']))
                                <a href="{{ route('calendar.index', ['view' => 'month', 'date' => $s1['target_date']->toDateString(), 'highlight' => 'lpk_jt_s1_' . $lpk->id, 'selected' => 1]) }}"
                                   class="assessment-cal-shortcut"
                                   title="Lihat target S1 di kalender">
                                    <x-icon name="calendar" size="13" />
                                    <span>Kalender</span>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                @if(auth()->user()?->isAdmin() && !$linkedS1)
                    <div class="lpk-milestone-footer-actions">
                        <a href="{{ route('assessments.create', ['lpk_id' => $lpk->id, 'alert_code' => 'S1', 'target_date' => ($s1['visit_target_date'] ?? $s1['target_date'])?->format('Y-m-d')]) }}" class="button primary button-sm lpk-primary-action-btn">
                            <x-icon name="plus" size="13" />
                            <span>Jadwalkan Kunjungan</span>
                        </a>
                    </div>
                @endif
            </div>

            <!-- S2 Card -->
            <div class="lpk-milestone-card {{ $s2CardState['card_class'] }} {{ $currentFocusCode === 'S2' ? 'is-current-focus' : '' }}">
                <div class="lpk-milestone-card-top">
                    <div class="lpk-milestone-header">
                        <div class="lpk-milestone-heading-left">
                            <h3 class="lpk-milestone-title">{{ $s2['name'] }}</h3>
                        </div>
                        <div class="lpk-milestone-badges">
                            @if($currentFocusCode === 'S2')
                                <span class="badge-focus">Fokus Siklus</span>
                            @endif
                            <span class="status {{ $s2CardState['badge_class'] }}">
                                {{ $s2CardState['badge_label'] }}
                            </span>
                        </div>
                    </div>
                    <p class="lpk-milestone-desc">Target Kunjungan Bulan ke-36 &bull; Toleransi jatuh tempo s/d Bulan ke-39</p>

                    {{-- Primary Target Date --}}
                    <div class="lpk-milestone-primary-target">
                        <span class="lpk-target-label">Target Kunjungan (Bulan 36)</span>
                        <span class="lpk-target-value">{{ ($s2['visit_target_date'] ?? null) ? $s2['visit_target_date']->format('d M Y') : '-' }}</span>
                    </div>

                    {{-- Supporting Metadata Dates --}}
                    <div class="lpk-milestone-subdates">
                        <div class="lpk-subdate-row">
                            <span class="lpk-subdate-label">Tanggal Notifikasi:</span>
                            <strong class="lpk-subdate-val">{{ $s2['notice_date'] ? $s2['notice_date']->format('d M Y') : '-' }}</strong>
                        </div>
                        <div class="lpk-subdate-row">
                            <span class="lpk-subdate-label">Batas Jatuh Tempo (Bulan 39):</span>
                            <strong class="lpk-subdate-val">{{ $s2['target_date'] ? $s2['target_date']->format('d M Y') : '-' }}</strong>
                        </div>
                    </div>

                    {{-- Agenda Asesmen Strip --}}
                    <div class="lpk-milestone-agenda-strip">
                        <span class="lpk-agenda-caption">Agenda Asesmen:</span>
                        <div class="lpk-agenda-actions">
                            @if($linkedS2)
                                <a href="{{ route('assessments.show', $linkedS2) }}" class="lpk-agenda-chip" title="Buka detail asesmen">
                                    <span>{{ $linkedS2->status === 'PLANNED' ? 'Rencana (Bulan 36)' : $linkedS2->status_label }}</span>
                                    <x-icon name="chevron-right" size="12" />
                                </a>
                            @elseif(!empty($s2['target_date']))
                                <span class="lpk-agenda-unassigned">Belum Dijadwalkan</span>
                            @endif

                            @if($linkedS2)
                                <a href="{{ route('calendar.index', ['view' => 'month', 'date' => $linkedS2->start_at->toDateString(), 'highlight' => $linkedS2->id, 'selected' => 1]) }}"
                                   class="assessment-cal-shortcut"
                                   title="Lihat agenda S2 di kalender">
                                    <x-icon name="calendar" size="13" />
                                    <span>Kalender</span>
                                </a>
                            @elseif(!empty($s2['target_date']))
                                <a href="{{ route('calendar.index', ['view' => 'month', 'date' => $s2['target_date']->toDateString(), 'highlight' => 'lpk_jt_s2_' . $lpk->id, 'selected' => 1]) }}"
                                   class="assessment-cal-shortcut"
                                   title="Lihat target S2 di kalender">
                                    <x-icon name="calendar" size="13" />
                                    <span>Kalender</span>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                @if(auth()->user()?->isAdmin() && !$linkedS2)
                    <div class="lpk-milestone-footer-actions">
                        <a href="{{ route('assessments.create', ['lpk_id' => $lpk->id, 'alert_code' => 'S2', 'target_date' => ($s2['visit_target_date'] ?? $s2['target_date'])?->format('Y-m-d')]) }}" class="button primary button-sm lpk-primary-action-btn">
                            <x-icon name="plus" size="13" />
                            <span>Jadwalkan Kunjungan</span>
                        </a>
                    </div>
                @endif
            </div>

            <!-- RA Card -->
            <div class="lpk-milestone-card {{ $raCardState['card_class'] }} {{ $currentFocusCode === 'RA' ? 'is-current-focus' : '' }}">
                <div class="lpk-milestone-card-top">
                    <div class="lpk-milestone-header">
                        <div class="lpk-milestone-heading-left">
                            <h3 class="lpk-milestone-title">{{ $ra['name'] }}</h3>
                        </div>
                        <div class="lpk-milestone-badges">
                            @if($currentFocusCode === 'RA')
                                <span class="badge-focus">Fokus Siklus</span>
                            @endif
                            <span class="status {{ $raCardState['badge_class'] }}">
                                {{ $raCardState['badge_label'] }}
                            </span>
                        </div>
                    </div>
                    <p class="lpk-milestone-desc">Pengajuan permohonan Bulan ke-48 s/d 51 &bull; Asesmen sebelum Bulan ke-60</p>

                    {{-- Primary Target Date --}}
                    <div class="lpk-milestone-primary-target">
                        <span class="lpk-target-label">Batas Permohonan Lengkap (Bulan 51)</span>
                        <span class="lpk-target-value">{{ $ra['target_date'] ? $ra['target_date']->format('d M Y') : '-' }}</span>
                    </div>

                    {{-- Supporting Metadata Dates --}}
                    <div class="lpk-milestone-subdates">
                        <div class="lpk-subdate-row">
                            <span class="lpk-subdate-label">Pengumuman Permohonan RA:</span>
                            <strong class="lpk-subdate-val">{{ $ra['notice_date'] ? $ra['notice_date']->format('d M Y') : '-' }}</strong>
                        </div>
                        <div class="lpk-subdate-row">
                            <span class="lpk-subdate-label">Target Asesmen Lapangan (Bulan 54):</span>
                            <strong class="lpk-subdate-val">{{ ($ra['visit_target_date'] ?? null) ? $ra['visit_target_date']->format('d M Y') : '-' }}</strong>
                        </div>
                        <div class="lpk-subdate-row">
                            <span class="lpk-subdate-label">Masa Berlaku Habis (Bulan 60):</span>
                            <strong class="lpk-subdate-val">{{ ($ra['tolerance_date'] ?? null) ? $ra['tolerance_date']->format('d M Y') : '-' }}</strong>
                        </div>
                        @if($lpk->isInGracePeriod() && $lpk->grace_period_deadline)
                            <div class="lpk-subdate-row" style="color: var(--warning-text, #b45309);">
                                <span class="lpk-subdate-label">Batas Masa Tenggang (6 Bulan):</span>
                                <strong class="lpk-subdate-val">{{ $lpk->grace_period_deadline->format('d M Y') }}</strong>
                            </div>
                        @endif
                    </div>

                    {{-- Agenda Asesmen Strip --}}
                    <div class="lpk-milestone-agenda-strip">
                        <span class="lpk-agenda-caption">Agenda Asesmen:</span>
                        <div class="lpk-agenda-actions">
                            @if($linkedRA)
                                <a href="{{ route('assessments.show', $linkedRA) }}" class="lpk-agenda-chip" title="Buka detail asesmen">
                                    <span>{{ $linkedRA->status === 'PLANNED' ? 'Rencana (Bulan 54)' : $linkedRA->status_label }}</span>
                                    <x-icon name="chevron-right" size="12" />
                                </a>
                            @elseif(!empty($ra['target_date']))
                                <span class="lpk-agenda-unassigned">Belum Dijadwalkan</span>
                            @endif

                            @if($linkedRA)
                                <a href="{{ route('calendar.index', ['view' => 'month', 'date' => $linkedRA->start_at->toDateString(), 'highlight' => $linkedRA->id, 'selected' => 1]) }}"
                                   class="assessment-cal-shortcut"
                                   title="Lihat agenda RA di kalender">
                                    <x-icon name="calendar" size="13" />
                                    <span>Kalender</span>
                                </a>
                            @elseif(!empty($ra['target_date']))
                                <a href="{{ route('calendar.index', ['view' => 'month', 'date' => $ra['target_date']->toDateString(), 'highlight' => 'lpk_jt_ra_' . $lpk->id, 'selected' => 1]) }}"
                                   class="assessment-cal-shortcut"
                                   title="Lihat target RA di kalender">
                                    <x-icon name="calendar" size="13" />
                                    <span>Kalender</span>
                                </a>
                            @endif
                        </div>
                    </div>
                </div>

                @if(auth()->user()?->isAdmin() && !$linkedRA)
                    <div class="lpk-milestone-footer-actions">
                        <a href="{{ route('assessments.create', ['lpk_id' => $lpk->id, 'alert_code' => 'RA', 'target_date' => $ra['target_date']?->format('Y-m-d')]) }}" class="button primary button-sm lpk-primary-action-btn">
                            <x-icon name="plus" size="13" />
                            <span>Jadwalkan Re-asesmen</span>
                        </a>
                    </div>
                @endif
            </div>
        </div>
    </div>

    {{-- 2-Column Responsive Layout --}}
    <div class="lpk-show-grid lpk-detail-main-grid">
        {{-- KOLOM KIRI: Informasi Dasar, Legalitas, Narahubung, Ruang Lingkup & Keterangan --}}
        <div class="lpk-show-col">
            {{-- KARTU 1: Profil & Legalitas Laboratorium --}}
            <div class="lpk-form-card">
                <div class="lpk-form-card-header">
                    <div class="lpk-card-icon-wrap">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <rect width="16" height="20" x="4" y="2" rx="2" ry="2"/>
                            <path d="M9 22v-4h6v4"/>
                            <path d="M8 6h.01"/>
                            <path d="M16 6h.01"/>
                            <path d="M8 10h.01"/>
                            <path d="M16 10h.01"/>
                            <path d="M8 14h.01"/>
                            <path d="M16 14h.01"/>
                        </svg>
                    </div>
                    <div class="lpk-card-header-text">
                        <h2>Profil &amp; Legalitas Laboratorium</h2>
                        <p>Identitas resmi lembaga dan nomor registrasi di Komite Akreditasi Nasional (KAN).</p>
                    </div>
                </div>

                <div class="lpk-meta-grid">
                    <div class="lpk-meta-item">
                        <span class="lpk-meta-label">No Reg LPK (ID KAN)</span>
                        <div class="lpk-meta-value">
                            <strong style="font-family: ui-monospace, SFMono-Regular, monospace; font-size: 13.5px; color: var(--ink);">{{ $lpk->no_reg ?: $lpk->registration_number }}</strong>
                        </div>
                    </div>

                    <div class="lpk-meta-item">
                        <span class="lpk-meta-label">No Akreditasi KAN</span>
                        <div class="lpk-meta-value">
                            @if($lpk->accreditation_number)
                                <strong style="color: var(--ink); font-family: ui-monospace, SFMono-Regular, monospace; font-size: 13.5px;">{{ $lpk->accreditation_number }}</strong>
                            @else
                                <span style="color: var(--muted);">-</span>
                            @endif
                        </div>
                    </div>

                    <div class="lpk-meta-item">
                        <span class="lpk-meta-label">Jenis Akreditasi</span>
                        <div class="lpk-meta-value">
                            {{ $lpk->accreditation_type ?: 'Laboratorium Penguji' }}
                        </div>
                    </div>

                    <div class="lpk-meta-item">
                        <span class="lpk-meta-label">Tanggal Terbit Sertifikat</span>
                        <div class="lpk-meta-value">
                            @if($lpk->certificate_date)
                                <strong>{{ $lpk->certificate_date->format('d M Y') }}</strong>
                            @else
                                <span style="color: var(--muted);">Belum ditentukan</span>
                            @endif
                        </div>
                    </div>

                    <div class="lpk-meta-item full-width">
                        <span class="lpk-meta-label">Masa Berlaku Akreditasi</span>
                        <div class="lpk-meta-value">
                            <strong>{{ $lpk->masa_akreditasi_label }}</strong>
                            @if($lpk->isExpired())
                                <span class="status status-danger" style="margin-left: 6px; font-size: 11px; vertical-align: middle;">Kedaluwarsa</span>
                            @elseif($lpk->isExpiringSoon())
                                <span class="status status-warn" style="margin-left: 6px; font-size: 11px; vertical-align: middle;">Mendekati Kedaluwarsa</span>
                            @endif
                        </div>
                    </div>

                    @if($lpk->drive_url)
                        <div class="lpk-meta-item full-width" style="border-top: 1px dashed var(--line); padding-top: 12px; margin-top: 4px;">
                            <span class="lpk-meta-label">Link Dokumen Google Drive</span>
                            <div class="lpk-meta-value" style="display: flex; align-items: center; gap: 8px;">
                                <a href="{{ $lpk->drive_url }}" target="_blank" rel="noopener noreferrer" class="button button-drive button-xs" style="display: inline-flex; align-items: center; gap: 6px;">
                                    <svg width="13" height="13" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M15 3h6v6"/><path d="M10 14 21 3"/><path d="M18 13v6a2 2 0 0 1-2 2H5a2 2 0 0 1-2-2V8a2 2 0 0 1 2-2h6"/></svg>
                                    <span>Buka Berkas di Google Drive</span>
                                </a>
                                <span style="color: var(--muted); font-size: 12px;">(Sertifikat Akreditasi, Amandemen &amp; Lampiran)</span>
                            </div>
                        </div>
                    @endif
                </div>
            </div>

            {{-- KARTU 2: Narahubung & Alamat Fasilitas --}}
            <div class="lpk-form-card">
                <div class="lpk-form-card-header">
                    <div class="lpk-card-icon-wrap icon-wrap-emerald">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M22 16.92v3a2 2 0 0 1-2.18 2 19.79 19.79 0 0 1-8.63-3.07 19.5 19.5 0 0 1-6-6 19.79 19.79 0 0 1-3.07-8.67A2 2 0 0 1 4.11 2h3a2 2 0 0 1 2 1.72 12.84 12.84 0 0 0 .7 2.81 2 2 0 0 1-.45 2.11L8.09 9.91a16 16 0 0 0 6 6l1.27-1.27a2 2 0 0 1 2.11-.45 12.84 12.84 0 0 0 2.81.7A2 2 0 0 1 22 16.92z"/>
                        </svg>
                    </div>
                    <div class="lpk-card-header-text">
                        <h2>Kontak &amp; Alamat Fasilitas</h2>
                        <p>Informasi penanggung jawab teknis dan domisili fisik fasilitas pengujian.</p>
                    </div>
                </div>

                <div class="lpk-meta-grid">
                    <div class="lpk-meta-item">
                        <span class="lpk-meta-label">PIC Penanggung Jawab</span>
                        <div class="lpk-meta-value">
                            @if($lpk->pic)
                                <strong>{{ $lpk->pic->name }}</strong>
                                <div style="font-size: 12px; color: var(--muted);">{{ $lpk->pic->email }}</div>
                            @else
                                <span style="color: var(--muted);">Belum ditentukan</span>
                            @endif
                        </div>
                    </div>

                    <div class="lpk-meta-item">
                        <span class="lpk-meta-label">Email Resmi Laboratorium</span>
                        <div class="lpk-meta-value">
                            {{ $lpk->email ?: 'Belum diisi' }}
                        </div>
                    </div>

                    <div class="lpk-meta-item full-width">
                        <span class="lpk-meta-label">Nomor Telepon / Hotline</span>
                        <div class="lpk-meta-value">
                            {{ $lpk->phone ?: 'Belum diisi' }}
                        </div>
                    </div>

                    <div class="lpk-meta-item full-width" style="border-top: 1px dashed var(--line); padding-top: 10px;">
                        <span class="lpk-meta-label">Alamat Fasilitas</span>
                        <div class="lpk-meta-value" style="color: var(--ink); line-height: 1.55;">
                            {{ $lpk->address ?: 'Belum diisi' }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- KARTU 3: Ruang Lingkup Akreditasi --}}
            <div class="lpk-form-card">
                <div class="lpk-form-card-header">
                    <div class="lpk-card-icon-wrap icon-wrap-green">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M14 2H6a2 2 0 0 0-2 2v16a2 2 0 0 0 2 2h12a2 2 0 0 0 2-2V8z"/>
                            <polyline points="14 2 14 8 20 8"/>
                            <line x1="16" y1="13" x2="8" y2="13"/>
                            <line x1="16" y1="17" x2="8" y2="17"/>
                            <polyline points="10 9 9 9 8 9"/>
                        </svg>
                    </div>
                    <div class="lpk-card-header-text">
                        <h2>Ruang Lingkup Akreditasi</h2>
                        <p>Bidang pengujian, kalibrasi, inspeksi, atau komoditas yang diakui dalam sertifikat KAN.</p>
                    </div>
                </div>

                <div>
                    @if($lpk->scope)
                        <div class="lpk-scope-box">{{ $lpk->scope }}</div>
                    @else
                        <div style="background: var(--surface-subtle); border: 1px dashed var(--line); border-radius: var(--radius-lg, 12px); padding: 16px; text-align: center; color: var(--muted); font-size: 13px;">
                            Belum ada rincian ruang lingkup akreditasi yang diinput.
                        </div>
                    @endif
                </div>
            </div>

            {{-- KARTU 4: Status Siklus Monitoring & Catatan PIC --}}
            <div class="lpk-form-card">
                <div class="lpk-form-card-header">
                    <div class="lpk-card-icon-wrap icon-wrap-amber">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                            <rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
                        </svg>
                    </div>
                    <div class="lpk-card-header-text">
                        <h2>Status Siklus &amp; Catatan Monitoring</h2>
                        <p>Pemantauan status pengawasan KAN otomatis dan catatan internal pengelola LPK.</p>
                    </div>
                </div>

                <div class="lpk-detail-keterangan-stack">
                    {{-- Status Operasional Otomatis --}}
                    @php
                        $ketLower = strtolower($lpk->dynamic_keterangan);
                        if (str_contains($ketLower, 'dibekukan')) {
                            $theme = 'blue';
                            $catLabel = 'Dibekukan';
                        } elseif (str_contains($ketLower, 'jatuh tempo') || str_contains($ketLower, 'terlampaui') || str_contains($ketLower, 'lewat jadwal') || str_contains($ketLower, 'kedaluwarsa') || str_contains($ketLower, 'dicabut')) {
                            $theme = 'rose';
                            $catLabel = 'Jatuh Tempo';
                        } elseif (str_contains($ketLower, 'segera berakhir') || str_contains($ketLower, 'reminder') || str_contains($ketLower, 'mendekati kedaluwarsa') || str_contains($ketLower, 'masa berlaku berakhir')) {
                            $theme = 'amber';
                            $catLabel = 'Reminder';
                        } elseif (str_contains($ketLower, 'batas tp') || str_contains($ketLower, 'penyusunan tp') || str_contains($ketLower, 'verifikasi tp') || str_contains($ketLower, 'perpanjangan tp')) {
                            $theme = 'cyan';
                            $catLabel = 'Batas TP';
                        } else {
                            $theme = 'emerald';
                            $catLabel = 'Pelaksanaan';
                        }

                        $hasColon = str_contains($lpk->dynamic_keterangan, ':');
                        if ($hasColon) {
                            [$processName, $statusDetail] = explode(':', $lpk->dynamic_keterangan, 2);
                            $processName = trim($processName);
                            $statusDetail = trim($statusDetail);
                        } else {
                            $processName = null;
                            $statusDetail = trim($lpk->dynamic_keterangan);
                        }

                        // Menentukan target tanggal dan highlight untuk pintasan kalender
                        $calTargetDate = null;
                        $calHighlight = null;
                        $activeAss = $lpk->getActiveOrUpcomingAssessment();

                        if ($activeAss) {
                            if ($activeAss->effective_tp_due_date && $activeAss->tp_status && !in_array($activeAss->tp_status, [\App\Models\Assessment::TP_STATUS_NONE, \App\Models\Assessment::TP_STATUS_SATISFIED], true)) {
                                $calTargetDate = $activeAss->effective_tp_due_date->toDateString();
                                $calHighlight = 'tp_' . $activeAss->id;
                            } elseif ($activeAss->start_at) {
                                $calTargetDate = $activeAss->start_at->toDateString();
                                $calHighlight = (string) $activeAss->id;
                            } elseif ($activeAss->end_at) {
                                $calTargetDate = $activeAss->end_at->toDateString();
                                $calHighlight = (string) $activeAss->id;
                            } else {
                                $calTargetDate = now()->toDateString();
                                $calHighlight = (string) $activeAss->id;
                            }
                        } else {
                            $survAlerts = $lpk->getActiveSurveillanceAlerts();
                            if (!empty($survAlerts)) {
                                $firstAlert = reset($survAlerts);
                                $calTargetDate = $firstAlert['target_date']?->toDateString() ?? now()->toDateString();
                                $calHighlight = 'lpk_jt_' . strtolower($firstAlert['code'] ?? 's1') . '_' . $lpk->id;
                            } else {
                                $calTargetDate = $lpk->expired_at?->toDateString() ?? $lpk->certificate_date?->toDateString() ?? now()->toDateString();
                                $calHighlight = null;
                            }
                        }

                        $calParams = ['view' => 'month', 'date' => $calTargetDate, 'selected' => 1];
                        if ($calHighlight) {
                            $calParams['highlight'] = $calHighlight;
                        }
                        $calStatusSiklusUrl = route('calendar.index', $calParams);
                    @endphp

                    <div class="lpk-keterangan-card lpk-keterangan-auto theme-{{ $theme }}">
                        <div class="lpk-keterangan-head">
                            <div class="lpk-keterangan-head-left">
                                <span class="lpk-keterangan-type-label">Status Siklus (Otomatis)</span>
                                @if($processName)
                                    <span class="lpk-keterangan-process-badge theme-{{ $theme }}">{{ $processName }}</span>
                                @endif
                            </div>
                            <div class="lpk-keterangan-head-right">
                                <span class="lpk-keterangan-cat-tag theme-{{ $theme }}">
                                    <span>{{ $catLabel }}</span>
                                </span>
                                <a href="{{ $calStatusSiklusUrl }}"
                                   class="button ghost button-xs lpk-keterangan-cal-btn"
                                   title="Buka langsung agenda siklus ini di kalender">
                                    <x-icon name="calendar" size="13" />
                                    <span>Lihat di Kalender</span>
                                </a>
                            </div>
                        </div>
                        <div class="lpk-keterangan-body">
                            {{ $statusDetail }}
                        </div>
                    </div>

                    {{-- Catatan Manual PIC --}}
                    <div class="lpk-keterangan-card lpk-keterangan-pic {{ $lpk->notes ? 'has-notes' : 'is-empty' }}">
                        <div class="lpk-keterangan-head">
                            <div class="lpk-keterangan-head-left">
                                <span class="lpk-keterangan-type-label">Catatan Khusus PIC</span>
                            </div>
                            @if($lpk->canManage(auth()->user()))
                                <button type="button" class="button secondary button-xs lpk-keterangan-edit-btn" onclick="window.openModal('modal-input-keterangan')">
                                    <x-icon name="edit" size="13" />
                                    <span>{{ $lpk->notes ? 'Ubah Keterangan' : 'Input Keterangan' }}</span>
                                </button>
                            @endif
                        </div>
                        <div class="lpk-keterangan-body {{ $lpk->notes ? '' : 'is-empty' }}">
                            @if($lpk->notes)
                                {!! nl2br(e(trim($lpk->notes))) !!}
                            @else
                                <em>Belum ada keterangan.</em>
                            @endif
                        </div>
                    </div>
                </div>
            </div>
        </div>

        {{-- KOLOM KANAN: Tim Kolaborasi & Program Asesmen Surveilen --}}
        <div class="lpk-show-col">
            {{-- KARTU: Tim PIC Laboratorium (Kolaborasi Antar-PIC) --}}
            <div class="lpk-form-card" id="card-tim-kolaborasi">
                <div class="lpk-form-card-header" style="border-bottom: 1px solid var(--line); padding-bottom: 12px; margin-bottom: 14px;">
                    <div class="lpk-card-icon-wrap icon-wrap-emerald">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M16 21v-2a4 4 0 0 0-4-4H6a4 4 0 0 0-4 4v2"/>
                            <circle cx="9" cy="7" r="4"/>
                            <path d="M22 21v-2a4 4 0 0 0-3-3.87"/>
                            <path d="M16 3.13a4 4 0 0 1 0 7.75"/>
                        </svg>
                    </div>
                    <div class="lpk-card-header-text" style="flex: 1;">
                        <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                            <h2 style="font-size: 15.5px; margin: 0;">Tim PIC Laboratorium</h2>
                        </div>
                    </div>
                </div>

                {{-- Daftar Anggota Tim Kolaborasi --}}
                <div class="lpk-team-list">
                    {{-- 1. Ketua Tim Utama (Pemilik Utama / Penanggung Jawab) --}}
                    <div class="lpk-team-member is-lead">
                        <div class="lpk-member-info">
                            <div class="lpk-member-avatar is-lead">
                                {{ $lpk->pic ? $lpk->pic->initials : 'U' }}
                            </div>
                            <div class="lpk-member-details">
                                <span class="lpk-member-name">
                                    {{ $lpk->pic ? $lpk->pic->name : 'PIC Belum Ditugaskan' }}
                                </span>
                                <span class="lpk-member-email">
                                    {{ $lpk->pic ? $lpk->pic->email : 'Laboratorium Terbuka' }}
                                </span>
                            </div>
                        </div>
                        <span class="badge badge-pic-lead" style="flex-shrink: 0;">
                            {{ $lpk->pic && $lpk->pic->isAdmin() ? 'Ketua Tim' : 'PIC Utama' }}
                        </span>
                    </div>

                    {{-- 2. Anggota PIC Tertaut --}}
                    @forelse($lpk->members as $member)
                        <div class="lpk-team-member">
                            <div class="lpk-member-info">
                                <div class="lpk-member-avatar">
                                    {{ $member->initials }}
                                </div>
                                <div class="lpk-member-details">
                                    <span class="lpk-member-name">
                                        {{ $member->name }}
                                    </span>
                                    <span class="lpk-member-email">
                                        {{ $member->email }}
                                    </span>
                                </div>
                            </div>
                            <div class="lpk-member-actions">
                                @if($member->pivot->role === 'lead')
                                    <span class="badge badge-pic-lead">
                                        {{ $member->isAdmin() ? 'Ketua Tim' : 'PIC Pendamping' }}
                                    </span>
                                @else
                                    <span class="badge badge-pic-viewer">
                                        Viewer
                                    </span>
                                @endif

                                @if($lpk->canManage(auth()->user()))
                                    <form method="POST" action="{{ route('lpks.members.destroy', [$lpk, $member]) }}"
                                          data-confirm-delete
                                          data-confirm-title="Putuskan Akses Tim?"
                                          data-confirm-text="Apakah Anda yakin ingin memutuskan akses PIC {{ $member->name }} dari LPK ini?"
                                          data-confirm-btn="Ya, Putuskan Akses"
                                          style="margin: 0;">
                                        @csrf
                                        @method('DELETE')
                                        <button type="submit" class="button-icon-only text-danger" style="background: none; border: none; padding: 4px; cursor: pointer; color: var(--danger-text, #ef4444); display: inline-flex; align-items: center;" title="Putuskan tautan akun PIC ini">
                                            <x-icon name="trash" size="14" />
                                        </button>
                                    </form>
                                @endif
                            </div>
                        </div>
                    @empty
                        @if(! $lpk->pic)
                            <div style="font-size: 12px; color: var(--muted); padding: 10px 12px;">
                                Belum ada penanggung jawab khusus untuk LPK ini.
                            </div>
                        @endif
                    @endforelse

                    {{-- 3. Viewer dari Tautan Akun PIC --}}
                    @if($lpk->pic && $lpk->pic->linkedViewers && $lpk->pic->linkedViewers->isNotEmpty())
                        @php
                            $memberIds = $lpk->members->pluck('id')->all();
                            $accountViewers = $lpk->pic->linkedViewers->reject(fn ($v) => in_array($v->id, $memberIds));
                        @endphp
                        @foreach($accountViewers as $viewer)
                            <div class="lpk-team-member">
                                <div class="lpk-member-info">
                                    <div class="lpk-member-avatar is-viewer">
                                        {{ $viewer->initials }}
                                    </div>
                                    <div class="lpk-member-details">
                                        <span class="lpk-member-name">
                                            {{ $viewer->name }}
                                        </span>
                                        <span class="lpk-member-email">
                                            {{ $viewer->email }}
                                        </span>
                                    </div>
                                </div>
                                <div class="lpk-member-actions">
                                    <span class="badge badge-viewer-linked" title="Memiliki akses lihat ke seluruh LPK akun ini">
                                        Viewer (Akses Akun)
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    @endif
                </div>

                {{-- Form Tautkan PIC Baru (Hanya tampil untuk Ketua Tim atau Admin) --}}
                @if($lpk->canManage(auth()->user()))
                    @php
                        $linkedUserIds = $lpk->members->pluck('id')->all();
                        if ($lpk->pic_id) {
                            $linkedUserIds[] = $lpk->pic_id;
                        }
                        $availablePics = \App\Models\User::where('role', \App\Models\User::ROLE_PIC)
                            ->whereNotIn('id', $linkedUserIds)
                            ->orderBy('name')
                            ->get();
                    @endphp

                    @if($availablePics->isNotEmpty())
                        <form method="POST" action="{{ route('lpks.members.store', $lpk) }}" style="border-top: 1px dashed var(--line); padding-top: 12px; margin-top: 4px;">
                            @csrf
                            <label for="select-link-pic" style="display: block; font-size: 12px; font-weight: 600; color: var(--ink); margin-bottom: 6px;">
                                Tautkan Akun PIC Lain (Per-LPK)
                            </label>
                            <div style="display: flex; gap: 6px; flex-wrap: wrap;">
                                <select id="select-link-pic" name="user_id" required style="flex: 1; min-width: 150px; font-size: 12px; padding: 6px 10px; border: 1px solid var(--input-border, var(--line)); border-radius: var(--radius-lg, 12px); background: var(--input-bg, #ffffff); color: var(--ink);">
                                    <option value="">-- Pilih PIC --</option>
                                    @foreach($availablePics as $ap)
                                        <option value="{{ $ap->id }}">{{ $ap->name }} ({{ $ap->email }})</option>
                                    @endforeach
                                </select>
                                <select name="role" style="font-size: 12px; padding: 6px 8px; border: 1px solid var(--input-border, var(--line)); border-radius: var(--radius-lg, 12px); background: var(--input-bg, #ffffff); color: var(--ink);">
                                    <option value="viewer" selected>Viewer</option>
                                    <option value="lead">PIC Pendamping</option>
                                </select>
                                <button type="submit" class="button secondary button-xs" style="font-size: 12px; padding: 6px 12px; font-weight: 600; white-space: nowrap;">
                                    + Tautkan
                                </button>
                            </div>
                            <span style="display: block; font-size: 11px; color: var(--muted); margin-top: 6px;">
                                PIC berstatus <em>Viewer</em> hanya dapat melihat data LPK dan agenda terkait.
                            </span>
                        </form>
                    @endif

                    <div style="border-top: 1px dashed var(--line); padding-top: 10px; margin-top: 10px; display: flex; justify-content: space-between; align-items: center; font-size: 11.5px;">
                        <span style="color: var(--muted);">Tautan Akun Kolaborasi (Seluruh LPK)</span>
                        <a href="{{ route('account-links.index') }}" style="color: var(--primary); font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                            <span>Kelola Tautan Akun</span>
                            <x-icon name="arrow-right" size="12" />
                        </a>
                    </div>
                @endif
            </div>

            {{-- KARTU: Program Asesmen Surveilen --}}
            <div class="lpk-assessment-card-wrapper">
                <div class="lpk-form-card lpk-assessment-program-card">
                    <div class="lpk-form-card-header" style="border-bottom: 1px solid var(--line); padding-bottom: 12px; margin-bottom: 14px; flex-shrink: 0;">
                        <div class="lpk-card-icon-wrap icon-wrap-blue">
                            <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                <rect width="18" height="18" x="3" y="4" rx="2" ry="2"/>
                                <line x1="16" y1="2" x2="16" y2="6"/>
                                <line x1="8" y1="2" x2="8" y2="6"/>
                                <line x1="3" y1="10" x2="21" y2="10"/>
                            </svg>
                        </div>
                        <div class="lpk-card-header-text" style="flex: 1;">
                            <div style="display: flex; justify-content: space-between; align-items: center; flex-wrap: wrap; gap: 8px;">
                                <h2 style="font-size: 15.5px; margin: 0; color: var(--ink);">Daftar Asesmen Surveilen</h2>
                                @if(isset($owner))
                                    <a href="{{ route('account-links.show', [$owner, 'tab' => 'assessments', 'search' => $lpk->registration_number]) }}" style="font-size: 12px; font-weight: 600; color: var(--primary); text-decoration: none; display: inline-flex; align-items: center; gap: 3px;">
                                        <span>Semua asesmen</span>
                                        <x-icon name="chevron-right" size="13" />
                                    </a>
                                @else
                                    <a href="{{ route('assessments.index', ['lpk_id' => $lpk->id]) }}" style="font-size: 12px; font-weight: 600; color: var(--primary); text-decoration: none; display: inline-flex; align-items: center; gap: 3px;">
                                        <span>Semua asesmen</span>
                                        <x-icon name="chevron-right" size="13" />
                                    </a>
                                @endif
                            </div>
                        </div>
                    </div>

                    <div class="lpk-assessment-scroll-list">
                        @forelse($lpk->assessments->sortBy('start_at') as $item)
                            <div class="assessment-list-row clickable-row" data-href="{{ isset($owner) ? route('account-links.assessments.show', [$owner, $item]) : route('assessments.show', $item) }}" tabindex="0" role="link" aria-label="{{ $item->display_title }}" style="background: var(--surface); border: 1px solid var(--line); border-radius: var(--radius-lg, 12px); padding: 12px 14px; margin: 0; display: flex; flex-direction: column; gap: 8px; transition: border-color 140ms ease, box-shadow 140ms ease; flex-shrink: 0; width: 100%; min-width: 0; box-sizing: border-box;">
                                <div class="assessment-list-content" style="min-width: 0; overflow-wrap: break-word; word-break: break-word;">
                                    <a href="{{ isset($owner) ? route('account-links.assessments.show', [$owner, $item]) : route('assessments.show', $item) }}" class="assessment-list-title" style="font-size: 13.5px; font-weight: 600; color: var(--ink); text-decoration: none; overflow-wrap: break-word; word-break: break-word;">
                                        {{ $item->display_title }}
                                    </a>
                                    <div class="assessment-list-meta" style="font-size: 12px; color: var(--muted); margin-top: 3px; overflow-wrap: break-word; word-break: break-word;">
                                        <span>{{ $lpk->registration_number }} &bull; {{ $item->assessment_type_label }} &bull; {{ $item->start_at->format('d M Y') }}</span>
                                        @if($item->sk_number)
                                            <div class="assessment-list-sk" style="margin-top: 4px; font-size: 11.5px; color: var(--muted); overflow-wrap: break-word; word-break: break-word;">
                                                SK KAN: {{ $item->sk_number }} @if($item->sk_date)({{ $item->sk_date->format('d/m/Y') }})@endif
                                                @if($item->sk_lead_time_days !== null)
                                                    &bull; <span style="font-weight: 600; color: var(--green, #10b981);">Durasi: {{ $item->sk_lead_time_days }} hari</span>
                                                @endif
                                            </div>
                                        @endif
                                    </div>
                                </div>
                                <div class="assessment-list-actions" style="display: flex; justify-content: space-between; align-items: center; border-top: 1px dashed var(--line); padding-top: 8px; margin-top: 2px; flex-wrap: wrap; gap: 8px; min-width: 0;">
                                    <div class="assessment-list-badges" style="display: flex; align-items: center; gap: 6px;">
                                        <x-status :value="$item->status" />
                                        @if($item->is_submission_overdue)
                                            <span class="badge-tp badge-tp-danger" style="font-size: 10px;" title="Toleransi pengisian asesmen telah terlampaui">Lewat Toleransi</span>
                                        @endif
                                    </div>
                                    <a href="{{ route('calendar.index', ['view' => 'month', 'date' => $item->start_at->toDateString(), 'highlight' => $item->id, 'selected' => 1]) }}"
                                       class="assessment-cal-shortcut"
                                       style="height: 24px; padding: 2px 8px; font-size: 11px; display: inline-flex; align-items: center; gap: 4px;"
                                       title="Lihat agenda ini di kalender">
                                       <x-icon name="calendar" size="12" />
                                       <span>Kalender</span>
                                    </a>
                                </div>
                            </div>
                        @empty
                            <div class="empty" style="text-align: center; padding: 24px 16px; color: var(--muted); font-size: 13px; background: var(--surface-subtle); border: 1px dashed var(--line); border-radius: var(--radius-lg, 12px);">
                                Belum ada agenda asesmen untuk LPK ini.
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>
</div>

@if($lpk->canManage(auth()->user()))
{{-- Modal: Input / Ubah Keterangan LPK --}}
<div class="simasadi-modal" id="modal-input-keterangan" role="dialog" aria-modal="true" aria-labelledby="modal-input-keterangan-title">
    <div class="simasadi-modal-box" style="max-width: 540px;">
        <div class="simasadi-modal-head">
            <h4 id="modal-input-keterangan-title">{{ $lpk->notes ? 'Ubah Keterangan LPK' : 'Input Keterangan LPK' }}</h4>
            <button type="button" class="simasadi-modal-close" data-modal-close onclick="window.closeModal('modal-input-keterangan')" aria-label="Tutup modal">&times;</button>
        </div>
        <form method="POST" action="{{ route('lpks.notes.update', $lpk) }}">
            @csrf
            <div style="display: grid; gap: 14px;">
                <p style="font-size: 13px; color: var(--muted); margin: 0;">
                    Keterangan atau catatan monitoring internal untuk <strong>{{ $lpk->name }}</strong> (No Reg: {{ $lpk->no_reg ?: $lpk->registration_number }}).
                </p>

                <div style="background: var(--surface-subtle, #f8fafc); border: 1px solid var(--line); border-radius: var(--radius-md, 10px); padding: 10px 12px;">
                    <div style="display: flex; justify-content: space-between; align-items: center; gap: 8px; margin-bottom: 6px; flex-wrap: wrap;">
                        <span style="font-size: 11px; font-weight: 700; color: var(--muted); text-transform: uppercase;">
                            Keterangan Otomatis:
                        </span>
                        <button type="button" class="button secondary" style="font-size: 11px; padding: 2px 8px; min-height: 24px;" onclick="document.getElementById('input-notes-textarea').value = {{ json_encode($lpk->dynamic_keterangan) }};">
                            Gunakan Keterangan Otomatis
                        </button>
                    </div>
                    <div style="font-size: 13px; color: var(--ink); line-height: 1.45;">
                        @if(str_contains($lpk->dynamic_keterangan, ':'))
                            @php
                                [$processName, $statusDetail] = explode(':', $lpk->dynamic_keterangan, 2);
                            @endphp
                            <strong style="color: var(--ink); font-weight: 700;">{{ $processName }}:</strong><span style="color: var(--ink); font-weight: 500;">{{ $statusDetail }}</span>
                        @else
                            <strong style="color: var(--ink); font-weight: 700;">{{ $lpk->dynamic_keterangan }}</strong>
                        @endif
                    </div>
                </div>

                <label>
                    <span style="font-size: 13px; font-weight: 600; display: block; margin-bottom: 6px;">Catatan Khusus PIC (Opsional)</span>
                    <textarea id="input-notes-textarea" name="notes" rows="4" placeholder="Masukkan catatan tambahan PIC jika ada..." style="width: 100%; padding: 10px 12px; border: 1px solid var(--line); border-radius: var(--radius-lg, 12px); font-family: inherit; font-size: 13.5px; line-height: 1.5; resize: vertical;">{{ old('notes', $lpk->notes) }}</textarea>
                    <small style="color: var(--muted); font-size: 11.5px; display: block; margin-top: 4px;">Catatan manual akan selalu ditampilkan berdampingan dengan status otomatis sistem.</small>
                </label>
            </div>
            <div class="modal-form-actions">
                <button type="button" class="button ghost" data-modal-close onclick="window.closeModal('modal-input-keterangan')">Batal</button>
                <button type="submit" class="button primary">
                    <span>Simpan Keterangan</span>
                </button>
            </div>
        </form>
    </div>
</div>
@endif
