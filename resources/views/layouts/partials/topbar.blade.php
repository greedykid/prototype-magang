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
                <button type="button" id="notif-dropdown-btn" class="topbar-notif-btn {{ $alertCount > 0 ? 'has-alerts' : '' }}" aria-expanded="false" aria-haspopup="true" aria-label="{{ $alertCount > 0 ? $alertCount . ' Notifikasi Pengawasan Jatuh Tempo' : 'Tidak ada notifikasi aktif' }}" title="{{ $alertCount > 0 ? $alertCount . ' Notifikasi Pengawasan Jatuh Tempo' : 'Tidak ada notifikasi aktif' }}">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                    @if($alertCount > 0)
                        <span class="topbar-notif-badge">
                            {{ $alertCount }}
                        </span>
                    @endif
                </button>

                {{-- Mobile Backdrop --}}
                <div id="notif-dropdown-backdrop" class="notif-backdrop" style="display: none;" aria-hidden="true"></div>

                <div id="notif-dropdown-menu" class="notif-dropdown" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="notif-dropdown-heading">
                    <div class="notif-dropdown-header">
                        <div class="notif-dropdown-heading" id="notif-dropdown-heading">
                            <div class="notif-header-icon-wrap {{ $alertCount > 0 ? 'is-alert' : '' }}">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                            </div>
                            <div class="notif-header-title-box">
                                <strong class="notif-dropdown-title">Notifikasi Siklus Pengawasan</strong>
                                <span class="notif-pill {{ $alertCount > 0 ? 'is-alert' : '' }}">
                                    {{ $alertCount }}
                                </span>
                            </div>
                        </div>
                        <div class="notif-header-actions">
                            @if($alertCount > 0)
                                <span class="notif-alert-badge">Perlu Tindakan</span>
                            @endif
                            <button type="button" id="notif-dropdown-close" class="notif-close-btn" aria-label="Tutup notifikasi" title="Tutup notifikasi">
                                <svg width="15" height="15" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2.2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><line x1="18" y1="6" x2="6" y2="18"></line><line x1="6" y1="6" x2="18" y2="18"></line></svg>
                            </button>
                        </div>
                    </div>

                    <div class="notif-dropdown-body">
                        @if($alertCount > 0)
                            <div class="notif-summary-banner">
                                <div class="notif-summary-icon">
                                    <svg width="16" height="16" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <circle cx="12" cy="12" r="10"></circle>
                                        <line x1="12" y1="8" x2="12" y2="12"></line>
                                        <line x1="12" y1="16" x2="12.01" y2="16"></line>
                                    </svg>
                                </div>
                                <div class="notif-summary-text">
                                    <div class="notif-summary-title notif-alert-title">
                                        {{ $alertCount }} Laboratorium Memerlukan Perhatian
                                    </div>
                                    <p class="notif-summary-desc notif-alert-desc">
                                        Siklus Surveilen (S1/S2) atau Re-Akreditasi KAN mendekati atau melewati batas regulasi.
                                    </p>
                                </div>
                            </div>

                            <div class="notif-card-list">
                                @foreach(array_slice($globalSurveillanceAlerts, 0, 4) as $alert)
                                    <div class="notif-item-card {{ $alert['is_urgent'] ? 'is-urgent' : '' }}">
                                        <div class="notif-item-top">
                                            <div class="notif-item-badge-wrap">
                                                <span class="badge-milestone {{ $alert['is_urgent'] ? 'badge-milestone-urgent' : 'badge-milestone-warning' }}">
                                                    {{ $alert['code'] }}
                                                </span>
                                                <span class="notif-milestone-label">{{ $alert['name'] ?? ($alert['code'] === 'RA' ? 'Re-Akreditasi' : 'Surveilen') }}</span>
                                            </div>
                                            <div class="notif-item-date {{ $alert['is_urgent'] ? 'is-urgent' : '' }}" title="Batas waktu siklus pengawasan">
                                                <svg width="12" height="12" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true"><rect x="3" y="4" width="18" height="18" rx="2" ry="2"></rect><line x1="16" y1="2" x2="16" y2="6"></line><line x1="8" y1="2" x2="8" y2="6"></line><line x1="3" y1="10" x2="21" y2="10"></line></svg>
                                                <span>{{ $alert['target_date'] ? $alert['target_date']->format('d M Y') : '-' }}</span>
                                            </div>
                                        </div>

                                        <div class="notif-item-content">
                                            <div class="notif-item-name" title="{{ $alert['lpk_name'] }}">
                                                {{ $alert['lpk_name'] }}
                                            </div>
                                        </div>

                                        <div class="notif-item-actions">
                                            @if(!empty($alert['target_date']))
                                                <a href="{{ route('calendar.index', [
                                                    'view' => 'month',
                                                    'date' => $alert['target_date']->format('Y-m-d'),
                                                    'highlight' => 'lpk_jt_' . strtolower($alert['code']) . '_' . $alert['lpk_id'],
                                                    'selected' => 1,
                                                ]) }}" class="notif-btn-ghost" title="Lihat di kalender">
                                                    <x-icon name="calendar" size="12" />
                                                    <span>Kalender</span>
                                                </a>
                                            @endif
                                            <a href="{{ route('lpks.show', $alert['lpk_id']) }}" class="notif-btn-secondary" title="Lihat detail LPK">
                                                <span>Detail</span>
                                                <x-icon name="chevron-right" size="12" />
                                            </a>
                                        </div>
                                    </div>
                                @endforeach

                                @if($alertCount > 4)
                                    <div class="notif-more-hint">
                                        +{{ $alertCount - 4 }} LPK lainnya memerlukan perhatian
                                    </div>
                                @endif
                            </div>
                        @else
                            <div class="notif-empty-state">
                                <div class="notif-empty-icon-wrap">
                                    <svg width="24" height="24" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" aria-hidden="true">
                                        <path d="M12 22s8-4 8-10V5l-8-3-8 3v7c0 6 8 10 8 10z"/>
                                        <path d="m9 12 2 2 4-4"/>
                                    </svg>
                                </div>
                                <div class="notif-empty-title">Tidak ada notifikasi aktif</div>
                                <div class="notif-empty-desc">Seluruh siklus pengawasan KAN dalam status aman.</div>
                            </div>
                        @endif
                    </div>

                    <div class="notif-dropdown-footer">
                        @if($alertCount > 0)
                            <a href="{{ route('lpks.index', ['surveillance' => 'NEEDS_ACTION']) }}" class="notif-primary-cta notif-cta-btn">
                                <span>Tinjau Seluruh LPK Jatuh Tempo ({{ $alertCount }})</span>
                                <x-icon name="arrow-right" size="13" />
                            </a>
                        @endif
                        <a href="{{ route('lpks.index', $alertCount > 0 ? ['surveillance' => 'NEEDS_ACTION'] : []) }}" class="notif-footer-link">
                            <span>Lihat Semua di Daftar LPK</span>
                            <x-icon name="chevron-right" size="12" />
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
