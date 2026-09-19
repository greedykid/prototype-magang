<!doctype html>
<html lang="id">
<head>
    <meta charset="utf-8">
    <meta name="viewport" content="width=device-width, initial-scale=1">
    <title>@yield('title', 'SIMASADI Workspace')</title>
    <script>
        if (localStorage.getItem('simasadi_sidebar_collapsed') === 'true') {
            document.documentElement.classList.add('sidebar-is-collapsed');
        }
    </script>
    @vite(['resources/css/app.css', 'resources/js/app.js'])
</head>
<body>
<a class="skip-link" href="#main-content">Lewati ke konten</a>
<div class="app-shell">
<aside class="sidebar">
    <div class="brand">
        <div class="brand-info">
            <a href="{{ route('dashboard') }}" class="brand-mark" title="SIMASADI - Badan Standardisasi Nasional">
                <img src="{{ asset('images/logo-bsn.png') }}" alt="Logo BSN" class="brand-logo-img">
            </a>
            <div class="brand-text"><strong>SIMASADI</strong><small>Badan Standardisasi Nasional</small></div>
        </div>
    </div>
    <button class="drawer-close" type="button" aria-label="Tutup navigasi"><span aria-hidden="true">&times;</span></button>
    <nav id="primary-navigation" aria-label="Navigasi utama">
        <div class="nav-section">
            <span class="nav-section-title">Utama</span>
            <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" data-tooltip="Ringkasan">
                <x-icon name="dashboard" size="18" />
                <span class="nav-label">Ringkasan</span>
            </a>
        </div>

        <div class="nav-section">
            <span class="nav-section-title">Akreditasi LPK</span>
            <a href="{{ route('lpks.index') }}" class="{{ request()->routeIs('lpks.*') ? 'active' : '' }}" data-tooltip="Data LPK">
                <x-icon name="lpks" size="18" />
                <span class="nav-label">Data LPK</span>
            </a>
            <a href="{{ route('accreditations.index') }}" class="{{ request()->routeIs('accreditations.*') ? 'active' : '' }}" data-tooltip="Akreditasi">
                <x-icon name="accreditations" size="18" />
                <span class="nav-label">Akreditasi</span>
            </a>
            <a href="{{ route('amendments.index') }}" class="{{ request()->routeIs('amendments.*') ? 'active' : '' }}" data-tooltip="Amandemen">
                <x-icon name="amendments" size="18" />
                <span class="nav-label">Amandemen</span>
            </a>
        </div>

        <div class="nav-section">
            <span class="nav-section-title">Asesmen & Jadwal</span>
            <a href="{{ route('assessments.index') }}" class="{{ request()->routeIs('assessments.*') ? 'active' : '' }}" data-tooltip="Program Asesmen">
                <x-icon name="assessments" size="18" />
                <span class="nav-label">Program Asesmen</span>
            </a>
            <a href="{{ route('calendar.index') }}" class="{{ request()->routeIs('calendar.*') ? 'active' : '' }}" data-tooltip="Kalender">
                <x-icon name="calendar" size="18" />
                <span class="nav-label">Kalender</span>
            </a>
        </div>

        <div class="nav-section">
            <span class="nav-section-title">Monitoring & Sistem</span>
            <a href="{{ route('monitoring.services') }}" class="{{ request()->routeIs('monitoring.services') ? 'active' : '' }}" data-tooltip="Layanan KANMIS">
                <x-icon name="services" size="18" />
                <span class="nav-label">Layanan KANMIS</span>
            </a>
            <a href="{{ route('monitoring.backups') }}" class="{{ request()->routeIs('monitoring.backups') ? 'active' : '' }}" data-tooltip="Backup">
                <x-icon name="backup" size="18" />
                <span class="nav-label">Backup</span>
            </a>
            <a href="{{ route('issues.index') }}" class="{{ request()->routeIs('issues.*') ? 'active' : '' }}" data-tooltip="Masalah">
                <x-icon name="issues" size="18" />
                <span class="nav-label">Masalah</span>
            </a>
        </div>
    </nav>
    <div class="sidebar-foot">
        <div class="drawer-account">
            <div class="user-identity">
                <span class="user-avatar" aria-hidden="true">{{ collect(explode(' ', auth()->user()->name ?? 'Tamu'))->map(fn ($part) => substr($part, 0, 1))->take(2)->implode('') }}</span>
                <div class="user-details">
                    <strong>{{ auth()->user()->name ?? 'Tamu' }}</strong>
                    <small>Petugas monitoring</small>
                </div>
            </div>
            <form method="POST" action="{{ route('logout') }}" id="logout-form" class="logout-form">
                @csrf
                <button type="submit" class="link-button logout-icon" aria-label="Keluar" title="Keluar">
                    <x-icon name="log-out" size="16" />
                </button>
            </form>
        </div>
        <div class="sidebar-foot-note">
            <span class="eyebrow">MODE BELAJAR</span>
            <p>Data contoh lokal, belum terhubung ke sistem resmi.</p>
        </div>
    </div>
</aside>
<div class="drawer-backdrop" data-drawer-close></div>
<main id="main-content" class="main-content">
    <header class="topbar">
        <div style="display: flex; align-items: center; gap: 12px;">
            <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="primary-navigation">
                <span class="sr-only">Buka navigasi</span>
                <span class="menu-icon" aria-hidden="true"></span>
            </button>
            <button class="navbar-sidebar-toggle" id="sidebar-toggle-btn" type="button" aria-label="Ciutkan sidebar" title="Ciutkan sidebar">
                <x-icon name="chevron-left" size="18" />
            </button>
            <div class="page-context">
                <span class="context-label" title="Sistem Informasi dan Administrasi Akreditasi">Sistem Informasi dan Administrasi Akreditasi</span>
                <strong class="context-title">@php($moduleCategory = match (true) {
                    request()->routeIs('dashboard') => 'Ringkasan Eksekutif',
                    request()->routeIs('lpks.*', 'accreditations.*', 'amendments.*') => 'Manajemen Akreditasi LPK',
                    request()->routeIs('assessments.*', 'calendar.*') => 'Jadwal & Penugasan Asesmen',
                    request()->routeIs('monitoring.*') => 'Monitoring Sistem & Infrastruktur',
                    request()->routeIs('issues.*') => 'Pusat Kendala & Tindak Lanjut',
                    default => 'Workspace',
                }){{ $moduleCategory }}</strong>
            </div>
        </div>
    </header>
    <div class="page-wrap" id="page-content-wrapper">
        @if(session('success'))
            <div id="flash-success-data" data-message="{{ session('success') }}" style="display: none;"></div>
        @endif
        @if(session('error'))
            <div id="flash-error-data" data-message="{{ session('error') }}" style="display: none;"></div>
        @endif
        @if($errors->any())
            <div id="flash-errors-data" data-errors='@json($errors->all())' data-title="Periksa kembali formulir" style="display: none;"></div>
        @endif
        @yield('content')
    </div>
</main>
</div>
<div id="logout-curtain" class="logout-curtain" aria-hidden="true">
    <div class="logout-curtain-content">
        <span class="logout-spinner">
            <x-icon name="loader" size="34" />
        </span>
        <span class="logout-curtain-text">Keluar dari workspace...</span>
    </div>
</div>
</body>
</html>
