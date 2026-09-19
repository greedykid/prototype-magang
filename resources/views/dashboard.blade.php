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

    <section class="metric-grid" aria-label="Ringkasan angka">
        <div class="metric"><span>LPK terdaftar</span><strong>{{ $lpkCount }}</strong><small>Data contoh yang tersimpan</small></div>
        <div class="metric"><span>Akreditasi berjalan</span><strong>{{ $activeAccreditationCount }}</strong><small>Status sedang berlangsung</small></div>
        <div class="metric"><span>Masalah terbuka</span><strong>{{ $openIssueCount }}</strong><small>Terbuka atau sedang ditangani</small></div>
        <div class="metric warn"><span>Melewati target</span><strong>{{ $overdueIssueCount }}</strong><small>Perlu ditinjau</small></div>
    </section>

    <section class="metric-grid secondary-metrics" aria-label="Monitoring layanan">
        <div class="metric"><span>Amandemen aktif</span><strong>{{ $amendmentCount }}</strong><small><a href="{{ route('amendments.index') }}">Buka daftar</a></small></div>
        <div class="metric"><span>Asesmen bulan ini</span><strong>{{ $assessmentCount }}</strong><small><a href="{{ route('assessments.index') }}">Lihat program</a></small></div>
        <div class="metric {{ $serviceIssueCount ? 'warn' : '' }}"><span>Layanan perlu perhatian</span><strong>{{ $serviceIssueCount }}</strong><small><a href="{{ route('monitoring.services') }}">Status layanan</a></small></div>
        <div class="metric"><span>Backup terakhir</span><strong>{{ $lastBackup?->finished_at?->format('d/m') ?: '-' }}</strong><small>{{ $lastBackup?->status ? str_replace(['SUCCESS', 'FAILED', 'RUNNING', 'SCHEDULED', 'UNKNOWN'], ['Berhasil', 'Gagal', 'Sedang Berjalan', 'Terjadwal', 'Tidak Diketahui'], $lastBackup->status) : 'Belum dicatat' }}</small></div>
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
                <a href="{{ route('accreditations.index') }}">Lihat proses</a>
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
