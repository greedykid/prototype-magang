@extends('layouts.app')

@section('title', 'Ringkasan | SIMASADI')

@section('content')
    {{-- Notifikasi Hijau Tanda Kondisi Aman (Simple & Compact di Paling Atas) --}}
    @php
        $isAllClear = empty($globalSurveillanceAlerts)
            && (!isset($urgentTpAssessments) || $urgentTpAssessments->isEmpty())
            && empty($overdueIssueCount)
            && empty($serviceIssueCount);
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
                    <strong>Semua Aman &amp; Kepatuhan Terkendali:</strong> Tidak ada tindakan mendesak yang diperlukan. Seluruh siklus pengawasan dan operasional berjalan optimal.
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
            <a class="button primary" href="{{ route('issues.create') }}"><x-icon name="plus" size="16" /><span>Lapor Masalah</span></a>
        </div>
    </div>

    {{-- 4 Primary Metric Cards --}}
    <section class="metric-grid" aria-label="Ringkasan angka">
        <a href="{{ route('lpks.index') }}" class="metric" style="text-decoration: none; color: inherit;">
            <span>LPK terdaftar</span>
            <strong>{{ $lpkCount }}</strong>
            <small>Total laboratorium terdata</small>
        </a>
        <a href="{{ route('lpks.index', ['surveillance' => 'NEEDS_ACTION']) }}" class="metric {{ !empty($globalSurveillanceAlerts) ? 'warn' : '' }}" style="text-decoration: none; color: inherit;">
            <span>Tindak lanjut surveilen</span>
            <strong style="{{ !empty($globalSurveillanceAlerts) ? 'color: var(--terracotta);' : '' }}">{{ count($globalSurveillanceAlerts ?? []) }}</strong>
            <small>{{ !empty($globalSurveillanceAlerts) ? 'Perlu tindakan segera' : 'Siklus pengawasan normal' }}</small>
        </a>
        <a href="{{ route('assessments.index', !empty($overdueTpCount) ? ['tp_status' => 'OVERDUE'] : []) }}" class="metric {{ !empty($overdueTpCount) ? 'warn' : '' }}" style="text-decoration: none; color: inherit;">
            <span>Asesmen bulan ini</span>
            <strong style="{{ !empty($overdueTpCount) ? 'color: var(--terracotta);' : '' }}">{{ $assessmentCount }}</strong>
            <small>{{ !empty($overdueTpCount) ? $overdueTpCount . ' TP melewati batas waktu KAN' : (!empty($dueSoonTpCount) ? $dueSoonTpCount . ' TP jatuh tempo segera' : 'Agenda penugasan asesor') }}</small>
        </a>
        <a href="{{ route('issues.index') }}" class="metric {{ $overdueIssueCount ? 'warn' : '' }}" style="text-decoration: none; color: inherit;">
            <span>Masalah terbuka</span>
            <strong>{{ $openIssueCount }}</strong>
            <small>{{ $overdueIssueCount ? $overdueIssueCount . ' melewati target' : 'Semua dalam target' }}</small>
        </a>
    </section>

    @php
        $accreditationStatuses = ['NOT_STARTED' => 'Belum Dimulai', 'IN_PROGRESS' => 'Sedang Berlangsung', 'COMPLETED' => 'Selesai'];
        $accreditationTotal = $accreditationStatusCounts->sum();
    @endphp

    {{-- Balanced 2-Column Responsive Workspace --}}
    <div class="dashboard-main-grid">
        {{-- Left Column: Core Operations, Surveillance Alerts & Field Agenda --}}
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
                        <span class="badge" style="background-color: #fee2e2; color: #991b1b; font-weight: 700; font-size: 11.5px; padding: 4px 10px; border-radius: 6px;">Wajib Tindak Lanjut</span>
                    </div>
                    <div class="surveillance-alert-list">
                        @foreach(array_slice($globalSurveillanceAlerts, 0, 5) as $alert)
                            <div class="surveillance-alert-item">
                                <div style="display: flex; align-items: center; gap: 10px;">
                                    <span class="badge" style="background-color: {{ $alert['is_urgent'] ? '#fee2e2' : '#fef3c7' }}; color: {{ $alert['is_urgent'] ? '#991b1b' : '#92400e' }}; font-weight: 700; font-size: 11px; padding: 3px 8px; border-radius: 4px;">
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
                                    <a href="{{ route('lpks.show', $alert['lpk_id']) }}" class="button secondary button-sm">
                                        Lihat Detail
                                    </a>
                                    @if(auth()->user()?->isAdmin())
                                        <a href="{{ route('assessments.index') }}" class="button primary button-sm">
                                            Jadwalkan Kunjungan
                                        </a>
                                    @endif
                                </div>
                            </div>
                        @endforeach
                        @if(count($globalSurveillanceAlerts) > 5)
                            <div style="text-align: center; margin-top: 6px;">
                                <a href="{{ route('lpks.index', ['surveillance' => 'NEEDS_ACTION']) }}" style="font-size: 13px; font-weight: 600; color: #2563eb; text-decoration: none;">
                                    Lihat seluruh {{ count($globalSurveillanceAlerts) }} LPK yang jatuh tempo &rarr;
                                </a>
                            </div>
                        @endif
                    </div>
                </section>
            @endif

            {{-- Panel Peringatan Batas Waktu Tindakan Perbaikan (TP & VTP) KAN --}}
            @if(isset($urgentTpAssessments) && $urgentTpAssessments->isNotEmpty())
                <section class="panel surveillance-alert-panel" aria-labelledby="tp-alert-heading" style="border-left: 4px solid #dc2626; margin-bottom: 20px;">
                    <div class="surveillance-alert-header">
                        <div>
                            <h2 id="tp-alert-heading" class="surveillance-alert-title" style="color: #991b1b;">
                                <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#dc2626" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><circle cx="12" cy="12" r="10"/><line x1="12" y1="8" x2="12" y2="12"/><line x1="12" y1="16" x2="12.01" y2="16"/></svg>
                                Peringatan Batas Waktu Tindakan Perbaikan (TP &amp; VTP) KAN
                            </h2>
                            <p style="margin: 0; font-size: 13px; color: var(--muted, #64748b);">Terdapat {{ $urgentTpAssessments->count() }} asesmen dengan tindakan perbaikan yang mendekati jatuh tempo (&le; 14 hari) atau melewati batas regulasi KAN.</p>
                        </div>
                        <span class="badge" style="background-color: #fee2e2; color: #991b1b; font-weight: 700; font-size: 11.5px; padding: 4px 10px; border-radius: 6px;">SLA Ketat KAN</span>
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
                                            {{ $assessment->title }}
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
                                    <a href="{{ route('assessments.show', $assessment) }}" class="button secondary button-sm">
                                        Periksa TP
                                    </a>
                                </div>
                            </div>
                        @endforeach
                    </div>
                </section>
            @endif

            {{-- Upcoming Assessments Widget --}}
            <section class="panel" aria-labelledby="upcoming-assessments-heading">
                <div class="panel-head">
                    <div>
                        <span class="eyebrow">AGENDA LAPANGAN</span>
                        <h2 id="upcoming-assessments-heading">Asesmen Terdekat</h2>
                    </div>
                    <a href="{{ route('assessments.index') }}">Buka agenda &rarr;</a>
                </div>
                @if(isset($upcomingAssessments) && $upcomingAssessments->isNotEmpty())
                    <div class="agenda-list">
                        @foreach($upcomingAssessments as $assessment)
                            <a href="{{ route('assessments.index') }}" class="agenda-card">
                                <div class="agenda-card-date">
                                    <span class="day">{{ $assessment->start_at->format('d') }}</span>
                                    <span class="month">{{ $assessment->start_at->translatedFormat('M') }}</span>
                                </div>
                                <div class="agenda-card-main">
                                    <div class="agenda-card-title">{{ $assessment->title }}</div>
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
                    <div class="empty">Belum ada agenda asesmen mendatang.@if(auth()->user()?->isAdmin()) <a href="{{ route('assessments.create') }}">Jadwalkan asesmen</a>.@endif</div>
                @endif
            </section>
        </div>

        {{-- Right Column: Accreditation Distribution, Financial Compliance, Issues & Follow-ups, and System Health --}}
        <div class="dashboard-column">
            {{-- Distribution Chart Panel --}}
            <section class="panel chart-panel" aria-labelledby="accreditation-chart-title">
                <div class="panel-head">
                    <div>
                        <span class="eyebrow">ANALISIS PROSES</span>
                        <h2 id="accreditation-chart-title">Distribusi proses akreditasi</h2>
                    </div>
                    @if(auth()->user()?->isAdmin())
                        <a href="{{ route('accreditations.index') }}">Lihat proses &rarr;</a>
                    @endif
                </div>
                @if($accreditationTotal)
                    <div class="chart-bars" role="img" aria-label="Distribusi proses akreditasi berdasarkan status">
                        @foreach($accreditationStatuses as $status => $label)
                            @php($total = $accreditationStatusCounts->get($status, 0))
                            @php($width = max(2, round(($total / $accreditationTotal) * 100)))
                            <div class="chart-bar-row">
                                <div class="chart-bar-label"><span>{{ $label }}</span><strong>{{ $total }} ({{ $width }}%)</strong></div>
                                <div class="chart-bar-track" aria-hidden="true"><span class="chart-bar-fill chart-bar-{{ strtolower($status) }}" style="width: {{ $width }}%"></span></div>
                            </div>
                        @endforeach
                    </div>
                    <ul class="sr-only">
                        @foreach($accreditationStatuses as $status => $label)
                            <li>{{ $label }}: {{ $accreditationStatusCounts->get($status, 0) }}</li>
                        @endforeach
                    </ul>
                @else
                    <div class="empty">Belum ada data proses akreditasi untuk dianalisis.</div>
                @endif
            </section>

            {{-- Operational, Financial & Administrative Compliance --}}
            @if(auth()->user()?->isAdmin())
                <section class="panel" aria-labelledby="compliance-heading">
                    <div class="panel-head">
                        <div>
                            <span class="eyebrow">KEPATUHAN OPERASIONAL</span>
                            <h2 id="compliance-heading">Finansial &amp; Administrasi</h2>
                        </div>
                    </div>
                    <div class="compliance-summary-grid">
                        <a href="{{ route('accreditations.index') }}" class="compliance-item">
                            <div class="compliance-item-info">
                                <strong>PNBP SIMPONI</strong>
                                <span>Tagihan belum lunas</span>
                            </div>
                            <span class="compliance-item-badge" style="background: {{ $unpaidBillingCount ? '#fee2e2' : '#f1f5f9' }}; color: {{ $unpaidBillingCount ? '#991b1b' : '#334155' }};">
                                {{ $unpaidBillingCount }} Tagihan
                            </span>
                        </a>
                        <a href="{{ route('assessments.index') }}" class="compliance-item">
                            <div class="compliance-item-info">
                                <strong>Pertanggungjawaban SBM</strong>
                                <span>Biaya asesmen menunggu verifikasi</span>
                            </div>
                            <span class="compliance-item-badge" style="background: {{ $pendingExpenseCount ? '#fef3c7' : '#f1f5f9' }}; color: {{ $pendingExpenseCount ? '#92400e' : '#334155' }};">
                                {{ $pendingExpenseCount }} Berkas
                            </span>
                        </a>
                        <a href="{{ route('amendments.index') }}" class="compliance-item">
                            <div class="compliance-item-info">
                                <strong>Amandemen Lingkup</strong>
                                <span>Pengajuan aktif</span>
                            </div>
                            <span class="compliance-item-badge" style="background: #f1f5f9; color: #334155;">
                                {{ $amendmentCount }} Pengajuan
                            </span>
                        </a>
                    </div>
                </section>
            @endif

            {{-- Recent Issues & Follow-ups --}}
            <section class="panel" aria-labelledby="recent-issues-heading">
                <div class="panel-head">
                    <div>
                        <span class="eyebrow">KENDALA &amp; TINDAK LANJUT</span>
                        <h2 id="recent-issues-heading">Hal yang perlu diikuti</h2>
                    </div>
                    <a href="{{ route('issues.index') }}">Lihat semua &rarr;</a>
                </div>
                @forelse($recentIssues as $issue)
                    <a class="list-row" href="{{ route('issues.show', $issue) }}">
                        <div>
                            <strong>{{ $issue->title }}</strong>
                            <span>{{ $issue->lpk->name }}</span>
                        </div>
                        <x-status :value="$issue->status" />
                    </a>
                @empty
                    <div class="empty">Belum ada laporan masalah terbuka. <a href="{{ route('issues.create') }}">Buat laporan pertama</a>.</div>
                @endforelse
            </section>

            <section class="panel" aria-labelledby="recent-followups-heading">
                <div class="panel-head">
                    <div>
                        <span class="eyebrow">CATATAN TIM</span>
                        <h2 id="recent-followups-heading">Tindak lanjut</h2>
                    </div>
                </div>
                @forelse($recentFollowups as $followup)
                    <div class="note-row">
                        <div class="note-row-header">
                            <a href="{{ route('issues.show', $followup->issue) }}" class="note-row-title">
                                {{ $followup->issue->title }}
                            </a>
                            <div class="note-row-meta">
                                <span class="note-row-author">{{ $followup->user->name }}</span>
                                <span class="note-row-dot" aria-hidden="true">&bull;</span>
                                <span class="note-row-time">{{ $followup->created_at->diffForHumans() }}</span>
                            </div>
                        </div>
                        <div class="note-row-body">
                            <p>{{ $followup->note }}</p>
                        </div>
                    </div>
                @empty
                    <div class="empty">Belum ada catatan tindak lanjut.</div>
                @endforelse
            </section>

            {{-- System Reliability & Backup --}}
            <section class="panel" aria-labelledby="system-health-heading">
                <div class="panel-head">
                    <div>
                        <span class="eyebrow">PEMELIHARAAN</span>
                        <h2 id="system-health-heading">Keandalan Sistem</h2>
                    </div>
                    @if(auth()->user()?->isAdmin())
                        <a href="{{ route('monitoring.services') }}">Status layanan &rarr;</a>
                    @endif
                </div>
                <div class="system-health-grid">
                    <div class="system-health-row">
                        <span>Layanan Sistem KANMIS</span>
                        <span class="badge" style="background: {{ $serviceIssueCount ? '#fee2e2' : '#dcfce7' }}; color: {{ $serviceIssueCount ? '#991b1b' : '#166534' }}; font-weight: 600; font-size: 11px; padding: 2px 8px; border-radius: 4px;">
                            {{ $serviceIssueCount ? $serviceIssueCount . ' Perlu Perhatian' : 'Semua Normal' }}
                        </span>
                    </div>
                    <div class="system-health-row">
                        <span>Pencadangan Terakhir</span>
                        <strong>{{ $lastBackup?->finished_at?->format('d/m/Y H:i') ?: '-' }}</strong>
                    </div>
                    <div class="system-health-row">
                        <span>Status Cadangan</span>
                        <span class="badge" style="background: {{ ($lastBackup?->status === 'SUCCESS') ? '#dcfce7' : '#fef3c7' }}; color: {{ ($lastBackup?->status === 'SUCCESS') ? '#166534' : '#92400e' }}; font-weight: 600; font-size: 11px; padding: 2px 8px; border-radius: 4px;">
                            {{ $lastBackup?->status ? str_replace(['SUCCESS', 'FAILED', 'RUNNING', 'SCHEDULED', 'UNKNOWN'], ['Berhasil', 'Gagal', 'Sedang Berjalan', 'Terjadwal', 'Tidak Diketahui'], $lastBackup->status) : 'Belum dicatat' }}
                        </span>
                    </div>
                </div>
            </section>
        </div>
    </div>
@endsection
