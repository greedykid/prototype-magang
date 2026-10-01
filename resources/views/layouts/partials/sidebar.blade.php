<aside class="sidebar">
    <div class="brand">
        <div class="brand-info">
            <a href="{{ route('dashboard') }}" class="brand-mark" title="SIMASADI - Badan Standardisasi Nasional">
                <img src="{{ asset('images/logo-bsn.png') }}" alt="Logo BSN" class="brand-logo-img">
            </a>
            <div class="brand-text"><strong>SIMASADI</strong><small>Unit Akreditasi Lab KAN</small></div>
        </div>
    </div>
    <button class="drawer-close" type="button" aria-label="Tutup navigasi"><span aria-hidden="true">&times;</span></button>
    <nav id="primary-navigation" aria-label="Navigasi utama">
        @if(auth()->user()?->isPic())
            {{-- Menu Khusus PIC Laboratorium Terakreditasi --}}
            <div class="nav-section">
                <span class="nav-section-title">Utama</span>
                <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" data-tooltip="Dasbor">
                    <x-icon name="dashboard" size="18" />
                    <span class="nav-label">Dasbor</span>
                </a>
            </div>

            <div class="nav-section">
                <span class="nav-section-title">Laboratorium Terakreditasi</span>
                <a href="{{ route('lpks.index') }}" class="{{ request()->routeIs('lpks.*') ? 'active' : '' }}" data-tooltip="Data Laboratorium">
                    <x-icon name="lpks" size="18" />
                    <span class="nav-label">Data Laboratorium</span>
                </a>
                <a href="{{ route('assessments.index') }}" class="{{ request()->routeIs('assessments.*') ? 'active' : '' }}" data-tooltip="Jadwal Asesmen">
                    <x-icon name="assessments" size="18" />
                    <span class="nav-label">Jadwal Asesmen</span>
                </a>
                <a href="{{ route('calendar.index') }}" class="{{ request()->routeIs('calendar.*') ? 'active' : '' }}" data-tooltip="Kalender Pengawasan">
                    <x-icon name="calendar" size="18" />
                    <span class="nav-label">Kalender Pengawasan</span>
                </a>
                <a href="{{ route('account-links.index') }}" class="{{ request()->routeIs('account-links.*') ? 'active' : '' }}" data-tooltip="Tautan Akun">
                    <x-icon name="link" size="18" />
                    <span class="nav-label">Tautan Akun</span>
                </a>
            </div>
        @else
            {{-- Menu Administrator Unit Akreditasi Laboratorium --}}
            <div class="nav-section">
                <span class="nav-section-title">Utama</span>
                <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" data-tooltip="Dasbor">
                    <x-icon name="dashboard" size="18" />
                    <span class="nav-label">Dasbor</span>
                </a>
            </div>

            <div class="nav-section">
                <span class="nav-section-title">Administrasi Laboratorium</span>
                <a href="{{ route('lpks.index') }}" class="{{ request()->routeIs('lpks.*') ? 'active' : '' }}" data-tooltip="Data Lab (LPK)">
                    <x-icon name="lpks" size="18" />
                    <span class="nav-label">Data Lab (LPK)</span>
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
                <span class="nav-section-title">Akses & Sistem</span>
                <a href="{{ route('users.index') }}" class="{{ request()->routeIs('users.*') ? 'active' : '' }}" data-tooltip="Manajemen Anggota">
                    <x-icon name="users" size="18" />
                    <span class="nav-label">Manajemen Anggota</span>
                </a>
                <a href="{{ route('account-links.index') }}" class="{{ request()->routeIs('account-links.*') ? 'active' : '' }}" data-tooltip="Tautan Akun">
                    <x-icon name="link" size="18" />
                    <span class="nav-label">Tautan Akun</span>
                </a>
            </div>
        @endif
    </nav>
    <div class="sidebar-foot">
        <div class="drawer-account">
            <a href="{{ route('profile.edit') }}" class="user-identity" title="Pengaturan Profil &amp; Kata Sandi">
                <span class="user-avatar" aria-hidden="true">{{ collect(explode(' ', auth()->user()->name ?? 'Tamu'))->map(fn ($part) => substr($part, 0, 1))->take(2)->implode('') }}</span>
                <div class="user-details">
                    <strong>{{ auth()->user()->name ?? 'Tamu' }}</strong>
                    <span class="badge-role {{ auth()->user()?->role_badge_class ?? 'badge-role-default' }}" title="{{ auth()->user()?->role_label ?? '' }}">
                        {{ auth()->user()?->role_short_label ?? auth()->user()?->role_label ?? 'Petugas' }}
                    </span>
                </div>
            </a>
            <form method="POST" action="{{ route('logout') }}" id="logout-form" class="logout-form">
                @csrf
                <button type="submit" class="logout-icon" aria-label="Keluar dari akun" title="Keluar">
                    <x-icon name="log-out" size="16" />
                </button>
            </form>
        </div>
    </div>
</aside>
