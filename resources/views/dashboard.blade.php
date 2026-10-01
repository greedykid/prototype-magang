@extends('layouts.app')

@section('title', 'Ringkasan | SIMASADI')

@section('content')
    {{-- Notifikasi Hijau Tanda Kondisi Aman (Simple & Compact di Paling Atas) --}}
    @php
        $isAllClear = empty($globalSurveillanceAlerts)
            && (!isset($urgentTpAssessments) || $urgentTpAssessments->isEmpty());
    @endphp

    @if($isAllClear)
        <aside class="dashboard-safe-banner" role="status" aria-label="Status kepatuhan operasional">
            <div class="dashboard-safe-banner-main">
                <span class="dashboard-safe-banner-icon">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                        <path d="m9 12 2 2 4-4"/>
                    </svg>
                </span>
                <span class="dashboard-safe-banner-text">
                    <strong>Tidak Ada Tindakan Mendesak:</strong> Semua siklus surveilen dan pemenuhan perbaikan asesmen saat ini berjalan sesuai jadwal.
                </span>
            </div>
            <span class="dashboard-safe-banner-badge">
                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="3" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M20 6 9 17l-5-5"/></svg>
                Kondisi Normal
            </span>
        </aside>
    @endif

    <div class="page-heading">
        <div>
            <h1 class="dashboard-greeting">Selamat datang, {{ auth()->user()->name }}.</h1>
            <p class="lede">Ringkasan operasional kepatuhan akreditasi, surveilen KAN, dan agenda penugasan asesmen.</p>
        </div>
        <div class="dashboard-header-actions">
            @if(auth()->user()?->isAdmin())
                <a class="button secondary" href="{{ route('lpks.create') }}"><x-icon name="plus" size="16" /><span>Tambah LPK</span></a>
            @endif
            <a class="button secondary" href="{{ route('calendar.index') }}"><x-icon name="calendar" size="16" /><span>Kalender Kerja</span></a>
        </div>
    </div>

    {{-- 4 Primary Metric Cards --}}
    <section class="metric-grid" aria-label="Ringkasan angka">
        <a href="{{ route('lpks.index') }}" class="metric">
            <div class="metric-head">
                <span class="metric-label">LPK terdaftar</span>
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
                <span class="metric-label">Tindak lanjut surveilen</span>
                <div class="metric-icon {{ !empty($globalSurveillanceAlerts) ? 'theme-rose' : 'theme-emerald' }}" aria-hidden="true">
                    <x-icon name="{{ !empty($globalSurveillanceAlerts) ? 'alert-circle' : 'check-circle' }}" size="18" />
                </div>
            </div>
            <div class="metric-body">
                <strong>{{ count($globalSurveillanceAlerts ?? []) }}</strong>
                <small>{{ !empty($globalSurveillanceAlerts) ? 'Perlu tindakan segera' : 'Siklus pengawasan normal' }}</small>
            </div>
        </a>
        <a href="{{ route('assessments.index', !empty($overdueTpCount) ? ['tp_status' => 'OVERDUE'] : []) }}" class="metric {{ !empty($overdueTpCount) ? 'warn' : '' }}">
            <div class="metric-head">
                <span class="metric-label">Asesmen bulan ini</span>
                <div class="metric-icon theme-sky" aria-hidden="true">
                    <x-icon name="calendar" size="18" />
                </div>
            </div>
            <div class="metric-body">
                <strong>{{ $assessmentCount }}</strong>
                <small>{{ !empty($overdueTpCount) ? $overdueTpCount . ' TP melewati batas waktu KAN' : (!empty($dueSoonTpCount) ? $dueSoonTpCount . ' TP jatuh tempo segera' : 'Agenda penugasan asesor') }}</small>
            </div>
        </a>
        <a href="{{ route('assessments.index') }}" class="metric {{ !empty($overdueTpCount) ? 'warn' : '' }}">
            <div class="metric-head">
                <span class="metric-label">Tindakan perbaikan (TP)</span>
                <div class="metric-icon {{ !empty($overdueTpCount) ? 'theme-rose' : ($activeTpCount > 0 ? 'theme-amber' : 'theme-emerald') }}" aria-hidden="true">
                    <x-icon name="assessments" size="18" />
                </div>
            </div>
            <div class="metric-body">
                <strong>{{ $activeTpCount }}</strong>
                <small>{{ !empty($overdueTpCount) ? $overdueTpCount . ' melewati batas KAN' : 'Pemenuhan temuan asesmen' }}</small>
            </div>
        </a>
    </section>

    @php
        $accreditationStatuses = ['NOT_STARTED' => 'Belum Dimulai', 'IN_PROGRESS' => 'Sedang Berlangsung', 'COMPLETED' => 'Selesai'];
        $accreditationTotal = $accreditationStatusCounts->sum();
    @endphp

    {{-- Balanced 2-Column Responsive Workspace --}}
    <div class="dashboard-main-grid">
        {{-- Left Column: Core Operations, Surveillance Alerts & Managed LPKs --}}
        <div class="dashboard-column">
            @if(!empty($globalSurveillanceAlerts))
                <section class="panel surveillance-alert-panel" aria-labelledby="surveillance-heading">
                    <div class="surveillance-alert-header">
                        <div>
                            <h2 id="surveillance-heading" class="surveillance-alert-title">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#e11d48" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                                Peringatan Jatuh Tempo Siklus Pengawasan KAN (Surveilen 1, 2 &amp; Re-Akreditasi)
                            </h2>
                            <p style="margin: 0; font-size: 13px; color: var(--muted, #64748b);">Terdapat {{ count($globalSurveillanceAlerts) }} LPK yang memerlukan penjadwalan kunjungan asesmen penilikan atau re-akreditasi KAN.</p>
                        </div>
                        <span class="badge" style="background-color: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; font-weight: 600; font-size: 11px; padding: 2.5px 9px; border-radius: 999px;">Wajib Tindak Lanjut</span>
                    </div>
                    <div class="surveillance-alert-list">
                        @foreach(array_slice($globalSurveillanceAlerts, 0, 5) as $alert)
                            <div class="surveillance-alert-item">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <span class="badge" style="background-color: {{ $alert['is_urgent'] ? '#fee2e2' : '#fffbeb' }}; border: 1px solid {{ $alert['is_urgent'] ? '#fca5a5' : '#fde68a' }}; color: {{ $alert['is_urgent'] ? '#991b1b' : '#92400e' }}; font-weight: 700; font-size: 10.5px; padding: 2px 7.5px; border-radius: 999px;">
                                        {{ $alert['code'] }}
                                    </span>
                                    <div>
                                        <a href="{{ route('lpks.show', $alert['lpk_id']) }}" style="font-weight: 600; color: var(--text, #0f172a); text-decoration: none;">
                                            {{ $alert['lpk_name'] }}
                                        </a>
                                        <span style="font-size: 12px; color: var(--muted, #64748b); margin-left: 6px;">({{ $alert['lpk_reg'] }})</span>
                                        <div style="font-size: 12px; color: {{ $alert['is_urgent'] ? '#b91c1c' : '#b45309' }};">
                                            {{ $alert['description'] }} &bull; Target Batas: <strong>{{ $alert['target_date'] ? $alert['target_date']->format('d/m/Y') : '-' }}</strong>
                                        </div>
                                    </div>
                                </div>
                                <div class="surveillance-alert-actions">
                                    @if(!empty($alert['target_date']))
                                        <a href="{{ route('calendar.index', [
                                            'view' => 'month',
                                            'date' => $alert['target_date']->format('Y-m-d'),
                                            'highlight' => 'lpk_jt_' . strtolower($alert['code']) . '_' . $alert['lpk_id'],
                                            'selected' => 1,
                                        ]) }}" class="button ghost button-sm" style="display: inline-flex; align-items: center; gap: 4px;" title="Lihat tanggal siklus di kalender">
                                            <x-icon name="calendar" size="14" />
                                            <span>Kalender</span>
                                        </a>
                                    @endif
                                    <a href="{{ route('lpks.show', $alert['lpk_id']) }}" class="button secondary button-sm">
                                        <span>Lihat Detail</span>
                                        <x-icon name="chevron-right" size="14" />
                                    </a>
                                    @if(auth()->user()?->isAdmin())
                                        <a href="{{ route('assessments.create', [
                                            'lpk_id' => $alert['lpk_id'],
                                            'alert_code' => $alert['code'],
                                            'target_date' => $alert['target_date'] ? $alert['target_date']->format('Y-m-d') : null,
                                        ]) }}" class="button primary button-sm">
                                            <x-icon name="plus" size="14" />
                                            <span>Jadwalkan Kunjungan</span>
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                        @if(count($globalSurveillanceAlerts) > 5)
                            <div style="text-align: center; margin-top: 6px;">
                                <a href="{{ route('lpks.index', ['surveillance' => 'NEEDS_ACTION']) }}" style="font-size: 13px; font-weight: 600; color: #2563eb; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                                    <span>Lihat seluruh {{ count($globalSurveillanceAlerts) }} LPK yang jatuh tempo</span>
                                    <x-icon name="chevron-right" size="14" />
                                </a>
                            </div>
                        @endif
                    </div>
                </section>
            @endif

            {{-- Panel Peringatan Batas Waktu Tindakan Perbaikan (TP & VTP) KAN --}}
            @if(isset($urgentTpAssessments) && $urgentTpAssessments->isNotEmpty())
                <section class="panel surveillance-alert-panel tp-alert-panel" aria-labelledby="tp-alert-heading" style="margin-bottom: 20px;">
                    <div class="surveillance-alert-header">
                        <div>
                            <h2 id="tp-alert-heading" class="surveillance-alert-title" style="color: #991b1b;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                Peringatan Batas Waktu Tindakan Perbaikan (TP &amp; VTP) KAN
                            </h2>
                            <p style="margin: 0; font-size: 13px; color: var(--muted, #64748b);">Terdapat {{ $urgentTpAssessments->count() }} asesmen dengan tindakan perbaikan yang mendekati jatuh tempo (&le; 14 hari) atau melewati batas regulasi KAN.</p>
                        </div>
                        <span class="badge" style="background-color: #fee2e2; border: 1px solid #fca5a5; color: #991b1b; font-weight: 600; font-size: 11px; padding: 2.5px 9px; border-radius: 999px;">Batas Waktu Ketat</span>
                    </div>
                    <div class="surveillance-alert-list">
                        @foreach($urgentTpAssessments as $assessment)
                            @php $tpBadge = $assessment->tp_sla_badge; @endphp
                            <div class="surveillance-alert-item">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <span class="badge-tp badge-tp-{{ $tpBadge['type'] }}">
                                        {{ $tpBadge['label'] }}
                                    </span>
                                    <div>
                                        <a href="{{ route('assessments.show', $assessment) }}" style="font-weight: 600; color: var(--text, #0f172a); text-decoration: none;">
                                            {{ $assessment->display_title }}
                                        </a>
                                        <span style="font-size: 12px; color: var(--muted, #64748b); margin-left: 6px;">({{ $assessment->lpk->name }})</span>
                                        <div style="font-size: 12px; color: {{ $assessment->is_tp_overdue ? '#b91c1c' : '#b45309' }};">
                                            {{ $assessment->assessment_type_label }} &bull; Batas Akhir: <strong>{{ $assessment->effective_tp_due_date ? $assessment->effective_tp_due_date->format('d/m/Y') : '-' }}</strong>
                                            @if($assessment->tp_has_extension)
                                                <span style="color: #7c3aed; margin-left: 4px; font-weight: 600;">(+1 Bulan Surat Resmi)</span>
                                            @endif
                                        </div>
                                    </div>
                                </div>
                                <div class="surveillance-alert-actions">
                                    <a href="{{ route('calendar.index', [
                                        'view' => 'month',
                                        'date' => ($assessment->effective_tp_due_date ?: $assessment->start_at)->format('Y-m-d'),
                                        'highlight' => 'tp_' . $assessment->id,
                                        'selected' => 1,
                                    ]) }}" class="button ghost button-sm" style="display: inline-flex; align-items: center; gap: 4px;" title="Lihat batas waktu TP di kalender">
                                        <x-icon name="calendar" size="14" />
                                        <span>Kalender</span>
                                    </a>
                                    <a href="{{ route('assessments.show', $assessment) }}" class="button secondary button-sm">
                                        <span>Periksa TP</span>
                                        <x-icon name="chevron-right" size="14" />
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            @if(empty($globalSurveillanceAlerts) && (!isset($urgentTpAssessments) || $urgentTpAssessments->isEmpty()))
                <section class="panel dashboard-health-panel" aria-labelledby="status-kepatuhan-heading">
                    <div class="panel-head" style="margin-bottom: 12px; flex-wrap: wrap; gap: 8px;">
                        <div>
                            <h2 id="status-kepatuhan-heading">Status Pengawasan &amp; TP KAN</h2>
                        </div>
                        <span class="badge" style="background: #ecfdf5; color: #047857; border: 1px solid #a7f3d0; font-weight: 600; font-size: 11.5px; padding: 2px 10px; border-radius: 999px;">
                            Semua Siklus Normal
                        </span>
                    </div>
                    <div class="dashboard-health-card">
                        <div class="dashboard-health-main">
                            <span class="dashboard-health-icon">
                                <x-icon name="check-circle" size="18" />
                            </span>
                            <div class="dashboard-health-copy">
                                <strong>Siklus &amp; Tindakan Perbaikan Terkendali</strong>
                                <p>Tidak ada agenda surveilen (S1/S2/RA) yang jatuh tempo ataupun temuan perbaikan yang mendekati batas waktu KAN saat ini.</p>
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
                </section>
            @endif

            {{-- Panel Daftar Laboratorium Binaan / Terkelola --}}
            <section class="panel dashboard-lpk-panel" aria-labelledby="managed-lpks-heading">
                <div class="panel-head">
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; min-width: 0;">
                        <h2 id="managed-lpks-heading">Laboratorium Terkelola</h2>
                        <span class="badge" style="background: #f1f5f9; color: #475569; font-weight: 600; font-size: 11px; padding: 2px 8px; border-radius: 999px;">
                            {{ $lpkCount }} LPK
                        </span>
                    </div>
                    <a href="{{ route('lpks.index') }}" class="dashboard-more-link" title="Buka seluruh daftar LPK">
                        <span>Lihat Semua</span>
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
                                        @if(auth()->check() && $lpk->isViewerPic(auth()->user()))
                                            <span>&bull;</span>
                                            <span style="color: #0284c7; font-weight: 600;">(Viewer Tertaut)</span>
                                        @endif
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
                        <p style="margin: 0 0 10px 0; font-size: 13px; color: var(--muted);">Belum ada data laboratorium terdaftar.</p>
                        @if(auth()->user()?->isAdmin() || auth()->user()?->isPic())
                            <a href="{{ route('lpks.create') }}" class="button primary button-sm">Tambah LPK Baru</a>
                        @endif
                    </div>
                @endif
            </section>
        </div>

        {{-- Right Column: Field Agenda (Asesmen Terdekat) --}}
        <div class="dashboard-column">
            {{-- Upcoming Assessments Widget --}}
            <section class="panel" aria-labelledby="upcoming-assessments-heading">
                <div class="panel-head">
                    <div style="display: flex; align-items: center; gap: 8px; flex-wrap: wrap; min-width: 0;">
                        <h2 id="upcoming-assessments-heading">Asesmen Terdekat</h2>
                        @if(isset($upcomingAssessments) && $upcomingAssessments->isNotEmpty())
                            <span class="badge" style="background: #f1f5f9; color: #475569; font-weight: 600; font-size: 11px; padding: 2px 8px; border-radius: 999px;">
                                {{ $upcomingAssessments->count() }} Agenda
                            </span>
                        @endif
                    </div>
                    <a href="{{ route('calendar.index') }}" class="dashboard-more-link" title="Buka seluruh jadwal di kalender kerja">
                        <span>Kalender Kerja</span>
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
                                </div>
                                <div class="agenda-card-main">
                                    <div class="agenda-card-title">{{ $assessment->display_title }}</div>
                                    <div class="agenda-card-meta">
                                        <span><strong>{{ $assessment->lpk->name }}</strong></span>
                                        <span>&bull;</span>
                                        <span>{{ $assessment->assessment_type_label }}</span>
                                        @if($assessment->lead_assessor)
                                            <span>&bull;</span>
                                            <span>Ketua: {{ $assessment->lead_assessor }}</span>
                                        @endif
                                    </div>
                                </div>
                                <x-status :value="$assessment->status" />
                            </a>
                        @endforeach
                    </div>
                @else
                    <div class="empty">
                        <p style="margin: 0 0 10px 0; font-size: 13px; color: var(--muted);">Belum ada agenda asesmen mendatang.</p>
                        @if(auth()->user()?->isAdmin())
                            <a href="{{ route('assessments.create') }}" class="button primary button-sm">Jadwalkan Asesmen</a>
                        @endif
                    </div>
                @endif
            </section>
        </div>
    </div>
@endsection
