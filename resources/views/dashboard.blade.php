@extends('layouts.app')

@section('title', 'Dasbor | SIMASADI')

@section('content')
    @php
        $isAllClear = empty($globalSurveillanceAlerts)
            && (!isset($urgentTpAssessments) || $urgentTpAssessments->isEmpty());

        $totalSurvAlerts = count($globalSurveillanceAlerts ?? []);
        $totalTpUrgent = isset($urgentTpAssessments) ? $urgentTpAssessments->count() : 0;
        $totalActionRequired = $totalSurvAlerts + $totalTpUrgent;

        // Triage alert calculations
        $hasSurveillanceAlerts = !empty($globalSurveillanceAlerts) || (isset($urgentTpAssessments) && $urgentTpAssessments->isNotEmpty());
        $maxAlerts = 5;
        $tpItems = isset($urgentTpAssessments) && $urgentTpAssessments->isNotEmpty() ? $urgentTpAssessments->take(2) : collect();
        $surveillanceLimit = max(0, $maxAlerts - $tpItems->count());
        $surveillanceList = array_slice($globalSurveillanceAlerts ?? [], 0, $surveillanceLimit);
        $actionCount = count($surveillanceList) + $tpItems->count();

        // Compliance radar & status calculations
        $dueLpkIds = collect($globalSurveillanceAlerts ?? [])->pluck('lpk_id')->unique();
        $dueCount = $dueLpkCount ?? $dueLpkIds->count();
        $suspCount = $suspendedCount ?? 0;
        $revCount = $revokedCount ?? 0;
        $actCount = $activeCount ?? max(0, $lpkCount - ($dueCount + $suspCount + $revCount));

        $totalLpks = max(1, $lpkCount);
        if ($lpkCount === 0) {
            $pctActive = 100;
            $pctDue = 0;
            $pctSuspended = 0;
            $pctRevoked = 0;
        } else {
            $pctActive = round(($actCount / $totalLpks) * 100);
            $pctDue = round(($dueCount / $totalLpks) * 100);
            $pctSuspended = round(($suspCount / $totalLpks) * 100);
            $pctRevoked = max(0, 100 - ($pctActive + $pctDue + $pctSuspended));
        }
    @endphp

    @if($isAllClear)
        <div class="dashboard-safe-banner" role="status" aria-live="polite">
            <div class="dashboard-safe-banner-main">
                <span class="dashboard-safe-banner-icon">
                    <x-icon name="check-circle" size="18" />
                </span>
                <div class="dashboard-safe-banner-text">
                    <strong>Semua Operasional Aman &amp; Terkendali.</strong>
                    <span>Seluruh agenda surveilen dan perbaikan berjalan lancar tanpa ada yang memerlukan tindak lanjut segera.</span>
                </div>
            </div>
            <span class="dashboard-safe-banner-badge">
                <x-icon name="shield-check" size="14" />
                <span>Semua Aman</span>
            </span>
        </div>
    @endif

    <div class="page-heading">
        <div>
            <h1 class="dashboard-greeting">Selamat datang, {{ auth()->user()?->isPic() ? 'PIC Laboratorium Penguji & Kalibrasi.' : auth()->user()->name . '.' }}</h1>
            <p class="lede">
                @if($totalActionRequired > 0)
                    Terdapat <strong>{{ $totalActionRequired }} agenda operasional</strong> ({{ $totalSurvAlerts > 0 ? $totalSurvAlerts . ' surveilen' : '' }}{{ $totalSurvAlerts > 0 && $totalTpUrgent > 0 ? ' dan ' : '' }}{{ $totalTpUrgent > 0 ? $totalTpUrgent . ' perbaikan TP' : '' }}) yang memerlukan tindak lanjut segera.
                @else
                    Seluruh <strong>{{ $lpkCount }} laboratorium binaan</strong> dalam kondisi kepatuhan terkendali dan sesuai siklus KAN.
                @endif
            </p>
        </div>
        <div class="dashboard-header-actions">
            @if(auth()->user()?->isAdmin() || auth()->user()?->isPic())
                <a class="button primary" href="{{ route('lpks.create') }}"><x-icon name="plus" size="16" /><span>Tambah LPK</span></a>
            @endif
            <a class="button secondary" href="{{ route('calendar.index') }}"><x-icon name="calendar" size="16" /><span>Kalender</span></a>
        </div>
    </div>

    {{-- =========================================================================
         TINGKAT 1: INTEGRATED KPI & COMPLIANCE RADAR STRIP
         ========================================================================= --}}
    <section class="dashboard-kpi-health-strip" aria-label="Ringkasan Operasional dan Radar Kepatuhan">
        {{-- KPI 1: LPK Terdaftar --}}
        <a href="{{ route('lpks.index') }}" class="metric">
            <div class="metric-head">
                <span class="metric-label">LPK Terdaftar</span>
                <div class="metric-icon theme-indigo" aria-hidden="true">
                    <x-icon name="lpks" size="18" />
                </div>
            </div>
            <div class="metric-body">
                <strong>{{ $lpkCount }}</strong>
                <small>Total laboratorium binaan terdata</small>
            </div>
        </a>

        {{-- KPI 2: Asesmen Bulan Ini --}}
        <a href="{{ route('assessments.index') }}" class="metric">
            <div class="metric-head">
                <span class="metric-label">Asesmen Bulan Ini</span>
                <div class="metric-icon theme-sky" aria-hidden="true">
                    <x-icon name="calendar" size="18" />
                </div>
            </div>
            <div class="metric-body">
                <strong>{{ $assessmentCount }}</strong>
                <small>{{ $assessmentCount > 0 ? $assessmentCount . ' agenda aktif berjalan' : 'Tidak ada agenda asesmen bulan ini' }}</small>
            </div>
        </a>

        {{-- KPI 3: Tindakan Perbaikan (TP) --}}
        <a href="{{ route('assessments.index', !empty($overdueTpCount) ? ['tp_status' => 'OVERDUE'] : []) }}" class="metric {{ !empty($overdueTpCount) ? 'warn' : '' }}">
            <div class="metric-head">
                <span class="metric-label">Tindakan Perbaikan (TP)</span>
                <div class="metric-icon {{ !empty($overdueTpCount) ? 'theme-rose' : ($activeTpCount > 0 ? 'theme-amber' : 'theme-emerald') }}" aria-hidden="true">
                    <x-icon name="assessments" size="18" />
                </div>
            </div>
            <div class="metric-body">
                <strong>{{ $activeTpCount }}</strong>
                <small>{{ $activeTpCount > 0 ? (!empty($overdueTpCount) ? $overdueTpCount . ' perbaikan melewati batas KAN' : $activeTpCount . ' perbaikan dalam proses') : 'Semua perbaikan selesai' }}</small>
            </div>
        </a>

        {{-- KPI 4: Radar Kepatuhan KAN --}}
        <div class="dashboard-radar-card compliance-meter-card" aria-labelledby="radar-kepatuhan-heading">
            <div class="dashboard-radar-head">
                <span class="dashboard-radar-label" id="radar-kepatuhan-heading">Radar Kepatuhan KAN</span>
                <a href="{{ route('lpks.index') }}" class="dashboard-radar-more-link" title="Buka master direktori laboratorium">
                    <span>Detail</span>
                    <x-icon name="chevron-right" size="13" />
                </a>
            </div>

            {{-- Multi-segment bar --}}
            <div class="compliance-meter-bar" role="img" aria-label="Distribusi Kepatuhan: {{ $actCount }} Aktif Aman, {{ $dueCount }} Jatuh Tempo, {{ $suspCount }} Dibekukan, {{ $revCount }} Dicabut" title="Distribusi Kepatuhan: {{ $actCount }} Aktif, {{ $dueCount }} Jatuh Tempo, {{ $suspCount }} Dibekukan, {{ $revCount }} Dicabut">
                @if($pctActive > 0)
                    <div class="compliance-meter-segment seg-active" data-legend="active" style="width: {{ $pctActive }}%;" aria-hidden="true" title="Aktif Aman: {{ $actCount }} LPK ({{ $pctActive }}%)"></div>
                @endif
                @if($pctDue > 0)
                    <div class="compliance-meter-segment seg-due" data-legend="due" style="width: {{ $pctDue }}%;" aria-hidden="true" title="Jatuh Tempo: {{ $dueCount }} LPK ({{ $pctDue }}%)"></div>
                @endif
                @if($pctSuspended > 0)
                    <div class="compliance-meter-segment seg-suspended" data-legend="suspended" style="width: {{ $pctSuspended }}%;" aria-hidden="true" title="Dibekukan: {{ $suspCount }} LPK ({{ $pctSuspended }}%)"></div>
                @endif
                @if($pctRevoked > 0)
                    <div class="compliance-meter-segment seg-revoked" data-legend="revoked" style="width: {{ $pctRevoked }}%;" aria-hidden="true" title="Dicabut / Tenggang: {{ $revCount }} LPK ({{ $pctRevoked }}%)"></div>
                @endif
            </div>

            {{-- 4 Legend Pills in 2x2 Grid --}}
            <div class="dashboard-radar-grid">
                <a href="{{ route('lpks.index', ['status' => 'ACTIVE']) }}" class="compliance-legend-item dashboard-radar-pill" data-legend="active" title="Filter laboratorium aktif terkendali">
                    <span class="compliance-legend-dot dot-active"></span>
                    <div class="compliance-legend-text">
                        <span class="compliance-legend-label">Aktif Aman</span>
                        <strong class="compliance-legend-val">{{ $actCount }} <small class="pct-sub">({{ $pctActive }}%)</small></strong>
                    </div>
                </a>
                <a href="{{ route('lpks.index', ['surveillance' => 'NEEDS_ACTION']) }}" class="compliance-legend-item dashboard-radar-pill" data-legend="due" title="Filter laboratorium jatuh tempo pengawasan">
                    <span class="compliance-legend-dot dot-due"></span>
                    <div class="compliance-legend-text">
                        <span class="compliance-legend-label">Jatuh Tempo</span>
                        <strong class="compliance-legend-val">{{ $dueCount }} <small class="pct-sub">({{ $pctDue }}%)</small></strong>
                    </div>
                </a>
                <a href="{{ route('lpks.index', ['status' => 'SUSPENDED']) }}" class="compliance-legend-item dashboard-radar-pill" data-legend="suspended" title="Filter laboratorium dibekukan">
                    <span class="compliance-legend-dot dot-suspended"></span>
                    <div class="compliance-legend-text">
                        <span class="compliance-legend-label">Dibekukan</span>
                        <strong class="compliance-legend-val">{{ $suspCount }} <small class="pct-sub">({{ $pctSuspended }}%)</small></strong>
                    </div>
                </a>
                <a href="{{ route('lpks.index', ['status' => 'REVOKED']) }}" class="compliance-legend-item dashboard-radar-pill" data-legend="revoked" title="Filter laboratorium dicabut atau masa tenggang habis">
                    <span class="compliance-legend-dot dot-revoked"></span>
                    <div class="compliance-legend-text">
                        <span class="compliance-legend-label">Dicabut / TG</span>
                        <strong class="compliance-legend-val">{{ $revCount }} <small class="pct-sub">({{ $pctRevoked }}%)</small></strong>
                    </div>
                </a>
            </div>
        </div>
    </section>

    {{-- =========================================================================
         TINGKAT 2: BALANCED OPERATIONAL WORKSPACE (50% / 50%)
         ========================================================================= --}}
    <div class="dashboard-workspace-grid">
        {{-- PANEL A (Kiri, 50%): Perlu Tindak Lanjut / Triage --}}
        <section class="panel dashboard-status-panel {{ $hasSurveillanceAlerts ? 'has-alerts' : '' }}" id="surveillance-section" aria-labelledby="surveillance-heading">
            <div class="panel-head">
                <div class="dashboard-panel-title-wrap">
                    <div class="dashboard-panel-title-row">
                        <h2 id="surveillance-heading">{{ $hasSurveillanceAlerts ? 'Perlu Tindak Lanjut' : 'Status Pengawasan' }}</h2>
                        @if($hasSurveillanceAlerts && $actionCount > 0)
                            <span class="dashboard-panel-count-badge" title="{{ $actionCount }} item perlu tindak lanjut">{{ $actionCount }}</span>
                        @endif
                    </div>
                    <p class="dashboard-panel-subtext">{{ $hasSurveillanceAlerts ? 'Surveilen & perbaikan butuh tindak lanjut' : 'Seluruh siklus surveilen & perbaikan terkendali' }}</p>
                </div>
                <a href="{{ route('lpks.index', ['surveillance' => 'NEEDS_ACTION']) }}" class="dashboard-more-link" aria-label="Lihat semua antrean pengawasan dan perbaikan" title="Buka seluruh antrean pengawasan dan perbaikan">
                    <span>Lihat semua</span>
                    <x-icon name="chevron-right" size="14" />
                </a>
            </div>

            @if(!empty($globalSurveillanceAlerts) || (isset($urgentTpAssessments) && $urgentTpAssessments->isNotEmpty()))
                <div class="dashboard-col-alert-list dashboard-lpk-list">
                    {{-- Peringatan Siklus Surveilen --}}
                    @foreach($surveillanceList as $alert)
                        <a href="{{ route('lpks.show', $alert['lpk_id']) }}" class="dashboard-lpk-item" title="Lihat detail {{ $alert['lpk_name'] }}">
                            <div class="dashboard-lpk-item-main">
                                <div class="dashboard-lpk-item-top">
                                    <span class="dashboard-lpk-reg">{{ $alert['lpk_reg'] }}</span>
                                    <x-status :value="$alert['status']" />
                                    @php
                                        $tDate = null;
                                        if (!empty($alert['target_date'])) {
                                            if ($alert['target_date'] instanceof \Carbon\CarbonInterface) {
                                                $tDate = $alert['target_date'];
                                            } elseif (is_string($alert['target_date'])) {
                                                try {
                                                    $tDate = \Illuminate\Support\Carbon::parse($alert['target_date']);
                                                } catch (\Throwable) {
                                                    $tDate = null;
                                                }
                                            }
                                        }
                                    @endphp
                                    @if($tDate)
                                        @php
                                            $diffDays = (int) now()->startOfDay()->diffInDays($tDate->startOfDay(), false);
                                        @endphp
                                        <span class="badge badge-countdown {{ $diffDays < 0 ? 'status-danger' : ($diffDays <= 14 ? 'status-warn' : 'status-scheduled') }}">
                                            {{ $diffDays < 0 ? 'Terlewat ' . abs($diffDays) . ' Hari' : ($diffDays === 0 ? 'Hari Ini' : 'H-' . $diffDays) }}
                                        </span>
                                    @endif
                                </div>
                                <strong class="dashboard-lpk-name">{{ $alert['lpk_name'] }}</strong>
                                <div class="dashboard-lpk-meta">
                                    <span>{{ $alert['name'] ?? $alert['code'] }}</span>
                                    <span>&bull;</span>
                                    <span>Batas: <strong>{{ $tDate ? $tDate->format('d/m/Y') : '-' }}</strong></span>
                                </div>
                            </div>
                            <div class="dashboard-lpk-arrow" aria-hidden="true">
                                <x-icon name="chevron-right" size="15" />
                            </div>
                        </a>
                    @endforeach

                    {{-- Peringatan Batas Waktu TP KAN --}}
                    @if($tpItems->isNotEmpty())
                        @foreach($tpItems as $assessment)
                            @php $tpBadge = $assessment->tp_sla_badge; @endphp
                            <a href="{{ route('assessments.show', $assessment) }}" class="dashboard-lpk-item" title="Buka detail asesmen {{ $assessment->display_title }}">
                                <div class="dashboard-lpk-item-main">
                                    <div class="dashboard-lpk-item-top">
                                        <span class="dashboard-lpk-reg">{{ $assessment->lpk->accreditation_number ?: $assessment->lpk->registration_number }}</span>
                                        <span class="badge badge-pill badge-tp badge-tp-{{ $tpBadge['type'] }}">{{ $tpBadge['label'] }}</span>
                                        @if($assessment->effective_tp_due_date && !str_contains($tpBadge['label'], 'Terlambat'))
                                            @php
                                                $diffTpDays = (int) now()->startOfDay()->diffInDays($assessment->effective_tp_due_date->startOfDay(), false);
                                            @endphp
                                            <span class="badge badge-countdown {{ $diffTpDays < 0 ? 'status-danger' : ($diffTpDays <= 14 ? 'status-warn' : 'status-scheduled') }}">
                                                {{ $diffTpDays < 0 ? 'Terlewat ' . abs($diffTpDays) . ' Hari' : ($diffTpDays === 0 ? 'Hari Ini' : 'H-' . $diffTpDays) }}
                                            </span>
                                        @endif
                                    </div>
                                    <strong class="dashboard-lpk-name">{{ $assessment->lpk->name }}</strong>
                                    <div class="dashboard-lpk-meta">
                                        <span>{{ $assessment->display_title }}</span>
                                        <span>&bull;</span>
                                        <span>Batas TP: <strong>{{ $assessment->effective_tp_due_date ? $assessment->effective_tp_due_date->format('d/m/Y') : '-' }}</strong></span>
                                    </div>
                                </div>
                                <div class="dashboard-lpk-arrow" aria-hidden="true">
                                    <x-icon name="chevron-right" size="15" />
                                </div>
                            </a>
                        @endforeach
                    @endif
                </div>
            @else
                {{-- Kondisi Aman / All Clear --}}
                <div class="dashboard-health-card">
                    <div class="dashboard-health-main">
                        <span class="dashboard-health-icon">
                            <x-icon name="check-circle" size="18" />
                        </span>
                        <div class="dashboard-health-copy">
                            <strong>Siklus &amp; Tindakan Perbaikan Terkendali</strong>
                            <p>Tidak ada agenda surveilen (S1/S2/RA) yang jatuh tempo ataupun temuan perbaikan yang mendekati batas waktu KAN.</p>
                        </div>
                    </div>
                    <div class="dashboard-health-metrics">
                        <div class="dashboard-health-stat">
                            <span class="stat-label"><span class="stat-dot green"></span>Siklus Surveilen</span>
                            <strong class="stat-val">100% Sesuai Skedul</strong>
                        </div>
                        <div class="dashboard-health-stat">
                            <span class="stat-label"><span class="stat-dot green"></span>Tindakan Perbaikan</span>
                            <strong class="stat-val">0 Menunggak</strong>
                        </div>
                        <div class="dashboard-health-stat">
                            <span class="stat-label"><span class="stat-dot blue"></span>Masa Berlaku LPK</span>
                            <strong class="stat-val">{{ $lpkCount }} Terakreditasi</strong>
                        </div>
                    </div>
                </div>
            @endif
        </section>

        {{-- PANEL B (Kanan, 50%): Agenda & Kunjungan Terdekat --}}
        <section class="panel dashboard-agenda-panel" aria-labelledby="upcoming-assessments-heading">
            <div class="panel-head">
                <div class="dashboard-panel-title-wrap">
                    <div class="dashboard-panel-title-row">
                        <h2 id="upcoming-assessments-heading">Agenda &amp; Kunjungan Terdekat</h2>
                        @if(isset($upcomingAssessments) && $upcomingAssessments->isNotEmpty())
                            <span class="dashboard-panel-count-badge" title="{{ $upcomingAssessments->count() }} asesmen terdekat">{{ $upcomingAssessments->count() }}</span>
                        @endif
                    </div>
                    <p class="dashboard-panel-subtext">Jadwal agenda asesmen dan surveilen mendatang</p>
                </div>
                <a href="{{ route('calendar.index') }}" class="dashboard-more-link" aria-label="Lihat seluruh agenda asesmen di kalender kerja" title="Buka seluruh jadwal di kalender kerja">
                    <span>Lihat semua</span>
                    <x-icon name="chevron-right" size="14" />
                </a>
            </div>
            @if(isset($upcomingAssessments) && $upcomingAssessments->isNotEmpty())
                <div class="agenda-list">
                    @foreach($upcomingAssessments as $assessment)
                        <a href="{{ route('calendar.index', ['view' => 'month', 'date' => $assessment->start_at->toDateString(), 'highlight' => $assessment->id, 'selected' => 1]) }}" class="agenda-card" title="Buka dan sorot agenda ini di kalender">
                            <div class="agenda-card-date">
                                <span class="day">{{ $assessment->start_at->format('d') }}</span>
                                <span class="month">{{ $assessment->start_at->translatedFormat('M') }}</span>
                                <span class="year">{{ $assessment->start_at->format('Y') }}</span>
                            </div>
                            <div class="agenda-card-main">
                                <div class="agenda-card-top">
                                    <span class="agenda-card-type">{{ $assessment->assessment_type_label }}</span>
                                    <x-status :value="$assessment->status" />
                                </div>
                                <strong class="agenda-card-lpk-name">{{ $assessment->lpk->name }}</strong>
                                <div class="agenda-card-sub">{{ $assessment->display_title }}</div>
                            </div>
                        </a>
                    @endforeach
                </div>
            @else
                <div class="dashboard-empty-state">
                    <div class="dashboard-empty-icon-wrap" aria-hidden="true">
                        <x-icon name="calendar" size="24" />
                    </div>
                    <strong class="dashboard-empty-title">Tidak Ada Agenda Asesmen Terdekat</strong>
                    <p class="dashboard-empty-desc">Seluruh jadwal kunjungan lapangan dan surveilen dalam waktu dekat telah selesai atau belum memasuki periode pelaksanaan.</p>
                    <div class="dashboard-empty-actions">
                        <a href="{{ route('calendar.index') }}" class="button secondary button-sm">
                            <x-icon name="calendar" size="14" />
                            <span>Buka Kalender Kerja</span>
                        </a>
                        @if(auth()->user()?->isAdmin())
                            <a href="{{ route('assessments.create') }}" class="button primary button-sm">
                                <x-icon name="plus" size="14" />
                                <span>Jadwalkan Asesmen</span>
                            </a>
                        @endif
                    </div>
                </div>
            @endif
        </section>
    </div>

    {{-- =========================================================================
         TINGKAT 3: MASTER DIREKTORI LABORATORIUM TERKELOLA (100% Full-Width)
         ========================================================================= --}}
    <section class="panel dashboard-directory-panel" aria-labelledby="directory-heading">
        <div class="panel-head">
            <div class="dashboard-panel-title-wrap">
                <div class="dashboard-panel-title-row">
                    <h2 id="directory-heading">Laboratorium Terkelola</h2>
                    <span class="dashboard-panel-count-badge" title="{{ $lpkCount }} total laboratorium binaan">{{ $lpkCount }}</span>
                </div>
                <p class="dashboard-panel-subtext">Daftar laboratorium terdata, masa berlaku akreditasi, dan status siklus KAN</p>
            </div>
            <div class="dashboard-directory-header-actions">
                <div class="dashboard-directory-search">
                    <span class="search-icon-wrap" aria-hidden="true">
                        <x-icon name="search" size="14" />
                    </span>
                    <input type="text"
                           id="directorySearchInput"
                           placeholder="Cari nama / nomor reg..."
                           class="dashboard-search-input"
                           autocomplete="off"
                           spellcheck="false"
                           aria-label="Cari laboratorium di daftar ini">
                    <button type="button"
                            id="directorySearchClear"
                            class="dashboard-search-clear"
                            aria-label="Hapus kata kunci pencarian"
                            style="display: none;">
                        <x-icon name="x" size="12" />
                    </button>
                </div>
                <a href="{{ route('lpks.index') }}" class="dashboard-more-link" aria-label="Lihat master data seluruh laboratorium" title="Buka master direktori laboratorium">
                    <span>Buka Master Direktori</span>
                    <x-icon name="chevron-right" size="14" />
                </a>
            </div>
        </div>

        {{-- Interactive Filter Tabs --}}
        <div class="dashboard-filter-tabs" role="tablist" aria-label="Filter status kepatuhan laboratorium">
            <button type="button" class="filter-tab-btn is-active" data-filter="all" role="tab" aria-selected="true">
                <span>Semua</span>
                <span class="tab-count">({{ $lpkCount }})</span>
            </button>
            <button type="button" class="filter-tab-btn" data-filter="active" role="tab" aria-selected="false">
                <span class="stat-dot green"></span>
                <span>Aktif Aman</span>
                <span class="tab-count">({{ $actCount }})</span>
            </button>
            <button type="button" class="filter-tab-btn" data-filter="due" role="tab" aria-selected="false">
                <span class="stat-dot amber"></span>
                <span>Jatuh Tempo</span>
                <span class="tab-count">({{ $dueCount }})</span>
            </button>
            <button type="button" class="filter-tab-btn" data-filter="suspended" role="tab" aria-selected="false">
                <span class="stat-dot blue"></span>
                <span>Dibekukan</span>
                <span class="tab-count">({{ $suspCount }})</span>
            </button>
            <button type="button" class="filter-tab-btn" data-filter="revoked" role="tab" aria-selected="false">
                <span class="stat-dot red"></span>
                <span>Dicabut / TG</span>
                <span class="tab-count">({{ $revCount }})</span>
            </button>
        </div>

        @if(isset($managedLpks) && $managedLpks->isNotEmpty())
            <div class="dashboard-directory-table-wrap" id="directoryTableWrap">
                <table class="dashboard-directory-table" aria-label="Tabel Laboratorium Terkelola">
                    <thead>
                        <tr>
                            <th scope="col" style="width: 140px;">No. Akreditasi</th>
                            <th scope="col">Nama Lembaga &amp; Laboratorium</th>
                            <th scope="col" style="width: 190px;">Jenis &amp; Lingkup</th>
                            <th scope="col" style="width: 140px;">Masa Berlaku</th>
                            <th scope="col" style="width: 130px;">Status Kepatuhan</th>
                            <th scope="col" style="width: 90px; text-align: right;">Aksi</th>
                        </tr>
                    </thead>
                    <tbody>
                        @foreach($managedLpks as $lpk)
                            @php
                                $dynStatus = $lpk->dynamic_status;
                                $statusTags = [];

                                // Tandai jika memiliki alert surveilen / jatuh tempo
                                if ($dueLpkIds->contains($lpk->id) || in_array($dynStatus, ['SURVEILLANCE_DUE', 'NEEDS_ACTION', 'JATUH_TEMPO'])) {
                                    $statusTags[] = 'due';
                                }

                                if ($dynStatus === 'SUSPENDED') {
                                    $statusTags[] = 'suspended';
                                }

                                if (in_array($dynStatus, ['REVOKED', 'DICABUT', 'INACTIVE', 'EXPIRED', 'GRACE_PERIOD'])) {
                                    $statusTags[] = 'revoked';
                                }

                                // Jika tidak ada masalah (tidak due, tidak suspended, tidak revoked), tandai active
                                if (!in_array('due', $statusTags) && !in_array('suspended', $statusTags) && !in_array('revoked', $statusTags)) {
                                    $statusTags[] = 'active';
                                }
                            @endphp
                            <tr data-status="{{ implode(' ', $statusTags) }}">
                                <td>
                                    <span class="dashboard-lpk-reg">{{ $lpk->accreditation_number ?: $lpk->registration_number }}</span>
                                </td>
                                <td>
                                    <div class="directory-cell-name">
                                        <a href="{{ route('lpks.show', $lpk) }}" class="directory-name-link" title="Lihat profil detail {{ $lpk->name }}">
                                            <strong>{{ $lpk->name }}</strong>
                                        </a>
                                        @if($lpk->address)
                                            <span class="directory-address-snippet">{{ Str::limit($lpk->address, 65) }}</span>
                                        @endif
                                    </div>
                                </td>
                                <td>
                                    <span class="directory-cell-type">{{ $lpk->accreditation_type ?: 'Laboratorium Penguji' }}</span>
                                </td>
                                <td>
                                    <span class="directory-cell-date">{{ $lpk->expired_at ? $lpk->expired_at->format('d/m/Y') : '-' }}</span>
                                </td>
                                <td>
                                    <x-status :value="$lpk->dynamic_status" />
                                </td>
                                <td style="text-align: right;">
                                    <a href="{{ route('lpks.show', $lpk) }}" class="button secondary button-xs" title="Lihat profil lengkap {{ $lpk->name }}">
                                        <span>Detail</span>
                                        <x-icon name="chevron-right" size="12" />
                                    </a>
                                </td>
                            </tr>
                        @endforeach
                    </tbody>
                </table>
            </div>

            {{-- Pesan Kosong Filter / Pencarian Berstandar SIMASADI --}}
            <div id="directoryEmptyState" class="empty dashboard-directory-empty" style="display: none;">
                <div class="empty-icon-wrap" aria-hidden="true">
                    <x-icon name="search" size="22" />
                </div>
                <strong class="empty-title">Tidak Ada Laboratorium yang Sesuai</strong>
                <p class="empty-desc">Tidak ada laboratorium yang sesuai dengan filter status atau kata kunci pencarian.</p>
                <button type="button" class="button secondary button-sm empty-action" id="resetDirectoryFilterBtn">
                    <x-icon name="refresh" size="14" />
                    <span>Reset Filter &amp; Pencarian</span>
                </button>
            </div>
        @else
            <div class="empty">
                <p class="empty-desc">Belum ada data laboratorium terdaftar.</p>
                @if(auth()->user()?->isAdmin() || auth()->user()?->isPic())
                    <a href="{{ route('lpks.create') }}" class="button primary button-sm">Tambah LPK Baru</a>
                @endif
            </div>
        @endif
    </section>
@endsection

