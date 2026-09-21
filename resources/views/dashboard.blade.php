@extends('layouts.app')

@section('title', 'Ringkasan | SIMASADI')

@section('content')
    <div class="page-heading">
        <div>
            <h1>Selamat datang, {{ auth()->user()->name }}.</h1>
            <p class="lede">Pantau pekerjaan yang perlu diperhatikan sebelum masuk ke detail.</p>
        </div>
        <a class="button primary" href="{{ route('issues.create') }}"><x-icon name="plus" size="16" /><span>Buat laporan masalah</span></a>
    </div>

    @if(!empty($globalSurveillanceAlerts))
        <section class="panel" style="border-left: 5px solid #e11d48; margin-bottom: 24px; padding: 18px 20px; border-radius: 8px; box-shadow: 0 2px 8px rgba(0,0,0,0.04);">
            <div style="display: flex; justify-content: space-between; align-items: center; border-bottom: 1px solid var(--line, #e2e8f0); padding-bottom: 12px; margin-bottom: 14px; flex-wrap: wrap; gap: 10px;">
                <div>
                    <h2 style="font-size: 15.5px; margin: 0 0 4px 0; color: #9f1239; display: flex; align-items: center; gap: 8px;">
                        <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="#e11d48" stroke-width="2.5" stroke-linecap="round" stroke-linejoin="round"><path d="m21.73 18-8-14a2 2 0 0 0-3.48 0l-8 14A2 2 0 0 0 4 21h16a2 2 0 0 0 1.73-3Z"/><line x1="12" y1="9" x2="12" y2="13"/><line x1="12" y1="17" x2="12.01" y2="17"/></svg>
                        Peringatan Jatuh Tempo Siklus Pengawasan KAN (Surveilen 1, 2 & Re-Akreditasi)
                    </h2>
                    <p style="margin: 0; font-size: 13px; color: var(--muted, #64748b);">Terdapat {{ count($globalSurveillanceAlerts) }} LPK yang memerlukan penjadwalan kunjungan asesmen penilikan atau re-akreditasi KAN.</p>
                </div>
                <span class="badge" style="background-color: #fee2e2; color: #991b1b; font-weight: 700; font-size: 11.5px; padding: 4px 10px; border-radius: 6px;">Wajib Tindak Lanjut</span>
            </div>
            <div style="display: flex; flex-direction: column; gap: 10px;">
                @foreach(array_slice($globalSurveillanceAlerts, 0, 5) as $alert)
                    <div style="display: flex; align-items: center; justify-content: space-between; background: var(--surface-subtle, #f8fafc); border: 1px solid var(--line, #e2e8f0); border-radius: 6px; padding: 10px 14px; gap: 12px; flex-wrap: wrap;">
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
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <a href="{{ route('lpks.show', $alert['lpk_id']) }}" class="button secondary" style="font-size: 12px; padding: 4px 10px; min-height: 28px;">
                                Roadmap Siklus
                            </a>
                            <a href="{{ route('assessments.index') }}" class="button primary" style="font-size: 12px; padding: 4px 10px; min-height: 28px;">
                                Jadwalkan Kunjungan
                            </a>
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

    <section class="metric-grid" aria-label="Ringkasan angka">
        <div class="metric"><span>LPK terdaftar</span><strong>{{ $lpkCount }}</strong><small>Total laboratorium terdata</small></div>
        <div class="metric"><span>Akreditasi berjalan</span><strong>{{ $activeAccreditationCount }}</strong><small>Status sedang berlangsung</small></div>
        <div class="metric"><span>Masalah terbuka</span><strong>{{ $openIssueCount }}</strong><small>Terbuka atau sedang ditangani</small></div>
        <div class="metric warn"><span>Melewati target</span><strong>{{ $overdueIssueCount }}</strong><small>Perlu ditinjau</small></div>
    </section>

    <section class="metric-grid secondary-metrics" aria-label="Monitoring layanan">
        <div class="metric">
            <span>Amandemen aktif</span>
            <strong>{{ $amendmentCount }}</strong>
            <small>
                @if(auth()->user()?->hasRole(['admin', 'staf']))
                    <a href="{{ route('amendments.index') }}">Buka daftar</a>
                @else
                    <span>Pengajuan lingkup</span>
                @endif
            </small>
        </div>
        <div class="metric">
            <span>Asesmen bulan ini</span>
            <strong>{{ $assessmentCount }}</strong>
            <small><a href="{{ route('assessments.index') }}">Lihat program</a></small>
        </div>
        <div class="metric {{ $serviceIssueCount ? 'warn' : '' }}">
            <span>Layanan perlu perhatian</span>
            <strong>{{ $serviceIssueCount }}</strong>
            <small>
                @if(auth()->user()?->isAdmin())
                    <a href="{{ route('monitoring.services') }}">Status layanan</a>
                @else
                    <span>Sistem KANMIS</span>
                @endif
            </small>
        </div>
        <div class="metric">
            <span>Backup terakhir</span>
            <strong>{{ $lastBackup?->finished_at?->format('d/m') ?: '-' }}</strong>
            <small>{{ $lastBackup?->status ? str_replace(['SUCCESS', 'FAILED', 'RUNNING', 'SCHEDULED', 'UNKNOWN'], ['Berhasil', 'Gagal', 'Sedang Berjalan', 'Terjadwal', 'Tidak Diketahui'], $lastBackup->status) : 'Belum dicatat' }}</small>
        </div>
    </section>

    @php
        $accreditationStatuses = ['NOT_STARTED' => 'Belum Dimulai', 'IN_PROGRESS' => 'Sedang Berlangsung', 'COMPLETED' => 'Selesai'];
        $accreditationTotal = $accreditationStatusCounts->sum();
    @endphp
    <section class="dashboard-analysis" aria-labelledby="accreditation-chart-title">
        <div class="panel chart-panel">
            <div class="panel-head">
                <div>
                    <span class="eyebrow">ANALISIS PROSES</span>
                    <h2 id="accreditation-chart-title">Distribusi proses akreditasi</h2>
                </div>
                @if(auth()->user()?->hasRole(['admin', 'staf']))
                    <a href="{{ route('accreditations.index') }}">Lihat proses</a>
                @endif
            </div>
            @if($accreditationTotal)
                <div class="chart-bars" role="img" aria-label="Distribusi proses akreditasi berdasarkan status">
                    @foreach($accreditationStatuses as $status => $label)
                        @php($total = $accreditationStatusCounts->get($status, 0))
                        @php($width = max(2, round(($total / $accreditationTotal) * 100)))
                        <div class="chart-bar-row">
                            <div class="chart-bar-label"><span>{{ $label }}</span><strong>{{ $total }}</strong></div>
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
        </div>
    </section>

    <div class="content-grid">
        <section class="panel">
            <div class="panel-head"><div><span class="eyebrow">MASALAH TERBARU</span><h2>Hal yang perlu diikuti</h2></div><a href="{{ route('issues.index') }}">Lihat semua</a></div>
            @forelse($recentIssues as $issue)
                <a class="list-row" href="{{ route('issues.show', $issue) }}"><div><strong>{{ $issue->title }}</strong><span>{{ $issue->lpk->name }}</span></div><x-status :value="$issue->status" /></a>
            @empty
                <div class="empty">Belum ada laporan masalah. <a href="{{ route('issues.create') }}">Buat laporan pertama</a>.</div>
            @endforelse
        </section>
        <section class="panel">
            <div class="panel-head"><div><span class="eyebrow">CATATAN TERBARU</span><h2>Tindak lanjut</h2></div></div>
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
    </div>
@endsection
