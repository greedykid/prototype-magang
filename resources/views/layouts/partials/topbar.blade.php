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
    @if(auth()->check())
        <div class="topbar-actions" style="display: flex; align-items: center; gap: 12px;">
            @php($alertCount = count($globalSurveillanceAlerts ?? []))
            <div class="topbar-notifications" style="position: relative;">
                <a href="{{ route('lpks.index') }}" title="{{ $alertCount > 0 ? $alertCount . ' Notifikasi Pengawasan Jatuh Tempo' : 'Tidak ada notifikasi aktif' }}" style="display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 8px; border: 1px solid #e2e8f0; background-color: {{ $alertCount > 0 ? '#fff1f2' : '#ffffff' }}; color: {{ $alertCount > 0 ? '#e11d48' : '#64748b' }}; position: relative; text-decoration: none;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                    @if($alertCount > 0)
                        <span style="position: absolute; top: -4px; right: -4px; background-color: #e11d48; color: #ffffff; font-size: 10.5px; font-weight: 700; min-width: 18px; height: 18px; border-radius: 9px; display: inline-flex; align-items: center; justify-content: center; padding: 0 4px; box-shadow: 0 0 0 2px #ffffff; animation: pulse 2s infinite;">
                            {{ $alertCount }}
                        </span>
                    @endif
                </a>
            </div>

            <span class="badge-role {{ auth()->user()->role_badge_class }}">
                {{ auth()->user()->role_label }}
            </span>
        </div>
    @endif
</header>
