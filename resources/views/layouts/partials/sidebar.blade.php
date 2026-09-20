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
        @if(auth()->user()?->isAssessor())
            {{-- Menu Khusus Auditor / Asesor KAN --}}
            <div class="nav-section">
                <span class="nav-section-title">Utama</span>
                <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" data-tooltip="Ringkasan">
                    <x-icon name="dashboard" size="18" />
                    <span class="nav-label">Ringkasan</span>
                </a>
            </div>

            <div class="nav-section">
                <span class="nav-section-title">Penugasan Asesmen</span>
                <a href="{{ route('assessments.index') }}" class="{{ request()->routeIs('assessments.*') ? 'active' : '' }}" data-tooltip="Program Asesmen">
                    <x-icon name="assessments" size="18" />
                    <span class="nav-label">Program Asesmen</span>
                </a>
                <a href="{{ route('calendar.index') }}" class="{{ request()->routeIs('calendar.*') ? 'active' : '' }}" data-tooltip="Kalender Kerja">
                    <x-icon name="calendar" size="18" />
                    <span class="nav-label">Kalender Kerja</span>
                </a>
                <a href="{{ route('lpks.index') }}" class="{{ request()->routeIs('lpks.*') ? 'active' : '' }}" data-tooltip="Data Lembaga (LPK)">
                    <x-icon name="lpks" size="18" />
                    <span class="nav-label">Data Lembaga (LPK)</span>
                </a>
            </div>

            <div class="nav-section">
                <span class="nav-section-title">Dukungan</span>
                <a href="{{ route('issues.index') }}" class="{{ request()->routeIs('issues.*') ? 'active' : '' }}" data-tooltip="Pusat Kendala">
                    <x-icon name="issues" size="18" />
                    <span class="nav-label">Pusat Kendala</span>
                </a>
            </div>
        @else
            {{-- Menu Staf Administrasi & Administrator Sistem --}}
            <div class="nav-section">
                <span class="nav-section-title">Utama</span>
                <a href="{{ route('dashboard') }}" class="{{ request()->routeIs('dashboard') ? 'active' : '' }}" data-tooltip="Ringkasan">
                    <x-icon name="dashboard" size="18" />
                    <span class="nav-label">Ringkasan</span>
                </a>
            </div>

            <div class="nav-section">
                <span class="nav-section-title">Administrasi LPK</span>
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
                <span class="nav-section-title">{{ auth()->user()?->isAdmin() ? 'Monitoring & Sistem' : 'Pusat Kendala' }}</span>
                @if(auth()->user()?->isAdmin())
                    <a href="{{ route('monitoring.services') }}" class="{{ request()->routeIs('monitoring.services') ? 'active' : '' }}" data-tooltip="Layanan KANMIS">
                        <x-icon name="services" size="18" />
                        <span class="nav-label">Layanan KANMIS</span>
                    </a>
                    <a href="{{ route('monitoring.backups') }}" class="{{ request()->routeIs('monitoring.backups') ? 'active' : '' }}" data-tooltip="Backup">
                        <x-icon name="backup" size="18" />
                        <span class="nav-label">Backup</span>
                    </a>
                @endif
                <a href="{{ route('issues.index') }}" class="{{ request()->routeIs('issues.*') ? 'active' : '' }}" data-tooltip="Masalah">
                    <x-icon name="issues" size="18" />
                    <span class="nav-label">Masalah</span>
                </a>
            </div>
        @endif
    </nav>
    <div class="sidebar-foot">
        <div class="drawer-account">
            <div class="user-identity">
                <span class="user-avatar" aria-hidden="true">{{ collect(explode(' ', auth()->user()->name ?? 'Tamu'))->map(fn ($part) => substr($part, 0, 1))->take(2)->implode('') }}</span>
                <div class="user-details">
                    <strong>{{ auth()->user()->name ?? 'Tamu' }}</strong>
                    <span class="badge-role {{ auth()->user()->role_badge_class ?? 'badge-role-default' }}" style="margin-top: 3px;">
                        {{ auth()->user()->role_label ?? 'Petugas' }}
                    </span>
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
