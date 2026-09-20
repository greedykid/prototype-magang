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
