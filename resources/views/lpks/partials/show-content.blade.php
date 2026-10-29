<div class="lpk-show-container">
    {{-- Navigasi Breadcrumb Kembali (Notion Style) --}}
    <div class="lpk-header-back-wrap">
        @if(isset($owner))
            <a href="{{ route('account-links.show', [$owner, 'tab' => 'lpks']) }}" class="lpk-back-btn" title="Kembali ke Daftar Laboratorium Akun Tertaut">
                <x-icon name="chevron-left" size="16" />
                <span>Daftar LPK Akun: {{ $owner->name }}</span>
            </a>
        @elseif(auth()->check() && $lpk->pic_id && auth()->user()->isViewerFor($lpk->pic_id))
            <a href="{{ route('account-links.show', $lpk->pic_id) }}" class="lpk-back-btn" title="Kembali ke Daftar Laboratorium Akun Tertaut">
                <x-icon name="chevron-left" size="16" />
                <span>Laboratorium Akun Tertaut ({{ $lpk->pic?->name }})</span>
            </a>
        @else
            <a href="{{ route('lpks.index') }}" class="lpk-back-btn" title="Kembali ke Daftar Laboratorium">
                <x-icon name="chevron-left" size="16" />
                <span>Daftar Laboratorium</span>
            </a>
        @endif
    </div>

    {{-- Header Card dengan Nama LPK, Identitas & Tombol Aksi --}}
    <div class="lpk-show-header">
        <div class="lpk-header-row">
            <div class="lpk-header-title-group">
                @php
                    $nameParts = preg_split('/\s+[-–—]\s+/u', $lpk->name);
                    $hasSubName = count($nameParts) > 1;
                    $mainName = $hasSubName ? end($nameParts) : $lpk->name;
                    $orgContext = $hasSubName ? implode(' - ', array_slice($nameParts, 0, -1)) : null;
                @endphp
                <h1 class="lpk-title">{{ $mainName }}</h1>
                @if($orgContext)
                    <p class="lpk-org-context">{{ $orgContext }}</p>
                @endif

                <div class="lpk-header-badges">
                    @if($lpk->no_reg)
                        <span class="lpk-badge-reg">No Reg: {{ $lpk->no_reg }}</span>
                    @endif
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
                        <span class="badge badge-pic-viewer">
                            Mode Pemantauan (Viewer) - Milik Akun: {{ $owner->name }}
                        </span>
                    @elseif(auth()->check() && $lpk->pic_id && auth()->user()->isViewerFor($lpk->pic_id))
                        <span class="badge badge-pic-viewer">
                            Akun Tertaut: {{ $lpk->pic?->name }} (Viewer)
                        </span>
                    @elseif(auth()->check() && $lpk->isViewerPic(auth()->user()))
                        <span class="badge badge-pic-viewer">
                            Akses: Viewer (Hanya Lihat)
                        </span>
                    @endif
                </div>
            </div>

            @if(!isset($owner) && (auth()->user()?->isAdmin() || (auth()->user()?->isPic() && $lpk->canManage(auth()->user()))))
                <div class="lpk-header-actions">
                    <a class="button secondary button-sm" href="{{ route('lpks.edit', $lpk) }}">
                        <x-icon name="edit" size="14" />
                        <span>Ubah data</span>
                    </a>
                    @if($lpk->canDelete(auth()->user()))
                        <form method="POST" action="{{ route('lpks.destroy', $lpk) }}" class="form-delete-lpk" data-lpk-name="{{ $lpk->name }}" data-lpk-reg="{{ $lpk->registration_number }}" style="margin: 0; display: inline-block;">
                            @csrf
                            @method('DELETE')
                            <button type="submit" class="button ghost button-sm lpk-delete-btn" title="Hapus LPK ini dari sistem">
                                <x-icon name="trash" size="14" />
                                <span>Hapus</span>
                            </button>
                        </form>
                    @endif
                </div>
            @endif
        </div>
    </div>

    @php
        $canManageLpk = !isset($owner) && $lpk->canManage(auth()->user());
        $milestones = $lpk->surveillance_milestones;
        $activeAlerts = $lpk->getActiveSurveillanceAlerts();
        $s1 = $milestones['s1'];
        $s2 = $milestones['s2'];
        $ra = $milestones['ra'];
        $cycleEnd = $lpk->expired_at ?: ($lpk->certificate_date ? $lpk->certificate_date->copy()->addYears(5) : null);
        $cycleStart = $lpk->certificate_date ?: ($cycleEnd ? $cycleEnd->copy()->subYears(5) : null);

        $linkedS1List = $lpk->assessments->filter(function ($a) use ($s1, $cycleStart) {
            $inCycle = ! $cycleStart || ($a->start_at && $a->start_at->gte($cycleStart->copy()->subMonths(2)) && $a->start_at->lte($cycleStart->copy()->addMonths(26)));

            return $inCycle && (
                str_contains(strtolower($a->title), 's1')
                || str_contains(strtolower($a->assessment_type), 'surveilen 1')
                || $a->assessment_type === \App\Models\Assessment::TYPE_SURVEILEN_1
                || (str_contains(strtolower($a->assessment_type), 'survei') && $a->start_at && $s1['target_date'] && abs($a->start_at->diffInMonths($s1['target_date'])) <= 6)
            );
        })->values();
        $linkedS1 = $linkedS1List->first();

        $linkedS1Ids = $linkedS1List->pluck('id')->all();

        $linkedS2List = $lpk->assessments->filter(function ($a) use ($s2, $cycleStart, $linkedS1Ids) {
            $inCycle = ! $cycleStart || ($a->start_at && $a->start_at->gte($cycleStart->copy()->addMonths(24)) && $a->start_at->lte($cycleStart->copy()->addMonths(46)));

            return $inCycle
                && ! in_array($a->id, $linkedS1Ids, true)
                && (
                    str_contains(strtolower($a->title), 's2')
                    || str_contains(strtolower($a->assessment_type), 'surveilen 2')
                    || $a->assessment_type === \App\Models\Assessment::TYPE_SURVEILEN_2
                    || (str_contains(strtolower($a->assessment_type), 'survei') && $a->start_at && $s2['target_date'] && abs($a->start_at->diffInMonths($s2['target_date'])) <= 6)
                );
        })->values();
        $linkedS2 = $linkedS2List->first();

        $linkedS2Ids = $linkedS2List->pluck('id')->all();
        $excludedFromRa = array_merge($linkedS1Ids, $linkedS2Ids);

        $linkedRAList = $lpk->assessments->filter(function ($a) use ($ra, $cycleStart, $cycleEnd, $excludedFromRa) {
            $inCycle = ! $cycleStart || ($a->start_at && $a->start_at->gte($cycleStart->copy()->addMonths(42)) && (! $cycleEnd || $a->start_at->lte($cycleEnd->copy()->addMonths(6))));

            return $inCycle
                && ! in_array($a->id, $excludedFromRa, true)
                && (
                    str_contains(strtolower($a->title), 're-akreditasi')
                    || str_contains(strtolower($a->title), 'reakreditasi')
                    || str_contains(strtolower($a->title), '(ra)')
                    || str_contains(strtolower($a->title), ' ra ')
                    || in_array($a->assessment_type, [\App\Models\Assessment::TYPE_RE_AKREDITASI, 'Re-Akreditasi', 'Re-asesmen', 'REASSESSMENT'], true)
                    || ($ra['target_date'] && $a->start_at && abs($a->start_at->diffInMonths($ra['target_date'])) <= 6)
                );
        })->values();
        $linkedRA = $linkedRAList->first();

        $resolveMilestoneCardState = function (?App\Models\Assessment $linked, array $milestone, string $typeCode) use ($lpk): array {
            // 1. DIBEKUKAN (Biru) - Cek pembekuan asesmen atau milestone siklus
            if (($linked && $linked->status === 'SUSPENDED') 
                || ($milestone['status'] ?? null) === 'SUSPENDED'
                || (($milestone['status'] ?? null) === 'OVERDUE' && $lpk->dynamic_status === 'SUSPENDED')) {
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

            // 3. PELAKSANAAN RESMI KAN (Selesai, Sedang Berlangsung, Terjadwal, maupun Direncanakan)
            if ($linked) {
                if ($linked->status === 'REVOKED') {
                    return [
                        'card_class' => 'is-jt is-overdue',
                        'badge_class' => 'status-jt status-danger',
                        'badge_label' => 'Dicabut',
                    ];
                }
                if ($linked->status === 'COMPLETED') {
                    return [
                        'card_class' => 'is-completed',
                        'badge_class' => 'status-completed',
                        'badge_label' => 'Selesai',
                    ];
                }
                if ($linked->status === 'IN_PROGRESS') {
                    return [
                        'card_class' => 'is-in-progress',
                        'badge_class' => 'status-in_progress status-warn',
                        'badge_label' => $linked->is_eha_needs_verification ? 'Perlu Verifikasi Panitia Teknis' : ($linked->is_eha_special_notes ? 'Catatan Khusus Panitia Teknis' : ($linked->is_waiting_sk ? 'Menunggu SK KAN' : ($linked->is_waiting_eha ? 'Menunggu Rapat Panitia Teknis' : 'Sedang Berlangsung'))),
                    ];
                }
                if ($linked->status === 'SCHEDULED') {
                    if ($linked->start_at && $linked->start_at->isPast()) {
                        return [
                            'card_class' => 'is-jt is-overdue',
                            'badge_class' => 'status-jt status-danger',
                            'badge_label' => 'Lewat Jatuh Tempo',
                        ];
                    }
                    return [
                        'card_class' => 'is-scheduled',
                        'badge_class' => 'status-scheduled',
                        'badge_label' => 'Terjadwal',
                    ];
                }
                if ($linked->status === 'PLANNED') {
                    if ($typeCode === 'RA' && (($milestone['status'] ?? null) === 'REVOKED' || $lpk->isRevocationOverdue() || $lpk->dynamic_status === 'REVOKED')) {
                        return [
                            'card_class' => 'is-jt is-overdue',
                            'badge_class' => 'status-jt status-danger',
                            'badge_label' => 'Dicabut',
                        ];
                    }
                    if (($milestone['status'] ?? null) === 'OVERDUE') {
                        return [
                            'card_class' => 'is-jt is-overdue',
                            'badge_class' => 'status-jt status-danger',
                            'badge_label' => 'Lewat Jatuh Tempo',
                        ];
                    }
                    if (($milestone['status'] ?? null) === 'EXPIRED') {
                        return [
                            'card_class' => 'is-jt is-overdue',
                            'badge_class' => 'status-jt status-danger',
                            'badge_label' => 'Kedaluwarsa',
                        ];
                    }
                    if (($milestone['status'] ?? null) === 'DUE') {
                        $lbl = match ($typeCode) {
                            'S1' => 'Reminder S1',
                            'S2' => 'Reminder S2',
                            default => 'Reminder RA',
                        };
                        return [
                            'card_class' => 'is-reminder is-due',
                            'badge_class' => 'status-reminder status-warn',
                            'badge_label' => $lbl,
                        ];
                    }

                    return [
                        'card_class' => 'is-upcoming',
                        'badge_class' => 'status-planned',
                        'badge_label' => 'Akan Datang',
                    ];
                }
            }

            // 4. JT S1 - RA (Merah) - Jatuh tempo, lewat jadwal, atau dicabut (tanpa agenda asesmen terkait)
            if ($typeCode === 'RA' && (($milestone['status'] ?? null) === 'REVOKED' || $lpk->isRevocationOverdue() || $lpk->dynamic_status === 'REVOKED')) {
                return [
                    'card_class' => 'is-jt is-overdue',
                    'badge_class' => 'status-jt status-danger',
                    'badge_label' => 'Dicabut',
                ];
            }
            if (($milestone['status'] ?? null) === 'OVERDUE') {
                return [
                    'card_class' => 'is-jt is-overdue',
                    'badge_class' => 'status-jt status-danger',
                    'badge_label' => 'Lewat Jatuh Tempo',
                ];
            }
            if (($milestone['status'] ?? null) === 'EXPIRED') {
                return [
                    'card_class' => 'is-jt is-overdue',
                    'badge_class' => 'status-jt status-danger',
                    'badge_label' => 'Kedaluwarsa',
                ];
            }

            if (($milestone['status'] ?? null) === 'COMPLETED_OR_SCHEDULED') {
                return [
                    'card_class' => 'is-completed',
                    'badge_class' => 'status-completed',
                    'badge_label' => 'Terealisasi',
                ];
            }

            // 5. REMINDER S1 - RA (Kuning)
            if (($milestone['status'] ?? null) === 'DUE') {
                $lbl = match ($typeCode) {
                    'S1' => 'Reminder S1',
                    'S2' => 'Reminder S2',
                    default => 'Reminder RA',
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

        $isRevoked = $lpk->isRevocationOverdue() || $lpk->dynamic_status === 'REVOKED';

        $currentFocusCode = null;
        if (! $isMilestoneResolved($raCardState) && ($lpk->isInGracePeriod() || $lpk->hasReaccreditationInFlight() || $linkedRA || ($ra['notice_date'] && now()->gte($ra['notice_date'])))) {
            $currentFocusCode = 'RA';
        } elseif (! $isMilestoneResolved($s2CardState) && ($linkedS2 || ($s2['notice_date'] && now()->gte($s2['notice_date'])))) {
            $currentFocusCode = 'S2';
        } elseif (! $isMilestoneResolved($s1CardState)) {
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
    @elseif($isRevoked)
        <div class="lpk-alert-callout is-revoked">
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
                    <a href="{{ route('lpks.edit', $lpk) }}" class="button secondary button-sm lpk-alert-action-btn" title="Perbarui data dan status laboratorium">
                        <x-icon name="edit" size="14" />
                        <span>Perbarui Data LPK</span>
                    </a>
                </div>
            @endif
        </div>
    @endif

    @php
        $filteredActiveAlerts = collect($activeAlerts)->filter(function ($a) use ($lpk, $isRevoked) {
            // Jika LPK dicabut, pengawasan aktif tidak lagi berlaku (sudah ditangani banner pencabutan di atas)
            if ($isRevoked) {
                return false;
            }
            // Jika LPK sedang dalam masa tenggang, alert RA sudah ditangani secara khusus oleh banner oranye di atas
            if ($lpk->isInGracePeriod() && strtoupper($a['code'] ?? '') === 'RA') {
                return false;
            }
            return true;
        })->values()->all();
    @endphp

    {{-- Alert Persisten Pengawasan KAN --}}
    @if(!empty($filteredActiveAlerts))
        @php
            $isSuspended = $lpk->dynamic_status === 'SUSPENDED';
            $isOverdueSurv = $lpk->dynamic_status === 'SURVEILLANCE_OVERDUE';
            $multiAuditAlert = $lpk->isMultilocation() ? $lpk->multilocationAuditSummary() : null;
        @endphp
        <div class="lpk-alert-callout {{ $isSuspended ? 'is-suspended' : ($isOverdueSurv ? 'is-warning is-overdue' : 'is-due') }}">
            <div class="lpk-alert-callout-content">
                <div class="lpk-alert-callout-head">
                    <x-icon name="{{ $isSuspended ? 'alert-circle' : 'alert-triangle' }}" size="18" class="lpk-alert-callout-icon" />
                    <strong>{{ $isSuspended ? 'Peringatan: Status Akreditasi Laboratorium Dibekukan' : ($isOverdueSurv ? 'Peringatan Kritis: LPK Lewat Jatuh Tempo Pengawasan KAN (Terancam Dibekukan)' : 'Peringatan Persisten: LPK Memasuki Masa Jatuh Tempo Pengawasan KAN') }}</strong>
                </div>
                <p class="lpk-alert-callout-desc">
                    @if($isSuspended)
                        Masa toleransi pengawasan surveilen KAN telah terlampaui untuk
                        @foreach($filteredActiveAlerts as $a)
                            @php
                                $targetDateFormatted = !empty($a['target_date']) ? \Illuminate\Support\Carbon::parse($a['target_date'])->format('d M Y') : null;
                                $cleanAlertName = str_replace([' (Akreditasi Ulang)', ' (RA)'], '', $a['name']);
                            @endphp
                            <span class="lpk-alert-tag">{{ $cleanAlertName }}{{ $targetDateFormatted ? " (Batas: {$targetDateFormatted})" : '' }}</span>@if(!$loop->last), @endif
                        @endforeach
                        sesuai regulasi KAN U-01. Segera jadwalkan agenda kunjungan asesmen untuk memulihkan status akreditasi aktif.
                    @elseif($isOverdueSurv)
                        Batas waktu standar pengawasan KAN telah terlampaui untuk
                        @foreach($filteredActiveAlerts as $a)
                            @php
                                $targetDateFormatted = !empty($a['target_date']) ? \Illuminate\Support\Carbon::parse($a['target_date'])->format('d M Y') : null;
                                $cleanAlertName = str_replace([' (Akreditasi Ulang)', ' (RA)'], '', $a['name']);
                            @endphp
                            <span class="lpk-alert-tag">{{ $cleanAlertName }}{{ $targetDateFormatted ? " (Jatuh Tempo: {$targetDateFormatted})" : '' }}</span>@if(!$loop->last), @endif
                        @endforeach.
                        @php
                            $firstTolerance = collect($filteredActiveAlerts)->first()['tolerance_date'] ?? null;
                            $toleranceFormatted = $firstTolerance ? \Illuminate\Support\Carbon::parse($firstTolerance)->format('d M Y') : null;
                        @endphp
                        Segera jadwalkan agenda kunjungan asesmen sebelum batas toleransi maksimal berakhir{{ $toleranceFormatted ? " ({$toleranceFormatted})" : '' }} untuk mencegah sanksi Pembekuan Akreditasi KAN.
                    @else
                        Masa pengawasan aktif untuk 
                        @foreach($filteredActiveAlerts as $a)
                            @php
                                $cleanAlertName = str_replace([' (Akreditasi Ulang)', ' (RA)'], '', $a['name']);
                            @endphp
                            <span class="lpk-alert-tag">{{ $cleanAlertName }}</span>@if(!$loop->last), @endif
                        @endforeach. Segera jadwalkan agenda kunjungan asesmen sebelum batas waktu berakhir.
                    @endif
                </p>

                {{-- Catatan Khusus Kepatuhan Multilokasi KAN U-01 --}}
                @if($multiAuditAlert && $multiAuditAlert['unassessed'] > 0)
                    @php
                        $unassessedNames = $multiAuditAlert['unassessed_locations']->pluck('name')->all();
                        $unassessedSummaryText = count($unassessedNames) <= 2
                            ? implode(', ', $unassessedNames)
                            : count($unassessedNames) . ' cabang (' . implode(', ', array_slice($unassessedNames, 0, 2)) . ', dll.)';
                    @endphp
                    <div class="lpk-multisite-alert-strip">
                        <strong>Perhatian Multilokasi:</strong>
                        Terdapat <strong>{{ $multiAuditAlert['unassessed'] }} dari {{ $multiAuditAlert['total'] }} cabang</strong> ({{ $unassessedSummaryText }}) yang belum pernah diasesmen pada siklus 5 tahun ini. Pastikan seluruh cabang telah terakomodasi sebelum masa berlaku akreditasi berakhir.
                    </div>
                @endif

                @if($lpk->last_surveillance_notified_at)
                    <div class="lpk-alert-callout-meta">
                        <x-icon name="mail" size="13" />
                        <span>Email pengingat terakhir dikirim ke PIC Lab: <strong>{{ $lpk->last_surveillance_notified_at->format('d M Y') }}</strong></span>
                    </div>
                @endif
            </div>
            <div class="lpk-alert-callout-actions">
                @if($lpk->canManage(auth()->user()))
                    @foreach($filteredActiveAlerts as $a)
                        @php
                            $alertCode = strtoupper($a['code'] ?? 'S1');
                            $alertTarget = ($a['visit_target_date'] ?? $a['target_date'])?->format('Y-m-d');
                            $cleanAlertName = str_replace([' (Akreditasi Ulang)', ' (RA)'], '', $a['name']);
                            $linkedList = match($alertCode) {
                                'S1' => $linkedS1List,
                                'S2' => $linkedS2List,
                                'RA' => $linkedRAList,
                                default => collect(),
                            };
                        @endphp
                        @if($linkedList->isNotEmpty())
                            @foreach($linkedList as $asLinkItem)
                                @php
                                    $locSuffix = ($lpk->isMultilocation() && ($asLinkItem->lpkLocation || $asLinkItem->location))
                                        ? ' (' . ($asLinkItem->lpkLocation?->name ?? $asLinkItem->location) . ')'
                                        : '';
                                @endphp
                                @if($asLinkItem->start_at)
                                    <a href="{{ route('assessments.show', $asLinkItem) }}" class="button primary button-sm lpk-alert-action-btn" title="Buka detail agenda asesmen {{ $cleanAlertName }}{{ $locSuffix }}">
                                        <x-icon name="calendar" size="14" />
                                        <span>Buka {{ $cleanAlertName }}{{ $locSuffix }}</span>
                                    </a>
                                @else
                                    <a href="{{ route('assessments.edit', $asLinkItem) }}" class="button primary button-sm lpk-alert-action-btn" title="Lengkapi jadwal kunjungan asesmen {{ $cleanAlertName }}{{ $locSuffix }}">
                                        <x-icon name="calendar" size="14" />
                                        <span>Input Tanggal {{ $cleanAlertName }}{{ $locSuffix }}</span>
                                    </a>
                                @endif
                            @endforeach

                            {{-- Jika masih ada cabang yang belum pernah diasesmen, sediakan tombol jadwalkan cabang --}}
                            @if($multiAuditAlert && $multiAuditAlert['unassessed'] > 0)
                                @php
                                    $firstUnassessedLoc = $multiAuditAlert['unassessed_locations']->first();
                                    $targetLocId = $firstUnassessedLoc?->id;
                                    $targetLocName = $firstUnassessedLoc?->name;
                                @endphp
                                <a href="{{ route('assessments.create', array_filter(['lpk_id' => $lpk->id, 'alert_code' => $alertCode, 'target_date' => $alertTarget, 'lpk_location_id' => $targetLocId])) }}" class="button secondary button-sm lpk-alert-action-btn" title="Jadwalkan agenda kunjungan untuk cabang {{ $targetLocName ?? 'lain' }} yang belum diasesmen">
                                    <x-icon name="plus" size="14" />
                                    <span>Jadwalkan {{ $targetLocName ?? 'Cabang Lain' }}</span>
                                </a>
                            @endif
                        @else
                            <a href="{{ route('assessments.create', ['lpk_id' => $lpk->id, 'alert_code' => $alertCode, 'target_date' => $alertTarget]) }}" class="button primary button-sm lpk-alert-action-btn" title="Jadwalkan agenda kunjungan asesmen {{ $cleanAlertName }} sekarang">
                                <x-icon name="plus" size="14" />
                                <span>Jadwalkan {{ $cleanAlertName }}</span>
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
                    <div style="display: flex; flex-direction: column; gap: 5px;">
                        <h2 style="font-size: 16px; margin: 0; display: flex; align-items: baseline; gap: 8px; flex-wrap: wrap;">
                            <span>Siklus Pengawasan &amp; Re-Akreditasi</span>
                            @if($cycleStart && $cycleEnd)
                                <span style="font-size: 13px; font-weight: 500; color: var(--muted);">(Periode {{ $cycleStart->format('Y') }} - {{ $cycleEnd->format('Y') }})</span>
                            @endif
                        </h2>
                        @php
                            $focusLabel = match($currentFocusCode) {
                                'S1' => 'Surveilen 1',
                                'S2' => 'Surveilen 2',
                                default => 'Re-Akreditasi',
                            };
                        @endphp
                        <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap;">
                            @if($isRevoked)
                                <span style="font-size: 11px; font-weight: 700; padding: 2.5px 9px; border-radius: var(--radius-pill, 9999px); background: #ffe4e6; color: #be123c; border: 1px solid #fda4af; display: inline-flex; align-items: center; letter-spacing: 0.02em;">
                                    Status Siklus: Akreditasi Dicabut
                                </span>
                            @else
                                <span style="font-size: 11px; font-weight: 700; padding: 2.5px 9px; border-radius: var(--radius-pill, 9999px); background: var(--neutral-chip-bg); color: var(--ink); border: 1px solid var(--line); display: inline-flex; align-items: center; letter-spacing: 0.02em;">
                                    Fokus Siklus: {{ $focusLabel }}
                                </span>
                                @if($lpk->isMultilocation())
                                    <span style="font-size: 11px; font-weight: 600; padding: 2.5px 9px; border-radius: var(--radius-pill, 9999px); background: rgba(2, 132, 199, 0.08); color: var(--primary); border: 1px solid rgba(2, 132, 199, 0.25); display: inline-flex; align-items: center; gap: 4px;">
                                        <x-icon name="building" size="12" />
                                        <span>Multilokasi ({{ $lpk->locations->count() }} Cabang)</span>
                                    </span>
                                @endif
                            @endif
                        </div>
                    </div>
                    @if($canManageLpk)
                        @if($isRevoked)
                            <a href="{{ route('assessments.create', ['lpk_id' => $lpk->id, 'assessment_type' => 'INITIAL', 'title' => 'Akreditasi Awal - ' . $lpk->name]) }}" class="button primary" style="font-size: 12px; padding: 5px 12px; min-height: 32px; display: inline-flex; align-items: center; gap: 6px;" title="Daftarkan permohonan asesmen akreditasi awal baru">
                                <x-icon name="plus" size="14" />
                                <span>Ajukan Akreditasi Awal</span>
                            </a>
                        @else
                            <a href="{{ route('assessments.create', ['lpk_id' => $lpk->id]) }}" class="button secondary" style="font-size: 12px; padding: 5px 12px; min-height: 32px; display: inline-flex; align-items: center; gap: 6px;" title="Tambah agenda asesmen baru untuk laboratorium ini (seperti PRL, STT, atau asesmen khusus)">
                                <x-icon name="plus" size="14" />
                                <span>Tambah Agenda Asesmen</span>
                            </a>
                        @endif
                    @endif
                </div>
            </div>
        </div>

        <div class="lpk-milestones-grid">
            <!-- S1 Card -->
            <div class="lpk-milestone-card {{ $s1CardState['card_class'] }} {{ $currentFocusCode === 'S1' ? 'is-current-focus' : '' }}">
                <div class="lpk-milestone-card-top">
                    <div class="lpk-milestone-header">
                        <div class="lpk-milestone-heading-left">
                            <h3 class="lpk-milestone-title">{{ $s1['name'] }} (S1)</h3>
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
                    <p class="lpk-milestone-desc">Toleransi pelaksanaan s/d Bulan ke-24</p>

                    {{-- Primary Target Date --}}
                    <div class="lpk-milestone-primary-target">
                        <span class="lpk-target-label">Target Kunjungan (Bulan 15)</span>
                        <span class="lpk-target-value">{{ ($s1['visit_target_date'] ?? null) ? $s1['visit_target_date']->format('d M Y') : '-' }}</span>
                    </div>

                    {{-- Supporting Metadata Dates --}}
                    <div class="lpk-milestone-subdates">
                        <div class="lpk-subdate-row">
                            <span class="lpk-subdate-label">Pengingat Terbit:</span>
                            <strong class="lpk-subdate-val">{{ $s1['notice_date'] ? $s1['notice_date']->format('d M Y') : '-' }}</strong>
                        </div>
                        <div class="lpk-subdate-row">
                            <span class="lpk-subdate-label">Batas Waktu Standar (Bulan 18):</span>
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
                            @if($linkedS1List->isNotEmpty())
                                @foreach($linkedS1List as $asItem)
                                    <div style="display: inline-flex; align-items: center; gap: 4px; flex-wrap: wrap;">
                                        <a href="{{ route('assessments.show', $asItem) }}" class="lpk-agenda-chip" title="Buka detail asesmen">
                                            <span>{{ $asItem->status === 'PLANNED' ? 'Rencana (Bulan 15)' : $asItem->status_label }}</span>
                                            @if($lpk->isMultilocation() && ($asItem->lpkLocation || $asItem->location))
                                                <span style="font-size: 10px; font-weight: 600; color: var(--primary); background: rgba(2, 132, 199, 0.08); padding: 1px 6px; border-radius: 4px; display: inline-flex; align-items: center; gap: 3px; max-width: 140px; overflow: hidden; text-overflow: ellipsis;">
                                                    <x-icon name="map-pin" size="10" />
                                                    <span style="overflow: hidden; text-overflow: ellipsis;">{{ $asItem->lpkLocation?->name ?? $asItem->location }}</span>
                                                </span>
                                            @endif
                                            <x-icon name="chevron-right" size="12" />
                                        </a>
                                        <a href="{{ route('calendar.index', ['view' => 'month', 'date' => $asItem->start_at->toDateString(), 'highlight' => $asItem->id, 'selected' => 1]) }}"
                                           class="assessment-cal-shortcut"
                                           title="Lihat agenda S1 di kalender">
                                            <x-icon name="calendar" size="13" />
                                            <span>Kalender</span>
                                        </a>
                                    </div>
                                @endforeach
                            @elseif($isMilestoneResolved($s1CardState))
                                <span class="lpk-agenda-unassigned is-historical">Terealisasi (Data Historis)</span>
                            @elseif(!empty($s1['target_date']))
                                <span class="lpk-agenda-unassigned">Belum Dijadwalkan</span>
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

                @if($canManageLpk && !$linkedS1 && ! $isMilestoneResolved($s1CardState) && ! $isRevoked)
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
                            <h3 class="lpk-milestone-title">{{ $s2['name'] }} (S2)</h3>
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
                    <p class="lpk-milestone-desc">Toleransi pelaksanaan s/d Bulan ke-39</p>

                    {{-- Primary Target Date --}}
                    <div class="lpk-milestone-primary-target">
                        <span class="lpk-target-label">Target Kunjungan (Bulan 36)</span>
                        <span class="lpk-target-value">{{ ($s2['visit_target_date'] ?? null) ? $s2['visit_target_date']->format('d M Y') : '-' }}</span>
                    </div>

                    {{-- Supporting Metadata Dates --}}
                    <div class="lpk-milestone-subdates">
                        <div class="lpk-subdate-row">
                            <span class="lpk-subdate-label">Pengingat Terbit:</span>
                            <strong class="lpk-subdate-val">{{ $s2['notice_date'] ? $s2['notice_date']->format('d M Y') : '-' }}</strong>
                        </div>
                        <div class="lpk-subdate-row">
                            <span class="lpk-subdate-label">Batas Waktu Standar (Bulan 39):</span>
                            <strong class="lpk-subdate-val">{{ $s2['target_date'] ? $s2['target_date']->format('d M Y') : '-' }}</strong>
                        </div>
                    </div>

                    {{-- Agenda Asesmen Strip --}}
                    <div class="lpk-milestone-agenda-strip">
                        <span class="lpk-agenda-caption">Agenda Asesmen:</span>
                        <div class="lpk-agenda-actions">
                            @if($linkedS2List->isNotEmpty())
                                @foreach($linkedS2List as $asItem)
                                    <div style="display: inline-flex; align-items: center; gap: 4px; flex-wrap: wrap;">
                                        <a href="{{ route('assessments.show', $asItem) }}" class="lpk-agenda-chip" title="Buka detail asesmen">
                                            <span>{{ $asItem->status === 'PLANNED' ? 'Rencana (Bulan 36)' : $asItem->status_label }}</span>
                                            @if($lpk->isMultilocation() && ($asItem->lpkLocation || $asItem->location))
                                                <span style="font-size: 10px; font-weight: 600; color: var(--primary); background: rgba(2, 132, 199, 0.08); padding: 1px 6px; border-radius: 4px; display: inline-flex; align-items: center; gap: 3px; max-width: 140px; overflow: hidden; text-overflow: ellipsis;">
                                                    <x-icon name="map-pin" size="10" />
                                                    <span style="overflow: hidden; text-overflow: ellipsis;">{{ $asItem->lpkLocation?->name ?? $asItem->location }}</span>
                                                </span>
                                            @endif
                                            <x-icon name="chevron-right" size="12" />
                                        </a>
                                        <a href="{{ route('calendar.index', ['view' => 'month', 'date' => $asItem->start_at->toDateString(), 'highlight' => $asItem->id, 'selected' => 1]) }}"
                                           class="assessment-cal-shortcut"
                                           title="Lihat agenda S2 di kalender">
                                            <x-icon name="calendar" size="13" />
                                            <span>Kalender</span>
                                        </a>
                                    </div>
                                @endforeach
                            @elseif($isMilestoneResolved($s2CardState))
                                <span class="lpk-agenda-unassigned is-historical">Terealisasi (Data Historis)</span>
                            @elseif(!empty($s2['target_date']))
                                <span class="lpk-agenda-unassigned">Belum Dijadwalkan</span>
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

                @if($canManageLpk && !$linkedS2 && ! $isMilestoneResolved($s2CardState) && ! $isRevoked)
                    <div class="lpk-milestone-footer-actions">
                        <a href="{{ route('assessments.create', ['lpk_id' => $lpk->id, 'alert_code' => 'S2', 'target_date' => ($s2['visit_target_date'] ?? $s2['target_date'])?->format('Y-m-d')]) }}" class="button {{ $currentFocusCode === 'S2' ? 'primary' : 'secondary' }} button-sm lpk-primary-action-btn">
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
                            <h3 class="lpk-milestone-title">Re-Akreditasi (RA)</h3>
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
                        @if($lpk->isInGracePeriod() && $lpk->grace_period_deadline)
                            <span class="lpk-target-label" style="color: var(--warning-text, #b45309);">Batas Akhir Toleransi (Masa Tenggang)</span>
                            <div style="display: flex; align-items: baseline; gap: 8px; flex-wrap: wrap;">
                                <span class="lpk-target-value" style="color: #b91c1c;">{{ $lpk->grace_period_deadline->format('d M Y') }}</span>
                                <span class="status status-warn" style="font-size: 11px; padding: 1px 7px;">Sisa {{ $lpk->days_remaining_grace_period }} hari</span>
                            </div>
                        @else
                            <span class="lpk-target-label">Batas Permohonan Lengkap (Bulan 51)</span>
                            <span class="lpk-target-value">{{ $ra['target_date'] ? $ra['target_date']->format('d M Y') : '-' }}</span>
                        @endif
                    </div>

                    {{-- Supporting Metadata Dates --}}
                    <div class="lpk-milestone-subdates">
                        <div class="lpk-subdate-row">
                            <span class="lpk-subdate-label">Pengumuman Permohonan:</span>
                            <strong class="lpk-subdate-val">{{ $ra['notice_date'] ? $ra['notice_date']->format('d M Y') : '-' }}</strong>
                        </div>
                        @if($lpk->isInGracePeriod())
                            <div class="lpk-subdate-row">
                                <span class="lpk-subdate-label">Batas Permohonan (Bulan 51):</span>
                                <strong class="lpk-subdate-val">{{ $ra['target_date'] ? $ra['target_date']->format('d M Y') : '-' }}</strong>
                            </div>
                        @endif
                        <div class="lpk-subdate-row">
                            <span class="lpk-subdate-label">Target Asesmen Lapangan (Bulan 54):</span>
                            <strong class="lpk-subdate-val">{{ ($ra['visit_target_date'] ?? null) ? $ra['visit_target_date']->format('d M Y') : '-' }}</strong>
                        </div>
                        <div class="lpk-subdate-row">
                            <span class="lpk-subdate-label">Masa Berlaku Habis (Bulan 60):</span>
                            <strong class="lpk-subdate-val">{{ ($ra['tolerance_date'] ?? null) ? $ra['tolerance_date']->format('d M Y') : '-' }}</strong>
                        </div>
                        @if($lpk->isInGracePeriod() && $lpk->grace_period_deadline)
                            <div class="lpk-subdate-row lpk-grace-subdate-highlight">
                                <span class="lpk-subdate-label">Batas Akhir Masa Tenggang:</span>
                                <strong class="lpk-subdate-val">{{ $lpk->grace_period_deadline->format('d M Y') }}</strong>
                            </div>
                        @endif
                    </div>

                    {{-- Agenda Asesmen Strip --}}
                    <div class="lpk-milestone-agenda-strip">
                        <span class="lpk-agenda-caption">Agenda Asesmen:</span>
                        <div class="lpk-agenda-actions">
                            @if($linkedRAList->isNotEmpty())
                                @foreach($linkedRAList as $asItem)
                                    <div style="display: inline-flex; align-items: center; gap: 4px; flex-wrap: wrap;">
                                        <a href="{{ route('assessments.show', $asItem) }}" class="lpk-agenda-chip" title="Buka detail asesmen">
                                            <span>{{ $asItem->status === 'PLANNED' ? 'Rencana (Bulan 54)' : $asItem->status_label }}</span>
                                            @if($lpk->isMultilocation() && ($asItem->lpkLocation || $asItem->location))
                                                <span style="font-size: 10px; font-weight: 600; color: var(--primary); background: rgba(2, 132, 199, 0.08); padding: 1px 6px; border-radius: 4px; display: inline-flex; align-items: center; gap: 3px; max-width: 140px; overflow: hidden; text-overflow: ellipsis;">
                                                    <x-icon name="map-pin" size="10" />
                                                    <span style="overflow: hidden; text-overflow: ellipsis;">{{ $asItem->lpkLocation?->name ?? $asItem->location }}</span>
                                                </span>
                                            @endif
                                            <x-icon name="chevron-right" size="12" />
                                        </a>
                                        <a href="{{ route('calendar.index', ['view' => 'month', 'date' => $asItem->start_at->toDateString(), 'highlight' => $asItem->id, 'selected' => 1]) }}"
                                           class="assessment-cal-shortcut"
                                           title="Lihat agenda RA di kalender">
                                            <x-icon name="calendar" size="13" />
                                            <span>Kalender</span>
                                        </a>
                                    </div>
                                @endforeach
                            @elseif($isMilestoneResolved($raCardState))
                                <span class="lpk-agenda-unassigned is-historical">Terealisasi (Data Historis)</span>
                            @elseif(!empty($ra['target_date']))
                                <span class="lpk-agenda-unassigned">Belum Dijadwalkan</span>
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

                @if($canManageLpk && !$linkedRA && ! $isMilestoneResolved($raCardState))
                    @if($isRevoked)
                        <div class="lpk-milestone-footer-actions">
                            <a href="{{ route('assessments.create', ['lpk_id' => $lpk->id, 'assessment_type' => 'INITIAL', 'title' => 'Akreditasi Awal - ' . $lpk->name]) }}" class="button primary button-sm lpk-primary-action-btn lpk-alert-action-btn-danger" title="Daftarkan permohonan akreditasi awal baru">
                                <x-icon name="plus" size="13" />
                                <span>Ajukan Akreditasi Awal</span>
                            </a>
                        </div>
                    @else
                        <div class="lpk-milestone-footer-actions">
                            <a href="{{ route('assessments.create', ['lpk_id' => $lpk->id, 'alert_code' => 'RA', 'target_date' => $ra['target_date']?->format('Y-m-d')]) }}" class="button {{ $currentFocusCode === 'RA' ? 'primary' : 'secondary' }} button-sm lpk-primary-action-btn">
                                <x-icon name="plus" size="13" />
                                <span>Jadwalkan Re-Akreditasi</span>
                            </a>
                        </div>
                    @endif
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
                    </div>
                </div>

                <div class="lpk-meta-grid">
                    <div class="lpk-meta-item">
                        <span class="lpk-meta-label">No. Registrasi LPK (ID KAN)</span>
                        <div class="lpk-meta-value">
                            <strong style="font-family: ui-monospace, SFMono-Regular, monospace; font-size: 13.5px; color: var(--ink);">{{ $lpk->no_reg ?: '-' }}</strong>
                        </div>
                    </div>

                    <div class="lpk-meta-item">
                        <span class="lpk-meta-label">Nomor Akreditasi KAN</span>
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
                    </div>
                </div>

                <div class="lpk-meta-grid">
                    <div class="lpk-meta-item">
                        <span class="lpk-meta-label">Penanggung Jawab (PIC)</span>
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
                        <span class="lpk-meta-label">Nomor Telepon</span>
                        <div class="lpk-meta-value">
                            {{ $lpk->phone ?: 'Belum diisi' }}
                        </div>
                    </div>

                    <div class="lpk-meta-item full-width">
                        <span class="lpk-meta-label">Alamat Fasilitas</span>
                        <div class="lpk-meta-value" style="color: var(--ink); line-height: 1.55;">
                            {{ $lpk->address ?: 'Belum diisi' }}
                        </div>
                    </div>
                </div>
            </div>

            {{-- KARTU: Fasilitas & Daftar Cabang (Sistem Multilokasi KAN U-05) --}}
            @include('lpks.partials.locations-card')
        </div>

        {{-- KOLOM KANAN: Tim Kolaborasi, Status Siklus & Daftar Asesmen --}}
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

                    {{-- 2. Viewer Kolaborasi dari Tautan Akun --}}
                    @if($lpk->pic && $lpk->pic->linkedViewers && $lpk->pic->linkedViewers->isNotEmpty())
                        @foreach($lpk->pic->linkedViewers as $viewer)
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
                                    <span class="badge badge-viewer-linked" title="Memiliki akses lihat ke seluruh daftar LPK akun ini">
                                        Viewer (Tautan Akun)
                                    </span>
                                </div>
                            </div>
                        @endforeach
                    @else
                        <div style="font-size: 12px; color: var(--muted); padding: 8px 12px;">
                            Belum ada akun viewer kolaborasi yang ditautkan ke PIC ini.
                        </div>
                    @endif
                </div>

                {{-- Tautan Pengaturan Akun Kolaborasi Global --}}
                <div style="border-top: 1px dashed var(--line); padding-top: 10px; margin-top: 10px; display: flex; justify-content: space-between; align-items: center; font-size: 11.5px;">
                    <span style="color: var(--muted);">Tautan Akun Kolaborasi (Seluruh LPK)</span>
                    <a href="{{ route('account-links.index') }}" style="color: var(--primary); font-weight: 600; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                        <span>Kelola Tautan Akun</span>
                        <x-icon name="arrow-right" size="12" />
                    </a>
                </div>
            </div>

            {{-- KARTU: Status Siklus Monitoring & Catatan PIC --}}
            <div class="lpk-form-card" id="card-status-monitoring">
                <div class="lpk-form-card-header">
                    <div class="lpk-card-icon-wrap icon-wrap-amber">
                        <svg width="20" height="20" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                            <path d="M16 4h2a2 2 0 0 1 2 2v14a2 2 0 0 1-2 2H6a2 2 0 0 1-2-2V6a2 2 0 0 1 2-2h2"/>
                            <rect x="8" y="2" width="8" height="4" rx="1" ry="1"/>
                        </svg>
                    </div>
                    <div class="lpk-card-header-text">
                        <h2>Status Siklus &amp; Catatan PIC</h2>
                    </div>
                </div>

                <div class="lpk-detail-keterangan-stack">
                    {{-- Status Operasional Otomatis --}}
                    @php
                        $hasColon = str_contains($lpk->dynamic_keterangan, ':');
                        if ($hasColon) {
                            [$processName, $statusDetail] = explode(':', $lpk->dynamic_keterangan, 2);
                            $cleanProcessName = str_replace([' (Akreditasi Ulang)', ' (RA)'], '', trim($processName));
                            $statusDetail = trim($statusDetail);
                        } else {
                            $cleanProcessName = null;
                            $statusDetail = trim($lpk->dynamic_keterangan);
                        }

                        $ketLower = strtolower($lpk->dynamic_keterangan);
                        $statusDetailLower = strtolower($statusDetail);

                        if ($lpk->isRevocationOverdue() || $lpk->dynamic_status === 'REVOKED' || $lpk->dynamic_status === 'EXPIRED' || str_contains($ketLower, 'akreditasi dicabut') || str_contains($ketLower, 'status dicabut')) {
                            $theme = 'rose';
                            $catLabel = 'Dicabut';
                        } elseif ($lpk->isInGracePeriod() || $lpk->dynamic_status === 'GRACE_PERIOD' || str_contains($ketLower, 'masa tenggang') || str_contains($ketLower, 'toleransi 6 bulan')) {
                            $theme = 'amber';
                            $catLabel = 'Masa Tenggang';
                        } elseif ($lpk->dynamic_status === 'SUSPENDED' || str_starts_with($statusDetailLower, 'dibekukan') || str_contains($ketLower, 'status akreditasi dibekukan')) {
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
                                @if($cleanProcessName)
                                    <span class="lpk-keterangan-process-badge theme-{{ $theme }}">{{ $cleanProcessName }}</span>
                                @else
                                    <span class="lpk-keterangan-type-label">Status Siklus</span>
                                @endif
                            </div>
                            <div class="lpk-keterangan-head-right">
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

            {{-- KARTU: Daftar Asesmen Surveilen --}}
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
                            @php
                                $dateStr = $item->start_at
                                    ? ($item->end_at && $item->end_at->ne($item->start_at)
                                        ? ($item->start_at->isSameMonth($item->end_at)
                                            ? $item->start_at->format('d') . ' - ' . $item->end_at->format('d M Y')
                                            : $item->start_at->format('d M Y') . ' - ' . $item->end_at->format('d M Y'))
                                        : $item->start_at->format('d M Y'))
                                    : '-';
                            @endphp
                            <div class="assessment-list-row clickable-row" data-href="{{ isset($owner) ? route('account-links.assessments.show', [$owner, $item]) : route('assessments.show', $item) }}" tabindex="0" role="link" aria-label="{{ $item->display_title }}">
                                <div class="assessment-row-head">
                                    <div class="assessment-row-title-wrap">
                                        <h4 class="assessment-row-title">{{ $item->display_title }}</h4>
                                        <div class="assessment-row-date">
                                            <x-icon name="calendar" size="12" />
                                            <span>{{ $dateStr }}</span>
                                        </div>
                                    </div>
                                    <div class="assessment-row-badges">
                                        <x-status :value="$item->status" />
                                        @if($item->is_submission_overdue)
                                            <span class="badge-tp badge-tp-danger" title="Toleransi pengisian asesmen telah terlampaui">Lewat Toleransi</span>
                                        @endif
                                    </div>
                                </div>

                                @if(($lpk->isMultilocation() && $item->lpkLocation) || $item->sk_number)
                                    <div class="assessment-row-meta">
                                        @if($lpk->isMultilocation() && $item->lpkLocation)
                                            <span class="assessment-loc-tag">
                                                <x-icon name="map-pin" size="11" />
                                                <span>{{ $item->lpkLocation->name }}</span>
                                            </span>
                                        @endif
                                        @if($item->sk_number)
                                            <span class="assessment-sk-tag">
                                                <span>SK KAN: <strong>{{ $item->sk_number }}</strong> @if($item->sk_date)({{ $item->sk_date->format('d/m/Y') }})@endif</span>
                                                @if($item->sk_lead_time_days !== null)
                                                    &bull; <strong class="assessment-duration-lead">Durasi: {{ $item->sk_lead_time_days }} hari</strong>
                                                @endif
                                            </span>
                                        @endif
                                    </div>
                                @endif

                                <div class="assessment-row-footer">
                                    <span class="assessment-row-link">Detail asesmen &rsaquo;</span>
                                    <a href="{{ route('calendar.index', ['view' => 'month', 'date' => $item->start_at->toDateString(), 'highlight' => $item->id, 'selected' => 1]) }}"
                                       class="assessment-cal-shortcut"
                                       title="Lihat agenda ini di kalender">
                                       <x-icon name="calendar" size="12" />
                                       <span>Kalender</span>
                                    </a>
                                </div>
                            </div>
                        @empty
                            <div class="lpk-assessment-empty-state">
                                <div class="lpk-empty-icon-circle">
                                    <x-icon name="calendar" size="20" />
                                </div>
                                <div class="lpk-empty-title">Belum Ada Agenda Asesmen</div>
                                <p class="lpk-empty-desc">
                                    Belum ada jadwal asesmen surveilen atau re-akreditasi yang ditambahkan untuk siklus ini.
                                </p>
                                @if($lpk->canManage(auth()->user()))
                                    <a href="{{ route('assessments.create', ['lpk_id' => $lpk->id]) }}" class="button secondary button-xs lpk-empty-action">
                                        <x-icon name="plus" size="12" />
                                        <span>Jadwalkan Asesmen Pertama</span>
                                    </a>
                                @endif
                            </div>
                        @endforelse
                    </div>
                </div>
            </div>
        </div>
    </div>

    {{-- KARTU: Ruang Lingkup Akreditasi (Full-Width di Bawah Grid) --}}
    <div class="lpk-form-card" id="card-ruang-lingkup" style="margin-top: 16px;">
        <div class="lpk-form-card-header" style="border-bottom: 1px solid var(--line); padding-bottom: 12px; margin-bottom: 14px;">
            <div class="lpk-card-icon-wrap icon-wrap-green">
                <x-icon name="file-text" size="20" />
            </div>
            <div class="lpk-card-header-text">
                <h2 style="font-size: 15.5px; margin: 0; color: var(--ink);">Ruang Lingkup Akreditasi</h2>
                <p style="margin: 2px 0 0 0; font-size: 12px; color: var(--muted);">Bidang pengujian, kalibrasi, inspeksi, atau komoditas yang diakui dalam sertifikat KAN.</p>
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
</div>

@if($lpk->canManage(auth()->user()))
{{-- Modal: Input / Ubah Keterangan LPK --}}
<div class="simasadi-modal" id="modal-input-keterangan" role="dialog" aria-modal="true" aria-labelledby="modal-input-keterangan-title">
    <div class="simasadi-modal-box" style="max-width: 540px;">
        <div class="simasadi-modal-head">
            <div class="modal-title-group">
                <div class="modal-title-icon-badge badge-blue" aria-hidden="true">
                    <x-icon name="file-text" size="17" />
                </div>
                <h4 id="modal-input-keterangan-title">{{ $lpk->notes ? 'Ubah Keterangan LPK' : 'Input Keterangan LPK' }}</h4>
            </div>
            <button type="button" class="simasadi-modal-close" data-modal-close onclick="window.closeModal('modal-input-keterangan')" aria-label="Tutup modal">&times;</button>
        </div>
        <form method="POST" action="{{ route('lpks.notes.update', $lpk) }}">
            @csrf
            <div class="simasadi-modal-body">
                <div style="display: grid; gap: 14px;">
                    <p style="font-size: 13px; color: var(--muted); margin: 0;">
                        Keterangan atau catatan monitoring internal untuk <strong>{{ $lpk->name }}</strong> (No Reg: {{ $lpk->no_reg ?: '-' }}).
                    </p>

                    <div style="background: var(--surface-subtle, #f8fafc); border: 1px solid var(--line); border-radius: var(--radius-md, 10px); padding: 10px 12px;">
                        <div style="display: flex; justify-content: space-between; align-items: center; gap: 8px; margin-bottom: 6px; flex-wrap: wrap;">
                            <span style="font-size: 11px; font-weight: 700; color: var(--muted); text-transform: uppercase;">
                                Keterangan Otomatis:
                            </span>
                            <button type="button" class="button secondary" style="font-size: 11px; padding: 2px 8px; min-height: 24px;" onclick="const t = document.getElementById('input-notes-textarea'); if(t){ t.value = {{ json_encode($lpk->dynamic_keterangan) }}; t.dispatchEvent(new Event('input', { bubbles: true })); }">
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
                        <textarea id="input-notes-textarea" name="notes" rows="4" maxlength="5000" placeholder="Masukkan catatan tambahan PIC jika ada..." style="width: 100%; padding: 10px 12px; border: 1px solid var(--line); border-radius: var(--radius-lg, 12px); font-family: inherit; font-size: 13.5px; line-height: 1.5; resize: vertical;">{{ old('notes', $lpk->notes) }}</textarea>
                        <small style="color: var(--muted); font-size: 11.5px; display: block; margin-top: 4px;">Catatan manual akan selalu ditampilkan berdampingan dengan status otomatis sistem.</small>
                    </label>
                </div>
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

@include('lpks.partials.modal-lpk-location')
