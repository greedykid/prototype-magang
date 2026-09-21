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
            <span class="context-label" title="Unit Akreditasi Laboratorium &bull; Direktorat Akreditasi Laboratorium KAN">Unit Akreditasi Lab &bull; Dit. Akreditasi Laboratorium KAN</span>
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
                <button type="button" id="notif-dropdown-btn" class="topbar-notif-btn" aria-expanded="false" aria-haspopup="true" title="{{ $alertCount > 0 ? $alertCount . ' Notifikasi Pengawasan Jatuh Tempo' : 'Tidak ada notifikasi aktif' }}" style="display: inline-flex; align-items: center; justify-content: center; width: 36px; height: 36px; border-radius: 8px; border: 1px solid #e2e8f0; background-color: {{ $alertCount > 0 ? '#fff1f2' : '#ffffff' }}; color: {{ $alertCount > 0 ? '#e11d48' : '#64748b' }}; position: relative; cursor: pointer; padding: 0;">
                    <svg width="18" height="18" viewBox="0 0 24 24" fill="none" stroke="currentColor" stroke-width="2" stroke-linecap="round" stroke-linejoin="round"><path d="M6 8a6 6 0 0 1 12 0c0 7 3 9 3 9H3s3-2 3-9"/><path d="M10.3 21a1.94 1.94 0 0 0 3.4 0"/></svg>
                    @if($alertCount > 0)
                        <span style="position: absolute; top: -4px; right: -4px; background-color: #e11d48; color: #ffffff; font-size: 10.5px; font-weight: 700; min-width: 18px; height: 18px; border-radius: 9px; display: inline-flex; align-items: center; justify-content: center; padding: 0 4px; box-shadow: 0 0 0 2px #ffffff;">
                            {{ $alertCount }}
                        </span>
                    @endif
                </button>

                <div id="notif-dropdown-menu" class="notif-dropdown" onclick="if(event.target.closest('a, button')) { this.style.display='none'; document.getElementById('notif-dropdown-btn')?.setAttribute('aria-expanded', 'false'); }" style="display: none; position: absolute; top: calc(100% + 8px); right: 0; width: 340px; max-width: calc(100vw - 24px); background: #ffffff; border: 1px solid #e2e8f0; border-radius: 12px; box-shadow: 0 10px 25px -5px rgba(0, 0, 0, 0.1), 0 8px 10px -6px rgba(0, 0, 0, 0.1); z-index: 1000; overflow: hidden;">
                    <div style="display: flex; align-items: center; justify-content: space-between; padding: 12px 16px; border-bottom: 1px solid #f1f5f9; background: #f8fafc;">
                        <div style="display: flex; align-items: center; gap: 8px;">
                            <strong style="font-size: 13px; color: #1e293b;">Notifikasi Siklus Pengawasan</strong>
                            <span style="font-size: 11px; font-weight: 700; padding: 2px 7px; border-radius: 999px; background: {{ $alertCount > 0 ? '#fee2e2' : '#f1f5f9' }}; color: {{ $alertCount > 0 ? '#b91c1c' : '#64748b' }};">
                                {{ $alertCount }}
                            </span>
                        </div>
                        @if($alertCount > 0)
                            <span style="font-size: 11px; color: #e11d48; font-weight: 600;">Perlu Tindakan</span>
                        @endif
                    </div>

                    <div style="padding: 16px;">
                        @if($alertCount > 0)
                            <div style="font-size: 12.5px; font-weight: 600; color: #1e293b; margin-bottom: 4px;">
                                {{ $alertCount }} Laboratorium Memerlukan Perhatian
                            </div>
                            <p style="font-size: 11.5px; color: #64748b; margin: 0 0 12px 0; line-height: 1.5;">
                                Terdapat siklus Surveilen (S1/S2) atau Re-Akreditasi KAN yang telah mendekati batas waktu atau melewati jadwal.
                            </p>
                            <a href="{{ route('lpks.index', ['surveillance' => 'NEEDS_ACTION']) }}" style="display: block; width: 100%; text-align: center; font-size: 12px; font-weight: 600; padding: 8px 12px; border-radius: 6px; background: #5645d4; color: #ffffff; text-decoration: none; box-sizing: border-box; transition: background 150ms ease;" onmouseover="this.style.background='#4738b8'" onmouseout="this.style.background='#5645d4'">
                                Tinjau LPK Jatuh Tempo &rarr;
                            </a>
                        @else
                            <div style="text-align: center; padding: 8px 0; color: #64748b;">
                                <svg width="28" height="28" viewBox="0 0 24 24" fill="none" stroke="#94a3b8" stroke-width="1.8" stroke-linecap="round" stroke-linejoin="round" style="margin: 0 auto 8px; display: block;"><path d="M18 8A6 6 0 0 0 6 8c0 7-3 9-3 9h18s-3-2-3-9"/><path d="M13.73 21a2 2 0 0 1-3.46 0"/><line x1="2" y1="2" x2="22" y2="22"/></svg>
                                <div style="font-size: 12.5px; font-weight: 600; color: #475569;">Tidak ada notifikasi aktif</div>
                                <div style="font-size: 11.5px; color: #94a3b8; margin-top: 2px;">Seluruh siklus pengawasan KAN dalam status aman.</div>
                            </div>
                        @endif
                    </div>

                    <div style="padding: 10px 16px; background: #f8fafc; border-top: 1px solid #f1f5f9; text-align: center;">
                        <a href="{{ route('lpks.index', $alertCount > 0 ? ['surveillance' => 'NEEDS_ACTION'] : []) }}" style="font-size: 12px; font-weight: 600; color: #5645d4; text-decoration: none; display: inline-flex; align-items: center; gap: 4px;">
                            Lihat Semua di Daftar LPK &rarr;
                        </a>
                    </div>
                </div>
            </div>

            <span class="badge-role topbar-role-badge {{ auth()->user()->role_badge_class }}">
                {{ auth()->user()->role_label }}
            </span>
        </div>
    @endif
</header>
