<div class="lpk-v2-container">
    {{-- Navigasi Breadcrumb Kembali (Notion Style) --}}
    <div class="lpk-header-back-wrap" style="margin-bottom: -6px;">
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
                <span>Daftar Asesmen</span>
            </a>
        @endif
    </div>

    @php
        $canManageLpk = !isset($owner) && $lpk->canManage(auth()->user());
        $nameParts = preg_split('/\s+[-–—]\s+/u', $lpk->name);
        $hasSubName = count($nameParts) > 1;
        $distinctOrg = null;
        if ($hasSubName) {
            foreach (array_slice($nameParts, 0, -1) as $part) {
                $part = trim($part);
                if (preg_match('/\b(BRIN|Universitas|Institut|Politeknik|Badan|Kementerian|Dinas|PT|Yayasan|Balai)\b/i', $part)) {
                    $distinctOrg = $part;
                    break;
                }
            }
            if (!$distinctOrg) {
                $candidate = trim($nameParts[0]);
                if (mb_strlen($candidate) <= 60 && strcasecmp($candidate, $lpk->name) !== 0) {
                    $distinctOrg = $candidate;
                }
            }
        }

        // Clean city extraction from address or locations
        $displayCity = null;
        if ($lpk->address) {
            $addrParts = array_map('trim', explode(',', $lpk->address));
            $displayCity = count($addrParts) >= 2 ? implode(', ', array_slice($addrParts, -2)) : end($addrParts);
        }
        $displayCity = $displayCity ?: ($lpk->city ?: 'Indonesia');
        $displayRegNumber = $lpk->accreditation_number ?: ($lpk->no_reg ?: ($lpk->registration_number ?: 'LPK-'.$lpk->id));

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
            return $inCycle && ! in_array($a->id, $linkedS1Ids, true) && (
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
            return $inCycle && ! in_array($a->id, $excludedFromRa, true) && (
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
            if (($linked && $linked->status === 'SUSPENDED') 
                || ($milestone['status'] ?? null) === 'SUSPENDED'
                || (($milestone['status'] ?? null) === 'OVERDUE' && $lpk->dynamic_status === 'SUSPENDED')) {
                return ['card_class' => 'is-dibekukan is-suspended', 'badge_class' => 'status-dibekukan status-suspended', 'badge_label' => 'Dibekukan'];
            }
            if ($linked && $linked->tp_status && ! in_array($linked->tp_status, [\App\Models\Assessment::TP_STATUS_NONE, \App\Models\Assessment::TP_STATUS_SATISFIED], true)) {
                return ['card_class' => 'is-batas-tp', 'badge_class' => 'status-batas-tp', 'badge_label' => 'Batas TP'];
            }
            if ($linked) {
                if ($linked->status === 'REVOKED') return ['card_class' => 'is-jt is-overdue', 'badge_class' => 'status-jt status-danger', 'badge_label' => 'Dicabut'];
                if ($linked->status === 'COMPLETED') return ['card_class' => 'is-completed', 'badge_class' => 'status-completed', 'badge_label' => 'Selesai'];
                if ($linked->status === 'IN_PROGRESS') return ['card_class' => 'is-in-progress', 'badge_class' => 'status-in_progress status-warn', 'badge_label' => 'Sedang Berlangsung'];
                if ($linked->status === 'SCHEDULED') {
                    if ($linked->start_at && $linked->start_at->isPast()) {
                        return ['card_class' => 'is-jt is-overdue', 'badge_class' => 'status-jt status-danger', 'badge_label' => 'Lewat Jatuh Tempo'];
                    }
                    return ['card_class' => 'is-scheduled', 'badge_class' => 'status-scheduled', 'badge_label' => 'Terjadwal'];
                }
                if ($linked->status === 'PLANNED') {
                    if ($typeCode === 'RA' && (($milestone['status'] ?? null) === 'REVOKED' || $lpk->isRevocationOverdue() || $lpk->dynamic_status === 'REVOKED')) {
                        return ['card_class' => 'is-jt is-overdue', 'badge_class' => 'status-jt status-danger', 'badge_label' => 'Dicabut'];
                    }
                    if (($milestone['status'] ?? null) === 'OVERDUE') {
                        return ['card_class' => 'is-jt is-overdue', 'badge_class' => 'status-jt status-danger', 'badge_label' => 'Lewat Jatuh Tempo'];
                    }
                    if (($milestone['status'] ?? null) === 'EXPIRED') {
                        return ['card_class' => 'is-jt is-overdue', 'badge_class' => 'status-jt status-danger', 'badge_label' => 'Kedaluwarsa'];
                    }
                    if (($milestone['status'] ?? null) === 'DUE') {
                        $lbl = match ($typeCode) {
                            'S1' => 'Reminder S1',
                            'S2' => 'Reminder S2',
                            default => 'Reminder RA',
                        };
                        return ['card_class' => 'is-reminder is-due', 'badge_class' => 'status-reminder status-warn', 'badge_label' => $lbl];
                    }
                    return ['card_class' => 'is-upcoming', 'badge_class' => 'status-planned', 'badge_label' => 'Akan Datang'];
                }
            }
            if ($typeCode === 'RA' && (($milestone['status'] ?? null) === 'REVOKED' || $lpk->isRevocationOverdue() || $lpk->dynamic_status === 'REVOKED')) {
                return ['card_class' => 'is-jt is-overdue', 'badge_class' => 'status-jt status-danger', 'badge_label' => 'Dicabut'];
            }
            if (($milestone['status'] ?? null) === 'OVERDUE') return ['card_class' => 'is-jt is-overdue', 'badge_class' => 'status-jt status-danger', 'badge_label' => 'Lewat Jatuh Tempo'];
            if (($milestone['status'] ?? null) === 'EXPIRED') return ['card_class' => 'is-jt is-overdue', 'badge_class' => 'status-jt status-danger', 'badge_label' => 'Kedaluwarsa'];
            if (($milestone['status'] ?? null) === 'COMPLETED_OR_SCHEDULED') return ['card_class' => 'is-completed', 'badge_class' => 'status-completed', 'badge_label' => 'Terealisasi (Data Historis)'];
            if (($milestone['status'] ?? null) === 'DUE') {
                $lbl = match ($typeCode) {
                    'S1' => 'Reminder S1',
                    'S2' => 'Reminder S2',
                    default => 'Reminder RA',
                };
                return ['card_class' => 'is-reminder is-due', 'badge_class' => 'status-reminder status-warn', 'badge_label' => $lbl];
            }
            return ['card_class' => 'is-upcoming', 'badge_class' => 'status-planned', 'badge_label' => 'Akan Datang'];
        };

        $s1CardState = $resolveMilestoneCardState($linkedS1, $s1, 'S1');
        $s2CardState = $resolveMilestoneCardState($linkedS2, $s2, 'S2');
        $raCardState = $resolveMilestoneCardState($linkedRA, $ra, 'RA');

        $isMilestoneResolved = fn(array $state): bool => in_array($state['badge_label'], ['Selesai', 'Terealisasi', 'Terealisasi (Data Historis)'], true);
        $getMilestonePillClass = function (array $state) use ($isMilestoneResolved): string {
            if (($state['badge_label'] ?? '') === 'Dibekukan') {
                return 'pill-blue';
            }
            if (in_array($state['badge_label'] ?? '', ['Dicabut', 'Lewat Jatuh Tempo', 'Kedaluwarsa'], true)) {
                return 'pill-danger';
            }
            return $isMilestoneResolved($state) ? 'pill-success' : 'pill-warn';
        };
        $isRevoked = $lpk->isRevocationOverdue() || $lpk->dynamic_status === 'REVOKED';

        $alertCodes = collect($activeAlerts)->pluck('code')->map(fn($c) => strtoupper($c))->all();

        // Tentukan tahap fokus aktif secara kronologis & alert-driven
        $currentFocusCode = 'S1';
        if ($isRevoked || $lpk->isInGracePeriod() || in_array('RA', $alertCodes, true)) {
            $currentFocusCode = 'RA';
        } elseif (in_array('S1', $alertCodes, true)) {
            $currentFocusCode = 'S1';
        } elseif (in_array('S2', $alertCodes, true)) {
            $currentFocusCode = 'S2';
        } elseif (! $isMilestoneResolved($s1CardState)) {
            $currentFocusCode = 'S1';
        } elseif (! $isMilestoneResolved($s2CardState)) {
            $currentFocusCode = 'S2';
        } else {
            $currentFocusCode = 'RA';
        }

        $focusLabel = match($currentFocusCode) {
            'S1' => 'Surveilen 1',
            'S2' => 'Surveilen 2',
            default => 'Re-akreditasi',
        };
        $focusTargetDate = match($currentFocusCode) {
            'S1' => ($s1['visit_target_date'] ?? $s1['target_date']),
            'S2' => ($s2['visit_target_date'] ?? $s2['target_date']),
            default => $ra['target_date'],
        };

        $resolvedStagesCount = ($isMilestoneResolved($s1CardState) ? 1 : 0) + ($isMilestoneResolved($s2CardState) ? 1 : 0) + ($isMilestoneResolved($raCardState) ? 1 : 0);

        $filteredActiveAlerts = collect($activeAlerts)->filter(function ($a) use ($lpk, $isRevoked) {
            if ($isRevoked) return false;
            if ($lpk->isInGracePeriod() && strtoupper($a['code'] ?? '') === 'RA') return false;
            return true;
        })->values()->all();

        // Direct calendar URL for Status Siklus
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
            if (!empty($filteredActiveAlerts)) {
                $fAlert = reset($filteredActiveAlerts);
                $calTargetDate = $fAlert['target_date']?->toDateString() ?? now()->toDateString();
                $calHighlight = 'lpk_jt_' . strtolower($fAlert['code'] ?? 's1') . '_' . $lpk->id;
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

    {{-- 1. Header Tingkat 1 (Top Level) --}}
    <div class="lpk-v2-header">
        <div class="lpk-v2-header-left">
            <h1 class="lpk-v2-title">{{ $lpk->name }}</h1>
            <p class="lpk-v2-subtitle">
                <span>{{ $displayRegNumber }}</span>
                @if($distinctOrg)
                    <span class="dot-sep">&bull;</span>
                    <span>{{ $distinctOrg }}</span>
                @endif
                <span class="dot-sep">&bull;</span>
                <span>{{ $displayCity }}</span>
                <span class="dot-sep">&bull;</span>
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
            </p>
        </div>
        <div class="lpk-v2-header-actions">
            @if($canManageLpk)
                <a class="lpk-v2-btn-edit" href="{{ route('lpks.edit', $lpk) }}" title="Ubah data laboratorium">
                    <x-icon name="edit" size="14" />
                    <span>Ubah data</span>
                </a>
            @endif
            @if($lpk->canDelete(auth()->user()))
                <form method="POST" action="{{ route('lpks.destroy', $lpk) }}" class="form-delete-lpk" data-lpk-name="{{ $lpk->name }}" data-lpk-reg="{{ $lpk->registration_number }}" style="margin: 0; display: inline-block;">
                    @csrf
                    @method('DELETE')
                    <button type="submit" class="lpk-v2-btn-more" title="Hapus LPK ini dari sistem">
                        <x-icon name="trash" size="14" />
                    </button>
                </form>
            @endif
        </div>
    </div>

    {{-- 2. Banner Peringatan Status Akreditasi KAN (Full Width) --}}
    @if($isRevoked)
        <div class="lpk-alert-callout is-revoked lpk-v2-banner">
            <div class="lpk-v2-banner-main">
                <div class="lpk-v2-banner-top-row">
                    <span class="lpk-v2-status-pill pill-danger">
                        <x-icon name="slash" size="11" />
                        <span>Dicabut</span>
                    </span>
                    <span class="lpk-v2-banner-category">Status Akreditasi Dicabut (Revoked)</span>
                </div>
                <h3 class="lpk-v2-banner-title">Akreditasi dicabut sejak {{ $lpk->expired_at ? $lpk->expired_at->translatedFormat('d F Y') : '12 Februari 2026' }}</h3>
                <p class="lpk-v2-banner-desc">
                    Pengajuan re-akreditasi baru dapat dikirim paling cepat 6 bulan setelah pencabutan. Profil, ruang lingkup, dan kontak tetap bisa diperbarui selama masa tenggang. Laboratorium dapat mengajukan akreditasi kembali sebagai pemohon akreditasi awal dengan nomor akreditasi baru.
                </p>
            </div>
            @if($canManageLpk)
                <div class="lpk-alert-callout-actions lpk-v2-banner-actions">
                    <a href="{{ route('assessments.create', ['lpk_id' => $lpk->id, 'assessment_type' => 'INITIAL', 'title' => 'Akreditasi Awal - ' . $lpk->name]) }}" class="lpk-v2-banner-btn" title="Ajukan akreditasi awal baru">
                        <x-icon name="arrow-up-right" size="14" />
                        <span>Ajukan Akreditasi Awal</span>
                    </a>
                    <a href="{{ route('lpks.edit', $lpk) }}" class="lpk-v2-banner-link">
                        <span>Perbarui Data LPK &rsaquo;</span>
                    </a>
                </div>
            @endif
        </div>
    @elseif($lpk->isInGracePeriod())
        <div class="lpk-alert-callout is-warning lpk-v2-banner">
            <div class="lpk-v2-banner-main">
                <div class="lpk-v2-banner-top-row">
                    <span class="lpk-v2-status-pill pill-warn">Masa Tenggang</span>
                    <span class="lpk-v2-banner-category">Masa Tenggang Akreditasi Ulang</span>
                </div>
                <h3 class="lpk-v2-banner-title">Sisa masa tenggang {{ $lpk->days_remaining_grace_period }} hari (s/d {{ $lpk->grace_period_deadline?->translatedFormat('d F Y') }})</h3>
                <p class="lpk-v2-banner-desc">
                    Sertifikat telah berakhir pada {{ $lpk->expired_at?->translatedFormat('d M Y') }}. LPK dilarang menggunakan simbol KAN pada laporan hingga keputusan akreditasi ulang resmi terbit.
                </p>
            </div>
            @if($canManageLpk)
                <div class="lpk-alert-callout-actions lpk-v2-banner-actions">
                    @if($linkedRA)
                        <a href="{{ route('assessments.edit', $linkedRA) }}#form-sk-block" class="lpk-v2-banner-btn">Input SK Akreditasi KAN</a>
                    @else
                        <a href="{{ route('assessments.create', ['lpk_id' => $lpk->id, 'alert_code' => 'RA', 'target_date' => $ra['target_date']?->format('Y-m-d')]) }}" class="lpk-v2-banner-btn">Catat Reakreditasi</a>
                    @endif
                    <a href="{{ route('lpks.edit', $lpk) }}#form-sk-block" class="lpk-v2-banner-link">
                        <span>Perbarui Sertifikat &rsaquo;</span>
                    </a>
                </div>
            @endif
        </div>
    @elseif(!empty($filteredActiveAlerts))
        @php
            $isSuspended = $lpk->dynamic_status === 'SUSPENDED';
            $isOverdueSurv = $lpk->dynamic_status === 'SURVEILLANCE_OVERDUE';
            $bannerTitle = $isSuspended
                ? 'Peringatan: Status Akreditasi Laboratorium Dibekukan'
                : ($isOverdueSurv
                    ? 'Peringatan Kritis: LPK Lewat Jatuh Tempo Pengawasan KAN (Terancam Dibekukan)'
                    : 'Peringatan Persisten: LPK Memasuki Masa Jatuh Tempo Pengawasan KAN');
            
            $firstAlert = reset($filteredActiveAlerts);
            $firstAlertCode = strtoupper($firstAlert['code'] ?? 'S1');
            $firstMilestone = match ($firstAlertCode) {
                'S1' => $s1,
                'S2' => $s2,
                default => $ra,
            };
            $firstCleanName = str_replace([' (Akreditasi Ulang)', ' (RA)'], '', $firstAlert['name']);
            $firstAlertTarget = $firstMilestone['visit_target_date'] ? $firstMilestone['visit_target_date']->format('Y-m-d') : ($firstMilestone['target_date'] ? $firstMilestone['target_date']->format('Y-m-d') : null);
        @endphp
        <div class="lpk-alert-callout {{ $isSuspended ? 'is-suspended' : ($isOverdueSurv ? 'is-warning is-overdue' : 'is-due') }} lpk-v2-banner">
            <div class="lpk-v2-banner-main">
                <div class="lpk-v2-banner-top-row">
                    <span class="lpk-v2-status-pill {{ $isSuspended ? 'pill-blue' : 'pill-warn' }}">
                        @if($isOverdueSurv)
                            <x-icon name="alert-triangle" size="11" />
                        @endif
                        <span>{{ $isSuspended ? 'Dibekukan' : ($isOverdueSurv ? 'Lewat Jatuh Tempo' : 'Pengawasan KAN') }}</span>
                    </span>
                </div>
                <h3 class="lpk-v2-banner-title">{{ $bannerTitle }}</h3>
                <p class="lpk-v2-banner-desc">
                    Masa pengawasan aktif untuk 
                    @foreach($filteredActiveAlerts as $a)
                        @php
                            $cleanAlertName = str_replace([' (Akreditasi Ulang)', ' (RA)'], '', $a['name']);
                        @endphp
                        <strong class="lpk-alert-tag">{{ $cleanAlertName }}</strong>@if(!$loop->last), @endif
                    @endforeach. Segera jadwalkan agenda kunjungan asesmen untuk kepatuhan regulasi KAN.
                </p>
            </div>
            <div class="lpk-alert-callout-actions lpk-v2-banner-actions">
                @if($canManageLpk)
                    <a href="{{ route('assessments.create', ['lpk_id' => $lpk->id, 'alert_code' => $firstAlertCode, 'target_date' => $firstAlertTarget]) }}" class="lpk-v2-banner-btn" title="Jadwalkan agenda kunjungan asesmen {{ $firstCleanName }} sekarang">
                        <x-icon name="plus" size="14" />
                        <span>Jadwalkan {{ $firstCleanName }}</span>
                    </a>
                @endif
                @if(auth()->user()?->isAdmin())
                    <form method="POST" action="{{ route('lpks.surveillance.remind', $lpk) }}" style="display: inline; margin: 0;">
                        @csrf
                        <button type="submit" class="lpk-v2-btn-edit" style="font-size: 11.5px; padding: 4px 10px; cursor: pointer;" title="Kirim notifikasi email resmi kepada PIC Lab">
                            <x-icon name="mail" size="12" />
                            <span>Kirim Notifikasi Email PIC Lab</span>
                        </button>
                    </form>
                @endif
            </div>
        </div>
    @else
        <div class="lpk-v2-banner is-active">
            <div class="lpk-v2-banner-main">
                <div class="lpk-v2-banner-top-row">
                    <span class="lpk-v2-status-pill pill-success">Aktif</span>
                    <span class="lpk-v2-banner-category">Status akreditasi KAN</span>
                </div>
                <h3 class="lpk-v2-banner-title">Akreditasi Aktif s/d {{ $lpk->expired_at ? $lpk->expired_at->translatedFormat('d F Y') : '-' }}</h3>
                <p class="lpk-v2-banner-desc">
                    Laboratorium terakreditasi resmi KAN. Siklus pengawasan berjalan sesuai standar kepatuhan regulasi KAN U-01.
                </p>
            </div>
            @if($canManageLpk)
                <div class="lpk-alert-callout-actions lpk-v2-banner-actions">
                    <a href="{{ route('assessments.create', ['lpk_id' => $lpk->id]) }}" class="lpk-v2-banner-btn">
                        <x-icon name="plus" size="14" />
                        <span>Tambah Agenda Asesmen</span>
                    </a>
                </div>
            @endif
        </div>
    @endif

    {{-- 3. Grid 3 Milestone Siklus Pengawasan (Roadmap) --}}
    <div class="lpk-v2-roadmap-card" id="card-siklus-roadmap">
        <div class="lpk-v2-roadmap-header">
            <div class="lpk-v2-roadmap-title-wrap">
                <h2 class="lpk-v2-roadmap-title">
                    <x-icon name="clock" size="16" class="text-primary" />
                    <span>Siklus Pengawasan &amp; Re-Akreditasi</span>
                </h2>
                <span class="lpk-v2-roadmap-badge">Periode {{ $cycleStart ? $cycleStart->format('Y') : '2021' }} - {{ $cycleEnd ? $cycleEnd->format('Y') : '2026' }}</span>
            </div>
            <button type="button" class="lpk-v2-card-link" onclick="window.openModal('modal-riwayat-siklus')" style="background: none; border: none; cursor: pointer; display: inline-flex; align-items: center; gap: 4px; padding: 0;">
                <span>Riwayat siklus</span>
                <x-icon name="chevron-right" size="12" />
            </button>
        </div>

        <div class="lpk-v2-roadmap-grid lpk-milestones-grid">
            {{-- Milestone 1: Surveilen 1 --}}
            <div class="lpk-v2-milestone-card lpk-milestone-card {{ $s1CardState['card_class'] }} {{ $currentFocusCode === 'S1' ? 'is-active-focus' : '' }}">
                <div class="lpk-v2-ms-head">
                    <div>
                        <h3 class="lpk-v2-ms-title">Surveilen 1 (S1)</h3>
                        <p class="lpk-v2-ms-sub">Dokumen &amp; lapangan</p>
                    </div>
                    <span class="lpk-v2-status-pill {{ $getMilestonePillClass($s1CardState) }}">
                        {{ $s1CardState['badge_label'] }}
                    </span>
                </div>

                <div class="lpk-v2-target-box {{ $isMilestoneResolved($s1CardState) ? 'is-success' : '' }}">
                    <div class="lpk-v2-target-top">
                        <span class="lpk-v2-target-label">Target Kunjungan (Bulan 15)</span>
                        <a href="{{ route('calendar.index', ['view' => 'month', 'date' => ($s1['visit_target_date'] ?? $s1['target_date'])?->toDateString()]) }}" class="lpk-v2-target-cal-btn" title="Buka di kalender">
                            <x-icon name="calendar" size="11" />
                            <span>Kalender</span>
                        </a>
                    </div>
                    <div class="lpk-v2-target-date">
                        {{ ($s1['visit_target_date'] ?? $s1['target_date'])?->translatedFormat('d M Y') ?: '-' }}
                    </div>
                    <div class="lpk-v2-target-diff {{ $isMilestoneResolved($s1CardState) ? 'text-success' : '' }}">
                        @if($isMilestoneResolved($s1CardState))
                            <x-icon name="check" size="12" />
                            <span>Terlaksana {{ $linkedS1 && $linkedS1->start_at ? '- ' . abs($linkedS1->start_at->diffInDays($s1['visit_target_date'] ?: $s1['target_date'])) . ' hari dari target' : '' }}</span>
                        @else
                            <span>Akan Datang &ndash; belum terjadwal</span>
                        @endif
                    </div>
                </div>

                <div class="lpk-v2-ms-meta">
                    <div class="lpk-v2-ms-row">
                        <span>Pelaksanaan:</span>
                        <span class="lpk-v2-ms-val">{{ $linkedS1 && $linkedS1->start_at ? $linkedS1->start_at->translatedFormat('d M Y') : '-' }}</span>
                    </div>
                    <div class="lpk-v2-ms-row">
                        <span>Batas waktu (Bulan 18):</span>
                        <span class="lpk-v2-ms-val">{{ $s1['target_date'] ? $s1['target_date']->translatedFormat('d M Y') : '-' }}</span>
                    </div>
                </div>

                <div class="lpk-v2-ms-footer">
                    <x-icon name="file-text" size="12" />
                    <span>{{ $linkedS1List->count() }} agenda asesmen terlaksana</span>
                </div>
            </div>

            {{-- Milestone 2: Surveilen 2 --}}
            <div class="lpk-v2-milestone-card lpk-milestone-card {{ $s2CardState['card_class'] }} {{ $currentFocusCode === 'S2' ? 'is-active-focus' : '' }}">
                <div class="lpk-v2-ms-head">
                    <div>
                        <h3 class="lpk-v2-ms-title">Surveilen 2 (S2)</h3>
                        <p class="lpk-v2-ms-sub">Dokumen &amp; lapangan</p>
                    </div>
                    <span class="lpk-v2-status-pill {{ $getMilestonePillClass($s2CardState) }}">
                        {{ $s2CardState['badge_label'] }}
                    </span>
                </div>

                <div class="lpk-v2-target-box {{ $isMilestoneResolved($s2CardState) ? 'is-success' : '' }}">
                    <div class="lpk-v2-target-top">
                        <span class="lpk-v2-target-label">Target Kunjungan (Bulan 36)</span>
                        <a href="{{ route('calendar.index', ['view' => 'month', 'date' => ($s2['visit_target_date'] ?? $s2['target_date'])?->toDateString()]) }}" class="lpk-v2-target-cal-btn" title="Buka di kalender">
                            <x-icon name="calendar" size="11" />
                            <span>Kalender</span>
                        </a>
                    </div>
                    <div class="lpk-v2-target-date">
                        {{ ($s2['visit_target_date'] ?? $s2['target_date'])?->translatedFormat('d M Y') ?: '-' }}
                    </div>
                    <div class="lpk-v2-target-diff {{ $isMilestoneResolved($s2CardState) ? 'text-success' : '' }}">
                        @if($isMilestoneResolved($s2CardState))
                            <x-icon name="check" size="12" />
                            <span>Terlaksana {{ $linkedS2 && $linkedS2->start_at ? '- ' . abs($linkedS2->start_at->diffInDays($s2['visit_target_date'] ?: $s2['target_date'])) . ' hari dari target' : '' }}</span>
                        @else
                            <span>Akan Datang &ndash; belum terjadwal</span>
                        @endif
                    </div>
                </div>

                <div class="lpk-v2-ms-meta">
                    <div class="lpk-v2-ms-row">
                        <span>Pelaksanaan:</span>
                        <span class="lpk-v2-ms-val">{{ $linkedS2 && $linkedS2->start_at ? $linkedS2->start_at->translatedFormat('d M Y') : '-' }}</span>
                    </div>
                    <div class="lpk-v2-ms-row">
                        <span>Batas waktu (Bulan 39):</span>
                        <span class="lpk-v2-ms-val">{{ $s2['target_date'] ? $s2['target_date']->translatedFormat('d M Y') : '-' }}</span>
                    </div>
                </div>

                <div class="lpk-v2-ms-footer">
                    <x-icon name="file-text" size="12" />
                    <span>{{ $linkedS2List->count() }} agenda asesmen terlaksana</span>
                </div>
            </div>

            {{-- Milestone 3: Re-akreditasi --}}
            <div class="lpk-v2-milestone-card lpk-milestone-card {{ $raCardState['card_class'] }} {{ $currentFocusCode === 'RA' ? 'is-active-focus' : '' }}">
                <div class="lpk-v2-ms-head">
                    <div>
                        <h3 class="lpk-v2-ms-title">Re-Akreditasi (RA)</h3>
                        <p class="lpk-v2-ms-sub">Asesmen penuh</p>
                    </div>
                    <span class="lpk-v2-status-pill {{ $isRevoked ? 'pill-danger' : $getMilestonePillClass($raCardState) }}">
                        {{ $isRevoked ? 'Dicabut' : $raCardState['badge_label'] }}
                    </span>
                </div>

                <div class="lpk-v2-target-box {{ $isRevoked ? 'is-danger' : ($isMilestoneResolved($raCardState) ? 'is-success' : '') }}">
                    <div class="lpk-v2-target-top">
                        <span class="lpk-v2-target-label">Target Kunjungan / Rencana (Bulan 54)</span>
                        <a href="{{ route('calendar.index', ['view' => 'month', 'date' => $ra['target_date']?->toDateString()]) }}" class="lpk-v2-target-cal-btn" title="Buka di kalender">
                            <x-icon name="calendar" size="11" />
                            <span>Kalender</span>
                        </a>
                    </div>
                    <div class="lpk-v2-target-date">
                        {{ $ra['target_date'] ? $ra['target_date']->translatedFormat('d M Y') : '-' }}
                    </div>
                    <div class="lpk-v2-target-diff {{ $isRevoked ? 'text-danger' : '' }}">
                        @if($isRevoked)
                            <x-icon name="alert-triangle" size="12" />
                            <span>Mendatang &ndash; belum terjadwal</span>
                        @elseif($isMilestoneResolved($raCardState))
                            <x-icon name="check" size="12" />
                            <span>Terlaksana</span>
                        @else
                            <span>Akan Datang &ndash; belum terjadwal</span>
                        @endif
                    </div>
                </div>

                <div class="lpk-v2-ms-meta">
                    <div class="lpk-v2-ms-row">
                        <span>{{ $isRevoked ? 'Dibatalkan:' : 'Pelaksanaan:' }}</span>
                        <span class="lpk-v2-ms-val">{{ $isRevoked ? ($lpk->expired_at ? $lpk->expired_at->translatedFormat('d M Y') : '-') : ($linkedRA && $linkedRA->start_at ? $linkedRA->start_at->translatedFormat('d M Y') : '-') }}</span>
                    </div>
                    <div class="lpk-v2-ms-row">
                        <span>Masa berlaku (Bulan 60):</span>
                        <span class="lpk-v2-ms-val">{{ $lpk->expired_at ? $lpk->expired_at->translatedFormat('d M Y') : '-' }}</span>
                    </div>
                </div>

                <div class="lpk-v2-ms-footer {{ $isRevoked ? 'is-warn' : '' }}">
                    @if($isRevoked)
                        <x-icon name="alert-circle" size="12" />
                        <span>Perlu pengajuan re-akreditasi baru</span>
                    @else
                        <x-icon name="file-text" size="12" />
                        <span>{{ $linkedRAList->count() }} agenda asesmen terlaksana</span>
                    @endif
                </div>
            </div>
        </div>
    </div>

    {{-- 4. Workspace 2-Kolom (65% / 35%) --}}
    <div class="lpk-v2-workspace-grid">
        {{-- KOLOM KIRI (65%) --}}
        <div class="lpk-v2-col-left">
            {{-- Kartu A: Profil & Legalitas --}}
            <div class="lpk-v2-card" id="card-profil-legalitas">
                <div class="lpk-v2-card-head">
                    <h3 class="lpk-v2-card-title">
                        <x-icon name="briefcase" size="16" class="text-primary" />
                        <span>Profil &amp; legalitas</span>
                    </h3>
                    @if($canManageLpk)
                        <a href="{{ route('lpks.edit', $lpk) }}" class="lpk-v2-card-link">Ubah</a>
                    @endif
                </div>

                <div class="lpk-v2-profile-summary">
                    <div class="lpk-v2-avatar-badge">LPK</div>
                    <div class="lpk-v2-profile-names">
                        <h4 class="lpk-v2-profile-inst">{{ $lpk->name }}</h4>
                        <p class="lpk-v2-profile-sub">{{ $distinctOrg ? $distinctOrg . ' • ' : ($lpk->accreditation_type ? $lpk->accreditation_type . ' • ' : '') }}{{ $displayCity }}</p>
                    </div>
                </div>

                <div class="lpk-v2-legal-caption">LEGALITAS</div>
                <div class="lpk-v2-legal-grid">
                    <div class="lpk-v2-legal-item">
                        <span class="lpk-v2-legal-label">Nomor Registrasi</span>
                        <div class="lpk-v2-legal-val">
                            <span>{{ $lpk->no_reg ?: '-' }}</span>
                            @if($lpk->no_reg)
                                <button type="button" class="button ghost button-xs" style="padding: 1px 4px; border: none; background: transparent; cursor: pointer;" onclick="navigator.clipboard.writeText('{{ $lpk->no_reg }}')" title="Salin nomor registrasi">
                                    <x-icon name="copy" size="12" />
                                </button>
                            @endif
                        </div>
                    </div>

                    <div class="lpk-v2-legal-item">
                        <span class="lpk-v2-legal-label">Nomor akreditasi</span>
                        <div class="lpk-v2-legal-val">
                            <strong>{{ $lpk->accreditation_number ?: ($lpk->registration_number ?: '-') }}</strong>
                        </div>
                    </div>

                    <div class="lpk-v2-legal-item">
                        <span class="lpk-v2-legal-label">Jenis akreditasi</span>
                        <div class="lpk-v2-legal-val">
                            @if($lpk->accreditation_type)
                                <span>{{ $lpk->accreditation_type }}</span>
                            @else
                                <span style="color: #b45309; display: inline-flex; align-items: center; gap: 4px; font-size: 12.5px;">
                                    <x-icon name="alert-circle" size="13" />
                                    <span>Belum diisi</span>
                                </span>
                            @endif
                        </div>
                    </div>

                    <div class="lpk-v2-legal-item">
                        <span class="lpk-v2-legal-label">Masa berlaku</span>
                        <div class="lpk-v2-legal-val">
                            <span>{{ $lpk->certificate_date ? $lpk->certificate_date->translatedFormat('d M Y') : '-' }} &ndash; {{ $lpk->expired_at ? $lpk->expired_at->translatedFormat('d M Y') : '-' }}</span>
                            @if($lpk->certificate_date && $lpk->expired_at)
                                <span class="lpk-v2-pill-main">{{ max(1, round($lpk->certificate_date->diffInYears($lpk->expired_at))) }} tahun</span>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            {{-- Kartu B: Ruang Lingkup Akreditasi --}}
            <div class="lpk-v2-card" id="card-ruang-lingkup">
                <div class="lpk-v2-card-head">
                    <h3 class="lpk-v2-card-title">
                        <x-icon name="layers" size="16" class="text-primary" />
                        <span>Ruang lingkup akreditasi</span>
                    </h3>
                    @if($canManageLpk)
                        <a href="{{ route('lpks.edit', $lpk) }}#form-scope-block" class="lpk-v2-card-link">Kelola</a>
                    @endif
                </div>

                @php
                    $scopeItems = [];
                    if ($lpk->scope) {
                        $rawParts = preg_split('/[,;\n\r]+/u', $lpk->scope);
                        foreach ($rawParts as $p) {
                            $trimmed = trim($p);
                            if ($trimmed !== '') {
                                $scopeItems[] = $trimmed;
                            }
                        }
                    }
                    $scopeCount = count($scopeItems);
                @endphp

                @if($scopeCount > 0)
                    <div class="lpk-v2-scope-meta-bar">
                        <span class="lpk-v2-scope-count-badge">{{ $scopeCount }} parameter uji</span>
                        <span>Gulir daftar untuk melihat seluruhnya</span>
                    </div>
                    <div class="lpk-v2-scope-scroll-box">
                        @foreach($scopeItems as $item)
                            <span class="lpk-v2-scope-chip">{{ $item }}</span>
                        @endforeach
                    </div>
                    <p class="lpk-v2-scope-note">
                        Laboratorium kerja sama ini diakreditasi oleh Komite Akreditasi Nasional (KAN) untuk seluruh parameter uji di atas yang tercantum dalam sertifikat {{ $lpk->accreditation_number ?: ($lpk->registration_number ?: '-') }}.
                    </p>
                @else
                    <div class="lpk-v2-scope-meta-bar">
                        <span class="lpk-v2-scope-count-badge">24 parameter uji &bull; 3 kategori</span>
                        <span>Gulir daftar untuk melihat seluruhnya</span>
                    </div>
                    <div class="lpk-v2-scope-categories">
                        <div class="lpk-v2-scope-cat-row">
                            <div class="lpk-v2-scope-cat-header">
                                <span class="lpk-v2-scope-num">01</span>
                                <span class="lpk-v2-scope-name">Tanah</span>
                                <span class="lpk-v2-scope-count">8 parameter</span>
                            </div>
                            <div class="lpk-v2-scope-params">Kadar air, berat jenis, batas Atterberg, CBR, proctor, analisis saringan</div>
                        </div>
                        <div class="lpk-v2-scope-cat-row">
                            <div class="lpk-v2-scope-cat-header">
                                <span class="lpk-v2-scope-num">02</span>
                                <span class="lpk-v2-scope-name">Agregat</span>
                                <span class="lpk-v2-scope-count">5 parameter</span>
                            </div>
                            <div class="lpk-v2-scope-params">Berat jenis & penyerapan, abrasi Los Angeles, kelekatan aspal, indeks kepipihan, kadar lumpur</div>
                        </div>
                        <div class="lpk-v2-scope-cat-row">
                            <div class="lpk-v2-scope-cat-header">
                                <span class="lpk-v2-scope-num">03</span>
                                <span class="lpk-v2-scope-name">Aspal</span>
                                <span class="lpk-v2-scope-count">4 parameter</span>
                            </div>
                            <div class="lpk-v2-scope-params">Penetrasi, titik lembek, daktilitas, berat jenis</div>
                        </div>
                    </div>
                @endif
            </div>

            {{-- Kartu C: Lokasi & Kontak (Multilokasi Fasilitas) --}}
            @include('lpks.partials.locations-card')
        </div>

        {{-- KOLOM KANAN (35% SIDEBAR) --}}
        <div class="lpk-v2-col-right">
            {{-- Kartu D: Tim PIC --}}
            <div class="lpk-v2-card" id="card-tim-pic">
                <div class="lpk-v2-card-head">
                    <h3 class="lpk-v2-card-title">
                        <x-icon name="users" size="16" class="text-primary" />
                        <span>Tim PIC</span>
                    </h3>
                    @if($canManageLpk)
                        <button type="button" class="lpk-v2-card-link" onclick="window.openModal('modal-manage-team-pic')" style="background: none; border: none; cursor: pointer; padding: 0; font-family: inherit; font-size: 13px; font-weight: 600; color: #0075de;">Kelola</button>
                    @endif
                </div>

                <div class="lpk-v2-team-stack">
                    {{-- PIC Utama --}}
                    <div class="lpk-v2-team-row">
                        <div class="lpk-v2-team-info">
                            <div class="lpk-v2-team-avatar">
                                {{ $lpk->pic ? $lpk->pic->initials : 'HW' }}
                            </div>
                            <div class="lpk-v2-team-text">
                                <span class="lpk-v2-team-name">{{ $lpk->pic ? $lpk->pic->name : 'Belum Ditugaskan' }}</span>
                                <span class="lpk-v2-team-role">{{ $lpk->pic && $lpk->pic->isAdmin() ? 'Ketua Tim' : 'Ketua LPK' }}</span>
                            </div>
                        </div>
                        @if($canManageLpk)
                            <button type="button" class="button ghost button-xs" onclick="window.openModal('modal-manage-team-pic')" style="border: none; background: transparent; color: var(--muted); cursor: pointer;" title="Kelola Tim PIC">
                                <x-icon name="more-vertical" size="14" />
                            </button>
                        @endif
                    </div>

                    @php
                        $renderedIds = $lpk->pic_id ? [$lpk->pic_id] : [];
                    @endphp

                    {{-- Anggota Kolaborasi ($lpk->members) --}}
                    @if($lpk->members && $lpk->members->isNotEmpty())
                        @foreach($lpk->members as $member)
                            @php $renderedIds[] = $member->id; @endphp
                            <div class="lpk-v2-team-row">
                                <div class="lpk-v2-team-info">
                                    <div class="lpk-v2-team-avatar" style="background: #f1f5f9; color: #475569;">
                                        {{ $member->initials }}
                                    </div>
                                    <div class="lpk-v2-team-text">
                                        <span class="lpk-v2-team-name">{{ $member->name }}</span>
                                        <span class="lpk-v2-team-role">{{ $member->pivot->role === 'lead' ? 'PIC Pendamping' : 'Viewer (Tim LPK)' }}</span>
                                    </div>
                                </div>
                                @if($canManageLpk)
                                    <button type="button" class="button ghost button-xs" onclick="window.openModal('modal-manage-team-pic')" style="border: none; background: transparent; color: var(--muted); cursor: pointer;" title="Kelola Tim PIC">
                                        <x-icon name="more-vertical" size="14" />
                                    </button>
                                @endif
                            </div>
                        @endforeach
                    @endif

                    {{-- Linked Viewers ($lpk->pic->linkedViewers) --}}
                    @if($lpk->pic && $lpk->pic->linkedViewers && $lpk->pic->linkedViewers->isNotEmpty())
                        @foreach($lpk->pic->linkedViewers as $viewer)
                            @if(! in_array($viewer->id, $renderedIds, true))
                                @php $renderedIds[] = $viewer->id; @endphp
                                <div class="lpk-v2-team-row">
                                    <div class="lpk-v2-team-info">
                                        <div class="lpk-v2-team-avatar" style="background: #f1f5f9; color: #475569;">
                                            {{ $viewer->initials }}
                                        </div>
                                        <div class="lpk-v2-team-text">
                                            <span class="lpk-v2-team-name">{{ $viewer->name }}</span>
                                            <span class="lpk-v2-team-role">Viewer (Tautan Akun)</span>
                                        </div>
                                    </div>
                                    @if($canManageLpk)
                                        <button type="button" class="button ghost button-xs" onclick="window.openModal('modal-manage-team-pic')" style="border: none; background: transparent; color: var(--muted); cursor: pointer;" title="Kelola Tim PIC">
                                            <x-icon name="more-vertical" size="14" />
                                        </button>
                                    @endif
                                </div>
                            @endif
                        @endforeach
                    @endif

                    @if(count($renderedIds) <= 1)
                        <div style="padding: 10px 14px; background: var(--surface-subtle); border: 1px dashed var(--line); border-radius: 6px; font-size: 11.5px; color: var(--muted); display: flex; align-items: center; justify-content: space-between;">
                            <span>Belum ada anggota staf tambahan</span>
                            @if($canManageLpk)
                                <button type="button" onclick="window.openModal('modal-manage-team-pic')" style="background: none; border: none; color: #0075de; font-size: 11.5px; font-weight: 600; cursor: pointer; padding: 0;">+ Tautkan</button>
                            @endif
                        </div>
                    @endif
                </div>
            </div>

            {{-- Kartu E: Status Siklus & Segmented Progress Bar --}}
            <div class="lpk-v2-card" id="card-status-siklus">
                <div class="lpk-v2-card-head">
                    <h3 class="lpk-v2-card-title">
                        <x-icon name="bar-chart-2" size="16" class="text-primary" />
                        <span>Status Siklus</span>
                    </h3>
                    <div style="display: flex; align-items: center; gap: 8px;">
                        <a href="{{ $calStatusSiklusUrl }}" class="button ghost button-xs lpk-keterangan-cal-btn" title="Buka langsung agenda siklus ini di kalender">
                            <x-icon name="calendar" size="12" />
                            <span>Lihat di Kalender</span>
                        </a>
                        <button type="button" class="lpk-v2-card-link" onclick="window.openModal('modal-riwayat-siklus')" style="background: none; border: none; cursor: pointer; padding: 0;">Riwayat</button>
                    </div>
                </div>

                <div class="lpk-v2-cycle-status-wrap">
                    <div class="lpk-v2-cycle-top-row">
                        <div class="lpk-v2-cycle-title">
                            <span>Siklus 3 &ndash; {{ $focusLabel }}</span>
                            <span class="lpk-v2-status-pill {{ $isRevoked ? 'pill-danger' : 'pill-success' }}">
                                {{ $isRevoked ? 'Dicabut' : 'Aktif' }}
                            </span>
                        </div>
                        <span class="lpk-v2-cycle-stages">{{ $resolvedStagesCount }} dari 3 tahap</span>
                    </div>

                    @php
                        $s1SegmentClass = match(true) {
                            $isMilestoneResolved($s1CardState) => 'is-complete',
                            $s1CardState['badge_label'] === 'Dicabut' => 'is-revoked',
                            in_array($s1CardState['badge_label'], ['Lewat Jatuh Tempo', 'Reminder S1', 'Kedaluwarsa']) => 'is-warn',
                            $currentFocusCode === 'S1' => 'is-active',
                            default => 'is-pending',
                        };

                        $s2SegmentClass = match(true) {
                            $isMilestoneResolved($s2CardState) => 'is-complete',
                            $s2CardState['badge_label'] === 'Dicabut' => 'is-revoked',
                            in_array($s2CardState['badge_label'], ['Lewat Jatuh Tempo', 'Reminder S2', 'Kedaluwarsa']) => 'is-warn',
                            $currentFocusCode === 'S2' => 'is-active',
                            default => 'is-pending',
                        };

                        $raSegmentClass = match(true) {
                            $isRevoked || $raCardState['badge_label'] === 'Dicabut' => 'is-revoked',
                            $isMilestoneResolved($raCardState) => 'is-complete',
                            in_array($raCardState['badge_label'], ['Lewat Jatuh Tempo', 'Reminder RA', 'Kedaluwarsa']) => 'is-warn',
                            $currentFocusCode === 'RA' => 'is-active',
                            default => 'is-pending',
                        };
                    @endphp

                    <div class="lpk-v2-segmented-bar">
                        <div class="lpk-v2-segment {{ $s1SegmentClass }}" title="Surveilen 1"></div>
                        <div class="lpk-v2-segment {{ $s2SegmentClass }}" title="Surveilen 2"></div>
                        <div class="lpk-v2-segment {{ $raSegmentClass }}" title="Re-akreditasi"></div>
                    </div>
                    <div class="lpk-v2-segment-labels">
                        <span>Surveilen 1</span>
                        <span>Surveilen 2</span>
                        <span>Re-akreditasi</span>
                    </div>

                    <div class="lpk-v2-cycle-meta-list">
                        @if($isRevoked)
                            <div class="lpk-v2-cycle-meta-item">
                                <span>Tahap terakhir:</span>
                                <strong>{{ $focusLabel }} &ndash; dibatalkan</strong>
                            </div>
                            <div class="lpk-v2-cycle-meta-item">
                                <span>Pencabutan:</span>
                                <strong>{{ $lpk->expired_at ? $lpk->expired_at->translatedFormat('d M Y') : '12 Feb 2026' }}</strong>
                            </div>
                            <div class="lpk-v2-cycle-meta-item">
                                <span>Pengajuan ulang:</span>
                                <strong>{{ $lpk->expired_at ? 'paling cepat ' . $lpk->expired_at->copy()->addMonths(6)->translatedFormat('d M Y') : '12 Agu 2026' }}</strong>
                            </div>
                        @else
                            <div class="lpk-v2-cycle-meta-item">
                                <span>Tahap aktif:</span>
                                <strong>{{ $focusLabel }}</strong>
                            </div>
                            <div class="lpk-v2-cycle-meta-item">
                                <span>Target kunjungan:</span>
                                <strong>{{ $focusTargetDate ? ($focusTargetDate instanceof \Carbon\CarbonInterface ? $focusTargetDate->translatedFormat('d M Y') : \Carbon\Carbon::parse($focusTargetDate)->translatedFormat('d M Y')) : '-' }}</strong>
                            </div>
                            <div class="lpk-v2-cycle-meta-item">
                                <span>Masa berlaku siklus:</span>
                                <strong>{{ $lpk->expired_at ? $lpk->expired_at->translatedFormat('d M Y') : '-' }}</strong>
                            </div>
                        @endif
                    </div>
                </div>
            </div>

            {{-- Kartu F: Catatan PIC & Quick Input --}}
            <div class="lpk-v2-card" id="card-catatan-pic">
                <div class="lpk-v2-card-head">
                    <h3 class="lpk-v2-card-title">
                        <x-icon name="message-square" size="16" class="text-primary" />
                        <span>Catatan PIC</span>
                        <span class="lpk-v2-roadmap-badge">{{ $lpk->notes ? count(array_filter(explode("\n", $lpk->notes))) : '0' }}</span>
                    </h3>
                    @if($canManageLpk)
                        <button type="button" class="lpk-v2-card-link" onclick="window.openModal('modal-input-keterangan')" style="border: none; background: transparent; cursor: pointer;">
                            <span>{{ $lpk->notes ? 'Ubah Keterangan' : 'Input Keterangan' }}</span>
                        </button>
                    @endif
                </div>

                <div class="lpk-v2-notes-feed">
                    @if($lpk->notes)
                        <div class="lpk-v2-note-item">
                            <div class="lpk-v2-team-avatar" style="width: 28px; height: 28px; font-size: 11px;">
                                {{ auth()->user()?->initials ?: 'HW' }}
                            </div>
                            <div class="lpk-v2-note-content">
                                <div class="lpk-v2-note-meta">
                                    <strong>{{ auth()->user()?->name ?: 'Dr. Hendra Wijaya' }}</strong>
                                    <span>{{ now()->translatedFormat('d M Y') }}</span>
                                </div>
                                <p class="lpk-v2-note-text">{!! nl2br(e($lpk->notes)) !!}</p>
                            </div>
                        </div>
                    @else
                        <div class="lpk-v2-notes-empty-state">
                            <div class="lpk-v2-notes-empty-icon">
                                <x-icon name="message-square" size="18" />
                            </div>
                            <p class="lpk-v2-notes-empty-text">Belum ada keterangan.</p>
                            <span class="lpk-v2-notes-empty-sub">Tulis catatan koordinasi atau pengawasan untuk tim PIC pada kolom di bawah.</span>
                        </div>
                    @endif
                </div>

                @if($canManageLpk)
                    <form method="POST" action="{{ route('lpks.notes.update', $lpk) }}" class="lpk-v2-quick-note-box">
                        @csrf
                        <textarea name="notes" class="lpk-v2-note-input" rows="2" placeholder="Tulis keterangan untuk PIC..."></textarea>
                        <button type="submit" class="lpk-v2-note-btn">Simpan keterangan</button>
                    </form>
                @endif
            </div>
        </div>
    </div>

    {{-- 5. Tabel Asesmen Lebar Penuh (100% Full Width di Bawah) --}}
    <div class="lpk-v2-assessments-card" id="card-asesmen-full">
        <div class="lpk-v2-as-header">
            <h3 class="lpk-v2-card-title">
                <x-icon name="calendar" size="16" class="text-primary" />
                <span>Asesmen</span>
                <span class="lpk-v2-roadmap-badge">{{ $lpk->assessments->count() }}</span>
            </h3>

            <div class="lpk-v2-as-controls">
                <div class="lpk-v2-tabs" role="tablist">
                    <button type="button" class="lpk-v2-tab-btn is-active" data-filter="all">Semua</button>
                    <button type="button" class="lpk-v2-tab-btn" data-filter="scheduled">Terjadwal</button>
                    <button type="button" class="lpk-v2-tab-btn" data-filter="completed">Selesai</button>
                </div>
                @if($canManageLpk)
                    <a href="{{ route('assessments.create', ['lpk_id' => $lpk->id]) }}" class="lpk-v2-as-add-btn" title="Jadwalkan agenda asesmen">
                        <x-icon name="calendar" size="13" />
                        <span>Jadwalkan asesmen</span>
                    </a>
                @endif
            </div>
        </div>

        <div class="lpk-v2-table-wrap">
            <table class="lpk-v2-table" id="table-assessments-v2">
                <thead>
                    <tr>
                        <th style="width: 32%;">AGENDA</th>
                        <th style="width: 20%;">TANGGAL</th>
                        <th style="width: 24%;">LOKASI</th>
                        <th style="width: 14%;">STATUS</th>
                        <th style="width: 10%; text-align: right;">AKSI</th>
                    </tr>
                </thead>
                <tbody>
                    @forelse($lpk->assessments->sortBy('start_at') as $item)
                        @php
                            $isCompletedStatus = in_array($item->status, ['COMPLETED', 'PASSED', 'SATISFIED']);
                            $isScheduledStatus = in_array($item->status, ['SCHEDULED', 'PLANNED', 'IN_PROGRESS']);
                            $rowFilterType = $isCompletedStatus ? 'completed' : ($isScheduledStatus ? 'scheduled' : 'other');
                            $locName = $item->lpkLocation?->name ?: ($lpk->city ?: 'Padang');
                            $targetHref = isset($owner) ? route('account-links.assessments.show', [$owner, $item]) : route('assessments.show', $item);
                        @endphp
                        <tr data-filter-type="{{ $rowFilterType }}">
                            <td>
                                <div class="lpk-v2-as-title-cell">
                                    <span class="lpk-v2-as-name">{{ $item->display_title }}</span>
                                    <span class="lpk-v2-as-cycle">Siklus {{ $item->cycle_year ?: '1' }} &bull; {{ $item->assessment_type ?: 'dokumen & lapangan' }}</span>
                                    @if($item->sk_number)
                                        <div style="font-size: 11px; color: var(--muted); margin-top: 2px;">
                                            <span>SK KAN: <strong>{{ $item->sk_number }}</strong></span>
                                            @if($item->sk_lead_time_days !== null)
                                                &bull; <strong class="assessment-duration-lead">Durasi: {{ $item->sk_lead_time_days }} hari</strong>
                                            @endif
                                        </div>
                                    @endif
                                </div>
                            </td>
                            <td>
                                <span style="font-weight: 500;">
                                    {{ $item->start_at ? $item->start_at->translatedFormat('d M Y') : '-' }}
                                </span>
                            </td>
                            <td>
                                <span>{{ $locName }}</span>
                            </td>
                            <td>
                                <x-status :value="$item->status" />
                            </td>
                            <td style="text-align: right;">
                                <a href="{{ $targetHref }}" class="lpk-v2-as-link">
                                    <span>Detail</span>
                                    <x-icon name="chevron-right" size="12" />
                                </a>
                            </td>
                        </tr>
                    @empty
                        <tr class="lpk-v2-empty-table-row">
                            <td colspan="5" style="text-align: center; padding: 42px 16px; color: var(--muted, #64748b);">
                                <div style="display: flex; flex-direction: column; align-items: center; justify-content: center; gap: 8px;">
                                    <div style="width: 38px; height: 38px; border-radius: 50%; background: var(--surface-subtle, #f8fafc); border: 1px solid var(--line, #e2e8f0); display: flex; align-items: center; justify-content: center; color: var(--muted, #64748b);">
                                        <x-icon name="calendar" size="18" />
                                    </div>
                                    <p style="margin: 0; font-size: 13.5px; font-weight: 600; color: var(--ink, #0f172a);">Belum ada agenda asesmen</p>
                                    <p style="margin: 0; font-size: 12.5px; color: var(--muted, #64748b);">Laboratorium ini belum memiliki riwayat atau jadwal asesmen yang tercatat.</p>
                                    @if($canManageLpk)
                                        <a href="{{ route('assessments.create', ['lpk_id' => $lpk->id]) }}" class="button primary button-xs" style="margin-top: 6px; display: inline-flex; align-items: center; gap: 6px;">
                                            <x-icon name="plus" size="13" />
                                            <span>Jadwalkan Asesmen Pertama</span>
                                        </a>
                                    @endif
                                </div>
                            </td>
                        </tr>
                    @endforelse
                    <tr id="lpk-v2-empty-filter-row" style="display: none;">
                        <td colspan="5" style="text-align: center; padding: 28px 16px; color: var(--muted, #64748b);">
                            <p style="margin: 0; font-size: 13px; font-weight: 500;">Tidak ada agenda asesmen dengan status ini.</p>
                        </td>
                    </tr>
                </tbody>
            </table>
        </div>

        {{-- Footer Alert Cabang --}}
        @php
            $unassessedBranch = $lpk->locations->where('is_main', false)->filter(fn($l) => ! $l->latestAssessment())->first();
        @endphp
        @if($unassessedBranch)
            <div class="lpk-v2-as-alert-footer">
                <span style="display: inline-flex; align-items: center; gap: 6px;">
                    <x-icon name="info" size="14" class="text-primary" />
                    <span>Belum ada agenda berikutnya. Cabang {{ $unassessedBranch->name }} belum pernah diasesmen di lokasi ini.</span>
                </span>
                @if($canManageLpk)
                    <a href="{{ route('assessments.create', ['lpk_id' => $lpk->id, 'lpk_location_id' => $unassessedBranch->id]) }}">
                        <span>Jadwalkan asesmen di cabang</span>
                    </a>
                @endif
            </div>
        @endif
    </div>

    {{-- Modals Modal Helper --}}
    @include('lpks.partials.modal-lpk-location')
    @include('lpks.partials.modal-team-pic')

    {{-- Modal Input Keterangan PIC --}}
    @if($canManageLpk)
        <div class="simasadi-modal" id="modal-input-keterangan" role="dialog" aria-modal="true" aria-labelledby="modal-input-keterangan-title">
            <div class="simasadi-modal-backdrop" onclick="window.closeModal('modal-input-keterangan')"></div>
            <div class="simasadi-modal-dialog">
                <div class="simasadi-modal-header">
                    <h4 id="modal-input-keterangan-title">{{ $lpk->notes ? 'Ubah Keterangan LPK' : 'Input Keterangan LPK' }}</h4>
                    <button type="button" class="simasadi-modal-close" data-modal-close onclick="window.closeModal('modal-input-keterangan')" aria-label="Tutup modal">&times;</button>
                </div>
                <form method="POST" action="{{ route('lpks.notes.update', $lpk) }}">
                    @csrf
                    <div class="simasadi-modal-body">
                        <p style="margin: 0 0 12px 0; font-size: 13px; color: var(--muted);">
                            Catatan ini akan tampil di kartu komunikasi PIC dan laporan pemantauan.
                        </p>
                        <div class="form-group">
                            <label for="lpk-notes-textarea" class="field-label" style="font-size: 12px; font-weight: 600;">Keterangan / Catatan Khusus</label>
                            <textarea id="lpk-notes-textarea"
                                      name="notes"
                                      class="input-textarea"
                                      rows="5"
                                      maxlength="1000"
                                      style="width: 100%; box-sizing: border-box; font-size: 13px; padding: 10px;"
                                      placeholder="Contoh: Berkas asesmen telah siap, menunggu jadwal sidang panitia teknis.">{{ old('notes', $lpk->notes) }}</textarea>
                        </div>
                    </div>
                    <div class="simasadi-modal-footer">
                        <button type="button" class="button ghost" data-modal-close onclick="window.closeModal('modal-input-keterangan')">Batal</button>
                        <button type="submit" class="button primary">Simpan Keterangan</button>
                    </div>
                </form>
            </div>
        </div>
    @endif

    {{-- Modal Riwayat Siklus & Kepatuhan KAN --}}
    <div class="simasadi-modal" id="modal-riwayat-siklus" role="dialog" aria-modal="true" aria-labelledby="modal-riwayat-siklus-title">
        <div class="simasadi-modal-backdrop" onclick="window.closeModal('modal-riwayat-siklus')"></div>
        <div class="simasadi-modal-box modal-lg" style="max-width: 660px;">
            <div class="simasadi-modal-head">
                <div class="modal-title-group">
                    <div class="modal-title-icon-badge">
                        <x-icon name="clock" size="18" />
                    </div>
                    <div>
                        <h4 id="modal-riwayat-siklus-title" style="font-size: 16px; font-weight: 700;">Riwayat Siklus Pengawasan &amp; Re-Akreditasi</h4>
                        <p style="margin: 2px 0 0 0; font-size: 11.5px; color: var(--muted);">
                            Siklus 5 Tahunan KAN &bull; Periode {{ $cycleStart ? $cycleStart->format('Y') : '2021' }} &ndash; {{ $cycleEnd ? $cycleEnd->format('Y') : '2026' }}
                        </p>
                    </div>
                </div>
                <button type="button" class="simasadi-modal-close" data-modal-close onclick="window.closeModal('modal-riwayat-siklus')" aria-label="Tutup modal">&times;</button>
            </div>

            <div class="simasadi-modal-body" style="padding: 18px 24px;">
                {{-- Ringkasan Siklus --}}
                <div class="lpk-v2-modal-summary-grid">
                    <div class="lpk-v2-modal-summary-item">
                        <span class="lpk-v2-modal-summary-label">Nomor Akreditasi</span>
                        <span class="lpk-v2-modal-summary-val">{{ $lpk->accreditation_number ?: ($lpk->registration_number ?: '-') }}</span>
                    </div>
                    <div class="lpk-v2-modal-summary-item">
                        <span class="lpk-v2-modal-summary-label">Masa Berlaku Siklus</span>
                        <span class="lpk-v2-modal-summary-val" style="font-size: 12px;">{{ $lpk->certificate_date ? $lpk->certificate_date->translatedFormat('d M Y') : '-' }} &ndash; {{ $lpk->expired_at ? $lpk->expired_at->translatedFormat('d M Y') : '-' }}</span>
                    </div>
                    <div class="lpk-v2-modal-summary-item">
                        <span class="lpk-v2-modal-summary-label">Status Saat Ini</span>
                        <div style="margin-top: 2px;">
                            <x-status :value="$lpk->dynamic_status" />
                        </div>
                    </div>
                </div>

                {{-- Timeline 3 Tahap KAN --}}
                <div class="lpk-v2-modal-timeline">
                    {{-- 1. Surveilen 1 --}}
                    @php
                        $s1IsComplete = $isMilestoneResolved($s1CardState);
                        $s1IsRevoked = $s1CardState['badge_label'] === 'Dicabut';
                    @endphp
                    <div class="lpk-v2-timeline-item {{ $s1IsComplete ? 'is-complete' : ($s1IsRevoked ? 'is-revoked' : ($currentFocusCode === 'S1' ? 'is-active-step' : '')) }}">
                        <div class="lpk-v2-timeline-dot">
                            @if($s1IsComplete)
                                <x-icon name="check" size="12" />
                            @else
                                1
                            @endif
                        </div>
                        <div class="lpk-v2-tl-head">
                            <h5 class="lpk-v2-tl-title">Surveilen 1 (S1) &ndash; Dokumen &amp; Lapangan</h5>
                            <span class="lpk-v2-status-pill {{ $s1IsComplete ? 'pill-success' : 'pill-warn' }}">
                                {{ $s1CardState['badge_label'] }}
                            </span>
                        </div>
                        <p class="lpk-v2-tl-desc">Sesuai regulasi KAN U-01, surveilen pertama dilaksanakan antara bulan ke-15 hingga ke-18 sejak akreditasi diterbitkan.</p>
                        <div class="lpk-v2-tl-grid">
                            <div class="lpk-v2-tl-field">
                                <span class="lpk-v2-tl-label">Target Kunjungan (Bulan 15)</span>
                                <span class="lpk-v2-tl-val">{{ ($s1['visit_target_date'] ?? $s1['target_date'])?->translatedFormat('d M Y') ?: '-' }}</span>
                            </div>
                            <div class="lpk-v2-tl-field">
                                <span class="lpk-v2-tl-label">Batas Waktu (Bulan 18)</span>
                                <span class="lpk-v2-tl-val">{{ $s1['target_date'] ? $s1['target_date']->translatedFormat('d M Y') : '-' }}</span>
                            </div>
                            <div class="lpk-v2-tl-field">
                                <span class="lpk-v2-tl-label">Pelaksanaan Riil</span>
                                <span class="lpk-v2-tl-val">{{ $linkedS1 && $linkedS1->start_at ? $linkedS1->start_at->translatedFormat('d M Y') : 'Belum terlaksana' }}</span>
                            </div>
                            @if($linkedS1 && $linkedS1->sk_number)
                                <div class="lpk-v2-tl-field">
                                    <span class="lpk-v2-tl-label">SK KAN</span>
                                    <span class="lpk-v2-tl-val">{{ $linkedS1->sk_number }}</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- 2. Surveilen 2 --}}
                    @php
                        $s2IsComplete = $isMilestoneResolved($s2CardState);
                        $s2IsRevoked = $s2CardState['badge_label'] === 'Dicabut';
                    @endphp
                    <div class="lpk-v2-timeline-item {{ $s2IsComplete ? 'is-complete' : ($s2IsRevoked ? 'is-revoked' : ($currentFocusCode === 'S2' ? 'is-active-step' : '')) }}">
                        <div class="lpk-v2-timeline-dot">
                            @if($s2IsComplete)
                                <x-icon name="check" size="12" />
                            @else
                                2
                            @endif
                        </div>
                        <div class="lpk-v2-tl-head">
                            <h5 class="lpk-v2-tl-title">Surveilen 2 (S2) &ndash; Dokumen &amp; Lapangan</h5>
                            <span class="lpk-v2-status-pill {{ $s2IsComplete ? 'pill-success' : 'pill-warn' }}">
                                {{ $s2CardState['badge_label'] }}
                            </span>
                        </div>
                        <p class="lpk-v2-tl-desc">Surveilen kedua dilaksanakan pada rentang bulan ke-36 hingga ke-39 untuk memastikan kepatuhan teknis berjalan konsisten.</p>
                        <div class="lpk-v2-tl-grid">
                            <div class="lpk-v2-tl-field">
                                <span class="lpk-v2-tl-label">Target Kunjungan (Bulan 36)</span>
                                <span class="lpk-v2-tl-val">{{ ($s2['visit_target_date'] ?? $s2['target_date'])?->translatedFormat('d M Y') ?: '-' }}</span>
                            </div>
                            <div class="lpk-v2-tl-field">
                                <span class="lpk-v2-tl-label">Batas Waktu (Bulan 39)</span>
                                <span class="lpk-v2-tl-val">{{ $s2['target_date'] ? $s2['target_date']->translatedFormat('d M Y') : '-' }}</span>
                            </div>
                            <div class="lpk-v2-tl-field">
                                <span class="lpk-v2-tl-label">Pelaksanaan Riil</span>
                                <span class="lpk-v2-tl-val">{{ $linkedS2 && $linkedS2->start_at ? $linkedS2->start_at->translatedFormat('d M Y') : 'Belum terlaksana' }}</span>
                            </div>
                            @if($linkedS2 && $linkedS2->sk_number)
                                <div class="lpk-v2-tl-field">
                                    <span class="lpk-v2-tl-label">SK KAN</span>
                                    <span class="lpk-v2-tl-val">{{ $linkedS2->sk_number }}</span>
                                </div>
                            @endif
                        </div>
                    </div>

                    {{-- 3. Re-Akreditasi --}}
                    @php
                        $raIsComplete = $isMilestoneResolved($raCardState);
                    @endphp
                    <div class="lpk-v2-timeline-item {{ $isRevoked ? 'is-revoked' : ($raIsComplete ? 'is-complete' : ($currentFocusCode === 'RA' ? 'is-active-step' : '')) }}">
                        <div class="lpk-v2-timeline-dot">
                            @if($raIsComplete)
                                <x-icon name="check" size="12" />
                            @elseif($isRevoked)
                                <x-icon name="slash" size="11" />
                            @else
                                3
                            @endif
                        </div>
                        <div class="lpk-v2-tl-head">
                            <h5 class="lpk-v2-tl-title">Re-Akreditasi (RA) &ndash; Asesmen Penuh</h5>
                            <span class="lpk-v2-status-pill {{ $isRevoked ? 'pill-danger' : ($raIsComplete ? 'pill-success' : 'pill-warn') }}">
                                {{ $isRevoked ? 'Dicabut' : $raCardState['badge_label'] }}
                            </span>
                        </div>
                        <p class="lpk-v2-tl-desc">Asesmen ulang penuh diajukan mulai bulan ke-48 s/d 51 dan diselesaikan sebelum masa sertifikat 5 tahun (bulan 60) berakhir.</p>
                        <div class="lpk-v2-tl-grid">
                            <div class="lpk-v2-tl-field">
                                <span class="lpk-v2-tl-label">Target Rencana (Bulan 54)</span>
                                <span class="lpk-v2-tl-val">{{ $ra['target_date'] ? $ra['target_date']->translatedFormat('d M Y') : '-' }}</span>
                            </div>
                            <div class="lpk-v2-tl-field">
                                <span class="lpk-v2-tl-label">Masa Berlaku (Bulan 60)</span>
                                <span class="lpk-v2-tl-val">{{ $lpk->expired_at ? $lpk->expired_at->translatedFormat('d M Y') : '-' }}</span>
                            </div>
                            <div class="lpk-v2-tl-field">
                                <span class="lpk-v2-tl-label">Pelaksanaan Riil</span>
                                <span class="lpk-v2-tl-val">{{ $linkedRA && $linkedRA->start_at ? $linkedRA->start_at->translatedFormat('d M Y') : ($isRevoked ? 'Dibatalkan' : 'Belum terlaksana') }}</span>
                            </div>
                            @if($linkedRA && $linkedRA->sk_number)
                                <div class="lpk-v2-tl-field">
                                    <span class="lpk-v2-tl-label">SK KAN</span>
                                    <span class="lpk-v2-tl-val">{{ $linkedRA->sk_number }}</span>
                                </div>
                            @endif
                        </div>
                    </div>
                </div>
            </div>

            <div class="simasadi-modal-foot" style="display: flex; justify-content: space-between; align-items: center; padding: 14px 24px; border-top: 1px solid var(--line); background: var(--surface-subtle);">
                <a href="{{ $calStatusSiklusUrl }}" class="button secondary button-xs" style="display: inline-flex; align-items: center; gap: 5px;">
                    <x-icon name="calendar" size="12" />
                    <span>Buka Kalender Pengawasan</span>
                </a>
                <button type="button" class="button ghost button-xs" data-modal-close onclick="window.closeModal('modal-riwayat-siklus')">Tutup</button>
            </div>
        </div>
    </div>
</div>

{{-- Interactive Tabs Script --}}
<script>
(function () {
    function initTabs() {
        const tabBtns = document.querySelectorAll('.lpk-v2-tab-btn');
        const table = document.getElementById('table-assessments-v2');
        if (!tabBtns.length || !table) return;

        tabBtns.forEach(btn => {
            btn.onclick = function (e) {
                e.preventDefault();
                tabBtns.forEach(b => b.classList.remove('is-active'));
                this.classList.add('is-active');

                const filter = this.getAttribute('data-filter') || 'all';
                const rows = table.querySelectorAll('tbody tr[data-filter-type]');
                if (rows.length === 0) return;
                let visibleCount = 0;

                rows.forEach(row => {
                    const rowType = row.getAttribute('data-filter-type');
                    let shouldShow = false;
                    if (filter === 'all') {
                        shouldShow = true;
                    } else if (filter === 'completed') {
                        shouldShow = (rowType === 'completed');
                    } else if (filter === 'scheduled') {
                        shouldShow = (rowType === 'scheduled');
                    }
                    row.style.display = shouldShow ? '' : 'none';
                    if (shouldShow) visibleCount++;
                });

                const emptyRow = document.getElementById('lpk-v2-empty-filter-row');
                if (emptyRow) {
                    emptyRow.style.display = (visibleCount === 0) ? '' : 'none';
                }
            };
        });
    }

    if (document.readyState === 'loading') {
        document.addEventListener('DOMContentLoaded', initTabs);
    } else {
        initTabs();
    }
    window.addEventListener('simasadi:page-loaded', initTabs);
})();
</script>
