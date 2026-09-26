<header class="topbar">
    <div class="topbar-nav-left">
        <button class="menu-toggle" type="button" aria-expanded="false" aria-controls="primary-navigation">
            <span class="sr-only">Buka navigasi</span>
            <span class="menu-icon" aria-hidden="true"></span>
        </button>
        <button class="navbar-sidebar-toggle" id="sidebar-toggle-btn" type="button" aria-label="Ciutkan sidebar" title="Ciutkan sidebar">
            <x-icon name="chevron-left" size="18" />
        </button>
        <div class="page-context">
            <span class="context-label" title="Unit Akreditasi Laboratorium &bull; Direktorat Akreditasi Laboratorium KAN">Unit Akreditasi Lab &bull; Dit. Akreditasi Laboratorium KAN</span>
            <strong class="context-title">@php($moduleCategory = match (true) {
                request()->routeIs('dashboard') => 'Ringkasan Eksekutif',
                request()->routeIs('profile.*') => 'Pengaturan Profil Pengguna',
                request()->routeIs('users.*') => 'Manajemen Pengguna & PIC',
                request()->routeIs('lpks.*', 'accreditations.*') => 'Manajemen Akreditasi LPK',
                request()->routeIs('assessments.*', 'calendar.*') => 'Jadwal & Penugasan Asesmen',
                request()->routeIs('monitoring.*') => 'Monitoring Sistem & Infrastruktur',
                default => 'Workspace',
            }){{ $moduleCategory }}</strong>
        </div>
    </div>
    @if(auth()->check())
        <div class="topbar-actions">
            @php($alertCount = count($globalSurveillanceAlerts ?? []))
            <div class="topbar-notifications">
                <button type="button" id="notif-dropdown-btn" class="topbar-notif-btn {{ $alertCount > 0 ? 'has-alerts' : '' }}" aria-expanded="false" aria-haspopup="true" title="{{ $alertCount > 0 ? $alertCount . ' Notifikasi Pengawasan Jatuh Tempo' : 'Tidak ada notifikasi aktif' }}">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                    @if($alertCount > 0)
                        <span class="topbar-notif-badge">
                            {{ $alertCount }}
                        </span>
                    @endif
                </button>

                <div id="notif-dropdown-menu" class="notif-dropdown" style="display: none;">
                    <div class="notif-dropdown-header">
                        <div class="notif-dropdown-heading">
                            <strong class="notif-dropdown-title">Notifikasi Siklus Pengawasan</strong>
                            <span class="notif-pill {{ $alertCount > 0 ? 'is-alert' : '' }}">
                                {{ $alertCount }}
                            </span>
                        </div>
                        @if($alertCount > 0)
                            <span class="notif-alert-badge">Perlu Tindakan</span>
                        @endif
                    </div>

                    <div class="notif-dropdown-body">
                        @if($alertCount > 0)
                            <div class="notif-alert-title">
                                {{ $alertCount }} Laboratorium Memerlukan Perhatian
                            </div>
                            <p class="notif-alert-desc">
                                Siklus Surveilen (S1/S2) atau Re-Akreditasi KAN mendekati atau melewati batas regulasi.
                            </p>

                            <div style="display: flex; flex-direction: column; gap: 8px; margin-bottom: 12px;">
                                @foreach(array_slice($globalSurveillanceAlerts, 0, 3) as $alert)
                                    <div style="background: #f8fafc; border: 1px solid #e2e8f0; border-radius: 8px; padding: 8px 10px; display: flex; flex-direction: column; gap: 4px;">
                                        <div style="display: flex; justify-content: space-between; align-items: center; gap: 6px;">
                                            <span class="badge" style="background-color: {{ $alert['is_urgent'] ? '#fee2e2' : '#fef3c7' }}; color: {{ $alert['is_urgent'] ? '#991b1b' : '#92400e' }}; font-weight: 700; font-size: 10.5px; padding: 2px 6px; border-radius: 4px;">
                                                {{ $alert['code'] }}
                                            </span>
                                            <span style="font-size: 11px; color: {{ $alert['is_urgent'] ? '#b91c1c' : '#b45309' }}; font-weight: 600;">
                                                {{ $alert['target_date'] ? $alert['target_date']->format('d M Y') : '-' }}
                                            </span>
                                        </div>
                                        <div style="font-size: 12px; font-weight: 600; color: #1e293b; overflow: hidden; text-overflow: ellipsis; white-space: nowrap;" title="{{ $alert['lpk_name'] }}">
                                            {{ $alert['lpk_name'] }}
                                        </div>
                                        <div style="display: flex; justify-content: flex-end; gap: 6px; margin-top: 2px;">
                                            @if(!empty($alert['target_date']))
                                                <a href="{{ route('calendar.index', [
                                                    'view' => 'month',
                                                    'date' => $alert['target_date']->format('Y-m-d'),
                                                    'highlight' => 'lpk_jt_' . strtolower($alert['code']) . '_' . $alert['lpk_id'],
                                                    'selected' => 1,
                                                ]) }}" class="button ghost button-xs" style="font-size: 11px; padding: 2px 7px; height: 22px; color: var(--primary, #0284c7); text-decoration: none; display: inline-flex; align-items: center; gap: 3px;" title="Lihat di kalender">
                                                    <x-icon name="calendar" size="11" />
                                                    <span>Kalender</span>
                                                </a>
                                            @endif
                                            <a href="{{ route('lpks.show', $alert['lpk_id']) }}" class="button secondary button-xs" style="font-size: 11px; padding: 2px 7px; height: 22px; display: inline-flex; align-items: center; gap: 3px;">
                                                <span>Detail</span>
                                                <x-icon name="chevron-right" size="11" />
                                            </a>
                                        </div>
                                    </div>
                                @endforeach
                            </div>

                            <a href="{{ route('lpks.index', ['surveillance' => 'NEEDS_ACTION']) }}" class="notif-cta-btn" style="display: inline-flex; align-items: center; justify-content: center; gap: 6px;">
                                <span>Tinjau Seluruh LPK Jatuh Tempo</span>
                                <x-icon name="chevron-right" size="14" />
                            </a>
                        @else
                            <div class="notif-empty-state">
                                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" class="notif-empty-icon"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/><line x1="2" y1="2" x2="22" y2="22"/></svg>
                                <div class="notif-empty-title">Tidak ada notifikasi aktif</div>
                                <div class="notif-empty-desc">Seluruh siklus pengawasan KAN dalam status aman.</div>
                            </div>
                        @endif
                    </div>

                    <div class="notif-dropdown-footer">
                        <a href="{{ route('lpks.index', $alertCount > 0 ? ['surveillance' => 'NEEDS_ACTION'] : []) }}" class="notif-footer-link" style="display: inline-flex; align-items: center; justify-content: center; gap: 4px;">
                            <span>Lihat Semua di Daftar LPK</span>
                            <x-icon name="chevron-right" size="14" />
                        </a>
                    </div>
                </div>
            </div>

            {{-- User Profile Avatar & Dropdown --}}
            <div class="topbar-user-dropdown">
                <button type="button" id="user-dropdown-btn" class="topbar-user-btn" aria-expanded="false" aria-haspopup="true" title="Profil Pengguna &amp; Akun ({{ auth()->user()->name }})">
                    <span class="user-avatar" aria-hidden="true">{{ auth()->user()->initials }}</span>
                    <x-icon name="chevron-down" size="14" class="topbar-user-chevron" />
                </button>

                <div id="user-dropdown-menu" class="user-dropdown-menu" style="display: none;">
                    {{-- Header Profil --}}
                    <div class="user-dropdown-header">
                        <div class="user-dropdown-identity">
                            <span class="user-avatar user-avatar-lg" aria-hidden="true">{{ auth()->user()->initials }}</span>
                            <div class="user-dropdown-meta">
                                <strong class="user-dropdown-name">{{ auth()->user()->name }}</strong>
                                <span class="user-dropdown-email">{{ auth()->user()->email }}</span>
                                <div class="user-dropdown-badge-wrap">
                                    <span class="badge-role {{ auth()->user()->role_badge_class }}">
                                        {{ auth()->user()->role_label }}
                                    </span>
                                </div>
                            </div>
                        </div>
                    </div>

                    <div class="user-dropdown-divider"></div>

                    {{-- Menu Cepat --}}
                    <div class="user-dropdown-body">
                        <a href="{{ route('dashboard') }}" class="user-dropdown-item">
                            <x-icon name="dashboard" size="15" />
                            <span>Dasbor Utama</span>
                        </a>
                        <a href="{{ route('profile.edit') }}" class="user-dropdown-item">
                            <x-icon name="user" size="15" />
                            <span>Profil &amp; Kata Sandi</span>
                        </a>
                        @if(auth()->user()->isAdmin())
                            <a href="{{ route('users.index') }}" class="user-dropdown-item">
                                <x-icon name="users" size="15" />
                                <span>Manajemen Pengguna</span>
                            </a>
                        @endif
                    </div>

                    <div class="user-dropdown-divider"></div>

                    {{-- Aksi Logout --}}
                    <div class="user-dropdown-footer">
                        <form method="POST" action="{{ route('logout') }}" id="topbar-logout-form" class="logout-form">
                            @csrf
                            <button type="submit" class="user-dropdown-item user-dropdown-logout" aria-label="Keluar dari akun">
                                <x-icon name="log-out" size="15" />
                                <span>Keluar dari Akun</span>
                            </button>
                        </form>
                    </div>
                </div>
            </div>
        </div>
    @endif
</header>
