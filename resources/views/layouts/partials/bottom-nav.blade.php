{{-- Mobile Bottom Navigation & Quick Action Sheet --}}
@if(auth()->check())
    @php
        $isPic = auth()->user()?->isPic();
        $isMoreActive = request()->routeIs('users.*', 'account-links.*', 'profile.*');
    @endphp

    <nav class="bottom-nav" id="bottom-nav" aria-label="Navigasi Utama Mobile">
        {{-- 1. Dasbor --}}
        <a href="{{ route('dashboard') }}"
           class="bottom-nav-item {{ request()->routeIs('dashboard') ? 'active' : '' }}"
           data-nav-target="dashboard"
           title="Dasbor">
            <span class="bottom-nav-icon-wrap" aria-hidden="true">
                <x-icon name="dashboard" size="19" />
            </span>
            <span class="bottom-nav-label">Dasbor</span>
        </a>

        {{-- 2. Data Lab (LPK) --}}
        <a href="{{ route('lpks.index') }}"
           class="bottom-nav-item {{ request()->routeIs('lpks.*') ? 'active' : '' }}"
           data-nav-target="lpks"
           title="{{ $isPic ? 'Data Laboratorium' : 'Data Lab (LPK)' }}">
            <span class="bottom-nav-icon-wrap" aria-hidden="true">
                <x-icon name="lpks" size="19" />
            </span>
            <span class="bottom-nav-label">Data Lab</span>
        </a>

        {{-- 3. Asesmen --}}
        <a href="{{ route('assessments.index') }}"
           class="bottom-nav-item {{ request()->routeIs('assessments.*') ? 'active' : '' }}"
           data-nav-target="assessments"
           title="{{ $isPic ? 'Jadwal Asesmen' : 'Program Asesmen' }}">
            <span class="bottom-nav-icon-wrap" aria-hidden="true">
                <x-icon name="assessments" size="19" />
            </span>
            <span class="bottom-nav-label">Asesmen</span>
        </a>

        {{-- 4. Kalender --}}
        <a href="{{ route('calendar.index') }}"
           class="bottom-nav-item {{ request()->routeIs('calendar.*') ? 'active' : '' }}"
           data-nav-target="calendar"
           title="{{ $isPic ? 'Kalender Pengawasan' : 'Kalender' }}">
            <span class="bottom-nav-icon-wrap" aria-hidden="true">
                <x-icon name="calendar" size="19" />
            </span>
            <span class="bottom-nav-label">Kalender</span>
        </a>

        {{-- 5. Lainnya (Bottom Sheet) --}}
        <button type="button"
                class="bottom-nav-item bottom-nav-more-btn {{ $isMoreActive ? 'active' : '' }}"
                id="bottom-nav-more-btn"
                aria-haspopup="dialog"
                aria-expanded="false"
                aria-controls="bottom-nav-sheet"
                title="Menu Lainnya & Akun">
            <span class="bottom-nav-icon-wrap" aria-hidden="true">
                <x-icon name="more-horizontal" size="20" />
            </span>
            <span class="bottom-nav-label">Lainnya</span>
        </button>
    </nav>

    {{-- Bottom Sheet for More / Account Options --}}
    <div id="bottom-sheet-backdrop" class="bottom-sheet-backdrop" style="display: none;" aria-hidden="true"></div>

    <div id="bottom-nav-sheet" class="bottom-sheet" style="display: none;" role="dialog" aria-modal="true" aria-labelledby="bottom-sheet-title">
        <div class="bottom-sheet-handle-bar">
            <div class="bottom-sheet-handle"></div>
        </div>

        <div class="bottom-sheet-header">
            <div class="bottom-sheet-title-box">
                <strong id="bottom-sheet-title" class="bottom-sheet-title">Menu & Pengaturan</strong>
                <span class="bottom-sheet-subtitle">Akses cepat manajemen akun &amp; sistem</span>
            </div>
            <button type="button" id="bottom-sheet-close" class="bottom-sheet-close" aria-label="Tutup menu">
                <x-icon name="x" size="16" />
            </button>
        </div>

        {{-- User Identity Mini Card --}}
        <div class="bottom-sheet-account">
            <a href="{{ route('profile.edit') }}" class="bottom-sheet-user-card" title="Buka profil">
                <span class="user-avatar user-avatar-md" aria-hidden="true">{{ auth()->user()->initials }}</span>
                <div class="bottom-sheet-user-meta">
                    <strong class="bottom-sheet-user-name">{{ auth()->user()->name }}</strong>
                    <span class="bottom-sheet-user-email">{{ auth()->user()->email }}</span>
                    <div class="bottom-sheet-badge-wrap">
                        <span class="badge-role {{ auth()->user()->role_badge_class }}">
                            {{ auth()->user()->role_label }}
                        </span>
                    </div>
                </div>
                <x-icon name="chevron-right" size="16" class="bottom-sheet-user-chevron" />
            </a>
        </div>

        <div class="bottom-sheet-divider"></div>

        {{-- Navigation Links List --}}
        <div class="bottom-sheet-nav-list">
            @if(auth()->user()->isAdmin())
                <a href="{{ route('users.index') }}" class="bottom-sheet-item {{ request()->routeIs('users.*') ? 'active' : '' }}">
                    <div class="bottom-sheet-item-icon">
                        <x-icon name="users" size="18" />
                    </div>
                    <div class="bottom-sheet-item-text">
                        <strong>Manajemen Anggota</strong>
                        <small>Kelola akun petugas, asesor, dan hak akses</small>
                    </div>
                    <x-icon name="chevron-right" size="14" class="bottom-sheet-item-arrow" />
                </a>
            @endif

            <a href="{{ route('account-links.index') }}" class="bottom-sheet-item {{ request()->routeIs('account-links.*') ? 'active' : '' }}">
                <div class="bottom-sheet-item-icon">
                    <x-icon name="link" size="18" />
                </div>
                <div class="bottom-sheet-item-text">
                    <strong>Tautan Akun</strong>
                    <small>Integrasi dan sinkronisasi akun laboratorium</small>
                </div>
                <x-icon name="chevron-right" size="14" class="bottom-sheet-item-arrow" />
            </a>

            <a href="{{ route('profile.edit') }}" class="bottom-sheet-item {{ request()->routeIs('profile.*') ? 'active' : '' }}">
                <div class="bottom-sheet-item-icon">
                    <x-icon name="user" size="18" />
                </div>
                <div class="bottom-sheet-item-text">
                    <strong>Profil &amp; Kata Sandi</strong>
                    <small>Perbarui data diri dan keamanan akun</small>
                </div>
                <x-icon name="chevron-right" size="14" class="bottom-sheet-item-arrow" />
            </a>
        </div>

        <div class="bottom-sheet-divider"></div>

        {{-- Footer Actions: Theme Toggle & Logout --}}
        <div class="bottom-sheet-footer">
            <form method="POST" action="{{ route('logout') }}" id="bottom-sheet-logout-form" class="logout-form">
                @csrf
                <button type="submit" class="bottom-sheet-logout-btn">
                    <x-icon name="log-out" size="16" />
                    <span>Keluar dari Akun</span>
                </button>
            </form>
        </div>
    </div>
@endif
