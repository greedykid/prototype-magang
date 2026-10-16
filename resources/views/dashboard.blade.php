@extends('layouts.app')

@section('title', 'Dasbor | SIMASADI')

@section('content')
    @php
        $isAllClear = empty($globalSurveillanceAlerts)
            && (!isset($urgentTpAssessments) || $urgentTpAssessments->isEmpty());
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
            <h1 class="dashboard-greeting">Selamat datang, {{ auth()->user()?->isPic() ? 'PIC Laboratorium Penguji & Kalibrasi.' : auth()->user()->name . '.' }} 👋</h1>
            <p class="lede">Ringkasan operasional kepatuhan akreditasi, surveilen KAN, dan agenda penugasan asesmen.</p>
        </div>
        <div class="dashboard-header-actions">
            @if(auth()->user()?->isAdmin() || auth()->user()?->isPic())
                <a class="button primary" href="{{ route('lpks.create') }}"><x-icon name="plus" size="16" /><span>Tambah LPK</span></a>
            @endif
            <a class="button secondary" href="{{ route('calendar.index') }}"><x-icon name="calendar" size="16" /><span>Kalender</span></a>
        </div>
    </div>

    {{-- 4 Primary Metric Cards --}}
    <section class="metric-grid" aria-label="Ringkasan angka">
        <a href="{{ route('lpks.index') }}" class="metric">
            <div class="metric-head">
                <span class="metric-label">LPK Terdaftar</span>
                <div class="metric-icon theme-indigo" aria-hidden="true">
                    <x-icon name="lpks" size="18" />
                </div>
            </div>
            <div class="metric-body">
                <strong>{{ $lpkCount }}</strong>
                <small>Total laboratorium terdata</small>
            </div>
        </a>
        <a href="{{ route('lpks.index', ['surveillance' => 'NEEDS_ACTION']) }}" class="metric {{ !empty($globalSurveillanceAlerts) ? 'warn' : '' }}">
            <div class="metric-head">
                <span class="metric-label">Tindak Lanjut Surveilen</span>
                <div class="metric-icon {{ !empty($globalSurveillanceAlerts) ? 'theme-rose' : 'theme-emerald' }}" aria-hidden="true">
                    <x-icon name="{{ !empty($globalSurveillanceAlerts) ? 'alert-circle' : 'check-circle' }}" size="18" />
                </div>
            </div>
            <div class="metric-body">
                <strong>{{ count($globalSurveillanceAlerts ?? []) }}</strong>
                <small>{{ !empty($globalSurveillanceAlerts) ? count($globalSurveillanceAlerts) . ' LPK perlu dijadwalkan' : 'Semua pengawasan sesuai jadwal' }}</small>
            </div>
        </a>
        <a href="{{ route('assessments.index', !empty($overdueTpCount) ? ['tp_status' => 'OVERDUE'] : []) }}" class="metric {{ !empty($overdueTpCount) ? 'warn' : '' }}">
            <div class="metric-head">
                <span class="metric-label">Asesmen Bulan Ini</span>
                <div class="metric-icon theme-sky" aria-hidden="true">
                    <x-icon name="calendar" size="18" />
                </div>
            </div>
            <div class="metric-body">
                <strong>{{ $assessmentCount }}</strong>
                <small>{{ $assessmentCount > 0 ? $assessmentCount . ' agenda asesmen berjalan' : 'Tidak ada jadwal asesmen bulan ini' }}</small>
            </div>
        </a>
        <a href="{{ route('assessments.index') }}" class="metric {{ !empty($overdueTpCount) ? 'warn' : '' }}">
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
    </section>

    {{-- Grid 3 Kolom Sejajar: Status Pengawasan & TP, Laboratorium Terkelola, Asesmen Terdekat --}}
    <div class="dashboard-tri-grid">
        @php
            $hasSurveillanceAlerts = !empty($globalSurveillanceAlerts) || (isset($urgentTpAssessments) && $urgentTpAssessments->isNotEmpty());
            $maxAlerts = 5;
            $tpItems = isset($urgentTpAssessments) && $urgentTpAssessments->isNotEmpty() ? $urgentTpAssessments->take(2) : collect();
            $surveillanceLimit = max(0, $maxAlerts - $tpItems->count());
            $surveillanceList = array_slice($globalSurveillanceAlerts ?? [], 0, $surveillanceLimit);
            $actionCount = count($surveillanceList) + $tpItems->count();
        @endphp
        {{-- Kolom 1: Perlu Tindak Lanjut --}}
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
                <a href="{{ route('lpks.index', ['surveillance' => 'NEEDS_ACTION']) }}" class="dashboard-more-link" title="Buka seluruh antrean pengawasan dan perbaikan">
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
                                    @if(!empty($alert['target_date']))
                                        @php
                                            $diffDays = (int) now()->startOfDay()->diffInDays($alert['target_date']->startOfDay(), false);
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
                                    <span>Batas: <strong>{{ $alert['target_date'] ? $alert['target_date']->format('d/m/Y') : '-' }}</strong></span>
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
                                        @if($assessment->effective_tp_due_date)
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

        {{-- Kolom 2: Laboratorium Terkelola --}}
        <section class="panel dashboard-lpk-panel" aria-labelledby="managed-lpks-heading">
            <div class="panel-head">
                <div class="dashboard-panel-title-wrap">
                    <div class="dashboard-panel-title-row">
                        <h2 id="managed-lpks-heading">Laboratorium Terkelola</h2>
                        @if(isset($managedLpks) && $managedLpks->isNotEmpty())
                            <span class="dashboard-panel-count-badge" title="{{ $managedLpks->count() }} laboratorium terkelola">{{ $managedLpks->count() }}</span>
                        @endif
                    </div>
                    <p class="dashboard-panel-subtext">Daftar laboratorium aktif dan masa berlaku akreditasi</p>
                </div>
                <a href="{{ route('lpks.index') }}" class="dashboard-more-link" title="Buka seluruh daftar LPK">
                    <span>Lihat semua</span>
                    <x-icon name="chevron-right" size="14" />
                </a>
            </div>

            @if(isset($managedLpks) && $managedLpks->isNotEmpty())
                <div class="dashboard-lpk-list">
                    @foreach($managedLpks as $lpk)
                        <a href="{{ route('lpks.show', $lpk) }}" class="dashboard-lpk-item" title="Lihat detail {{ $lpk->name }}">
                            <div class="dashboard-lpk-item-main">
                                <div class="dashboard-lpk-item-top">
                                    <span class="dashboard-lpk-reg">{{ $lpk->accreditation_number ?: $lpk->registration_number }}</span>
                                    <x-status :value="$lpk->dynamic_status" />
                                </div>
                                <strong class="dashboard-lpk-name">{{ $lpk->name }}</strong>
                                <div class="dashboard-lpk-meta">
                                    <span>{{ $lpk->accreditation_type ?: 'Laboratorium Terakreditasi' }}</span>
                                    <span>&bull;</span>
                                    <span>Masa Berlaku: <strong>{{ $lpk->expired_at ? $lpk->expired_at->format('d/m/Y') : '-' }}</strong></span>
                                </div>
                            </div>
                            <div class="dashboard-lpk-arrow" aria-hidden="true">
                                <x-icon name="chevron-right" size="15" />
                            </div>
                        </a>
                    @endforeach
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

        {{-- Kolom 3: Asesmen Terdekat --}}
        <section class="panel dashboard-agenda-panel" aria-labelledby="upcoming-assessments-heading">
            <div class="panel-head">
                <div class="dashboard-panel-title-wrap">
                    <div class="dashboard-panel-title-row">
                        <h2 id="upcoming-assessments-heading">Asesmen Terdekat</h2>
                        @if(isset($upcomingAssessments) && $upcomingAssessments->isNotEmpty())
                            <span class="dashboard-panel-count-badge" title="{{ $upcomingAssessments->count() }} asesmen terdekat">{{ $upcomingAssessments->count() }}</span>
                        @endif
                    </div>
                    <p class="dashboard-panel-subtext">Jadwal agenda asesmen dan surveilen mendatang</p>
                </div>
                <a href="{{ route('calendar.index') }}" class="dashboard-more-link" title="Buka seluruh jadwal di kalender kerja">
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
                <div class="empty">
                    <p class="empty-desc">Belum ada agenda asesmen mendatang.</p>
                    @if(auth()->user()?->isAdmin())
                        <a href="{{ route('assessments.create') }}" class="button primary button-sm">Jadwalkan Asesmen</a>
                    @endif
                </div>
            @endif
        </section>
    </div>
@endsection
